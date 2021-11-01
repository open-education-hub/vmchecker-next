<?php

defined('MOODLE_INTERNAL') || die();

class qtype_vmchecker_qe2_attempt_updater extends question_qtype_attempt_updater {
    public function right_answer() {
        return '';
    }

    public function response_summary($state) {
        if (!empty($state->answer)) {
            return $this->to_text($state->answer);
        } else {
            return null;
        }
    }

    public function was_answered($state) {
        return !empty($state->answer);
    }

    public function set_first_step_data_elements($state, &$data) {
    }

    public function supply_missing_first_step_data(&$data) {
    }

    public function set_data_elements_for_step($state, &$data) {
        if (!empty($state->answer)) {
            $data['answer'] = $state->answer;
            $data['answerformat'] = FORMAT_HTML;
        }
    }
}
