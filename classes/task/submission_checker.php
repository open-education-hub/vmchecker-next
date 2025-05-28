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

use qbank_managecategories\output\categories;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/blocks/vmchecker/classes/logger/logger.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

use \block_vmchecker\logger\logger;

/**
 * Definition of the submission checker task.
 *
 * @copyright 2022 Mihai Baruta <baruta.mihai99@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class submission_checker extends \core\task\scheduled_task {

    /**
     * @var logger logger
     */
    private logger $logger;

    /**
     * Marker for begining of the trace.
     * @var string
     */
    private const VMCK_NEXT_BEGIN = "<VMCK_NEXT_BEGIN>";
    /**
     * Marker for ending of the trace.
     * @var string
     */
    private const VMCK_NEXT_END = "<VMCK_NEXT_END>";
    /**
     * Number of lines from the trace to be used as teacher feedback.
     * @var int
     */
    private const NUMBER_OF_FEEDBACK_LINES = 300;

    /**
     * Constructor for the submission checker task.
     * Initializes the logger.
     */
    public function __construct() {
        $this->logger = new logger(['VMChecker', 'submission_checker']);
    }

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

        try {
            $response = $api->trace($submission->uuid);
        } catch (\block_vmchecker\exceptions\api_exception $e) {
            $this->logger->error('Failed to retrieve the trace for task ' . $submission->id . ': ' . $e->getMessage());
            return;
        }

        $trace = $this->clean_trace(base64_decode($response['trace']));
        $this->logger->info('Trace:\n' . $trace);

        $cm = get_coursemodule_from_instance('assign', $submission->assignid, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        $assign = new \assign($context, null, null);

        $matches = array();
        $found = preg_match('/Total:\ *([0-9]+)/', $trace, $matches);

        $grade = 0;
        if ($found === 1) {
            $gradekey = array_key_last($matches);
            $grade = $matches[$gradekey];
        }

        $teachercommenttext = $this->format_feedback($trace);
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
     * Returns the fist 300 lines of the output
     * @param string $trace
     * @return string
     */
    private function format_feedback(string $trace) {
        $tracelines = explode(PHP_EOL, $trace);
        $tracelines = array_slice($tracelines, 0, self::NUMBER_OF_FEEDBACK_LINES);
        return implode(PHP_EOL, $tracelines);
    }

    /**
     * Remove extra clutter found in the Gitlab's trace
     * @param string $trace
     * @return string
     */
    private function clean_trace(string $trace) {
        $offset = strpos($trace, self::VMCK_NEXT_BEGIN);
        $this->logger->info('Found start cleanup mark at: ' . $offset);
        $trace = substr($trace, $offset + strlen(self::VMCK_NEXT_BEGIN) + 1);   // Add new line.

        $offset = strpos($trace, self::VMCK_NEXT_END);
        $this->logger->info('Found end cleanup mark at: ' . $offset);
        $trace = substr($trace, 0, $offset);

        return $trace;
    }

    /**
     * Execution handler
     * @return void
     */
    public function execute() {
        global $DB;

        $activesubmissions = $DB->get_records(
            'block_vmchecker_submissions',
            null,
            'updatedat ASC',
            '*',
            0,
            get_config('block_vmchecker', 'submission_check')
        );

        if (!$activesubmissions || count($activesubmissions) == 0) {
            $this->logger->info('No submissions to check. Exiting.');
            return;
        }

        $this->logger->info('Found ' . count($activesubmissions) . ' submissions to be checked');
        $api = new \block_vmchecker\backend\api(get_config('block_vmchecker', 'backend'));

        foreach ($activesubmissions as $submission) {
            $this->logger->info('Checking task ' . $submission->id);

            $submission->updatedat = time();
            $DB->update_record('block_vmchecker_submissions', $submission);

            try {
                $response = $api->status($submission->uuid);
            } catch (\block_vmchecker\exceptions\api_exception $e) {
                $this->logger->error('Failed to retrieve the status for task ' . $submission->id . ': ' . $e->getMessage());
                continue;
            }

            $this->logger->info('Task ' . $submission->id . ' with UUID ' . $submission->uuid . ' status is ' . $response['status']);
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
