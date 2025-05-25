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
 * Definition of vmchecker assignment event processor.
 *
 * @package   block_vmchecker
 * @copyright 2022 Mihai Baruta <baruta.mihai99@gmail.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace block_vmchecker\event;

defined('MOODLE_INTERNAL') || die();

class task_submitted extends \core\event\base {
    /**
     * Initialize the event.
     */
    protected function init() {
        $this->data['crud'] = 'c'; // 'c' = create, 'r' = read, 'u' = update, 'd' = delete
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'block_vmchecker_submissions'; // The database table related to the event.
    }

    /**
     * Returns a description of the event.
     */
    public function get_description() {
        return "The user with id '{$this->userid}' submitted a task with id '{$this->objectid}'.";
    }

    /**
     * Returns the name of the event.
     * @return string
     */
    public static function get_name() {
        return "Task Submitted";
    }
}
