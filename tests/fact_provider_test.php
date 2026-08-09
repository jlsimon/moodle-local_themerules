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

/**
 * Unit tests for fact_provider.
 */
#[\PHPUnit\Framework\Attributes\CoversClass(fact_provider::class)]
final class fact_provider_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
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
}
