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

use local_themerules\external\search_entities;

#[\PHPUnit\Framework\Attributes\CoversClass(search_entities::class)]
/**
 * End-to-end tests for the search_entities AJAX external function, exercising the same
 * parameter/context/capability validation a real web service call goes through - entity_search's
 * own test suite already covers the underlying query logic in isolation.
 *
 * @covers \local_themerules\external\search_entities
 */
final class search_entities_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_rejects_user_without_manage_capability(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->expectException(\moodle_exception::class);
        search_entities::execute('user', '');
    }

    public function test_rejects_unknown_entitytype(): void {
        $this->setAdminUser();

        $this->expectException(\invalid_parameter_exception::class);
        search_entities::execute('cohort', '');
    }

    public function test_admin_can_search_users(): void {
        $this->setAdminUser();
        $target = $this->getDataGenerator()->create_user(['firstname' => 'Searchable']);

        $results = search_entities::execute('user', 'Searchable');

        $this->assertSame((int) $target->id, $results[0]['value']);
    }

    public function test_admin_can_search_coursegroups_scoped_to_a_course(): void {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id, 'name' => 'Search Group']);

        $results = search_entities::execute('coursegroup', 'Search', (int) $course->id);

        $this->assertSame((int) $group->id, $results[0]['value']);
    }
}
