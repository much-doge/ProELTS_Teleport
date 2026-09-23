<?php
// This file is part of Moodle - http://moodle.org/.

namespace local_proelts_teleport\local;

use context_module;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Resolves and validates a candidate-owned live quiz attempt.
 *
 * @package local_proelts_teleport
 */
final class attempt_guard {
    /**
     * Validate a Teleport operation and return its trusted Moodle objects.
     *
     * @param int $attemptid Quiz attempt id.
     * @param int $cmid Course-module id.
     * @return array{attempt:object,cm:object,context:context_module,media:array}
     */
    public static function require_live_attempt(int $attemptid, int $cmid): array {
        global $DB, $USER;

        if (!(bool) get_config('local_proelts_teleport', 'enabled') ||
                !configuration::cmid_allowed($cmid)) {
            throw new moodle_exception('notavailable');
        }
        $cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
        $context = context_module::instance($cmid);
        require_login($cm->course, false, $cm);
        require_capability('mod/quiz:attempt', $context);

        $attempt = $DB->get_record('quiz_attempts', ['id' => $attemptid],
            'id,quiz,userid,state,timecheckstate', MUST_EXIST);
        if ((int) $attempt->userid !== (int) $USER->id ||
                (int) $attempt->quiz !== (int) $cm->instance ||
                $attempt->state !== 'inprogress' ||
                ((int) $attempt->timecheckstate > 0 && (int) $attempt->timecheckstate <= time())) {
            throw new moodle_exception('notavailable');
        }
        $media = configuration::media_for_cmid($cmid);
        if ($media === null) {
            throw new moodle_exception('invalidconfig', 'error');
        }
        return ['attempt' => $attempt, 'cm' => $cm, 'context' => $context, 'media' => $media];
    }
}
