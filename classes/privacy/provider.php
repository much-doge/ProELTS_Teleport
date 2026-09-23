<?php
// This file is part of Moodle - http://moodle.org/.

namespace local_proelts_teleport\privacy;

use context;
use context_module;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

/** Privacy provider for attempt-linked playback state. */
final class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider {
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_proelts_tp_session', [
            'attemptid' => 'privacy:metadata:local_proelts_teleport_session:attemptid',
            'userid' => 'privacy:metadata:local_proelts_teleport_session:userid',
            'mediaid' => 'privacy:metadata:local_proelts_teleport_session:mediaid',
            'durationms' => 'privacy:metadata:local_proelts_teleport_session:durationms',
            'positionms' => 'privacy:metadata:local_proelts_teleport_session:positionms',
            'startedat' => 'privacy:metadata:local_proelts_teleport_session:startedat',
            'timemodified' => 'privacy:metadata:local_proelts_teleport_session:timemodified',
        ], 'privacy:metadata:local_proelts_teleport_session');
        return $collection;
    }

    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = 'SELECT ctx.id
                  FROM {local_proelts_tp_session} s
                  JOIN {context} ctx ON ctx.instanceid = s.cmid AND ctx.contextlevel = :contextlevel
                 WHERE s.userid = :userid';
        $params = ['contextlevel' => CONTEXT_MODULE, 'userid' => $userid];
        return (new contextlist())->add_from_sql($sql, $params);
    }

    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof context_module) {
                continue;
            }
            $records = $DB->get_records('local_proelts_tp_session', [
                'userid' => $contextlist->get_user()->id,
                'cmid' => $context->instanceid,
            ]);
            foreach ($records as $record) {
                writer::with_context($context)->export_data([
                    get_string('pluginname', 'local_proelts_teleport'),
                    (string) $record->attemptid,
                ], (object) [
                    'attemptid' => $record->attemptid,
                    'mediaid' => $record->mediaid,
                    'positionms' => $record->positionms,
                    'completed' => transform::yesno($record->completed),
                    'startedat' => transform::datetime($record->startedat),
                    'timemodified' => transform::datetime($record->timemodified),
                ]);
            }
        }
    }

    public static function delete_data_for_all_users_in_context(context $context): void {
        global $DB;
        if ($context instanceof context_module) {
            $DB->delete_records('local_proelts_tp_session', ['cmid' => $context->instanceid]);
        }
    }

    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context instanceof context_module) {
                $DB->delete_records('local_proelts_tp_session', [
                    'userid' => $contextlist->get_user()->id,
                    'cmid' => $context->instanceid,
                ]);
            }
        }
    }
}
