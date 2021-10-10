<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/questionbase.php');

/**
 * Represents a vmchecker question.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_vmchecker_question extends question_graded_automatically {

    public $responseformat;

    /** @var int Indicates whether an inline response is required ('0') or optional ('1')  */
    public $responserequired;

    public $responsefieldlines;
    public $attachments;

    /** @var int The number of attachments required for a response to be complete. */
    public $attachmentsrequired;

    public $graderinfo;
    public $graderinfoformat;
    public $responsetemplate;
    public $responsetemplateformat;

    /** @var array The string array of file types accepted upon file submission. */
    public $filetypeslist;

    public function make_behaviour(question_attempt $qa, $preferredbehaviour) {
        return question_engine::make_behaviour('deferredfeedback', $qa, $preferredbehaviour);
    }

    public function grade_response(array $response) {
        return array(1, question_state::$gradedright);
    }

    /**
     * @param moodle_page the page we are outputting to.
     * @return qtype_vmchecker_format_renderer_base the response-format-specific renderer.
     */
    public function get_format_renderer(moodle_page $page) {
        return $page->get_renderer('qtype_vmchecker', 'format_' . $this->responseformat);
    }

    public function get_expected_data() {
        if ($this->responseformat == 'editorfilepicker') {
            $expecteddata = array('answer' => question_attempt::PARAM_RAW_FILES);
        } else {
            $expecteddata = array('answer' => PARAM_RAW);
        }
        $expecteddata['answerformat'] = PARAM_ALPHANUMEXT;
        if ($this->attachments != 0) {
            $expecteddata['attachments'] = question_attempt::PARAM_FILES;
        }
        return $expecteddata;
    }

    public function summarise_response(array $response) {
        $output = null;

        if (isset($response['answer'])) {
            $output .= question_utils::to_plain_text($response['answer'],
                $response['answerformat'], array('para' => false));
        }

        if (isset($response['attachments'])  && $response['attachments']) {
            $attachedfiles = [];
            foreach ($response['attachments']->get_files() as $file) {
                $attachedfiles[] = $file->get_filename() . ' (' . display_size($file->get_filesize()) . ')';
            }
            if ($attachedfiles) {
                $output .= get_string('attachedfiles', 'qtype_vmchecker', implode(', ', $attachedfiles));
            }
        }
        return $output;
    }

    public function un_summarise_response(string $summary) {
        if (!empty($summary)) {
            return ['answer' => text_to_html($summary)];
        } else {
            return [];
        }
    }

    public function get_correct_response() {
        return null;
    }

    public function is_complete_response(array $response) {
        // Determine if the given response has online text and attachments.
        $hasinlinetext = array_key_exists('answer', $response) && ($response['answer'] !== '');
        $hasattachments = array_key_exists('attachments', $response)
            && $response['attachments'] instanceof question_response_files;

        // Determine the number of attachments present.
        if ($hasattachments) {
            // Check the filetypes.
            $filetypesutil = new \core_form\filetypes_util();
            $whitelist = $filetypesutil->normalize_file_types($this->filetypeslist);
            $wrongfiles = array();
            foreach ($response['attachments']->get_files() as $file) {
                if (!$filetypesutil->is_allowed_file_type($file->get_filename(), $whitelist)) {
                    $wrongfiles[] = $file->get_filename();
                }
            }
            if ($wrongfiles) { // At least one filetype is wrong.
                return false;
            }
            $attachcount = count($response['attachments']->get_files());
        } else {
            $attachcount = 0;
        }

        // Determine if we have /some/ content to be graded.
        $hascontent = $hasinlinetext || ($attachcount > 0);

        // Determine if we meet the optional requirements.
        $meetsinlinereq = $hasinlinetext || (!$this->responserequired) || ($this->responseformat == 'noinline');
        $meetsattachmentreq = ($attachcount >= $this->attachmentsrequired);

        // The response is complete iff all of our requirements are met.
        return $hascontent && $meetsinlinereq && $meetsattachmentreq;
    }

    public function is_gradable_response(array $response) {
        // Determine if the given response has online text and attachments.
        if (array_key_exists('answer', $response) && ($response['answer'] !== '')) {
            return true;
        } else if (array_key_exists('attachments', $response)
                && $response['attachments'] instanceof question_response_files) {
            // TODO: DO stuff with response
            $student_archive = current($response['attachments']->get_files());
            $tmp_archive = $student_archive->copy_content_to_temp('files/' .  $student_archive->get_id());
            $tmpdir = dirname($tmp_archive);
            $repo = $tmpdir .'/repo';
            mkdir($repo);
            $res = shell_exec('git clone ssh://git@localhost:4444/acs/iocla/iocla-1.git ' . $repo);
            $res = shell_exec('unzip -o ' . $tmp_archive . ' -d ' . $repo . '/skel 2>&1');
            $branch_name =  'branch-' . $student_archive->get_id();
            $res = shell_exec('cd ' . $repo . '; git checkout -b ' . $branch_name . '; git add .; git commit -m wip; git push origin ' . $branch_name);
            $ch = curl_init();

            $project_id = '6';
            curl_setopt($ch, CURLOPT_URL, 'http://localhost:5555/api/v4/projects/' . $project_id . '/pipelines?ref=' . $branch_name);
            $headers = [
                'PRIVATE-TOKEN: yRBZTJvP1f68Rx2Dbs_z',
            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            do {
                sleep(5);
                $res = curl_exec($ch);
                $json = json_decode($res, true)[0];
            } while($json['status'] == 'running');

            curl_setopt($ch, CURLOPT_URL, 'http://localhost:5555/api/v4/projects/' . $project_id . '/pipelines/' . $json['id'] . '/jobs');
            $res = curl_exec($ch);
            $json = json_decode($res, true)[0];

            curl_setopt($ch, CURLOPT_URL, 'http://localhost:5555/api/v4/projects/' . $project_id . '/jobs/' . $json['id'] . '/trace');
            $res = curl_exec($ch);

            $matches = array();
            preg_match('/Total: ([0-9]+)/', $res , $matches);
            $score = intval($matches[1]);

            curl_close($ch);

            return true;
        } else {
            return false;
        }
    }

    public function is_same_response(array $prevresponse, array $newresponse) {
        if (array_key_exists('answer', $prevresponse) && $prevresponse['answer'] !== $this->responsetemplate) {
            $value1 = (string) $prevresponse['answer'];
        } else {
            $value1 = '';
        }
        if (array_key_exists('answer', $newresponse) && $newresponse['answer'] !== $this->responsetemplate) {
            $value2 = (string) $newresponse['answer'];
        } else {
            $value2 = '';
        }
        return $value1 === $value2 && ($this->attachments == 0 ||
                question_utils::arrays_same_at_key_missing_is_blank(
                $prevresponse, $newresponse, 'attachments'));
    }

    public function check_file_access($qa, $options, $component, $filearea, $args, $forcedownload) {
        if ($component == 'question' && $filearea == 'response_attachments') {
            // Response attachments visible if the question has them.
            return $this->attachments != 0;

        } else if ($component == 'question' && $filearea == 'response_answer') {
            // Response attachments visible if the question has them.
            return $this->responseformat === 'editorfilepicker';

        } else if ($component == 'qtype_vmchecker' && $filearea == 'graderinfo') {
            return $options->manualcomment && $args[0] == $this->id;

        } else {
            return parent::check_file_access($qa, $options, $component,
                    $filearea, $args, $forcedownload);
        }
    }
}
