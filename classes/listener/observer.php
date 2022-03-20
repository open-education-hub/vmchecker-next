<?php

namespace block_vmchecker\listener;

defined('MOODLE_INTERNAL') || die();

class observer {
    private static function stop_previous_attempt($assignmentId) {
        global $DB, $USER, $CFG;

        $previous_attempt = $DB->get_record('block_vmchecker_submissions',
            array(
                'userid' => $USER->id,
                'assignid' => $assignmentId,
        ));
        if (!$previous_attempt)
            return;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $CFG->block_vmchecker_backend . $previous_attempt->uuid . '/cancel');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_exec($ch);

        curl_close($ch);

        $DB->delete_records('block_vmchecker_submissions', array('id' => $previous_attempt->id));
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

        \block_vmchecker\listener\observer::stop_previous_attempt($submission->assignment);

        $config_data = $DB->get_record('block_instances',
            array('id' => $vmchecker_options->blockinstanceid), 'configdata')->configdata;
        $config = unserialize(base64_decode($config_data));

        $fs = get_file_storage();
        $files = $fs->get_area_files($data['contextid'], 'assignsubmission_file', 'submission_files', $submission->submission);
        $submited_file = null;
        foreach($files as $file) {
            if ($file->get_filename() != '.') {
                $submited_file = $file;
                break;
            }
        }

        if (!$submited_file)
            return;

        $payload = json_encode(array(
            'gitlab_private_token' => $config->gitlab_private_token,
            'gitlab_project_id' => $config->gitlab_project_id,
            'username' => $USER->username,
            'archive' => base64_encode($submited_file->get_content()),
        ));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $CFG->block_vmchecker_backend . 'submit');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

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
