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
 * Definition of vmchecker settings.
 *
 * @package   block_vmchecker
 * @copyright 2022 Mihai Baruta <baruta.mihai99@gmail.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext(
        'block_vmchecker/backend',
        get_string('backend_title', 'block_vmchecker'),
        get_string('backend_description', 'block_vmchecker'),
        'http://localhost:8000/api/v1/',
        PARAM_RAW
    ));
    $settings->add(new admin_setting_configtext(
        'block_vmchecker/submission_check',
        get_string('check_count_title', 'block_vmchecker'),
        get_string('check_count_description', 'block_vmchecker'),
        50,
        PARAM_INT
    ));
}
