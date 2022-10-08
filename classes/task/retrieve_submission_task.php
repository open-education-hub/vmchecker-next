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
 * Definition of vmchecker submission retrival task.
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
 * Definition of the retrieving of a submission task.
 *
 * @copyright 2022 Mihai Baruta <baruta.mihai99@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class retrieve_submission_task extends \core\task\adhoc_task {

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
        $data = $this->get_custom_data();

        $api = new \block_vmchecker\backend\api(get_config('block_vmchecker', 'backend'));
        $response = $api->archive(
            array(
                "gitlab_private_token" => $data->gitlab_private_token,
                "gitlab_project_id" => $data->gitlab_project_id,
            )
        );

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
        $userarchive = (object) array(
            'contextid' => $context->id,
            'component' => 'assignsubmission_file',
            'filearea' => ASSIGNSUBMISSION_FILE_FILEAREA,
            'itemid' => $submission->id,
            'filepath' => '/',
            'filename' => 'archive.zip'
        );
        $fs->delete_area_files($context->id, 'assignsubmission_file', ASSIGNSUBMISSION_FILE_FILEAREA, $submission->id);
        $fs->create_file_from_string($userarchive, base64_decode($response['diff']));

        $submission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
        $plugin = $assign->get_submission_plugin_by_type('file');
        $plugin->save($submission, new \stdClass());
    }
}
