<?php

/**
 * This file defines an adhoc task to run submissions
 *
 * @package    qtype_vmchecker
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace qtype_vmchecker\task;

defined('MOODLE_INTERNAL') || die();

class run_submission_task extends \core\task\adhoc_task {
    private function log(string $msg) {
        mtrace('[' . time() . '] ' . $msg);
    }

    public function execute() {
        $this->log('Started vmchecker task with: ' . $this->get_custom_data_as_string());

        $data = json_decode($this->get_custom_data_as_string(), true);

        $tmp_archive = $data['tmp_archive_path'];
        $tmpdir = dirname($tmp_archive);
        $repo = $tmpdir .'/repo';
        mkdir($repo);
        $res = shell_exec('whoami');
        $this->log('User: ' . $res);
        $res = shell_exec('git clone ssh://git@localhost:4444/acs/iocla/iocla-1.git ' . $repo);
        $res = shell_exec('unzip -o ' . $tmp_archive . ' -d ' . $repo . '/skel 2>&1');
        $res = shell_exec('whoami');
        $branch_name =  'branch-' . $data['archive_id'];
        $res = shell_exec('cd ' . $repo . '; git checkout -b ' . $branch_name . '; git add .');
        $res = shell_exec('cd ' . $repo . '; git config user.email "mail@mail.com"; git config user.name moodle; git commit -m wip 2>&1');
        $res = shell_exec('cd ' . $repo . '; git push -u origin ' . $branch_name);
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
        $score = floatval($matches[1]) / 100;
        curl_close($ch);

        $this->log('Finished vmchecker task successfully. Final score ' . $score);
        // question_engine::make_questions_usage_by_activity
    }
}
