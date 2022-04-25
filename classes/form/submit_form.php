<?php

namespace block_vmchecker\form;

use moodleform;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class submit_form extends moodleform {

    public function definition() {
        $mform = $this->_form;

        $gitlab_access_token_id = 'gitlab_access_token' . $this->_customdata['assignid'];
        $mform->addElement('text', $gitlab_access_token_id, 'Access token');
        $mform->setType($gitlab_access_token_id, PARAM_RAW);

        $gitlab_project_id_id = 'gitlab_project_id' . $this->_customdata['assignid'];
        $mform->addElement('text', $gitlab_project_id_id, 'Project ID');
        $mform->setType($gitlab_project_id_id, PARAM_INT);

        $mform->addElement('hidden', 'assignid', $this->_customdata['assignid']);
        $mform->setType('assignid', PARAM_RAW);

        $this->add_action_buttons(false, 'Submit');
    }

    function validation($data, $files) {
        return array();
    }
}
