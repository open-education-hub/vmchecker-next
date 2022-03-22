<?php

namespace block_vmchecker\listener;

use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assign/locallib.php');

class observer {
    private static function stop_previous_attempt($assignmentId) {
        global $DB, $USER, $CFG;

        $previous_attempt = $DB->get_record('block_vmchecker_submissions',
            array(
                'userid' => $USER->id,
                'assignid' => $assignmentId,
        ));
        if (!$previous_attempt)
            return true;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $CFG->block_vmchecker_backend . '/' . $previous_attempt->uuid . '/cancel');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $raw_data = curl_exec($ch);
        if ($raw_data === false)
            return false;

        curl_close($ch);

        $DB->delete_records('block_vmchecker_submissions', array('id' => $previous_attempt->id));
        return true;
    }

    public static function submit(\core\event\base $event) {
        global $DB, $USER, $CFG;

        $data = $event->get_data();
        $submission = $DB->get_record($data['objecttable'],
            array('id' => $data['objectid']));

        $vmchecker_options = $DB->get_record('block_vmchecker_options',
            array('assignid' => $submission->assignment));

        // The assignment is not a vmchecker type
        if ($vmchecker_options == null)
            return;

        if (!\block_vmchecker\listener\observer::stop_previous_attempt($submission->assignment))
            return;

        $config_data = $DB->get_record('block_instances',
            array('id' => $vmchecker_options->blockinstanceid), 'configdata')->configdata;
        $config = unserialize(base64_decode($config_data));

        $cm = get_coursemodule_from_instance('assign', $submission->assignment, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        $assign = new \assign($context, null, null);
        $mocked_submission = new stdClass();
        $mocked_submission->id = $submission->submission;
        $submited_files = (new \assign_submission_file($assign, null))->get_files($mocked_submission, new stdClass);
        if (count($submited_files) !== 1)
            return;

        $submited_file = $submited_files[array_keys($submited_files)[0]];


        $payload = json_encode(array(
            'gitlab_private_token' => $config->gitlab_private_token,
            'gitlab_project_id' => $config->gitlab_project_id,
            'username' => $USER->username,
            'archive' => base64_encode($submited_file->get_content()),
        ));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $CFG->block_vmchecker_backend . '/submit');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        if ($response === false)
            return;

        curl_close($ch);

        $DB->insert_record('block_vmchecker_submissions',
            array(
                'userid' => $USER->id,
                'assignid' => $submission->assignment,
                'uuid' => json_decode($response, true)['UUID'],
                'autograde' => $config->autograding === '1',
                'updatedat' => time(),
        ));
    }
}
