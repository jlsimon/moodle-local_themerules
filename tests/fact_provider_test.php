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

use local_themerules\local\engine\fact_provider;

#[\PHPUnit\Framework\Attributes\CoversClass(fact_provider::class)]
/**
 * Unit tests for fact_provider.
 *
 * @covers \local_themerules\local\engine\fact_provider
 */
final class fact_provider_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    protected function tearDown(): void {
        // Undo any forced user agent (test_..._detects_real_device_type() below), so it never
        // leaks into a later test.
        \core_useragent::instance(true);
        parent::tearDown();
    }

    public function test_create_for_current_user_has_no_course_facts(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $context = fact_provider::create_for_current_user();

        $this->assertSame((int) $user->id, $context->get_userid());
        $this->assertNull($context->get_courseid());
        $this->assertNull($context->get_coursecategoryid());
        $this->assertSame([], $context->get_coursecategorypath());
    }

    public function test_create_for_current_user_resolves_cohorts(): void {
        $user = $this->getDataGenerator()->create_user();
        $cohort = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort->id, $user->id);
        $this->setUser($user);

        $context = fact_provider::create_for_current_user();

        $this->assertSame([(int) $cohort->id], $context->get_cohortids());
    }

    /**
     * SPECIFICATIONS.md section 12: FUNDAE > IT > Cybersecurity.
     */
    public function test_create_for_course_resolves_category_ancestry(): void {
        $fundae = $this->getDataGenerator()->create_category(['name' => 'FUNDAE']);
        $it = $this->getDataGenerator()->create_category(['name' => 'IT', 'parent' => $fundae->id]);
        $cybersecurity = $this->getDataGenerator()->create_category(['name' => 'Cybersecurity', 'parent' => $it->id]);
        $course = $this->getDataGenerator()->create_course(['category' => $cybersecurity->id]);
        $user = $this->getDataGenerator()->create_user();

        $context = fact_provider::create_for_course((int) $user->id, $course);

        $this->assertSame((int) $course->id, $context->get_courseid());
        $this->assertSame((int) $cybersecurity->id, $context->get_coursecategoryid());
        $this->assertSame(
            [(int) $fundae->id, (int) $it->id, (int) $cybersecurity->id],
            $context->get_coursecategorypath()
        );
    }

    public function test_create_for_course_also_resolves_cohorts(): void {
        $category = $this->getDataGenerator()->create_category();
        $course = $this->getDataGenerator()->create_course(['category' => $category->id]);
        $user = $this->getDataGenerator()->create_user();
        $cohort = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort->id, $user->id);

        $context = fact_provider::create_for_course((int) $user->id, $course);

        $this->assertSame([(int) $cohort->id], $context->get_cohortids());
    }

    public function test_create_for_current_user_detects_real_device_type(): void {
        \core_useragent::instance(
            true,
            'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15 ' .
            '(KHTML, like Gecko) Version/15.0 Mobile/15E148 Safari/604.1'
        );

        $context = fact_provider::create_for_current_user();

        $this->assertSame('mobile', $context->get_devicetype());
    }

    /**
     * Simulator support (SPECIFICATIONS.md section 31): an explicit override must win over the
     * real device making the request, since the whole point is letting an admin test "what if
     * this were a tablet" without needing an actual tablet.
     */
    public function test_create_for_user_and_course_devicetype_override_wins_over_real_device(): void {
        \core_useragent::instance(
            true,
            'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15 ' .
            '(KHTML, like Gecko) Version/15.0 Mobile/15E148 Safari/604.1'
        );

        $context = fact_provider::create_for_user_and_course(1, null, 'tablet');

        $this->assertSame('tablet', $context->get_devicetype());
    }

    public function test_create_for_user_and_course_defaults_to_real_device_when_no_override(): void {
        \core_useragent::instance(true, 'some legacy-looking string');

        $context = fact_provider::create_for_user_and_course(1, null);

        $this->assertSame(\core_useragent::get_user_device_type(), $context->get_devicetype());
    }

    public function test_create_for_course_resolves_tags(): void {
        $course = $this->getDataGenerator()->create_course(['tags' => 'Exam Mode, Archived']);
        $user = $this->getDataGenerator()->create_user();

        $context = fact_provider::create_for_course((int) $user->id, $course);

        $this->assertSame(['exam mode', 'archived'], $context->get_coursetags());
    }

    public function test_create_for_current_user_has_no_tag_facts(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $context = fact_provider::create_for_current_user();

        $this->assertSame([], $context->get_coursetags());
    }

    public function test_create_for_course_resolves_group_membership(): void {
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        // Core's groups_add_member() silently no-ops for a user not enrolled in the course.
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group->id, 'userid' => $user->id]);

        $context = fact_provider::create_for_course((int) $user->id, $course);

        $this->assertSame([(int) $group->id], $context->get_coursegroupids());
    }

    public function test_create_for_course_group_membership_is_scoped_to_the_course(): void {
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course2->id);
        $group = $this->getDataGenerator()->create_group(['courseid' => $course2->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group->id, 'userid' => $user->id]);

        $context = fact_provider::create_for_course((int) $user->id, $course1);

        $this->assertSame([], $context->get_coursegroupids());
    }

    public function test_create_for_current_user_has_no_group_facts(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $context = fact_provider::create_for_current_user();

        $this->assertSame([], $context->get_coursegroupids());
    }
}
