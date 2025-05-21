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
 * Definition of vmchecker assignment event processor.
 *
 * @package   block_vmchecker
 * @copyright 2022 Mihai Baruta <baruta.mihai99@gmail.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace block_vmchecker\listener;

use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assign/locallib.php');

/**
 * Definition of assignment events listener.
 *
 * @copyright 2022 Mihai Baruta <baruta.mihai99@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /**
     * Stop the previous submission attemp before making a new one.
     * @param string $assignid
     * @return bool
     */
    private static function stop_previous_attempt($assignid) {
        global $DB, $USER;

        $previousattempt = $DB->get_record(
            'block_vmchecker_submissions',
            array(
                'userid' => $USER->id,
                'assignid' => $assignid,
            )
        );
        if (!$previousattempt) {
            return true;
        }

        $backendurl = get_config('block_vmchecker', 'backend');
        $api = new \block_vmchecker\backend\api($backendurl);
        $rawdata = $api->cancel($previousattempt->uuid);

        if ($rawdata === false) {
            return false;
        }

        $DB->delete_records('block_vmchecker_submissions', array('id' => $previousattempt->id));
        return true;
    }

    /**
     * Submit event handler.
     * @param \core\event\base $event
     * @return void
     */
    public static function submit(\core\event\base $event) {
        global $DB;

        $data = $event->get_data();
        $submissionfile = $DB->get_record(
            $data['objecttable'],
            array('id' => $data['objectid'])
        );

        $vmcheckeroptions = $DB->get_record(
            'block_vmchecker_options',
            array('assignid' => $submissionfile->assignment)
        );

        // The assignment is not a vmchecker type.
        if ($vmcheckeroptions == null) {
            return;
        }

        if (!self::stop_previous_attempt($submissionfile->assignment)) {
            return;
        }

        $configdata = $DB->get_record(
            'block_instances',
            array('id' => $vmcheckeroptions->blockinstanceid),
            'configdata'
        )->configdata;
        $config = unserialize(base64_decode($configdata));

        $cm = get_coursemodule_from_instance('assign', $submissionfile->assignment, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        $assign = new \assign($context, null, null);

        $params = array('assignment' => $assign->get_instance()->id, 'id' => $submissionfile->submission);
        $usersubmission = $DB->get_record('assign_submission', $params, '*', MUST_EXIST);

        $submitedfiles = (new \assign_submission_file($assign, null))->get_files($usersubmission, new stdClass());
        if (count($submitedfiles) !== 1) {
            return;
        }

        $submitedfile = reset($submitedfiles);

        $payload = array(
            'gitlab_private_token' => $config->gitlab_private_token,
            'gitlab_project_id' => $config->gitlab_project_id,
            'gitlab_branch' => $config->gitlab_branch,
            'username' => $assign->get_participant($usersubmission->userid)->username,
            'archive' => base64_encode($submitedfile->get_content()),
        );

        // Remove previous feedback for the assignament
        $teachercommenttext = "Waiting for automatic feedback";
    
        $data = new \stdClass();
        $data->attemptnumber = -1;
        $data->grade = -1;
        $data->assignfeedbackcomments_editor = ['text' => $teachercommenttext, 'format' => FORMAT_MOODLE];
        
        $assign->save_grade($submission->userid, $data);

        $api = new \block_vmchecker\backend\api(get_config('block_vmchecker', 'backend'));
        $response = $api->submit($payload);
        if (empty($response)) {
            return;
        }

        $DB->insert_record(
            'block_vmchecker_submissions',
            array(
                'userid' => $usersubmission->userid,
                'assignid' => $usersubmission->assignment,
                'uuid' => $response['UUID'],
                'autograde' => $config->autograding === '1',
                'updatedat' => time(),
            )
        );
    }
}
