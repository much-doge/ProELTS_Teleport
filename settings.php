<?php
// This file is part of Moodle - http://moodle.org/.

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_proelts_teleport',
        get_string('pluginname', 'local_proelts_teleport'));

    $settings->add(new admin_setting_configcheckbox(
        'local_proelts_teleport/enabled',
        get_string('enabled', 'local_proelts_teleport'),
        get_string('enabled_desc', 'local_proelts_teleport'),
        0
    ));
    $settings->add(new admin_setting_configtext(
        'local_proelts_teleport/enabledcmids',
        get_string('enabledcmids', 'local_proelts_teleport'),
        get_string('enabledcmids_desc', 'local_proelts_teleport'),
        '',
        PARAM_TEXT
    ));
    $settings->add(new admin_setting_configtext(
        'local_proelts_teleport/targetselector',
        get_string('targetselector', 'local_proelts_teleport'),
        get_string('targetselector_desc', 'local_proelts_teleport'),
        '.proelts-listening',
        PARAM_TEXT
    ));
    $settings->add(new admin_setting_configtextarea(
        'local_proelts_teleport/mediaconfig',
        get_string('mediaconfig', 'local_proelts_teleport'),
        get_string('mediaconfig_desc', 'local_proelts_teleport'),
        '',
        PARAM_RAW_TRIMMED
    ));
    $settings->add(new admin_setting_configtext(
        'local_proelts_teleport/checkpointseconds',
        get_string('checkpointseconds', 'local_proelts_teleport'),
        get_string('checkpointseconds_desc', 'local_proelts_teleport'),
        30,
        PARAM_INT
    ));

    $ADMIN->add('localplugins', $settings);
}
