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

use local_themerules\local\action\logo_action;
use local_themerules\local\engine\evaluation_context;

#[\PHPUnit\Framework\Attributes\CoversClass(logo_action::class)]
/**
 * Unit tests for logo_action.
 *
 * @covers \local_themerules\local\action\logo_action
 */
final class logo_action_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Inserts a minimal logo_repository row directly, for tests that only need a real logoid.
     *
     * @return int
     */
    private function create_logo(): int {
        global $DB;

        $now = time();
        return (int) $DB->insert_record('local_themerules_logo', (object) [
            'name' => 'Test logo',
            'filename' => 'test.png',
            'timecreated' => $now,
            'timemodified' => $now,
            'usermodified' => 2,
        ]);
    }

    public function test_apply_returns_logoid_as_string(): void {
        $logoid = $this->create_logo();

        $result = (new logo_action())->apply(['logoid' => $logoid], new evaluation_context(1));

        $this->assertSame((string) $logoid, $result);
    }

    public function test_apply_declines_when_logo_does_not_exist(): void {
        $result = (new logo_action())->apply(['logoid' => 999999], new evaluation_context(1));

        $this->assertNull($result);
        $this->assertDebuggingCalled();
    }

    public function test_validate_rejects_missing_logoid(): void {
        $this->expectException(\coding_exception::class);
        (new logo_action())->validate([]);
    }

    public function test_validate_rejects_non_numeric_logoid(): void {
        $this->expectException(\coding_exception::class);
        (new logo_action())->validate(['logoid' => 'not-a-number']);
    }

    public function test_validate_rejects_nonexistent_logo(): void {
        $this->expectException(\coding_exception::class);
        (new logo_action())->validate(['logoid' => 999999]);
    }

    public function test_validate_accepts_existing_logo(): void {
        $logoid = $this->create_logo();

        // Should not throw.
        (new logo_action())->validate(['logoid' => $logoid]);
        $this->addToAssertionCount(1);
    }
}
