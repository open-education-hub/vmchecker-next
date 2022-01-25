<?php

namespace block_vmchecker\form;

use moodleform;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class ta_form extends moodleform {

    public function definition() {
        $mform = $this->_form;

        $mform->addElement('select', 'action', 'Action', array('Recheck submissions for ...', 'MOSS check for ...'), null);
        $mform->addElement('text', 'search', 'Search', array('size'=>'20', 'id'=>'ceva'));
        $mform->setType('search', PARAM_RAW);
        $usersSelect = $mform->addElement('select', 'user', 'User ID', $this->getParticipants(), array('id'=>'ceva2'));
        $usersSelect->setMultiple(true);
        $mform->addElement('html', '
        <script>
            let timeout;
            document.getElementById("ceva").addEventListener("input", (e) => {
                if (timeout)
                    clearTimeout(timeout);

                timeout = setTimeout(() => filterList(e.target.value), 300);
            });

            function filterList(text) {
                if (!text)
                    text = "";

                const list = document.getElementById("ceva2");

                for (const option of list.children)
                    if (!option.innerText.includes(text))
                        option.style.display = "none";
                    else
                        option.style.display = null;
            }
        </script>
        ');

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
