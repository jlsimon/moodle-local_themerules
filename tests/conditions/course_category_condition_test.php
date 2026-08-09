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

use local_themerules\local\condition\course_category_condition;
use local_themerules\local\engine\evaluation_context;

/**
 * Unit tests for course_category_condition.
 */
#[\PHPUnit\Framework\Attributes\CoversClass(course_category_condition::class)]
final class course_category_condition_test extends \advanced_testcase {
    public function test_exact_match(): void {
        $condition = new course_category_condition();
        $context = new evaluation_context(1, 100, 12, [1, 5, 12]);

        $this->assertTrue($condition->evaluate(['operator' => 'in_category', 'value' => 12], $context));
    }

    public function test_exact_match_fails_for_ancestor(): void {
        $condition = new course_category_condition();
        $context = new evaluation_context(1, 100, 12, [1, 5, 12]);

        $this->assertFalse($condition->evaluate(['operator' => 'in_category', 'value' => 5], $context));
    }

    /**
     * SPECIFICATIONS.md section 12: category = FUNDAE, scope = include descendants,
     * must match FUNDAE > IT > Cybersecurity.
     */
    public function test_includes_descendants_when_flagged(): void {
        $condition = new course_category_condition();
        $context = new evaluation_context(1, 100, 30, [1, 5, 30]); // 5 = FUNDAE, 30 = Cybersecurity.

        $this->assertTrue($condition->evaluate(
            ['operator' => 'in_category', 'value' => 5, 'includechildren' => true],
            $context
        ));
    }

    public function test_does_not_include_descendants_by_default(): void {
        $condition = new course_category_condition();
        $context = new evaluation_context(1, 100, 30, [1, 5, 30]);

        $this->assertFalse($condition->evaluate(['operator' => 'in_category', 'value' => 5], $context));
    }

    public function test_false_when_no_category_known(): void {
        $condition = new course_category_condition();
        $context = new evaluation_context(1); // No course (Tier A context).

        $this->assertFalse($condition->evaluate(['operator' => 'in_category', 'value' => 5], $context));
    }

    public function test_validate_rejects_non_bool_includechildren(): void {
        $this->expectException(\coding_exception::class);
        (new course_category_condition())->validate([
            'operator' => 'in_category', 'value' => 5, 'includechildren' => 'yes',
        ]);
    }
}
