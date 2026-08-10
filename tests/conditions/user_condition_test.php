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

use local_themerules\local\condition\user_condition;
use local_themerules\local\engine\evaluation_context;

#[\PHPUnit\Framework\Attributes\CoversClass(user_condition::class)]
/**
 * Unit tests for user_condition.
 *
 * @covers \local_themerules\local\condition\user_condition
 */
final class user_condition_test extends \advanced_testcase {
    public function test_evaluate_matches_same_user(): void {
        $condition = new user_condition();
        $context = new evaluation_context(123);

        $this->assertTrue($condition->evaluate(['operator' => 'is', 'value' => 123], $context));
    }

    public function test_evaluate_does_not_match_other_user(): void {
        $condition = new user_condition();
        $context = new evaluation_context(456);

        $this->assertFalse($condition->evaluate(['operator' => 'is', 'value' => 123], $context));
    }

    public function test_validate_rejects_unknown_operator(): void {
        $condition = new user_condition();

        $this->expectException(\coding_exception::class);
        $condition->validate(['operator' => 'is not', 'value' => 123]);
    }

    public function test_validate_rejects_non_numeric_value(): void {
        $condition = new user_condition();

        $this->expectException(\coding_exception::class);
        $condition->validate(['operator' => 'is', 'value' => 'abc']);
    }
}
