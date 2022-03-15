<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree)
    $settings->add(new admin_setting_configtext('block_vmchecker_backend', 'vmck backend',
                   'Backend API. The URL path must include the api version', 'http://localhost:8000/api/v1/', PARAM_RAW));
    $settings->add(new admin_setting_configtext('block_vmchecker_submission_check', 'Submissions check count',
                   'Number of submissions to be checked in a single run of cron', 50, PARAM_INT));
 