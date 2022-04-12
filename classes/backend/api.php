<?php

namespace block_vmchecker\backend;

defined('MOODLE_INTERNAL') || die();

class api {
    private string $api_url;

    public const TASK_STATE_NEW = 'new';
    public const TASK_STATE_WAITING_FOR_RESULTS = 'waiting_for_results';
    public const TASK_STATE_DONE = 'done';
    public const TASK_STATE_ERROR = 'error';

    public function __construct(string $api_url) {
        $this->api_url = $api_url;
    }

    private function clean_url(string $url) {
        $offset = strlen('http://');

        if (strncmp('https', $url, strlen('https')) === 0)
            $offset++;

        $protocol_free = substr($url, $offset);
        $clean_url_part = preg_replace('/(\/+)/', '/', $protocol_free);

        return substr($url, 0, $offset) . $clean_url_part;
    }

    private function query_service(string $endpoint, ?array $query_params, ?array $payload) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        } else {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        }

        $full_url = $this->api_url . $endpoint;
        $clean_url = $this->clean_url($full_url);   // reduce multiple / to one 'http://aa///b' -> 'http://a/b'
        if ($query_params !== null)
            $clean_url .= '?' . http_build_query($query_params);

        curl_setopt($ch, CURLOPT_URL, $clean_url);
        $raw_data = curl_exec($ch);
        if ($raw_data === false)
            return array();

        $response = json_decode($raw_data, true);
        curl_close($ch);

        return $response;
    }

    /*
    *   @param  array   $query_params
    *       status?:            string,  “new” | “waiting_for_results” | “done” | “all” // default: all
    *       gitlab_project_id?: int,
    *       moodle_username?:   string,
    *       count?:             int
    *       order?:             str,     “asc” | “desc” - by id; default: desc
    *   @return array
    */
    public function info(array $query_params) {
        return $this->query_service('/info', $query_params, null);
    }

    /*
    *   @param  array   $payload
    *       gitlab_private_token:   string
    *       gitlab_project_id:      int
    *       username:               string
    *       archive:                string, archive content - base64 encoded
    *   @return array
    */
    public function submit(array $payload) {
        return $this->query_service('/submit', null, $payload);
    }

    /*
    *   @param  array   $payload
    *       gitlab_private_token:   string
    *       gitlab_project_id:      int
    *   @return array
    */
    public function archive(array $payload) {
        return $this->query_service('/archive', null, $payload);
    }

    /*
    *   @param  string   $uuid  UUID of the task to check
    */
    public function status(string $uuid) {
        return $this->query_service('/' . $uuid . '/status', null, null);
    }

    /*
    *   @param  string   $uuid  UUID of the task to check
    */
    public function trace(string $uuid) {
        return $this->query_service('/' . $uuid . '/trace', null, null);
    }

    /*
    *   @param  string   $uuid  UUID of the task to cancel
    */
    public function cancel(string $uuid) {
        return $this->query_service('/' . $uuid . '/cancel', null, null);
    }


    public function healthcheck() {
        return !empty($this->query_service('/healthcheck', null, null));
    }
}
