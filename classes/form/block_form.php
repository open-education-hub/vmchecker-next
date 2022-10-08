<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Definition of vmchecker teaching assistant form.
 *
 * @package   block_vmchecker
 * @copyright 2022 Mihai Baruta <baruta.mihai99@gmail.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace block_vmchecker\form;

use moodleform;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Definition of the teaching assistant form.
 *
 * @copyright 2022 Mihai Baruta <baruta.mihai99@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_form extends moodleform {
    /**
     * Rechecking of a single/all selected submission(s) action.
     * @var string
     */
    public const ACTION_RECHECK = 'recheck';
    /**
     * Recheking of all submissions.
     * @var string
     */
    public const ACTION_RECHECK_ALL = 'recheck_all';
    /**
     * Moss check for the entire assignment.
     * @var string
     */
    public const ACTION_MOSS = 'moss';

    /**
     * Form definition.
     * @return void
     */
    public function definition() {
        $mform = $this->_form;

        $searchboxid = uniqid();
        $userlistid = uniqid();
        $selectid = uniqid();

        $mform->addElement('text', 'search', 'Search', array('size' => '20', 'id' => $searchboxid));
        $mform->setType('search', PARAM_RAW);
        $usersselect = $mform->addElement(
            'select',
            'user',
            'User',
            $this->get_participants(),
            array('style' => 'width: 100%', 'id' => $userlistid)
        );
        $usersselect->setMultiple(true);
        $mform->addElement(
            'select',
            'action',
            'Action',
            array(
                self::ACTION_RECHECK => get_string('action_recheck', 'block_vmchecker'),
                self::ACTION_RECHECK_ALL => get_string('action_recheck_all', 'block_vmchecker'),
                self::ACTION_MOSS => get_string('action_moss', 'block_vmchecker'),
            ),
            array('id' => $selectid)
        );
        $mform->addElement('html', '
        <script>
            let timeout;
            const searchBox = document.getElementById("' . $searchboxid . '");
            const userList = document.getElementById("' . $userlistid . '");
            const select = document.getElementById("' . $selectid . '");

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
                if (selectValue === "' . self::ACTION_RECHECK_ALL . '") {
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

        $this->add_action_buttons(false, get_string('form_run_action', 'block_vmchecker'));
    }

    /**
     * Format the list of students that have submitted their assignment.
     * @return object
     */
    public function get_participants() {
        $participants = $this->_customdata['participants'];
        $formateddata = array();

        foreach ($participants as $user) {
            $formateddata[$user->id] = $user->firstname . ' ' . $user->lastname . ' - ' . $user->username;
        }

        return $formateddata;
    }

    /**
     * Validation of the form.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        return array();
    }
}
