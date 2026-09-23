<?php
// This file is part of Moodle - http://moodle.org/.

namespace local_proelts_teleport\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_proelts_teleport\local\attempt_guard;
use local_proelts_teleport\local\session_manager;

/** Start or recover a controlled playback session. */
final class start_playback extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'attemptid' => new external_value(PARAM_INT, 'Quiz attempt id'),
            'cmid' => new external_value(PARAM_INT, 'Quiz course-module id'),
            'mediaid' => new external_value(PARAM_ALPHANUMEXT, 'Authored media revision id'),
        ]);
    }

    public static function execute(int $attemptid, int $cmid, string $mediaid): array {
        $params = self::validate_parameters(self::execute_parameters(), compact('attemptid', 'cmid', 'mediaid'));
        $trusted = attempt_guard::require_live_attempt($params['attemptid'], $params['cmid']);
        self::validate_context($trusted['context']);
        if ($mediaid !== $trusted['media']['mediaid']) {
            throw new \moodle_exception('invalidparameter');
        }
        return self::format(session_manager::start($trusted['attempt'], $trusted['cm'], $trusted['media']));
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'positionms' => new external_value(PARAM_INT, 'Authoritative recovery position'),
            'sequence' => new external_value(PARAM_INT, 'Latest accepted sequence'),
            'durationms' => new external_value(PARAM_INT, 'Configured duration'),
            'completed' => new external_value(PARAM_BOOL, 'Whether playback completed'),
        ]);
    }

    private static function format(object $record): array {
        return [
            'positionms' => (int) $record->positionms,
            'sequence' => (int) $record->sequence,
            'durationms' => (int) $record->durationms,
            'completed' => (bool) $record->completed,
        ];
    }
}
