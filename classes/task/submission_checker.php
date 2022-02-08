<?php

namespace block_vmchecker\task;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

class submission_checker extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('submission_checker', 'block_vmchecker');
    }

    private function clean_trace(string $trace) {
        $matches = array();
        preg_match('/VMCHECKER_TRACE_CLEANUP\n/', $trace , $matches, PREG_OFFSET_CAPTURE);
        $trace = substr($trace, $matches[0][1] + strlen($matches[0][0]));

        $matches = array();
        preg_match('/Total:\ *([0-9]+)/', $trace , $matches, PREG_OFFSET_CAPTURE);
        $trace = substr($trace, 0, $matches[1][1] + strlen($matches[1][0]));  // Remove everything after score declaration

        return $trace;
    }

    private function log(string $msg) {
        mtrace('[' . time() . '] ' . $msg);
    }

    public function execute() {
        global $DB, $CFG;

        $this->log('Starting VMChecker task');

        $active_submissions = $DB->get_records('block_vmchecker_submissions');

        if (!$active_submissions || count($active_submissions) == 0)
            return;

        $this->log('Found ' . count($active_submissions) . ' submissions to be checked');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPGET, true);

        foreach($active_submissions as $submission) {
            $this->log('Checking task ' . $submission->id);

            curl_setopt($ch, CURLOPT_URL, $CFG->block_vmchecker_backend . $submission->uuid . '/status');

            $response = json_decode(curl_exec($ch), true);
            $this->log('Task status is ' . $response['status']);
            if ($response['status'] != 'done')
                continue;

            curl_setopt($ch, CURLOPT_URL, $CFG->block_vmchecker_backend . $submission->uuid . '/trace');

            $response = json_decode(curl_exec($ch), true);
            $trace = $this->clean_trace(base64_decode($response['trace']));
            $this->log('Trace:\n' . $trace);

            $cm = get_coursemodule_from_instance('assign', $submission->assignid, 0, false, MUST_EXIST);
            $context = \context_module::instance($cm->id);

            $assign = new \assign($context, null, null);

            $matches = array();
            preg_match('/Total:\ *([0-9]+)/', $trace , $matches);
            $grade = $matches[1];
            $teachercommenttext = $trace;
            $data = new \stdClass();
            $data->attemptnumber = 0;
            $data->grade = $grade;
            $data->assignfeedbackcomments_editor = ['text' => $teachercommenttext, 'format' => FORMAT_MOODLE];

            // Give the submission a grade.
            $assign->save_grade($submission->userid, $data);

            $DB->delete_records('block_vmchecker_submissions', array('id' => $submission->id));
        }

        curl_close($ch);
    }
}
