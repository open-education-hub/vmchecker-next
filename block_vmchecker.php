<?php

defined('MOODLE_INTERNAL') || die();

// WTF!?!? - auto descover?
require_once(__DIR__ . '/classes/form/ta_form.php');

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

        $mform = new block_vmchecker\form\ta_form();

        //Form processing and displaying is done here
        if ($mform->is_cancelled()) {
            //Handle form cancel operation, if cancel button is present on form
        } else if ($fromform = $mform->get_data()) {
            //In this case you process validated data. $mform->get_data() returns data posted in form.
        } else {
            // this branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
            // or on the first display of the form.

            //displays the form
            $this->content->text .= '<br><br>' . $mform->render();
        }

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
