<?php

$observers = array(
    array(
        'eventname'   => 'mod_assign\event\submission_created',
        'callback'    => 'block_vmchecker\listener\observer::submit',
    ),
    array(
        'eventname'   => 'mod_assign\event\submission_updated',
        'callback'    => 'block_vmchecker\listener\observer::submit',
    ),
);
