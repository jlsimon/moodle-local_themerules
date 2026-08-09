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

use local_themerules\local\condition\course_condition;
use local_themerules\local\engine\evaluation_context;

/**
 * Unit tests for course_condition.
 */
#[\PHPUnit\Framework\Attributes\CoversClass(course_condition::class)]
final class course_condition_test extends \advanced_testcase {
    public function test_evaluate_matches_same_course(): void {
        $condition = new course_condition();
        $context = new evaluation_context(1, 55);

        $this->assertTrue($condition->evaluate(['operator' => 'is', 'value' => 55], $context));
    }

    public function test_evaluate_does_not_match_other_course(): void {
        $condition = new course_condition();
        $context = new evaluation_context(1, 55);

        $this->assertFalse($condition->evaluate(['operator' => 'is', 'value' => 999], $context));
    }

    public function test_evaluate_false_when_no_course_known(): void {
        $condition = new course_condition();
        $context = new evaluation_context(1); // No course (Tier A context).

        $this->assertFalse($condition->evaluate(['operator' => 'is', 'value' => 55], $context));
    }

    public function test_validate_rejects_unknown_operator(): void {
        $this->expectException(\coding_exception::class);
        (new course_condition())->validate(['operator' => 'is not', 'value' => 55]);
    }
}
