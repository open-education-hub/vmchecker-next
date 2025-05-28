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

namespace block_vmchecker\logger;

defined('MOODLE_INTERNAL') || die();

class logger {
    private const ERROR = 'ERROR';
    private const WARNING = 'WARNING';
    private const INFO = 'INFO';

    /**
     * Tags to be added to the log messages.
     *
     * @var string[]|null
     */
    private $tags;

    /**
     * Constructor.
     *
     * @param string[]|null $tags Tags to be added to the log messages.
     */
    public function __construct(array $tags = null) {
        $this->tags = $tags;
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array  $context
     */
    public function error(string $message, array $context = array()) {
        $this->log(self::ERROR, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array  $context
     */
    public function warning(string $message, array $context = array()) {
        $this->log(self::WARNING, $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array  $context
     */
    public function info(string $message, array $context = array()) {
        $this->log(self::INFO, $message, $context);
    }

    private function log(string $level, string $message, array $context = array()) {
        $timestamp = date('Y-m-d H:i:s');
        $resolvedTags = $this->tags != null ? '[' . implode('] [', $this->tags) . ']' : '';

        $output = sprintf(
            "[%s] [%s] %s %s",
            $timestamp,
            $level,
            $resolvedTags,
            $message
        );

        mtrace($output);
    }
}
