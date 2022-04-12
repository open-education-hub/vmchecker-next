<?php

namespace block_vmchecker\task;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

class retrieve_submission_task extends \core\task\adhoc_task {
    private function log(string $msg) {
        mtrace('[' . time() . '] ' . $msg);
    }

    public function execute() {
        $data = $this->get_custom_data();

        $api = new \block_vmchecker\backend\api(get_config('block_vmchecker', 'backend'));
        $response = $api->archive(
            array(
                "gitlab_private_token" => $data->gitlab_private_token,
                "gitlab_project_id" => $data->gitlab_project_id,
        ));

        if (empty($response)) {
            $this->log('Failed to retrieve archive for user ' . $data->username);
            return;
        }

        $this->log('Retrieved archive for user ' . $data->username . ' with id ' . strval($data->userid));

        $cm = get_coursemodule_from_instance('assign', $data->assignmentid, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        $assign = new \assign($context, null, null);
        $submission = $assign->get_user_submission($data->userid, true, 0);

        $fs = get_file_storage();
        $user_archive = (object) array(
            'contextid' => $context->id,
            'component' => 'assignsubmission_file',
            'filearea' => ASSIGNSUBMISSION_FILE_FILEAREA,
            'itemid' => $submission->id,
            'filepath' => '/',
            'filename' => 'archive.zip'
        );
        $fs->delete_area_files($context->id, 'assignsubmission_file', ASSIGNSUBMISSION_FILE_FILEAREA, $submission->id);
        $fs->create_file_from_string($user_archive, base64_decode($response['diff']));

        $submission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
        $plugin = $assign->get_submission_plugin_by_type('file');
        $plugin->save($submission, new \stdClass());
    }
}
