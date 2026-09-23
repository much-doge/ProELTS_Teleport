<?php
// This file is part of Moodle - http://moodle.org/.

$string['pluginname'] = 'ProELTS Teleport';
$string['enabled'] = 'Enable Teleport';
$string['enabled_desc'] = 'Enable controlled Listening playback for allowlisted quiz activities.';
$string['enabledcmids'] = 'Allowed quiz activity IDs';
$string['enabledcmids_desc'] = 'Comma-separated course-module IDs. Teleport never activates outside this list.';
$string['targetselector'] = 'Listening wrapper selector';
$string['targetselector_desc'] = 'CSS selector for explicitly marked Listening audio wrappers.';
$string['mediaconfig'] = 'Controlled media definitions';
$string['mediaconfig_desc'] = 'One line per quiz: CMID|media-id|duration-ms. The authored wrapper must use the matching data-proelts-media-id.';
$string['checkpointseconds'] = 'Server checkpoint interval';
$string['checkpointseconds_desc'] = 'Minimum seconds between routine progress writes. Significant events may save sooner.';
$string['privacy:metadata:local_proelts_teleport_session'] = 'Attempt-specific controlled audio playback state.';
$string['privacy:metadata:local_proelts_teleport_session:attemptid'] = 'The Moodle quiz attempt.';
$string['privacy:metadata:local_proelts_teleport_session:userid'] = 'The candidate who owns the attempt.';
$string['privacy:metadata:local_proelts_teleport_session:mediaid'] = 'The configured media revision identifier.';
$string['privacy:metadata:local_proelts_teleport_session:durationms'] = 'The configured recording duration in milliseconds.';
$string['privacy:metadata:local_proelts_teleport_session:positionms'] = 'The latest accepted playback checkpoint.';
$string['privacy:metadata:local_proelts_teleport_session:startedat'] = 'When playback first started.';
$string['privacy:metadata:local_proelts_teleport_session:timemodified'] = 'When playback state was last updated.';
