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

use local_themerules\local\condition\cohort_condition;
use local_themerules\local\engine\evaluation_context;

/**
 * Unit tests for cohort_condition.
 */
#[\PHPUnit\Framework\Attributes\CoversClass(cohort_condition::class)]
final class cohort_condition_test extends \advanced_testcase {
    public function test_member_matches(): void {
        $condition = new cohort_condition();
        $context = new evaluation_context(1, null, null, [], [7, 9]);

        $this->assertTrue($condition->evaluate(['operator' => 'member', 'value' => 7], $context));
    }

    public function test_member_does_not_match(): void {
        $condition = new cohort_condition();
        $context = new evaluation_context(1, null, null, [], [7, 9]);

        $this->assertFalse($condition->evaluate(['operator' => 'member', 'value' => 8], $context));
    }

    public function test_not_member_matches_when_absent(): void {
        $condition = new cohort_condition();
        $context = new evaluation_context(1, null, null, [], [7, 9]);

        $this->assertTrue($condition->evaluate(['operator' => 'not_member', 'value' => 8], $context));
    }

    public function test_not_member_fails_when_present(): void {
        $condition = new cohort_condition();
        $context = new evaluation_context(1, null, null, [], [7, 9]);

        $this->assertFalse($condition->evaluate(['operator' => 'not_member', 'value' => 7], $context));
    }

    public function test_validate_rejects_unknown_operator(): void {
        $this->expectException(\coding_exception::class);
        (new cohort_condition())->validate(['operator' => 'contains', 'value' => 7]);
    }
}
