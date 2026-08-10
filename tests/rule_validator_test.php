<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Tests.
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules;

use local_themerules\local\validation\rule_validator;

/**
 * Unit tests for rule_validator.
 */
#[\PHPUnit\Framework\Attributes\CoversClass(rule_validator::class)]
final class rule_validator_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    private function valid_data(array $overrides = []): array {
        return array_merge([
            'name' => 'My rule',
            'expressionjson' => json_encode(['type' => 'condition', 'condition' => 'user',
                'operator' => 'is', 'value' => 5]),
            'theme' => 'boost',
        ], $overrides);
    }

    private function create_logo(): int {
        global $DB;

        $now = time();
        return (int) $DB->insert_record('local_themerules_logo', (object) [
            'name' => 'Test logo',
            'filename' => 'test.png',
            'timecreated' => $now,
            'timemodified' => $now,
            'usermodified' => 2,
        ]);
    }

    public function test_valid_data_has_no_errors(): void {
        $this->assertSame([], rule_validator::validate($this->valid_data()));
    }

    public function test_missing_name_is_rejected(): void {
        $errors = rule_validator::validate($this->valid_data(['name' => '  ']));

        $this->assertArrayHasKey('name', $errors);
    }

    public function test_invalid_expression_json_is_rejected(): void {
        $errors = rule_validator::validate($this->valid_data(['expressionjson' => '{bad']));

        $this->assertArrayHasKey('expressionjson', $errors);
    }

    public function test_missing_theme_is_rejected(): void {
        $errors = rule_validator::validate($this->valid_data(['theme' => '']));

        $this->assertArrayHasKey('theme', $errors);
    }

    public function test_uninstalled_theme_is_rejected(): void {
        $errors = rule_validator::validate($this->valid_data(['theme' => 'does_not_exist']));

        $this->assertArrayHasKey('theme', $errors);
    }

    public function test_timeend_before_timestart_is_rejected(): void {
        $errors = rule_validator::validate($this->valid_data([
            'timestart' => 2000,
            'timeend' => 1000,
        ]));

        $this->assertArrayHasKey('timeend', $errors);
    }

    public function test_build_and_extract_action_json_round_trip(): void {
        $json = rule_validator::build_action_json('boost');

        $this->assertSame('boost', rule_validator::extract_theme($json));
    }

    public function test_logo_only_rule_is_not_rejected_for_missing_theme(): void {
        $logoid = $this->create_logo();

        $errors = rule_validator::validate($this->valid_data(['theme' => '', 'logoid' => $logoid]));

        $this->assertSame([], $errors);
    }

    public function test_neither_theme_nor_logo_is_rejected(): void {
        $errors = rule_validator::validate($this->valid_data(['theme' => '']));

        $this->assertArrayHasKey('theme', $errors);
    }

    public function test_nonexistent_logo_is_rejected(): void {
        $errors = rule_validator::validate($this->valid_data(['theme' => '', 'logoid' => 999999]));

        $this->assertArrayHasKey('logoid', $errors);
    }

    public function test_build_and_extract_action_json_round_trip_with_logo(): void {
        $logoid = $this->create_logo();

        $json = rule_validator::build_action_json('boost', $logoid);

        $this->assertSame('boost', rule_validator::extract_theme($json));
        $this->assertSame($logoid, rule_validator::extract_logoid($json));
    }

    public function test_build_action_json_logo_only(): void {
        $logoid = $this->create_logo();

        $json = rule_validator::build_action_json('', $logoid);

        $this->assertSame('', rule_validator::extract_theme($json));
        $this->assertSame($logoid, rule_validator::extract_logoid($json));
    }

    public function test_extract_theme_and_logoid_from_legacy_single_object_format(): void {
        $legacy = json_encode(['type' => 'theme', 'theme' => 'boost']);

        $this->assertSame('boost', rule_validator::extract_theme($legacy));
        $this->assertNull(rule_validator::extract_logoid($legacy));
    }
}
