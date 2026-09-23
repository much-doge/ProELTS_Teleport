<?php
// This file is part of Moodle - http://moodle.org/.

namespace local_proelts_teleport\local;

use dml_write_exception;
use moodle_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Persistent playback state with conservative, ordered updates.
 *
 * @package local_proelts_teleport
 */
final class session_manager {
    /** Maximum scheduling/network tolerance added to plausible playback advancement. */
    private const ADVANCE_TOLERANCE_MS = 3000;

    /**
     * Start once or recover an existing session.
     *
     * @param object $attempt Trusted quiz attempt.
     * @param object $cm Trusted course module.
     * @param array $media Trusted configured media.
     * @return stdClass
     */
    public static function start(object $attempt, object $cm, array $media): stdClass {
        global $DB, $USER;

        $existing = $DB->get_record('local_proelts_tp_session', ['attemptid' => $attempt->id]);
        if ($existing) {
            self::require_matching_media($existing, $media);
            return $existing;
        }

        $now = time();
        $record = (object) [
            'attemptid' => (int) $attempt->id,
            'userid' => (int) $USER->id,
            'quizid' => (int) $attempt->quiz,
            'cmid' => (int) $cm->id,
            'mediaid' => $media['mediaid'],
            'durationms' => $media['durationms'],
            'positionms' => 0,
            'sequence' => 0,
            'completed' => 0,
            'startedat' => $now,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        try {
            $record->id = $DB->insert_record('local_proelts_tp_session', $record);
            return $record;
        } catch (dml_write_exception $e) {
            // A simultaneous first-play request may win the unique attempt key.
            $existing = $DB->get_record('local_proelts_tp_session', ['attemptid' => $attempt->id], '*', MUST_EXIST);
            self::require_matching_media($existing, $media);
            return $existing;
        }
    }

    /**
     * Accept an ordered checkpoint only when its forward movement is plausible.
     *
     * @param stdClass $record Existing session.
     * @param int $positionms Candidate-reported actual playback position.
     * @param int $sequence Strictly increasing client sequence.
     * @param bool $completed Whether the media ended naturally.
     * @return stdClass Current authoritative record.
     */
    public static function checkpoint(stdClass $record, int $positionms, int $sequence, bool $completed): stdClass {
        global $DB;

        if ($record->completed) {
            return $record;
        }
        if ($sequence <= (int) $record->sequence) {
            return $record;
        }
        if ($positionms < (int) $record->positionms || $positionms > (int) $record->durationms) {
            throw new moodle_exception('invalidparameter');
        }
        $elapsedms = max(0, time() - (int) $record->timemodified) * 1000;
        $maximum = min((int) $record->durationms,
            (int) $record->positionms + $elapsedms + self::ADVANCE_TOLERANCE_MS);
        if ($positionms > $maximum) {
            throw new moodle_exception('invalidparameter');
        }
        if ($completed && $positionms < (int) $record->durationms - 1500) {
            throw new moodle_exception('invalidparameter');
        }

        $record->positionms = $positionms;
        $record->sequence = $sequence;
        $record->completed = $completed ? 1 : 0;
        $record->timemodified = time();
        $DB->update_record('local_proelts_tp_session', $record);
        return $record;
    }

    /**
     * Ensure an attempt cannot switch to another media revision.
     *
     * @param stdClass $record Existing session.
     * @param array $media Trusted media definition.
     */
    private static function require_matching_media(stdClass $record, array $media): void {
        if ($record->mediaid !== $media['mediaid'] ||
                (int) $record->durationms !== (int) $media['durationms']) {
            throw new moodle_exception('invalidconfig', 'error');
        }
    }
}
