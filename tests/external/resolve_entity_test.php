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

use local_themerules\external\resolve_entity;

/**
 * End-to-end tests for the resolve_entity AJAX external function.
 */
#[\PHPUnit\Framework\Attributes\CoversClass(resolve_entity::class)]
final class resolve_entity_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_rejects_user_without_manage_capability(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->expectException(\moodle_exception::class);
        resolve_entity::execute('user', $user->id);
    }

    public function test_resolves_a_real_course(): void {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course(['fullname' => 'Resolvable Via Webservice']);

        $result = resolve_entity::execute('course', (int) $course->id);

        $this->assertTrue($result['found']);
        $this->assertStringContainsString('Resolvable Via Webservice', $result['label']);
    }

    public function test_not_found_is_not_an_error(): void {
        $this->setAdminUser();

        $result = resolve_entity::execute('user', 999999);

        $this->assertFalse($result['found']);
        $this->assertSame(999999, $result['value']);
    }

    public function test_resolves_coursegroup_owning_course(): void {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course(['fullname' => 'Group Owner']);
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);

        $result = resolve_entity::execute('coursegroup', (int) $group->id);

        $this->assertTrue($result['found']);
        $this->assertSame((int) $course->id, $result['courseid']);
        $this->assertStringContainsString('Group Owner', $result['coursename']);
    }
}
