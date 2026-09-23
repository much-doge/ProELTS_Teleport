<?php
// This file is part of Moodle - http://moodle.org/.

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_proelts_teleport_start' => [
        'classname' => 'local_proelts_teleport\\external\\start_playback',
        'description' => 'Start or recover controlled playback for a quiz attempt.',
        'type' => 'write',
        'ajax' => true,
    ],
    'local_proelts_teleport_checkpoint' => [
        'classname' => 'local_proelts_teleport\\external\\save_checkpoint',
        'description' => 'Save an ordered controlled-playback checkpoint.',
        'type' => 'write',
        'ajax' => true,
    ],
];
