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
 * Definition of vmchecker backend API.
 *
 * @package   block_vmchecker
 * @copyright 2022 Mihai Baruta <baruta.mihai99@gmail.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace block_vmchecker\backend;

use \block_vmchecker\exceptions\api_exception;

/**
 * Definition of the backend API for VMChecker Next.
 *
 * @copyright 2022 Mihai Baruta <baruta.mihai99@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api {
    /**
     * The base url of the endpoint (e.g: http://localhost:8000/api/v1)
     * @var string
     */
    private $apiurl;

    /**
     * Task that was freshly submitted.
     * @var string
     */
    public const TASK_STATE_NEW = 'new';
    /**
     * Task that is currently running.
     * @var string
     */
    public const TASK_STATE_WAITING_FOR_RESULTS = 'waiting_for_results';
    /**
     * Task that has finished its execution.
     * @var string
     */
    public const TASK_STATE_DONE = 'done';
    /**
     * Task that had an error while being processed.
     * @var string
     */
    public const TASK_STATE_ERROR = 'error';


    private const OPERATION_GET = 'GET';
    private const OPERATION_POST = 'POST';

    /**
     * Class constructor
     * @param string $apiurl The endpoint's URL
     */
    public function __construct(string $apiurl) {
        $this->apiurl = $apiurl;
    }

    /**
     * Remove extra '/' from the URL
     * @param string $url
     * @return string
     */
    private function clean_url(string $url) {
        $offset = strlen('http://');

        if (strncmp('https', $url, strlen('https')) === 0) {
            $offset++;
        }

        $protocolfree = substr($url, $offset);
        $cleanurlpart = preg_replace('/(\/+)/', '/', $protocolfree);

        return substr($url, 0, $offset) . $cleanurlpart;
    }

    /**
     * Queries the backend endpoint
     * @param string $endpoint
     * @param array $queryparams
     * @param array $payload
     * @throws \block_vmchecker\exceptions\api_exception
     * @return object
     */
    private function query_service(string $endpoint, string $operation = OPERATION_GET, ?array $queryparams, ?array $payload): array {
        $curl = new \curl();
        $curl->setopt(
            array(
                'CURLOPT_TIMEOUT' => 10,
                'CURLOPT_CONNECTTIMEOUT' => 5)
        );

        $fullurl = $this->apiurl . $endpoint;
        $cleanurl = $this->clean_url($fullurl);   // Reduce multiple / to one 'http://aa///b' -> 'http://a/b'.

        \block_vmchecker\event\api_called::create(
            array(
                'other' => array(
                    'operation' => $operation,
                    'method' => $endpoint
                )
            )
        )->trigger();

        $rawresponse = '';
        if ($operation === self::OPERATION_POST) {
            $rawresponse = $curl->post(
                $cleanurl,
                $payload ? json_encode($payload) : null,
                array('CURLOPT_HTTPHEADER' => array("Content-Type: application/json"))
            );
        } else if ($operation === self::OPERATION_GET) {
            $rawresponse = $curl->get($cleanurl, $queryparams);
        } else {
            throw new api_exception(
                'vmchecker_backend_api_error',
                'block_vmchecker',
                array('error' => "Invalid operation type!"),
                'Operation type: ' . $operation
            );
        }

        $info = $curl->get_info();
        if ($curlerrno = $curl->get_errno()) {
            // CURL connection error.
            throw new api_exception(
                'vmchecker_backend_api_error',
                'block_vmchecker',
                array('error' => "Unexpected cURL error!"),
                'cURL error number: ' . $curlerrno
            );
        } else if ($info['http_code'] != 200) {
            throw new api_exception(
                'vmchecker_backend_api_error',
                'block_vmchecker',
                array('error' => "Unexpected response from the backend server!"),
                'HTTP error code: ' . $info['http_code']
            );
        }

        $response = json_decode($rawresponse, true);
        if (!is_array($response)) {
            throw new api_exception(
                'vmchecker_backend_api_error',
                'block_vmchecker',
                array('error' => "Invalid response format from the backend server!"),
                'Response is not an array'
            );
        }

        return $response;
    }

    /**
     * Info endpoint.
     * @param array $queryparams
     *      status?:            string,  “new” | “waiting_for_results” | “done” | “all” // default: all
     *      gitlab_project_id?: int,
     *      moodle_username?:   string,
     *      count?:             int
     *      order?:             str,     “asc” | “desc” - by id; default: desc
     * @throws \block_vmchecker\exceptions\api_exception
     * @return array
     */
    public function info(array $queryparams) {
        return $this->query_service('/info', self::OPERATION_GET, $queryparams, null);
    }

    /**
     * Submit endpoint.
     * @param array $payload
     *      gitlab_private_token:   string
     *      gitlab_project_id:      int
     *      gitlab_branch:          string
     *      username:               string
     *      archive:                string, archive content - base64 encoded.
     * @throws \block_vmchecker\exceptions\api_exception
     * @return array
     */
    public function submit(array $payload) {
        return $this->query_service('/submit', self::OPERATION_POST, null, $payload);
    }

    /**
     * Get archive endpoint.
     * @param array $payload
     *      gitlab_private_token:   string
     *      gitlab_project_id:      int
     *      gitlab_branch:          string
     * @throws \block_vmchecker\exceptions\api_exception
     * @return array
     */
    public function archive(array $payload) {
        return $this->query_service('/archive', self::OPERATION_POST, null, $payload);
    }

    /**
     * Get status endpoint.
     * @param string $uuid UUID of the task to check.
     * @throws \block_vmchecker\exceptions\api_exception
     * @return object
     */
    public function status(string $uuid) {
        return $this->query_service('/' . $uuid . '/status', self:: OPERATION_GET, null, null);
    }

    /**
     * Get trace endpoint.
     * @param string $uuid UUID of the task to check.
     * @throws \block_vmchecker\exceptions\api_exception
     * @return object
     */
    public function trace(string $uuid) {
        return $this->query_service('/' . $uuid . '/trace', self::OPERATION_GET, null, null);
    }

    /**
     * Cancel submission endpoint
     * @param  string $uuid UUID of the task to cancel.
     * @throws \block_vmchecker\exceptions\api_exception
     * @return object
     */
    public function cancel(string $uuid) {
        return $this->query_service('/' . $uuid . '/cancel', self::OPERATION_POST, null, null);
    }

    /**
     * Checks if the API endpoint is alive.
     * @throws \block_vmchecker\exceptions\api_exception
     * @return bool
     */
    public function healthcheck() {
        return !empty($this->query_service('/health', self::OPERATION_GET, null, null));
    }
}
