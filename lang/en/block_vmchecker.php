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
 * Definition of vmchecker strings.
 *
 * @package   block_vmchecker
 * @copyright 2022 Mihai Baruta <baruta.mihai99@gmail.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

$string['pluginname'] = 'VMChecker block';
$string['vmchecker'] = 'VMChecker';
$string['vmchecker:addinstance'] = 'Add a new VMChecker block';
$string['vmchecker:view'] = 'User roles allowed to see the block';
$string['vmchecker:submit'] = 'Types of users that can submit through the VMChecker block';
$string['vmchecker:manage'] = 'User roles allowed to manage the assignemnt (teaching assistants)';
$string['header'] = 'VMChecker settings';
$string['gitlab_project_id'] = 'Gitlab repository ID';
$string['gitlab_private_token'] = 'Gitlab private token';
$string['assignment'] = 'Assignment';
$string['submission_checker'] = 'Submission checker';
$string['autograding'] = 'Enable autograding of submissions';
$string['no_assignment_selected'] = 'No assignment selected';
$string['action_recheck'] = 'Recheck submissions for ...';
$string['action_recheck_all'] = 'Recheck all submissions';
$string['action_moss'] = 'MOSS check for ...';
$string['backend_description'] = 'Backend API. The URL path must include the api version';
$string['check_count_description'] = 'Number of submissions to be checked in a single run of cron';
$string['autograding_yes'] = 'Yes';
$string['autograding_no'] = 'No';
$string['form_run_action'] = 'Run action';
$string['form_queue_info'] = 'New: {$a->new}<br>Waiting for results: {$a->waiting_for_results}';
$string['form_backend_down'] = 'VMChecker backend at "{$a}" is offline.';
$string['form_after_deadline'] = 'The assignment has finished.';
$string['form_invalid_action'] = 'Invalid action!';
$string['form_student_invalid_action'] = 'Error processing the request!';
$string['form_not_allowed'] = 'You are not allowed to access this form!';
