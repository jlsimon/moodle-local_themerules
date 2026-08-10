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

use local_themerules\local\condition\device_condition;
use local_themerules\local\engine\evaluation_context;

#[\PHPUnit\Framework\Attributes\CoversClass(device_condition::class)]
/**
 * Unit tests for device_condition.
 *
 * @covers \local_themerules\local\condition\device_condition
 */
final class device_condition_test extends \advanced_testcase {
    public function test_is_matches(): void {
        $condition = new device_condition();
        $context = new evaluation_context(1, null, null, [], [], 'mobile');

        $this->assertTrue($condition->evaluate(['operator' => 'is', 'value' => 'mobile'], $context));
    }

    public function test_is_does_not_match(): void {
        $condition = new device_condition();
        $context = new evaluation_context(1, null, null, [], [], 'mobile');

        $this->assertFalse($condition->evaluate(['operator' => 'is', 'value' => 'tablet'], $context));
    }

    public function test_is_not_matches_when_different(): void {
        $condition = new device_condition();
        $context = new evaluation_context(1, null, null, [], [], 'mobile');

        $this->assertTrue($condition->evaluate(['operator' => 'is_not', 'value' => 'tablet'], $context));
    }

    public function test_is_not_fails_when_same(): void {
        $condition = new device_condition();
        $context = new evaluation_context(1, null, null, [], [], 'mobile');

        $this->assertFalse($condition->evaluate(['operator' => 'is_not', 'value' => 'mobile'], $context));
    }

    public function test_default_context_devicetype_is_default(): void {
        $condition = new device_condition();
        $context = new evaluation_context(1);

        $this->assertTrue($condition->evaluate(['operator' => 'is', 'value' => 'default'], $context));
    }

    public function test_validate_rejects_unknown_operator(): void {
        $this->expectException(\coding_exception::class);
        (new device_condition())->validate(['operator' => 'contains', 'value' => 'mobile']);
    }

    public function test_validate_rejects_unknown_value(): void {
        $this->expectException(\coding_exception::class);
        (new device_condition())->validate(['operator' => 'is', 'value' => 'smartwatch']);
    }
}
