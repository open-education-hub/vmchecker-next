<?php

namespace block_vmchecker\listener;

use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assign/locallib.php');

class observer {
    private static function stop_previous_attempt($assignment_id) {
        global $DB, $USER;

        $previous_attempt = $DB->get_record('block_vmchecker_submissions',
            array(
                'userid' => $USER->id,
                'assignid' => $assignment_id,
        ));
        if (!$previous_attempt)
            return true;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, get_config('block_vmchecker', 'backend') . '/' . $previous_attempt->uuid . '/cancel');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
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
        global $DB;

        $data = $event->get_data();
        $submission_file = $DB->get_record($data['objecttable'],
            array('id' => $data['objectid']));

        $vmchecker_options = $DB->get_record('block_vmchecker_options',
            array('assignid' => $submission_file->assignment));

        // The assignment is not a vmchecker type
        if ($vmchecker_options == null)
            return;

        if (!\block_vmchecker\listener\observer::stop_previous_attempt($submission_file->assignment))
            return;

        $config_data = $DB->get_record('block_instances',
            array('id' => $vmchecker_options->blockinstanceid), 'configdata')->configdata;
        $config = unserialize(base64_decode($config_data));

        $cm = get_coursemodule_from_instance('assign', $submission_file->assignment, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        $assign = new \assign($context, null, null);

        $params = array('assignment'=>$assign->get_instance()->id, 'id'=>$submission_file->submission);
        $user_submission = $DB->get_record('assign_submission', $params, '*', MUST_EXIST);

        $submited_files = (new \assign_submission_file($assign, null))->get_files($user_submission, new stdClass);
        if (count($submited_files) !== 1)
            return;

        $submited_file = reset($submited_files);

        $payload = array(
            'gitlab_private_token' => $config->gitlab_private_token,
            'gitlab_project_id' => $config->gitlab_project_id,
            'username' => $assign->get_participant($user_submission->userid)->username,
            'archive' => base64_encode($submited_file->get_content()),
        );

        $api = new \block_vmchecker\backend\api(get_config('block_vmchecker', 'backend'));
        $response = $api->submit($payload);
        if (empty($response))
            return;

        $DB->insert_record('block_vmchecker_submissions',
            array(
                'userid' => $user_submission->userid,
                'assignid' => $user_submission->assignment,
                'uuid' => $response['UUID'],
                'autograde' => $config->autograding === '1',
                'updatedat' => time(),
        ));
    }
}
