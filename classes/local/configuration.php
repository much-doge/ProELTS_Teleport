<?php
// This file is part of Moodle - http://moodle.org/.

namespace local_proelts_teleport\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Parses fail-closed Teleport configuration.
 *
 * @package local_proelts_teleport
 */
final class configuration {
    /**
     * Return the configured media definition for a course module.
     *
     * @param int $cmid Course-module id.
     * @param string|null $raw Optional configuration value for tests.
     * @return array{mediaid:string,durationms:int}|null
     */
    public static function media_for_cmid(int $cmid, ?string $raw = null): ?array {
        $raw = $raw ?? (string) get_config('local_proelts_teleport', 'mediaconfig');
        foreach (preg_split('/\R/', $raw) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) !== 3 || !ctype_digit($parts[0]) || !ctype_digit($parts[2])) {
                continue;
            }
            $linecmid = (int) $parts[0];
            $durationms = (int) $parts[2];
            if ($linecmid !== $cmid) {
                continue;
            }
            if (!preg_match('/\A[A-Za-z0-9._-]{1,64}\z/', $parts[1])) {
                return null;
            }
            if ($durationms < 1000 || $durationms > 7200000) {
                return null;
            }
            return ['mediaid' => $parts[1], 'durationms' => $durationms];
        }
        return null;
    }

    /**
     * Whether a CMID occurs in the explicit allowlist.
     *
     * @param int $cmid Course-module id.
     * @param string|null $raw Optional value for tests.
     * @return bool
     */
    public static function cmid_allowed(int $cmid, ?string $raw = null): bool {
        $raw = $raw ?? (string) get_config('local_proelts_teleport', 'enabledcmids');
        $ids = array_filter(array_map('trim', explode(',', $raw)), 'strlen');
        return in_array((string) $cmid, $ids, true);
    }
}
