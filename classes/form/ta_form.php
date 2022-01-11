<?php

namespace block_vmchecker\form;

use moodleform;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class ta_form extends moodleform {

    public function definition() {
        $mform = $this->_form;

        $mform->addElement('select', 'action', 'Action', array('Recheck submissions for ...', 'MOSS check for ...'), null);
        $usersSelect = $mform->addElement('select', 'user', 'User ID', $this->getParticipants(), null);
        $usersSelect->setMultiple(true);

        $this->add_action_buttons(false, 'Run action');
    }

    function getParticipants() {
        $participants = $this->_customdata;
        $formatedData = array();

        foreach ($participants as $user) {
            $formatedData[$user->id] = $user->firstname . ' ' . $user->lastname . ' - ' . $user->username;
        }

        return $formatedData;
    }

    function validation($data, $files) {
        return array();
    }
}
