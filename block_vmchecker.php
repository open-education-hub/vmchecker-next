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
 * Definition of vmchecker block entrypoint.
 *
 * @package   block_vmchecker
 * @copyright 2022 Mihai Baruta <baruta.mihai99@gmail.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/classes/form/block_form.php');
require_once(__DIR__ . '/classes/form/submit_form.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

/**
 * Definition of the block.
 *
 * @copyright 2022 Mihai Baruta <baruta.mihai99@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_vmchecker extends block_base {

    /**
     * Initialization handler.
     * @return void
     */
    public function init() {
        $this->title = get_string('vmchecker', 'block_vmchecker');
    }

    /**
     * Whether or not the block can be configured.
     * @return bool
     */
    public function has_config() {
        return true;
    }

    /**
     * Set thte tile of the block.
     * @return void
     */
    private function set_title() {
        if (!$this->config->assignment) {
            return;
        }

        $cm = get_coursemodule_from_instance('assign', $this->config->assignment, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        $assign = new \assign($context, null, null);
        $this->title = get_string('vmchecker', 'block_vmchecker') . ' - ' . $assign->get_default_instance()->name;
    }

    /**
     * Handler for the teaching assistant form.
     * @param block_vmchecker\form\block_form $form
     * @param array $allusers
     * @return void
     */
    private function process_block_form(block_vmchecker\form\block_form $form, array $allusers) {
        $fromform = $form->get_data();

        if ($fromform === null) {
            return;
        }

        if ($fromform->assignid !== $this->config->assignment) {
            return;
        }

        switch ($fromform->action) {
            case block_vmchecker\form\block_form::ACTION_RECHECK:
                $task = new block_vmchecker\task\recheck_task();
                $task->set_custom_data(array(
                    'assignid' => $this->config->assignment,
                    'config' => $this->config,
                    'users' => $fromform->user,
                ));
                \core\task\manager::queue_adhoc_task($task, true);
                break;
            case block_vmchecker\form\block_form::ACTION_RECHECK_ALL:
                $task = new block_vmchecker\task\recheck_task();
                $task->set_custom_data(array(
                    'assignid' => $this->config->assignment,
                    'config' => $this->config,
                    'users' => $allusers,
                ));
                \core\task\manager::queue_adhoc_task($task, true);
                break;
            case block_vmchecker\form\block_form::ACTION_MOSS:
                break;
            default:
                return false;
        }

        return true;
    }

    /**
     * Handler for the stuent form.
     * @param \block_vmchecker\form\submit_form $form
     * @return void
     */
    private function process_submit_form(block_vmchecker\form\submit_form $form) {
        global $USER, $DB;

        $fromform = $form->get_data();

        if ($fromform === null) {
            return;
        }

        if ($fromform->assignid !== $this->config->assignment) {
            return;
        }

        $record = $DB->get_record('block_vmchecker_gitlab', array(
            'userid' => $USER->id,
            'assignid' => $this->config->assignment,
        ), 'id');

        if ($record) {
            $DB->update_record('block_vmchecker_gitlab', array(
                'id' => $record->id,
                'projecttoken' => $fromform->{'gitlab_access_token' . $this->config->assignment},
                'projectid' => (int) $fromform->{'gitlab_project_id' . $this->config->assignment},
                'projectbranch' => $fromform->{'gitlab_branch' . $this->config->assignment},
            ));
        } else {
            $DB->insert_record('block_vmchecker_gitlab', array(
                'userid' => $USER->id,
                'assignid' => $this->config->assignment,
                'projecttoken' => $fromform->{'gitlab_access_token' . $this->config->assignment},
                'projectid' => (int) $fromform->{'gitlab_project_id' . $this->config->assignment},
                'projectbranch' => $fromform->{'gitlab_branch' . $this->config->assignment},
            ));
        }

        $task = new block_vmchecker\task\retrieve_submission_task();
        $task->set_custom_data(array(
            'gitlab_private_token' => $fromform->{'gitlab_access_token' . $this->config->assignment},
            'gitlab_project_id' => $fromform->{'gitlab_project_id' . $this->config->assignment},
            'gitlab_branch' => $fromform->{'gitlab_branch' . $this->config->assignment},
            'username' => $USER->username,
            'userid' => $USER->id,
            'assignmentid' => $this->config->assignment,
        ));
        \core\task\manager::queue_adhoc_task($task, true);

        return true;
    }

    /**
     * Get the HTML content for the block.
     * @return string
     */
    public function get_content() {
        global $FULLME;

        if (!has_capability('block/vmchecker:view', $this->context)) {
            return null;
        }

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();

        if ($this->config->assignment == null) {
            $this->content->text = get_string('no_assignment_selected', 'block_vmchecker');
            return $this->content;
        }

        try {
            $cm = get_coursemodule_from_instance('assign', $this->config->assignment, 0, false, MUST_EXIST);
        } catch (dml_missing_record_exception | dml_multiple_records_exception $e) {
            $this->content->text = get_string('no_assignment_selected', 'block_vmchecker');
            return $this->content;
        }
        $context = \context_module::instance($cm->id);

        $assign = new \assign($context, null, null);

        $this->set_title();
        $backendurl = get_config('block_vmchecker', 'backend');
        $api = new \block_vmchecker\backend\api($backendurl);

        if (!$api->healthcheck()) {
            $this->content->text = get_string('form_backend_down', 'block_vmchecker', $backendurl);
            return $this->content;
        }

        if (has_capability('block/vmchecker:submit', $this->context)) {
            $this->content->text = '';

            $cutoffdate = intval($assign->get_instance()->cutoffdate);
            if ($cutoffdate !== 0 && time() > $cutoffdate) {
                $this->content->text .= get_string('form_after_deadline', 'block_vmchecker');
            } else {
                // NOTE: Using FULLME because if null is passed strip_querystring($FULLME) will be used.
                // It will throw an error for view.php if it does not have a id in its query string.
                $mform = new block_vmchecker\form\submit_form($FULLME, array('assignid' => $this->config->assignment));
                if ($mform->get_data() && !$this->process_submit_form($mform)) {
                    $this->content->text .= get_string('form_student_invalid_action', ' block_vmchecker') . '<br><br>';
                }

                $this->content->text .= $mform->render();
            }
        } else if (has_capability('block/vmchecker:manage', $this->context)) {
            $tasksnew = $api->info(array(
                'status' => \block_vmchecker\backend\api::TASK_STATE_NEW,
                'gitlab_project_id' => $this->config->gitlab_project_id,
            ));
            $taskswfr = $api->info(array(
                'status' => \block_vmchecker\backend\api::TASK_STATE_WAITING_FOR_RESULTS,
                'gitlab_project_id' => $this->config->gitlab_project_id,
            ));

            $this->content->text = get_string(
                'form_queue_info',
                'block_vmchecker',
                ['new' => count($tasksnew), 'waiting_for_results' => count($taskswfr)]
            );

            $participants = $assign->list_participants(0, false, false);
            $filteredparticipants = array();
            $allusersid = array();
            foreach ($participants as $p) {
                $submission = $assign->get_user_submission($p->id, false);
                if ($submission == null || $submission->status != ASSIGN_SUBMISSION_STATUS_SUBMITTED) {
                    continue;
                }

                array_push($filteredparticipants, $p);
                array_push($allusersid, $p->id);
            }

            $formcustomdata = array(
                'participants' => $filteredparticipants,
                'assignid' => $this->config->assignment,
            );

            // NOTE: Using FULLME because if null is passed strip_querystring($FULLME) will be used.
            // It will throw an error for view.php if it does not have a id in its query string.
            $mform = new block_vmchecker\form\block_form($FULLME, $formcustomdata);
            if ($mform->get_data() && !$this->process_block_form($mform, $allusersid)) {
                $this->content->text .= '<br>' . get_string('form_invalid_action', ' block_vmchecker');
            }

            $this->content->text .= '<br><br>' . $mform->render();
        } else {
            $this->content->text = get_string('form_not_allowed', 'block_vmchecker');
        }

        return $this->content;
    }

    /**
     * At what level can the block be instantiated (e.g. main page, course view, etc.).
     * @return array
     */
    public function applicable_formats() {
        return array('course-view' => true);
    }

    /**
     * Can the block be instantiated multiple times.
     * @return bool
     */
    public function instance_allow_multiple() {
        return true;
    }

    /**
     * On instance create handler.
     * @return bool
     */
    public function instance_create() {
        global $DB;

        $DB->insert_record(
            'block_vmchecker_options',
            array(
                'blockinstanceid' => $this->instance->id,
                'assignid' => -1,
            )
        );

        return parent::instance_create();
    }

    /**
     * On instance config save handler.
     * @param object $data
     * @param bool $nolongerused
     * @return void
     */
    public function instance_config_save($data, $nolongerused = false) {
        global $DB;

        parent::instance_config_save($data, $nolongerused);
        $DB->update_record('block_vmchecker_options', [
            'id' => $DB->get_record('block_vmchecker_options', array('blockinstanceid' => $this->instance->id), 'id')->id,
            'assignid' => $data->assignment,
        ]);
    }

    /**
     * On instance delete handler.
     * @return void
     */
    public function instance_delete() {
        global $DB;

        $DB->delete_records('block_vmchecker_options', array('blockinstanceid' => $this->instance->id));
        return parent::instance_delete();
    }
}
