<?php

namespace block_vmchecker\form;

use moodleform;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class ta_form extends moodleform {
    public const ACTION_RECHECK = 'recheck';
    public const ACTION_RECHECK_ALL = 'recheck_all';
    public const ACTION_MOSS = 'moss';

    public function definition() {
        $mform = $this->_form;

        $searchBoxID = uniqid();
        $userListID = uniqid();
        $selectID = uniqid();

        $mform->addElement('text', 'search', 'Search', array('size' => '20', 'id' => $searchBoxID));
        $mform->setType('search', PARAM_RAW);
        $usersSelect = $mform->addElement('select', 'user', 'User', $this->getParticipants(), array('style' => 'width: 100%','id' => $userListID));
        $usersSelect->setMultiple(true);
        $mform->addElement(
            'select',
            'action',
            'Action',
            array(
                ta_form::ACTION_RECHECK => 'Recheck submissions for ...',
                ta_form::ACTION_RECHECK_ALL => 'Recheck all submissions',
                ta_form::ACTION_MOSS => 'MOSS check for ...'
            ),
            array('id' => $selectID)
        );
        $mform->addElement('html', '
        <script>
            let timeout;
            const searchBox = document.getElementById("' . $searchBoxID . '");
            const userList = document.getElementById("' . $userListID . '");
            const select = document.getElementById("' . $selectID . '");

            searchBox.addEventListener("input", (e) => {
                if (timeout)
                    clearTimeout(timeout);

                timeout = setTimeout(() => filterList(e.target.value), 300);
            });

            function filterList(text) {
                if (!text)
                    text = "";

                for (const option of userList.children)
                    if (!option.innerText.includes(text))
                        option.style.display = "none";
                    else
                        option.style.display = null;
            }

            select.addEventListener("change", selectAction);
            selectAction();
            
            function selectAction() {
                const selectValue = select.options[select.selectedIndex].value
                if (selectValue === "' . ta_form::ACTION_RECHECK_ALL . '") {
                    userList.parentNode.parentNode.style.display = "none";
                    searchBox.parentNode.parentNode.style.display = "none";
                } else {
                    userList.parentNode.parentNode.style.display = null;
                    searchBox.parentNode.parentNode.style.display = null;
                }
            }
        </script>
        ');

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
