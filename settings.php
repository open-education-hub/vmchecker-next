<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext('block_vmchecker/backend', 'vmck backend',
                    get_string('backend_description', 'block_vmchecker'), 'http://localhost:8000/api/v1/', PARAM_RAW));
    $settings->add(new admin_setting_configtext('block_vmchecker/submission_check', 'Submissions check count',
                    get_string('check_count_description', 'block_vmchecker'), 50, PARAM_INT));
}
