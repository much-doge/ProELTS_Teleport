<?php
// This file is part of Moodle - http://moodle.org/.

namespace local_proelts_teleport;

use advanced_testcase;
use local_proelts_teleport\local\configuration;

/** Tests strict Teleport configuration parsing. */
final class configuration_test extends advanced_testcase {
    public function test_matching_media_definition_is_parsed(): void {
        $media = configuration::media_for_cmid(116, "77|preview-v1|1000\n116|109-v001|1912345");
        $this->assertSame(['mediaid' => '109-v001', 'durationms' => 1912345], $media);
    }

    public function test_malformed_matching_definition_fails_closed(): void {
        $this->assertNull(configuration::media_for_cmid(116, '116|bad media id|1912345'));
        $this->assertNull(configuration::media_for_cmid(116, '116|valid-id|0'));
    }

    public function test_cmid_allowlist_requires_exact_token(): void {
        $this->assertTrue(configuration::cmid_allowed(116, '77, 116'));
        $this->assertFalse(configuration::cmid_allowed(16, '77, 116'));
    }
}
