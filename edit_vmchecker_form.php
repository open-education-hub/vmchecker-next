<?php

defined('MOODLE_INTERNAL') || die();

/**
 * vmchecker question type editing form.
 *
 * @copyright  2007 Jamie Pratt me@jamiep.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_vmchecker_edit_form extends question_edit_form {

    protected function definition_inner($mform) {
        // TODO: use it later for out own options
        // $qtype = question_bank::get_qtype('vmchecker');

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
