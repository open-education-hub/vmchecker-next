<?php

class block_vmchecker_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        $mform->addElement('header', 'config_vmchecker_header', get_string('header', 'block_vmchecker'));
        $mform->setExpanded('config_vmchecker_header');

        $mform->addElement('select', 'config_autograding', get_string('autograding', 'block_vmchecker'),
            array(true => get_string('autograding_yes', 'block_vmchecker'), false => get_string('autograding_no', 'block_vmchecker')), null);
        $mform->setDefault('config_autograding', true);
        $mform->settype('config_autograding', PARAM_BOOL);

        $mform->addElement('text', 'config_gitlab_project_id', get_string('gitlab_project_id', 'block_vmchecker'));
        $mform->addRule('config_gitlab_project_id', null, 'required', null, 'client');
        $mform->settype('config_gitlab_project_id', PARAM_INT);

        $mform->addElement('text', 'config_gitlab_private_token', get_string('gitlab_private_token', 'block_vmchecker'));
        $mform->addRule('config_gitlab_private_token', null, 'required', null, 'client');
        $mform->settype('config_gitlab_private_token', PARAM_TEXT);

        $course_activities = get_array_of_activities($this->page->course->id);
        $assignments = array();
        foreach ($course_activities as $activity) {
            if ($activity->mod != "assign")
                continue;

            $assignments[$activity->id] = $activity->name;
        }

        $mform->addElement('select', 'config_assignment', get_string('assignment', 'block_vmchecker'), $assignments);
        $mform->addRule('config_assignment', null, 'required', null, 'client');
        $mform->settype('config_assignment', PARAM_TEXT);
    }
}
