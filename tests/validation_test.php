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

use local_themerules\local\engine\expression_parser;

#[\PHPUnit\Framework\Attributes\CoversClass(expression_parser::class)]
/**
 * Unit tests for expression_parser.
 *
 * @covers \local_themerules\local\engine\expression_parser
 */
final class validation_test extends \advanced_testcase {
    public function test_parses_valid_expression(): void {
        $json = json_encode(['type' => 'condition', 'condition' => 'user', 'operator' => 'is', 'value' => 5]);

        $parsed = (new expression_parser())->parse($json);

        $this->assertSame('user', $parsed['condition']);
    }

    public function test_rejects_invalid_json(): void {
        $this->expectException(\coding_exception::class);
        (new expression_parser())->parse('{not valid json');
    }

    public function test_rejects_unknown_condition(): void {
        $json = json_encode(['type' => 'condition', 'condition' => 'does_not_exist', 'operator' => 'is', 'value' => 5]);

        $this->expectException(\coding_exception::class);
        (new expression_parser())->parse($json);
    }

    public function test_rejects_unknown_node_type(): void {
        $json = json_encode(['type' => 'bogus']);

        $this->expectException(\coding_exception::class);
        (new expression_parser())->parse($json);
    }

    public function test_rejects_group_with_no_children(): void {
        $json = json_encode(['type' => 'group', 'operator' => 'and', 'children' => []]);

        $this->expectException(\coding_exception::class);
        (new expression_parser())->parse($json);
    }

    public function test_rejects_group_with_unknown_operator(): void {
        $json = json_encode(['type' => 'group', 'operator' => 'xor', 'children' => [
            ['type' => 'condition', 'condition' => 'user', 'operator' => 'is', 'value' => 5],
        ]]);

        $this->expectException(\coding_exception::class);
        (new expression_parser())->parse($json);
    }

    public function test_rejects_condition_missing_value(): void {
        $json = json_encode(['type' => 'condition', 'condition' => 'user', 'operator' => 'is']);

        $this->expectException(\coding_exception::class);
        (new expression_parser())->parse($json);
    }
}
