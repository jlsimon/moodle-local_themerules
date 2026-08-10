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

use local_themerules\local\entity\entity_search;

/**
 * Unit tests for entity_search.
 */
#[\PHPUnit\Framework\Attributes\CoversClass(entity_search::class)]
final class entity_search_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_search_users_matches_firstname(): void {
        $user = $this->getDataGenerator()->create_user(['firstname' => 'Zaragoza', 'lastname' => 'Testuser']);

        $results = entity_search::search('user', 'zarago');

        $this->assertCount(1, $results);
        $this->assertSame((int) $user->id, $results[0]['value']);
        $this->assertStringContainsString('Zaragoza', $results[0]['label']);
    }

    public function test_search_users_matches_email(): void {
        $user = $this->getDataGenerator()->create_user(['email' => 'findme@example.com']);

        $results = entity_search::search('user', 'findme@');

        $this->assertSame((int) $user->id, $results[0]['value']);
    }

    public function test_search_users_excludes_deleted(): void {
        $user = $this->getDataGenerator()->create_user(['firstname' => 'Deleteduser']);
        delete_user($user);

        $results = entity_search::search('user', 'Deleteduser');

        $this->assertSame([], $results);
    }

    public function test_search_users_empty_query_returns_a_page(): void {
        $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->create_user();

        $results = entity_search::search('user', '');

        // At least the two just created (real Moodle sites also have an admin user by default
        // in the test fixture) - not asserting an exact count, just that browsing works.
        $this->assertGreaterThanOrEqual(2, count($results));
    }

    public function test_search_courses_matches_fullname(): void {
        $course = $this->getDataGenerator()->create_course(['fullname' => 'Cybersecurity Basics']);

        $results = entity_search::search('course', 'cybersecurity');

        $this->assertSame((int) $course->id, $results[0]['value']);
    }

    public function test_search_courses_matches_shortname(): void {
        $course = $this->getDataGenerator()->create_course(['shortname' => 'CYBERSEC101']);

        $results = entity_search::search('course', 'CYBERSEC101');

        $this->assertSame((int) $course->id, $results[0]['value']);
    }

    public function test_search_courses_excludes_site_course(): void {
        $results = entity_search::search('course', '');

        foreach ($results as $result) {
            $this->assertNotSame(SITEID, $result['value']);
        }
    }

    public function test_search_coursegroup_requires_a_courseid(): void {
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_group(['courseid' => $course->id, 'name' => 'Alpha Team']);

        $this->assertSame([], entity_search::search('coursegroup', 'Alpha'));
    }

    public function test_search_coursegroup_matches_name_within_course(): void {
        $course = $this->getDataGenerator()->create_course();
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id, 'name' => 'Alpha Team']);

        $results = entity_search::search('coursegroup', 'Alpha', (int) $course->id);

        $this->assertSame((int) $group->id, $results[0]['value']);
    }

    public function test_search_coursegroup_does_not_leak_across_courses(): void {
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_group(['courseid' => $course2->id, 'name' => 'Beta Team']);

        $results = entity_search::search('coursegroup', 'Beta', (int) $course1->id);

        $this->assertSame([], $results);
    }

    public function test_search_rejects_unknown_entitytype(): void {
        $this->expectException(\coding_exception::class);
        entity_search::search('cohort', '');
    }

    public function test_resolve_user_found(): void {
        $user = $this->getDataGenerator()->create_user(['firstname' => 'Findable']);

        $resolved = entity_search::resolve('user', (int) $user->id);

        $this->assertSame((int) $user->id, $resolved['value']);
        $this->assertStringContainsString('Findable', $resolved['label']);
    }

    public function test_resolve_user_not_found(): void {
        $this->assertNull(entity_search::resolve('user', 999999));
    }

    public function test_resolve_course_found(): void {
        $course = $this->getDataGenerator()->create_course(['fullname' => 'Resolvable Course']);

        $resolved = entity_search::resolve('course', (int) $course->id);

        $this->assertStringContainsString('Resolvable Course', $resolved['label']);
    }

    public function test_resolve_coursegroup_includes_owning_course(): void {
        $course = $this->getDataGenerator()->create_course(['fullname' => 'Owning Course']);
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id, 'name' => 'Resolvable Group']);

        $resolved = entity_search::resolve('coursegroup', (int) $group->id);

        $this->assertStringContainsString('Resolvable Group', $resolved['label']);
        $this->assertSame((int) $course->id, $resolved['courseid']);
        $this->assertStringContainsString('Owning Course', $resolved['coursename']);
    }

    public function test_resolve_coursegroup_not_found(): void {
        $this->assertNull(entity_search::resolve('coursegroup', 999999));
    }

    public function test_resolve_rejects_unknown_entitytype(): void {
        $this->expectException(\coding_exception::class);
        entity_search::resolve('cohort', 1);
    }
}
