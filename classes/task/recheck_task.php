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
        global $DB;

        $data = $this->get_custom_data();

        $cm = get_coursemodule_from_instance('assign', $data->assignid, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $assign = new \assign($context, null, null);

        $api = new \block_vmchecker\backend\api(get_config('block_vmchecker', 'backend'));

        $this->log(count($data->users) . ' tasks to recheck');

        foreach ($data->users as $user_id) {
            $submission = $assign->get_user_submission($user_id, false);
            if ($submission == null || $submission->status != ASSIGN_SUBMISSION_STATUS_SUBMITTED)
                continue;

            $submited_files = (new \assign_submission_file($assign, null))->get_files($submission, new \stdClass);
            if (count($submited_files) !== 1)
                return;

            $submited_file = $submited_files[array_keys($submited_files)[0]];

            $payload = array(
                'gitlab_private_token' => $data->config->gitlab_private_token,
                'gitlab_project_id' => $data->config->gitlab_project_id,
                'username' => $DB->get_record('user', array('id' => $user_id), 'username')->username,
                'archive' => base64_encode($submited_file->get_content()),
            );

            $response = $api->submit($payload);
            if (empty($response))
                continue;

            $DB->insert_record('block_vmchecker_submissions',
                array(
                    'userid' => $user_id,
                    'assignid' => $submission->assignment,
                    'uuid' => $response['UUID'],
                    'autograde' => $data->config->autograding,
                    'updatedat' => time(),
            ));
        }
    }
}
