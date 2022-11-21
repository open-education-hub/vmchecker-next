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
 * Definition of vmchecker recheck submission ask.
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
 * Definition of rechecking of submissions task.
 *
 * @copyright 2022 Mihai Baruta <baruta.mihai99@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class recheck_task extends \core\task\adhoc_task {

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
        global $DB;

        $data = $this->get_custom_data();

        $cm = get_coursemodule_from_instance('assign', $data->assignid, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $assign = new \assign($context, null, null);

        $api = new \block_vmchecker\backend\api(get_config('block_vmchecker', 'backend'));

        $this->log(count($data->users) . ' tasks to recheck');

        foreach ($data->users as $userid) {
            $submission = $assign->get_user_submission($userid, false);
            if ($submission == null || $submission->status != ASSIGN_SUBMISSION_STATUS_SUBMITTED) {
                continue;
            }

            $submitedfiles = (new \assign_submission_file($assign, null))->get_files($submission, new \stdClass());
            if (count($submitedfiles) !== 1) {
                return;
            }

            $submitedfile = $submitedfiles[array_keys($submitedfiles)[0]];

            $payload = array(
                'gitlab_private_token' => $data->config->gitlab_private_token,
                'gitlab_project_id' => $data->config->gitlab_project_id,
                'gitlab_branch' => $data->config->gitlab_branch,
                'username' => $DB->get_record('user', array('id' => $userid), 'username')->username,
                'archive' => base64_encode($submitedfile->get_content()),
            );

            $response = $api->submit($payload);
            if (empty($response)) {
                continue;
            }

            $DB->insert_record(
                'block_vmchecker_submissions',
                array(
                    'userid' => $userid,
                    'assignid' => $submission->assignment,
                    'uuid' => $response['UUID'],
                    'autograde' => $data->config->autograding,
                    'updatedat' => time(),
                )
            );
        }
    }
}
