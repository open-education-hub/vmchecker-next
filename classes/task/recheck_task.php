<?php

namespace block_vmchecker\task;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

class recheck_task extends \core\task\adhoc_task {
    private function log(string $msg) {
        mtrace('[' . time() . '] ' . $msg);
    }

    public function execute() {
        global $CFG, $DB;

        $data = $this->get_custom_data();

        $cm = get_coursemodule_from_instance('assign', $data->assignid, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $assign = new \assign($context, null, null);

        foreach ($data->users as $user_id) {
            $submission = $assign->get_user_submission($user_id, false);
            if ($submission == null || $submission->status != ASSIGN_SUBMISSION_STATUS_SUBMITTED)
                continue;

            $submited_files = (new \assign_submission_file($assign, null))->get_files($submission, new \stdClass);
            if (count($submited_files) !== 1)
                return;

            $submited_file = $submited_files[array_keys($submited_files)[0]];

            $payload = json_encode(array(
                'gitlab_private_token' => $data->config->gitlab_private_token,
                'gitlab_project_id' => $data->config->gitlab_project_id,
                'username' => $DB->get_record('user', array('id' => $user_id), 'username')->username,
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
                    'userid' => $user_id,
                    'assignid' => $submission->assignment,
                    'uuid' => json_decode($response, true)['UUID'],
                    'autograde' => $data->config->autograding,
                    'updatedat' => time(),
            ));
        }
    }
}
