<?php

namespace block_vmchecker\backend;

defined('MOODLE_INTERNAL') || die();

class api {
    private string $apiURL;

    public function __construct(string $apiURL) {
        $this->apiURL = $apiURL;
    }

    public function info(array $query_params) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPGET, true);

        curl_setopt($ch, CURLOPT_URL, $this->apiURL . 'info?' . http_build_query($query_params));
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        return $response;
    }
}
