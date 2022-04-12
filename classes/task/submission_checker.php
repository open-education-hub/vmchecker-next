<?php

namespace block_vmchecker\task;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

class submission_checker extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('submission_checker', 'block_vmchecker');
    }

    private function done_submission($api, $submission) {
        global $DB;

        $response = $api->trace($submission->uuid);
        $trace = $this->clean_trace(base64_decode($response['trace']));
        $this->log('Trace:\n' . $trace);

        $cm = get_coursemodule_from_instance('assign', $submission->assignid, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        $assign = new \assign($context, null, null);

        $matches = array();
        preg_match('/Total:\ *([0-9]+)/', $trace , $matches);
        $grade_key = array_key_last($matches);
        $grade = $matches[$grade_key];
        $teachercommenttext = $trace;
        $data = new \stdClass();
        $data->attemptnumber = -1;
        if ($submission->autograde)
            $data->grade = $grade;
        else
            $data->grade = null;
        $data->assignfeedbackcomments_editor = ['text' => $teachercommenttext, 'format' => FORMAT_MOODLE];

        // Give the submission a grade.
        $assign->save_grade($submission->userid, $data);

        $DB->delete_records('block_vmchecker_submissions', array('id' => $submission->id));
    }

    private function clean_trace(string $trace) {
        $offset = strpos($trace, 'VMCHECKER_TRACE_CLEANUP\n');
        $trace = substr($trace, $offset + strlen('VMCHECKER_TRACE_CLEANUP\n'));

        $matches = array();
        preg_match('/Total:\ *([0-9]+)/', $trace , $matches, PREG_OFFSET_CAPTURE);
        $last_capture_key = array_key_last($matches);
        $last_capture_group = $matches[$last_capture_key];
        $trace = substr($trace, 0, $last_capture_group[1] + strlen($last_capture_group[0]));  // Remove everything after score declaration

        return $trace;
    }

    private function log(string $msg) {
        mtrace('[' . time() . '] ' . $msg);
    }

    public function execute() {
        global $DB;

        $this->log('Starting VMChecker task');

        $active_submissions = $DB->get_records('block_vmchecker_submissions', null, 'updatedat ASC', '*', 0, get_config('block_vmchecker', 'submission_check'));

        if (!$active_submissions || count($active_submissions) == 0)
            return;

        $this->log('Found ' . count($active_submissions) . ' submissions to be checked');
        $api = new \block_vmchecker\backend\api(get_config('block_vmchecker', 'backend'));

        foreach($active_submissions as $submission) {
            $this->log('Checking task ' . $submission->id);

            $submission->updatedat = time();
            $DB->update_record('block_vmchecker_submissions', $submission);

            $response = $api->status($submission->uuid );
            if (empty($response)) {
                $this->log('Failed to retrieve data for task ' . $submission->id);
                continue;
            }

            $this->log('Task status is ' . $response['status']);

            switch($response['status']) {
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
