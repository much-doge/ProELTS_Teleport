<?php
// This file is part of Moodle - http://moodle.org/.

namespace local_proelts_teleport;

use core\hook\output\before_http_headers;
use local_proelts_teleport\local\configuration;

defined('MOODLE_INTERNAL') || die();

/** Moodle output-hook integration. */
final class hook_callbacks {
    /** Load Teleport only for an eligible candidate-owned live attempt. */
    public static function before_http_headers(before_http_headers $hook): void {
        global $DB, $PAGE, $USER;

        if (during_initial_install() || $PAGE->pagetype !== 'mod-quiz-attempt' ||
                !(bool) get_config('local_proelts_teleport', 'enabled')) {
            return;
        }
        $attemptid = optional_param('attempt', 0, PARAM_INT);
        if ($attemptid <= 0 || !$PAGE->cm || $PAGE->cm->modname !== 'quiz' ||
                !configuration::cmid_allowed((int) $PAGE->cm->id)) {
            return;
        }
        $attempt = $DB->get_record('quiz_attempts', ['id' => $attemptid], 'id,quiz,userid,state');
        if (!$attempt || (int) $attempt->userid !== (int) $USER->id ||
                (int) $attempt->quiz !== (int) $PAGE->cm->instance || $attempt->state !== 'inprogress') {
            return;
        }
        $media = configuration::media_for_cmid((int) $PAGE->cm->id);
        if ($media === null) {
            return;
        }
        $selector = trim((string) get_config('local_proelts_teleport', 'targetselector'));
        $checkpointseconds = max(10, min(120,
            (int) get_config('local_proelts_teleport', 'checkpointseconds')));
        if ($selector === '') {
            $selector = '.proelts-listening';
        }
        $PAGE->requires->js_call_amd('local_proelts_teleport/player', 'init', [[
            'attemptid' => $attemptid,
            'cmid' => (int) $PAGE->cm->id,
            'selector' => $selector,
            'mediaid' => $media['mediaid'],
            'durationms' => $media['durationms'],
            'checkpointseconds' => $checkpointseconds,
        ]]);
    }
}
