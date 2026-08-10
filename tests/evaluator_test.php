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

use local_themerules\local\engine\evaluation_context;
use local_themerules\local\engine\evaluator;

#[\PHPUnit\Framework\Attributes\CoversClass(evaluator::class)]
/**
 * Unit tests for evaluator.
 *
 * @covers \local_themerules\local\engine\evaluator
 */
final class evaluator_test extends \advanced_testcase {
    /**
     * A "user is $userid" condition node.
     *
     * @param int $userid
     * @return array
     */
    private function user_node(int $userid): array {
        return ['type' => 'condition', 'condition' => 'user', 'operator' => 'is', 'value' => $userid];
    }

    /**
     * A node that throws if the evaluator ever tries to evaluate it, to prove short-circuiting.
     */
    private function poison_node(): array {
        return ['type' => 'condition', 'condition' => 'does_not_exist', 'operator' => 'is', 'value' => 1];
    }

    public function test_and_true_when_all_children_match(): void {
        $node = ['type' => 'group', 'operator' => 'and', 'children' => [
            $this->user_node(5), $this->user_node(5),
        ]];

        $this->assertTrue((new evaluator())->evaluate($node, new evaluation_context(5)));
    }

    public function test_and_stops_at_first_false(): void {
        $node = ['type' => 'group', 'operator' => 'and', 'children' => [
            $this->user_node(999), $this->poison_node(),
        ]];

        $this->assertFalse((new evaluator())->evaluate($node, new evaluation_context(5)));
    }

    public function test_or_stops_at_first_true(): void {
        $node = ['type' => 'group', 'operator' => 'or', 'children' => [
            $this->user_node(5), $this->poison_node(),
        ]];

        $this->assertTrue((new evaluator())->evaluate($node, new evaluation_context(5)));
    }

    public function test_or_false_when_no_child_matches(): void {
        $node = ['type' => 'group', 'operator' => 'or', 'children' => [
            $this->user_node(1), $this->user_node(2),
        ]];

        $this->assertFalse((new evaluator())->evaluate($node, new evaluation_context(5)));
    }

    /**
     * SPECIFICATIONS.md section 46, Test C:
     * category = 12 AND (cohort = 7 OR cohort = 8); here re-expressed with the user
     * condition since course/category/cohort conditions do not exist until Phase 2.
     */
    public function test_nested_and_or_group(): void {
        $node = ['type' => 'group', 'operator' => 'and', 'children' => [
            $this->user_node(5),
            ['type' => 'group', 'operator' => 'or', 'children' => [
                $this->user_node(7),
                $this->user_node(5),
            ]],
        ]];

        $this->assertTrue((new evaluator())->evaluate($node, new evaluation_context(5)));
    }
}
