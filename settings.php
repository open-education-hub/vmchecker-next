<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree)
    $settings->add(new admin_setting_configtext('block_vmchecker_backend', 'vmck backend',
                   'description', 'http://localhost:8000/api/v1/', PARAM_RAW));
 