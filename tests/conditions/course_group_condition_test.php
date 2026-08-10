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

use local_themerules\local\condition\course_group_condition;
use local_themerules\local\engine\evaluation_context;

#[\PHPUnit\Framework\Attributes\CoversClass(course_group_condition::class)]
/**
 * Unit tests for course_group_condition.
 *
 * @covers \local_themerules\local\condition\course_group_condition
 */
final class course_group_condition_test extends \advanced_testcase {
    /**
     * Builds a context with a real course id (5) and the given group memberships, matching the
     * constructor's positional argument order (evaluation_context.php).
     *
     * @param int[] $groupids
     * @return evaluation_context
     */
    private function context_with_groups(array $groupids): evaluation_context {
        return new evaluation_context(1, 5, null, [], [], 'default', [], $groupids);
    }

    public function test_member_matches(): void {
        $condition = new course_group_condition();
        $context = $this->context_with_groups([3, 4]);

        $this->assertTrue($condition->evaluate(['operator' => 'member', 'value' => 3], $context));
    }

    public function test_member_does_not_match(): void {
        $condition = new course_group_condition();
        $context = $this->context_with_groups([3, 4]);

        $this->assertFalse($condition->evaluate(['operator' => 'member', 'value' => 9], $context));
    }

    public function test_not_member_matches_when_absent(): void {
        $condition = new course_group_condition();
        $context = $this->context_with_groups([3, 4]);

        $this->assertTrue($condition->evaluate(['operator' => 'not_member', 'value' => 9], $context));
    }

    public function test_not_member_fails_when_present(): void {
        $condition = new course_group_condition();
        $context = $this->context_with_groups([3, 4]);

        $this->assertFalse($condition->evaluate(['operator' => 'not_member', 'value' => 3], $context));
    }

    /**
     * Group id 0 means "any group in the course" (mirrors Moodle's own availability_group).
     */
    public function test_zero_matches_any_group_when_member_of_one(): void {
        $condition = new course_group_condition();
        $context = $this->context_with_groups([3]);

        $this->assertTrue($condition->evaluate(['operator' => 'member', 'value' => 0], $context));
    }

    public function test_zero_does_not_match_when_member_of_none(): void {
        $condition = new course_group_condition();
        $context = $this->context_with_groups([]);

        $this->assertFalse($condition->evaluate(['operator' => 'member', 'value' => 0], $context));
    }

    public function test_not_member_zero_matches_when_in_no_group(): void {
        $condition = new course_group_condition();
        $context = $this->context_with_groups([]);

        $this->assertTrue($condition->evaluate(['operator' => 'not_member', 'value' => 0], $context));
    }

    /**
     * DECISIONS.md "Phase 2": course-scoped conditions never match outside a real course context.
     */
    public function test_never_matches_without_a_course(): void {
        $condition = new course_group_condition();
        $context = new evaluation_context(1, null, null, [], [], 'default', [], [3]);

        $this->assertFalse($condition->evaluate(['operator' => 'member', 'value' => 3], $context));
    }

    public function test_validate_rejects_unknown_operator(): void {
        $this->expectException(\coding_exception::class);
        (new course_group_condition())->validate(['operator' => 'contains', 'value' => 3]);
    }

    public function test_validate_rejects_negative_value(): void {
        $this->expectException(\coding_exception::class);
        (new course_group_condition())->validate(['operator' => 'member', 'value' => -1]);
    }

    public function test_validate_accepts_zero(): void {
        $this->expectNotToPerformAssertions();
        (new course_group_condition())->validate(['operator' => 'member', 'value' => 0]);
    }
}
