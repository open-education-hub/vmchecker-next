<?php

defined('MOODLE_INTERNAL') || die();

class block_vmchecker extends block_base {

    public function init() {
        $this->title = get_string('vmchecker', 'block_vmchecker');
    }

    public function get_content() {
        global $DB;

        if ($this->content !== null) {
          return $this->content;
        }

        $course_activities = get_array_of_activities($this->page->course->id);
        foreach ($course_activities as $activity) {
            if ($activity->mod != "assign" || $activity->id != $this->config->assignment)
                continue;

            $this->title = get_string('vmchecker', 'block_vmchecker') . ' - ' . $activity->name;
            break;
        }

        $this->content         =  new stdClass;
        $this->content->text   = 'In queue: ' .
            $DB->count_records('block_vmchecker_submissions',
                array('assignid' => $this->config->assignment)
            );

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
