<?php
// This file is part of Moodle - http://moodle.org/.

namespace local_proelts_teleport\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_proelts_teleport\local\attempt_guard;
use local_proelts_teleport\local\session_manager;

/** Save an ordered playback checkpoint. */
final class save_checkpoint extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'attemptid' => new external_value(PARAM_INT, 'Quiz attempt id'),
            'cmid' => new external_value(PARAM_INT, 'Quiz course-module id'),
            'mediaid' => new external_value(PARAM_ALPHANUMEXT, 'Media revision id'),
            'positionms' => new external_value(PARAM_INT, 'Actual playback position'),
            'sequence' => new external_value(PARAM_INT, 'Strictly increasing sequence'),
            'completed' => new external_value(PARAM_BOOL, 'Natural media completion'),
        ]);
    }

    public static function execute(int $attemptid, int $cmid, string $mediaid,
            int $positionms, int $sequence, bool $completed): array {
        global $DB;
        $params = self::validate_parameters(self::execute_parameters(),
            compact('attemptid', 'cmid', 'mediaid', 'positionms', 'sequence', 'completed'));
        $trusted = attempt_guard::require_live_attempt($params['attemptid'], $params['cmid']);
        self::validate_context($trusted['context']);
        if ($params['mediaid'] !== $trusted['media']['mediaid']) {
            throw new \moodle_exception('invalidparameter');
        }
        $transaction = $DB->start_delegated_transaction();
        $record = $DB->get_record_sql(
            'SELECT * FROM {local_proelts_tp_session} WHERE attemptid = ? FOR UPDATE',
            [$params['attemptid']], MUST_EXIST);
        $record = session_manager::checkpoint($record, $params['positionms'], $params['sequence'], $params['completed']);
        $transaction->allow_commit();
        return [
            'positionms' => (int) $record->positionms,
            'sequence' => (int) $record->sequence,
            'completed' => (bool) $record->completed,
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'positionms' => new external_value(PARAM_INT, 'Authoritative position'),
            'sequence' => new external_value(PARAM_INT, 'Latest accepted sequence'),
            'completed' => new external_value(PARAM_BOOL, 'Whether playback completed'),
        ]);
    }
}
