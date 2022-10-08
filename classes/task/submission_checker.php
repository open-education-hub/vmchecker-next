<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Definition of vmchecker submission checker task.
 *
 * @package   block_vmchecker
 * @copyright 2022 Mihai Baruta <baruta.mihai99@gmail.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace block_vmchecker\task;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

/**
 * Definition of the submission checker task.
 *
 * @copyright 2022 Mihai Baruta <baruta.mihai99@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class submission_checker extends \core\task\scheduled_task {

    /**
     * Task name
     * @return string
     */
    public function get_name() {
        return get_string('submission_checker', 'block_vmchecker');
    }

    /**
     * Handler for done submissions
     * @param \block_vmchecker\backend\api $api
     * @param object $submission
     * @return void
     */
    private function done_submission($api, $submission) {
        global $DB;

        $response = $api->trace($submission->uuid);
        $trace = $this->clean_trace(base64_decode($response['trace']));
        $this->log('Trace:\n' . $trace);

        $cm = get_coursemodule_from_instance('assign', $submission->assignid, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        $assign = new \assign($context, null, null);

        $matches = array();
        preg_match('/Total:\ *([0-9]+)/', $trace, $matches);
        $gradekey = array_key_last($matches);
        $grade = $matches[$gradekey];
        $teachercommenttext = $trace;
        $data = new \stdClass();
        $data->attemptnumber = -1;
        if ($submission->autograde) {
            $data->grade = $grade;
        } else {
            $data->grade = null;
        }
        $data->assignfeedbackcomments_editor = ['text' => $teachercommenttext, 'format' => FORMAT_MOODLE];

        // Give the submission a grade.
        $assign->save_grade($submission->userid, $data);

        $DB->delete_records('block_vmchecker_submissions', array('id' => $submission->id));
    }

    /**
     * Remove extra clutter found in the Gitlab's trace
     * @param string $trace
     * @return string
     */
    private function clean_trace(string $trace) {
        $offset = strpos($trace, 'VMCHECKER_TRACE_CLEANUP');
        $this->log('Found cleanup mark at: ' . $offset);
        $trace = substr($trace, $offset + strlen('VMCHECKER_TRACE_CLEANUP') + 1);   // Add new line.

        $matches = array();
        preg_match('/Total:\ *([0-9]+)/', $trace, $matches, PREG_OFFSET_CAPTURE);
        $lastcapturekey = array_key_last($matches);
        $lastcapturegroup = $matches[$lastcapturekey];
        $trace = substr(
            $trace,
            0,
            $lastcapturegroup[1] + strlen($lastcapturegroup[0])
        );  // Remove everything after score declaration.

        return $trace;
    }

    /**
     * Logger
     * @param string $msg
     * @return void
     */
    private function log(string $msg) {
        mtrace('[' . time() . '] ' . $msg);
    }

    /**
     * Execution handler
     * @return void
     */
    public function execute() {
        global $DB;

        $this->log('Starting VMChecker task');

        $activesubmissions = $DB->get_records(
            'block_vmchecker_submissions',
            null,
            'updatedat ASC',
            '*',
            0,
            get_config('block_vmchecker', 'submission_check')
        );

        if (!$activesubmissions || count($activesubmissions) == 0) {
            return;
        }

        $this->log('Found ' . count($activesubmissions) . ' submissions to be checked');
        $api = new \block_vmchecker\backend\api(get_config('block_vmchecker', 'backend'));

        foreach ($activesubmissions as $submission) {
            $this->log('Checking task ' . $submission->id);

            $submission->updatedat = time();
            $DB->update_record('block_vmchecker_submissions', $submission);

            $response = $api->status($submission->uuid);
            if (empty($response)) {
                $this->log('Failed to retrieve data for task ' . $submission->id);
                continue;
            }

            $this->log('Task status is ' . $response['status']);

            switch ($response['status']) {
                case \block_vmchecker\backend\api::TASK_STATE_DONE:
                    $this->done_submission($api, $submission);
                    break;
                case \block_vmchecker\backend\api::TASK_STATE_ERROR:
                    $DB->delete_records('block_vmchecker_submissions', array('id' => $submission->id));
                    break;
            }
        }
    }
}
