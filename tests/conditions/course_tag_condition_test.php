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

use local_themerules\local\condition\course_tag_condition;
use local_themerules\local\engine\evaluation_context;

/**
 * Unit tests for course_tag_condition.
 */
#[\PHPUnit\Framework\Attributes\CoversClass(course_tag_condition::class)]
final class course_tag_condition_test extends \advanced_testcase {
    public function test_has_matches(): void {
        $condition = new course_tag_condition();
        $context = new evaluation_context(1, 55, null, [], [], 'default', ['exam-mode']);

        $this->assertTrue($condition->evaluate(['operator' => 'has', 'value' => 'exam-mode'], $context));
    }

    public function test_has_matches_regardless_of_case_and_whitespace(): void {
        $condition = new course_tag_condition();
        $context = new evaluation_context(1, 55, null, [], [], 'default', ['exam-mode']);

        $this->assertTrue($condition->evaluate(['operator' => 'has', 'value' => '  Exam-Mode  '], $context));
    }

    public function test_has_does_not_match_absent_tag(): void {
        $condition = new course_tag_condition();
        $context = new evaluation_context(1, 55, null, [], [], 'default', ['exam-mode']);

        $this->assertFalse($condition->evaluate(['operator' => 'has', 'value' => 'archived'], $context));
    }

    public function test_not_has_matches_when_absent(): void {
        $condition = new course_tag_condition();
        $context = new evaluation_context(1, 55, null, [], [], 'default', ['exam-mode']);

        $this->assertTrue($condition->evaluate(['operator' => 'not_has', 'value' => 'archived'], $context));
    }

    public function test_not_has_fails_when_present(): void {
        $condition = new course_tag_condition();
        $context = new evaluation_context(1, 55, null, [], [], 'default', ['exam-mode']);

        $this->assertFalse($condition->evaluate(['operator' => 'not_has', 'value' => 'exam-mode'], $context));
    }

    public function test_evaluate_false_when_no_course_known(): void {
        $condition = new course_tag_condition();
        $context = new evaluation_context(1); // No course (Tier A context).

        $this->assertFalse($condition->evaluate(['operator' => 'has', 'value' => 'exam-mode'], $context));
    }

    public function test_validate_rejects_unknown_operator(): void {
        $this->expectException(\coding_exception::class);
        (new course_tag_condition())->validate(['operator' => 'contains', 'value' => 'exam-mode']);
    }

    public function test_validate_rejects_empty_value(): void {
        $this->expectException(\coding_exception::class);
        (new course_tag_condition())->validate(['operator' => 'has', 'value' => '   ']);
    }
}
