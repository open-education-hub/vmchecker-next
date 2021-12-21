<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree)
    $settings->add(new admin_setting_configtext('block_vmchecker_backend', 'vmck backend',
                   'description', 'svwd', PARAM_RAW));
