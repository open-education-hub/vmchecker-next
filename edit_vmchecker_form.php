<?php

defined('MOODLE_INTERNAL') || die();

class qtype_vmchecker_edit_form extends question_edit_form {

    protected function definition_inner($mform) {
        // TODO: use it later for our own options
        // $qtype = question_bank::get_qtype('vmchecker');

        // NOTE: Moodle's public ssh key must be added to the repo

        $mform->addElement('header', 'vmcheckeroptions', get_string('vmcheckeroptions', 'qtype_vmchecker'));
        $mform->setExpanded('vmcheckeroptions');
        $mform->settype('vmcheckeroptions', PARAM_TEXT);

        $mform->addElement('text', 'gitlaburl', get_string('gitlaburl', 'qtype_vmchecker'));
        $mform->addRule('gitlaburl', null, 'required', null, 'client');
        $mform->settype('gitlaburl', PARAM_TEXT);

        $mform->addElement('text', 'gitlabprojectid', get_string('gitlabprojectid', 'qtype_vmchecker'));
        $mform->addRule('gitlabprojectid', null, 'required', null, 'client');
        $mform->settype('gitlabprojectid', PARAM_INT);

        $mform->addElement('text', 'gitlabprivaterepotoken', get_string('gitlabprivaterepotoken', 'qtype_vmchecker'));
        $mform->addRule('gitlabprivaterepotoken', null, 'required', null, 'client');
        $mform->settype('gitlabprivaterepotoken', PARAM_TEXT);

        $mform->addElement('header', 'responseoptions', get_string('responseoptions', 'qtype_vmchecker'));
        $mform->setExpanded('responseoptions');

        $mform->addElement('filetypes', 'filetypeslist', get_string('acceptedfiletypes', 'qtype_vmchecker'));
        $mform->addRule('filetypeslist', null, 'required', null, 'client');

        $mform->addElement('header', 'graderinfoheader', get_string('graderinfoheader', 'qtype_vmchecker'));
        $mform->setExpanded('graderinfoheader');
        $mform->addElement('editor', 'graderinfo', get_string('graderinfo', 'qtype_vmchecker'),
                array('rows' => 10), $this->editoroptions);
    }

    protected function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);

        if (empty($question->options)) {
            return $question;
        }

        $question->filetypeslist = $question->options->filetypeslist;

        $draftid = file_get_submitted_draft_itemid('graderinfo');
        $question->graderinfo = array();
        $question->graderinfo['text'] = file_prepare_draft_area(
            $draftid,           // Draftid
            $this->context->id, // context
            'qtype_vmchecker',  // component
            'graderinfo',       // filarea
            !empty($question->id) ? (int) $question->id : null, // itemid
            $this->fileoptions, // options
            $question->options->graderinfo // text.
        );
        $question->graderinfo['format'] = $question->options->graderinfoformat;
        $question->graderinfo['itemid'] = $draftid;

        return $question;
    }

    public function validation($fromform, $files) {
        // TODO: Do validations here
        return parent::validation($fromform, $files);
    }

    public function qtype() {
        return 'vmchecker';
    }
}
