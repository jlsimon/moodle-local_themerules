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
    private function valid_data(array $overrides = []): array {
        return array_merge([
            'name' => 'My rule',
            'priority' => 10,
            'expressionjson' => json_encode(['type' => 'condition', 'condition' => 'user',
                'operator' => 'is', 'value' => 5]),
            'theme' => 'boost',
        ], $overrides);
    }

    public function test_valid_data_has_no_errors(): void {
        $this->assertSame([], rule_validator::validate($this->valid_data()));
    }

    public function test_missing_name_is_rejected(): void {
        $errors = rule_validator::validate($this->valid_data(['name' => '  ']));

        $this->assertArrayHasKey('name', $errors);
    }

    public function test_non_numeric_priority_is_rejected(): void {
        $errors = rule_validator::validate($this->valid_data(['priority' => 'high']));

        $this->assertArrayHasKey('priority', $errors);
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
}
