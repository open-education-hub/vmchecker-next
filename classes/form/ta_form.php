<?php

namespace block_vmchecker\form;

use moodleform;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class ta_form extends moodleform {

    public function definition() {
        $mform = $this->_form;

        $searchBoxID = uniqid();
        $userListID = uniqid();

        $mform->addElement('text', 'search', 'Search', array('size' => '20', 'id' => $searchBoxID));
        $mform->setType('search', PARAM_RAW);
        $usersSelect = $mform->addElement('select', 'user', 'User', $this->getParticipants(), array('style' => 'width: 100%','id' => $userListID));
        $usersSelect->setMultiple(true);
        $mform->addElement('html', '
        <script>
            let timeout;
            document.getElementById("' . $searchBoxID . '").addEventListener("input", (e) => {
                if (timeout)
                    clearTimeout(timeout);

                timeout = setTimeout(() => filterList(e.target.value), 300);
            });

            function filterList(text) {
                if (!text)
                    text = "";

                const list = document.getElementById("' . $userListID . '");

                for (const option of list.children)
                    if (!option.innerText.includes(text))
                        option.style.display = "none";
                    else
                        option.style.display = null;
            }
        </script>
        ');
        $mform->addElement(
            'select',
            'action',
            'Action',
            array(
                'recheck' => 'Recheck submissions for ...',
                'moss' => 'MOSS check for ...'
            ),
            null
        );

        $mform->addElement('hidden', 'assignid', $this->_customdata['assignid']);
        $mform->setType('assignid', PARAM_RAW);

        $this->add_action_buttons(false, 'Run action');
    }

    function getParticipants() {
        $participants = $this->_customdata['participants'];
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
