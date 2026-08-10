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
use local_themerules\local\engine\expression_parser;

#[\PHPUnit\Framework\Attributes\CoversClass(evaluator::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(expression_parser::class)]
/**
 * Phase 3 (SPECIFICATIONS.md section 63): nested AND/OR groups must evaluate
 * correctly for every truth-table combination, and expression_parser's safety
 * limits (section 17: max nesting depth 10, max nodes 100) must hold exactly
 * at the boundary.
 *
 * @covers \local_themerules\local\engine\evaluator
 * @covers \local_themerules\local\engine\expression_parser
 */
final class nested_logic_test extends \advanced_testcase {
    /**
     * SPECIFICATIONS.md section 63/72: category = FUNDAE AND (cohort A OR cohort B).
     */
    private function canonical_expression(): array {
        return ['type' => 'group', 'operator' => 'and', 'children' => [
            ['type' => 'condition', 'condition' => 'coursecategory', 'operator' => 'in_category', 'value' => 12],
            ['type' => 'group', 'operator' => 'or', 'children' => [
                ['type' => 'condition', 'condition' => 'cohort', 'operator' => 'member', 'value' => 7],
                ['type' => 'condition', 'condition' => 'cohort', 'operator' => 'member', 'value' => 8],
            ]],
        ]];
    }

    /**
     * An evaluation_context matching (or not) the canonical_expression()'s three atoms.
     *
     * @param bool $incategory
     * @param bool $incohorta
     * @param bool $incohortb
     * @return evaluation_context
     */
    private function context_for(bool $incategory, bool $incohorta, bool $incohortb): evaluation_context {
        $cohortids = [];
        if ($incohorta) {
            $cohortids[] = 7;
        }
        if ($incohortb) {
            $cohortids[] = 8;
        }

        return new evaluation_context(1, 100, $incategory ? 12 : 13, [1, $incategory ? 12 : 13], $cohortids);
    }

    /**
     * Every combination of the three atoms must match classical boolean logic:
     * a AND (b OR c).
     */
    public function test_truth_table_for_and_or_combination(): void {
        $node = $this->canonical_expression();
        $evaluator = new evaluator();

        foreach ([true, false] as $a) {
            foreach ([true, false] as $b) {
                foreach ([true, false] as $c) {
                    $expected = $a && ($b || $c);
                    $context = $this->context_for($a, $b, $c);

                    $this->assertSame(
                        $expected,
                        $evaluator->evaluate($node, $context),
                        sprintf(
                            'a=%s b=%s c=%s expected %s',
                            var_export($a, true),
                            var_export($b, true),
                            var_export($c, true),
                            var_export($expected, true)
                        )
                    );
                }
            }
        }
    }

    /**
     * Builds a chain of $levels nested "and" groups around a single leaf condition.
     *
     * @param int $levels
     * @return array
     */
    private function nested_group(int $levels): array {
        $node = ['type' => 'condition', 'condition' => 'user', 'operator' => 'is', 'value' => 5];
        for ($i = 0; $i < $levels; $i++) {
            $node = ['type' => 'group', 'operator' => 'and', 'children' => [$node]];
        }
        return $node;
    }

    public function test_deeply_nested_mixed_and_or_evaluates_correctly(): void {
        // Expression tree: outer AND of an OR group and a nested AND-of-OR group.
        $node = ['type' => 'group', 'operator' => 'and', 'children' => [
            ['type' => 'group', 'operator' => 'or', 'children' => [
                ['type' => 'condition', 'condition' => 'user', 'operator' => 'is', 'value' => 5],
                ['type' => 'condition', 'condition' => 'user', 'operator' => 'is', 'value' => 999],
            ]],
            ['type' => 'group', 'operator' => 'and', 'children' => [
                ['type' => 'condition', 'condition' => 'user', 'operator' => 'is', 'value' => 5],
                ['type' => 'group', 'operator' => 'or', 'children' => [
                    ['type' => 'condition', 'condition' => 'user', 'operator' => 'is', 'value' => 5],
                    ['type' => 'condition', 'condition' => 'user', 'operator' => 'is', 'value' => 999],
                ]],
            ]],
        ]];

        $this->assertTrue((new evaluator())->evaluate($node, new evaluation_context(5)));
        $this->assertFalse((new evaluator())->evaluate($node, new evaluation_context(999)));
    }

    /**
     * SPECIFICATIONS.md section 17: nesting depth of exactly 10 must still parse.
     */
    public function test_max_nesting_depth_boundary_is_accepted(): void {
        $json = json_encode($this->nested_group(9)); // Root group + 9 wraps = depth 10.

        $parsed = (new expression_parser())->parse($json);

        $this->assertSame('group', $parsed['type']);
    }

    /**
     * SPECIFICATIONS.md section 17: nesting depth of 11 must be rejected.
     */
    public function test_nesting_depth_over_the_limit_is_rejected(): void {
        $json = json_encode($this->nested_group(10)); // Depth 11.

        $this->expectException(\coding_exception::class);
        (new expression_parser())->parse($json);
    }

    /**
     * A single "or" group with $childrencount leaf conditions.
     *
     * @param int $childrencount
     * @return array
     */
    private function flat_or_group(int $childrencount): array {
        $children = [];
        for ($i = 0; $i < $childrencount; $i++) {
            $children[] = ['type' => 'condition', 'condition' => 'user', 'operator' => 'is', 'value' => $i];
        }
        return ['type' => 'group', 'operator' => 'or', 'children' => $children];
    }

    /**
     * SPECIFICATIONS.md section 17: exactly 100 nodes (1 group + 99 children) must still parse.
     */
    public function test_max_node_count_boundary_is_accepted(): void {
        $json = json_encode($this->flat_or_group(99));

        $parsed = (new expression_parser())->parse($json);

        $this->assertCount(99, $parsed['children']);
    }

    /**
     * SPECIFICATIONS.md section 17: 101 nodes must be rejected.
     */
    public function test_node_count_over_the_limit_is_rejected(): void {
        $json = json_encode($this->flat_or_group(100));

        $this->expectException(\coding_exception::class);
        (new expression_parser())->parse($json);
    }
}
