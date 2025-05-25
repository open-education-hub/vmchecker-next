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
 * Definition of vmchecker block entrypoint.
 *
 * @package   block_vmchecker
 * @copyright 2022 Mihai Baruta <baruta.mihai99@gmail.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace block_vmchecker\exceptions;

defined('MOODLE_INTERNAL') || die();

class api_exception extends \moodle_exception {

    /**
     * Constructor for the API exception.
     *
     * @param string $message The error message.
     * @param string $module The module name, default is 'block_vmchecker'.
     * @param mixed $extra_data Additional data to be passed with the exception.
     * @param string|null $debuginfo Debug information, if any.
     */
    public function __construct($message, $module = 'block_vmchecker', $extra_data = null, $debuginfo = null) {
        parent::__construct($message, $module, null, $extra_data, $debuginfo);
    }
}
