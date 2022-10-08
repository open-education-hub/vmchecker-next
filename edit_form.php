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
 * Definition of vmchecker admin config form.
 *
 * @package   block_vmchecker
 * @copyright 2022 Mihai Baruta <baruta.mihai99@gmail.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

/**
 * Definition of the editing form found in the admin menu of Moodle.
 *
 * @copyright 2022 Mihai Baruta <baruta.mihai99@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_vmchecker_edit_form extends block_edit_form {

    /**
     * Creates the admin config form.
     * @param \MoodleQuickForm $mform quickform object definition
     * @return void
     */
    protected function specific_definition($mform) {
        $mform->addElement('header', 'config_vmchecker_header', get_string('header', 'block_vmchecker'));
        $mform->setExpanded('config_vmchecker_header');

        $mform->addElement(
            'select',
            'config_autograding',
            get_string('autograding', 'block_vmchecker'),
            array(
                true => get_string('autograding_yes', 'block_vmchecker'),
                false => get_string('autograding_no', 'block_vmchecker')
            ),
            null
        );
        $mform->setDefault('config_autograding', true);
        $mform->settype('config_autograding', PARAM_BOOL);

        $mform->addElement('text', 'config_gitlab_project_id', get_string('gitlab_project_id', 'block_vmchecker'));
        $mform->addRule('config_gitlab_project_id', null, 'required', null, 'client');
        $mform->settype('config_gitlab_project_id', PARAM_INT);

        $mform->addElement('text', 'config_gitlab_private_token', get_string('gitlab_private_token', 'block_vmchecker'));
        $mform->addRule('config_gitlab_private_token', null, 'required', null, 'client');
        $mform->settype('config_gitlab_private_token', PARAM_TEXT);

        $courseactivities = get_array_of_activities($this->page->course->id);
        $assignments = array();
        foreach ($courseactivities as $activity) {
            if ($activity->mod != "assign") {
                continue;
            }

            $assignments[$activity->id] = $activity->name;
        }

        $mform->addElement('select', 'config_assignment', get_string('assignment', 'block_vmchecker'), $assignments);
        $mform->addRule('config_assignment', null, 'required', null, 'client');
        $mform->settype('config_assignment', PARAM_TEXT);
    }
}
