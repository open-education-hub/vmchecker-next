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
 * Definition of vmchecker student submission form.
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
 * Definition of the student submission form.
 *
 * @copyright 2022 Mihai Baruta <baruta.mihai99@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class submit_form extends moodleform {

    /**
     * Form definition.
     * @return void
     */
    public function definition() {
        global $USER, $DB;

        $mform = $this->_form;

        $defaultvalues = $DB->get_record('block_vmchecker_gitlab', array(
            'userid' => $USER->id,
            'assignid' => (int) $this->_customdata['assignid'],
        ));

        // NOTE: Using a custom id for each form element because after a submit all forms are filled in with the submited form data.
        $gitlabaccesstokenid = 'gitlab_access_token' . $this->_customdata['assignid'];
        $mform->addElement('text', $gitlabaccesstokenid, 'Access token');
        $mform->setType($gitlabaccesstokenid, PARAM_RAW);
        if ($defaultvalues) {
            $mform->setDefault($gitlabaccesstokenid, $defaultvalues->projecttoken);
        }

        $gitlabprojectid = 'gitlab_project_id' . $this->_customdata['assignid'];
        $mform->addElement('text', $gitlabprojectid, 'Project ID');
        $mform->setType($gitlabprojectid, PARAM_INT);
        if ($defaultvalues) {
            $mform->setDefault($gitlabprojectid, $defaultvalues->projectid);
        }

        $mform->addElement('hidden', 'assignid', $this->_customdata['assignid']);
        $mform->setType('assignid', PARAM_RAW);

        $this->add_action_buttons(false, 'Submit');
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
