<?php

defined('MOODLE_INTERNAL') || die();

// WTF!?!? - auto descover?
require_once(__DIR__ . '/classes/form/ta_form.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

class block_vmchecker extends block_base {

    public function init() {
        $this->title = get_string('vmchecker', 'block_vmchecker');
    }

    public function has_config() {
        return true;
    }

    public function get_content() {
        global $DB, $CFG, $FULLME;

        if ($this->content !== null) {
            return $this->content;
        }

        if ($this->config->assignment == null) {
            return 'No assignment';
        }

        $course_activities = get_array_of_activities($this->page->course->id);
        foreach ($course_activities as $activity) {
            if ($activity->mod != "assign" || $activity->id != $this->config->assignment)
                continue;

            $this->title = get_string('vmchecker', 'block_vmchecker') . ' - ' . $activity->name;
            break;
        }

        $api = new \block_vmchecker\backend\api($CFG->block_vmchecker_backend);
        $tasks_new = $api->info(array(
            'status' => 'new',
            'gitlab_project_id' => $this->config->gitlab_project_id,
        ));
        $tasks_wfr = $api->info(array(
            'status' => 'waiting_for_results',
            'gitlab_project_id' => $this->config->gitlab_project_id,
        ));

        $this->content         =  new stdClass;
        $this->content->text   = 'New: ' . count($tasks_new) . '<br>';
        $this->content->text  .= 'Waiting for results: ' . count($tasks_wfr);

        $cm = get_coursemodule_from_instance('assign', $this->config->assignment, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        $assign = new \assign($context, null, null);
        $participants = $assign->list_participants(0, false, false);
        $filtered_participants = array();
        foreach ($participants as $user) {
            $submission = $assign->get_user_submission($user->id, false);
            if ($submission == null || $submission->status != "submitted")
                continue;

            array_push($filtered_participants, $user);
        }

        $mform = new block_vmchecker\form\ta_form($FULLME, $filtered_participants);

        if ($fromform = $mform->get_data()) {
            $task = new block_vmchecker\task\recheck_task();
            $task->set_custom_data(array(
                'assignid' => $this->config->assignment,
                'config' => $this->config,
                'users' => $fromform->user,
            ));
            \core\task\manager::queue_adhoc_task($task, true);
        }
        $this->content->text .= '<br><br>' . $mform->render();

        return $this->content;
    }

    public function applicable_formats() {
        return array('course-view' => true);
    }

    public function instance_allow_multiple() {
        return true;
    }

    function instance_create() {
        global $DB;

        $DB->insert_record('block_vmchecker_options',
            array(
                'blockinstanceid' => $this->instance->id,
                'assignid' => -1,
        ));

        return parent::instance_create();
    }

    function instance_config_save($data, $nolongerused = false) {
        global $DB;

        parent::instance_config_save($data, $nolongerused);
        $DB->update_record('block_vmchecker_options', [
            'id' => $DB->get_record('block_vmchecker_options', array('blockinstanceid' => $this->instance->id), 'id')->id,
            'assignid' => $data->assignment,
        ]);
    }

    function instance_delete() {
        global $DB;

        $DB->delete_records('block_vmchecker_options', array('blockinstanceid' => $this->instance->id));
        return parent::instance_delete();
    }
}
