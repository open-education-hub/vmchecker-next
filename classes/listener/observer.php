<?php

namespace block_vmchecker\listener;

defined('MOODLE_INTERNAL') || die();

class observer {
    public static function submit(\core\event\base $event) {
        global $DB;

        $data = $event->get_data();
        $submission = $DB->get_record($data['objecttable'],
            array(
                'id' => $data['objectid']
        ));

        $vmchecker_options = $DB->get_record('block_vmchecker_options',
            array(
                'assignid' => $submission->assignment
        ));

        // The assignment is not a vmchecker type
        if ($vmchecker_options == null)
            return;

        $config_data = $DB->get_record('block_instances',
            array(
                'id' => $vmchecker_options->blockinstanceid
        ), 'configdata')->configdata;
        $config = unserialize(base64_decode($config_data));

        $fs = get_file_storage();
        $files = $fs->get_area_files($data['contextid'], 'assignsubmission_file', 'submission_files', $submission->submission);
        $submited_file = null;
        foreach($files as $file) {
            if ($file->get_filename() != '.') {
                $submited_file = $file;
                break;
            }
        }

        // TODO do stuff with service and etc
    }
}
