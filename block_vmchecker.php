<?php

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/classes/form/block_form.php');
require_once(__DIR__ . '/classes/form/submit_form.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

class block_vmchecker extends block_base
{

    public function init()
    {
        $this->title = get_string('vmchecker', 'block_vmchecker');
    }

    public function has_config()
    {
        return true;
    }

    private function set_title()
    {
        if (!$this->config->assignment)
            return;

        $cm = get_coursemodule_from_instance('assign', $this->config->assignment, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        $assign = new \assign($context, null, null);
        $this->title = get_string('vmchecker', 'block_vmchecker') . ' - ' . $assign->get_default_instance()->name;
    }

    private function process_block_form(block_vmchecker\form\block_form $form, array $all_users)
    {
        $fromform = $form->get_data();

        if ($fromform === null)
            return;

        if ($fromform->assignid !== $this->config->assignment)
            return;

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
                    'users' => $all_users,
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

    private function process_submit_form(block_vmchecker\form\submit_form $form)
    {
        global $USER, $DB;

        $fromform = $form->get_data();

        if ($fromform === null)
            return;

        if ($fromform->assignid !== $this->config->assignment)
            return;

        $record = $DB->get_record('block_vmchecker_gitlab',  array(
            'userid' => $USER->id,
            'assignid' => $this->config->assignment,
        ), 'id');

        if ($record)
            $DB->update_record('block_vmchecker_gitlab', array(
                'id' => $record->id,
                'projecttoken' => $fromform->{'gitlab_access_token' . $this->config->assignment},
                'projectid' => (int) $fromform->{'gitlab_project_id' . $this->config->assignment},
            ));
        else
            $DB->insert_record('block_vmchecker_gitlab', array(
                'userid' => $USER->id,
                'assignid' => $this->config->assignment,
                'projecttoken' => $fromform->{'gitlab_access_token' . $this->config->assignment},
                'projectid' => (int) $fromform->{'gitlab_project_id' . $this->config->assignment},
            ));

        $task = new block_vmchecker\task\retrieve_submission_task();
        $task->set_custom_data(array(
            'gitlab_private_token' => $fromform->{'gitlab_access_token' . $this->config->assignment},
            'gitlab_project_id' => $fromform->{'gitlab_project_id' . $this->config->assignment},
            'username' => $USER->username,
            'userid' => $USER->id,
            'assignmentid' => $this->config->assignment,
        ));
        \core\task\manager::queue_adhoc_task($task, true);

        return true;
    }

    public function get_content()
    {
        global $FULLME;

        if (!has_capability('block/vmchecker:view', $this->context)) {
            return null;
        }

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;

        if ($this->config->assignment == null) {
            $this->content->text = get_string('no_assignment_selected', 'block_vmchecker');
            return $this->content;
        }

        $cm = get_coursemodule_from_instance('assign', $this->config->assignment, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        $assign = new \assign($context, null, null);

        $this->set_title();
        $backend_url = get_config('block_vmchecker', 'backend');
        $api = new \block_vmchecker\backend\api($backend_url);

        if (!$api->healthcheck()) {
            $this->content->text = get_string('form_backend_down', 'block_vmchecker', $backend_url);
            return $this->content;
        }

        if (has_capability('block/vmchecker:submit', $this->context)) {
            $this->content->text = '';

            $cutoffdate = intval($assign->get_instance()->cutoffdate);
            if ($cutoffdate !== 0 && time() > $cutoffdate) {
                $this->content->text .= get_string('form_after_deadline', 'block_vmchecker');
            } else {
                // NOTE: Using FULLME because if null is passed strip_querystring($FULLME) will be used.
                //      It will throw an error for view.php if it does not have a id in its query string
                $mform = new block_vmchecker\form\submit_form($FULLME, array('assignid' => $this->config->assignment));
                if($mform->get_data() && !$this->process_submit_form($mform))
                    $this->content->text .= get_string('form_student_invalid_action', ' block_vmchecker') . '<br><br>';

                $this->content->text .= $mform->render();
            }
        } else if (has_capability('block/vmchecker:manage', $this->context)) {
            $tasks_new = $api->info(array(
                'status' => \block_vmchecker\backend\api::TASK_STATE_NEW,
                'gitlab_project_id' => $this->config->gitlab_project_id,
            ));
            $tasks_wfr = $api->info(array(
                'status' => \block_vmchecker\backend\api::TASK_STATE_WAITING_FOR_RESULTS,
                'gitlab_project_id' => $this->config->gitlab_project_id,
            ));

            $this->content->text = get_string('form_queue_info', 'block_vmchecker',
                ['new' => count($tasks_new), 'waiting_for_results' => count($tasks_wfr)]);

            $participants = $assign->list_participants(0, false, false);
            $filtered_participants = array();
            $all_users_id = array();
            foreach ($participants as $p) {
                $submission = $assign->get_user_submission($p->id, false);
                if ($submission == null || $submission->status != ASSIGN_SUBMISSION_STATUS_SUBMITTED)
                    continue;

                array_push($filtered_participants, $p);
                array_push($all_users_id, $p->id);
            }

            $form_custom_data = array(
                'participants' => $filtered_participants,
                'assignid' => $this->config->assignment,
            );

            // NOTE: Using FULLME because if null is passed strip_querystring($FULLME) will be used.
            //      It will throw an error for view.php if it does not have a id in its query string
            $mform = new block_vmchecker\form\block_form($FULLME, $form_custom_data);
            if($mform->get_data() && !$this->process_block_form($mform, $all_users_id))
                $this->content->text .= '<br>' . get_string('form_invalid_action', ' block_vmchecker');

            $this->content->text .= '<br><br>' . $mform->render();
        } else {
            $this->content->text = get_string('form_not_allowed', 'block_vmchecker');
        }

        return $this->content;
    }

    public function applicable_formats()
    {
        return array('course-view' => true);
    }

    public function instance_allow_multiple()
    {
        return true;
    }

    function instance_create()
    {
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

    function instance_config_save($data, $nolongerused = false)
    {
        global $DB;

        parent::instance_config_save($data, $nolongerused);
        $DB->update_record('block_vmchecker_options', [
            'id' => $DB->get_record('block_vmchecker_options', array('blockinstanceid' => $this->instance->id), 'id')->id,
            'assignid' => $data->assignment,
        ]);
    }

    function instance_delete()
    {
        global $DB;

        $DB->delete_records('block_vmchecker_options', array('blockinstanceid' => $this->instance->id));
        return parent::instance_delete();
    }
}
