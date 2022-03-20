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

        $search_box_id = uniqid();
        $user_list_id = uniqid();
        $select_id = uniqid();

        $mform->addElement('text', 'search', 'Search', array('size' => '20', 'id' => $search_box_id));
        $mform->setType('search', PARAM_RAW);
        $users_select = $mform->addElement('select', 'user', 'User', $this->get_participants(), array('style' => 'width: 100%','id' => $user_list_id));
        $users_select->setMultiple(true);
        $mform->addElement(
            'select',
            'action',
            'Action',
            array(
                ta_form::ACTION_RECHECK => get_string('action_recheck', 'block_vmchecker'),
                ta_form::ACTION_RECHECK_ALL => get_string('action_recheck_all', 'block_vmchecker'),
                ta_form::ACTION_MOSS => get_string('action_moss', 'block_vmchecker'),
            ),
            array('id' => $select_id)
        );
        $mform->addElement('html', '
        <script>
            let timeout;
            const searchBox = document.getElementById("' . $search_box_id . '");
            const userList = document.getElementById("' . $user_list_id . '");
            const select = document.getElementById("' . $select_id . '");

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

        $this->add_action_buttons(false,  get_string('form_run_action', 'block_vmchecker'));
    }

    function get_participants() {
        $participants = $this->_customdata['participants'];
        $formated_data = array();

        foreach ($participants as $user) {
            $formated_data[$user->id] = $user->firstname . ' ' . $user->lastname . ' - ' . $user->username;
        }

        return $formated_data;
    }

    function validation($data, $files) {
        return array();
    }
}
