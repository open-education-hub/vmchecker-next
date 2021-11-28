<?php

namespace block_vmchecker\form;

use moodleform;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class ta_form extends moodleform {
    public function definition() {
        $this->add_action_buttons(false, 'Recheck');
    }

    function validation($data, $files) {
        return array();
    }
}
