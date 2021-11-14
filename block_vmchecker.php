<?php

defined('MOODLE_INTERNAL') || die();

class block_vmchecker extends block_base {

    public function init() {
        $this->title = get_string('vmchecker', 'block_vmchecker');
    }

    public function get_content() {
        if ($this->content !== null) {
          return $this->content;
        }

        $this->content         =  new stdClass;
        $this->content->text   = 'The content of our VMChecker block!';
        $this->content->footer = 'Footer here...';

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
