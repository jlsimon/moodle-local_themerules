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

use local_themerules\local\diagnostics\simulator;
use local_themerules\local\engine\fact_provider;

#[\PHPUnit\Framework\Attributes\CoversClass(simulator::class)]
/**
 * Unit tests for simulator.
 *
 * @covers \local_themerules\local\diagnostics\simulator
 */
final class simulator_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->setAdminUser();
    }

    /**
     * Inserts a minimal valid local_themerules_rule row directly, for simulator tests.
     *
     * @param array $overrides
     * @return int
     */
    private function create_rule(array $overrides = []): int {
        global $DB, $USER;

        $now = time();
        $record = array_merge([
            'name' => 'Simulated rule',
            'enabled' => 1,
            'expressionjson' => json_encode(['type' => 'condition', 'condition' => 'user',
                'operator' => 'is', 'value' => 123]),
            'actionjson' => json_encode(['type' => 'theme', 'theme' => 'boost']),
            'timestart' => 0,
            'timeend' => 0,
            'sortorder' => 0,
            'timecreated' => $now,
            'timemodified' => $now,
            'usermodified' => $USER->id,
        ], $overrides);

        return (int) $DB->insert_record('local_themerules_rule', (object) $record);
    }

    public function test_matching_rule_is_traced_true_with_selected_theme(): void {
        $this->create_rule();

        $result = simulator::run(fact_provider::create_for_user_and_course(123, null));

        $this->assertSame('boost', $result->selectedtheme);
        $this->assertCount(1, $result->ruletraces);
        $this->assertTrue($result->ruletraces[0]->matched);
        $this->assertSame('boost', $result->ruletraces[0]->theme);
        $this->assertNotEmpty($result->ruletraces[0]->conditionlines);
        $this->assertTrue($result->ruletraces[0]->conditionlines[0]['result']);
    }

    public function test_non_matching_rule_is_traced_false_with_no_theme(): void {
        $this->create_rule();

        $result = simulator::run(fact_provider::create_for_user_and_course(456, null));

        $this->assertNull($result->selectedtheme);
        $this->assertFalse($result->ruletraces[0]->matched);
        $this->assertNull($result->ruletraces[0]->theme);
        $this->assertFalse($result->ruletraces[0]->conditionlines[0]['result']);
    }

    public function test_first_matching_rule_in_list_order_wins_and_stops_selecting(): void {
        $this->create_rule(['name' => 'Later in list', 'sortorder' => 1,
            'actionjson' => json_encode(['type' => 'theme', 'theme' => 'classic'])]);
        $this->create_rule(['name' => 'Earlier in list', 'sortorder' => 0,
            'actionjson' => json_encode(['type' => 'theme', 'theme' => 'boost'])]);

        $result = simulator::run(fact_provider::create_for_user_and_course(123, null));

        $this->assertSame('boost', $result->selectedtheme);
        $this->assertCount(2, $result->ruletraces);
        // Both rules matched (both traced as TRUE for transparency), but only the
        // earlier-in-list one's theme was actually selected.
        $this->assertTrue($result->ruletraces[0]->matched); // Earlier in list, evaluated first.
        $this->assertSame('boost', $result->ruletraces[0]->theme);
        $this->assertTrue($result->ruletraces[1]->matched); // Later in list, still shown as matched...
        $this->assertNull($result->ruletraces[1]->theme); // ...but did not win.
    }

    public function test_disabled_rule_does_not_appear_in_trace(): void {
        $this->create_rule(['enabled' => 0]);

        $result = simulator::run(fact_provider::create_for_user_and_course(123, null));

        $this->assertCount(0, $result->ruletraces);
        $this->assertNull($result->selectedtheme);
    }

    /**
     * SPECIFICATIONS.md section 50: a rule referencing a deleted entity must not break the
     * simulator; it should indicate the reference could not be resolved.
     */
    public function test_condition_referencing_a_deleted_user_shows_not_found(): void {
        $this->create_rule([
            'expressionjson' => json_encode(['type' => 'condition', 'condition' => 'user',
                'operator' => 'is', 'value' => 999999]),
        ]);

        $result = simulator::run(fact_provider::create_for_user_and_course(123, null));

        $this->assertStringContainsString('not found', $result->ruletraces[0]->conditionlines[0]['text']);
    }

    public function test_corrupt_rule_is_traced_as_an_error_not_a_fatal(): void {
        $this->create_rule(['expressionjson' => '{not valid']);

        $result = simulator::run(fact_provider::create_for_user_and_course(123, null));

        $this->assertFalse($result->ruletraces[0]->matched);
        $this->assertNull($result->selectedtheme);
    }

    public function test_facts_include_resolved_course_and_category_names(): void {
        $category = $this->getDataGenerator()->create_category(['name' => 'Test Category']);
        $course = $this->getDataGenerator()->create_course(['category' => $category->id, 'fullname' => 'Test Course']);

        $result = simulator::run(fact_provider::create_for_user_and_course(123, $course));

        $facts = array_values($result->facts);
        $this->assertTrue((bool) array_filter($facts, fn ($v) => str_contains($v, 'Test Course')));
        $this->assertTrue((bool) array_filter($facts, fn ($v) => str_contains($v, 'Test Category')));
    }

    public function test_facts_include_devicetype_override(): void {
        $result = simulator::run(fact_provider::create_for_user_and_course(123, null, 'tablet'));

        $this->assertContains('Tablet', array_values($result->facts));
    }

    public function test_device_condition_is_traced_with_readable_text(): void {
        $this->create_rule([
            'expressionjson' => json_encode(['type' => 'condition', 'condition' => 'device',
                'operator' => 'is', 'value' => 'mobile']),
        ]);

        $result = simulator::run(fact_provider::create_for_user_and_course(123, null, 'mobile'));

        $this->assertTrue($result->ruletraces[0]->matched);
        $this->assertStringContainsString('Mobile', $result->ruletraces[0]->conditionlines[0]['text']);
    }

    public function test_coursetag_condition_is_traced_with_readable_text(): void {
        $course = $this->getDataGenerator()->create_course(['tags' => 'exam-mode']);
        $this->create_rule([
            'expressionjson' => json_encode(['type' => 'condition', 'condition' => 'coursetag',
                'operator' => 'has', 'value' => 'exam-mode']),
        ]);

        $result = simulator::run(fact_provider::create_for_user_and_course(123, $course));

        $this->assertTrue($result->ruletraces[0]->matched);
        $this->assertStringContainsString('exam-mode', $result->ruletraces[0]->conditionlines[0]['text']);
        $this->assertContains('exam-mode', array_values($result->facts));
    }

    public function test_coursegroup_condition_is_traced_with_readable_text(): void {
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        // Core's groups_add_member() silently no-ops for a user not enrolled in the course.
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id, 'name' => 'Team Alpha']);
        $this->getDataGenerator()->create_group_member(['groupid' => $group->id, 'userid' => $user->id]);
        $this->create_rule([
            'expressionjson' => json_encode(['type' => 'condition', 'condition' => 'coursegroup',
                'operator' => 'member', 'value' => (int) $group->id]),
        ]);

        $result = simulator::run(fact_provider::create_for_course((int) $user->id, $course));

        $this->assertTrue($result->ruletraces[0]->matched);
        $this->assertStringContainsString('Team Alpha', $result->ruletraces[0]->conditionlines[0]['text']);
        $this->assertContains('Team Alpha (id ' . $group->id . ')', array_values($result->facts));
    }

    public function test_coursegroup_any_group_is_traced_with_distinct_text(): void {
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group->id, 'userid' => $user->id]);
        $this->create_rule([
            'expressionjson' => json_encode(['type' => 'condition', 'condition' => 'coursegroup',
                'operator' => 'member', 'value' => 0]),
        ]);

        $result = simulator::run(fact_provider::create_for_course((int) $user->id, $course));

        $this->assertTrue($result->ruletraces[0]->matched);
        $this->assertStringContainsString('at least one group', $result->ruletraces[0]->conditionlines[0]['text']);
    }

    /**
     * userid 0 is not "not found" - it is what a genuinely anonymous, not-logged-in visitor's
     * userid actually is at Tier A, so the simulator must let an admin test that scenario
     * (previously simulate.php refused to run at all for userid 0).
     */
    public function test_anonymous_userid_zero_is_traced_as_anonymous_not_not_found(): void {
        $result = simulator::run(fact_provider::create_for_user_and_course(0, null));

        $this->assertContains('Anonymous / not logged in', array_values($result->facts));
        $this->assertStringNotContainsString('not found', implode(' ', array_values($result->facts)));
    }

    public function test_user_condition_targeting_zero_matches_anonymous_context(): void {
        $this->create_rule([
            'expressionjson' => json_encode(['type' => 'condition', 'condition' => 'user',
                'operator' => 'is', 'value' => 0]),
        ]);

        $result = simulator::run(fact_provider::create_for_user_and_course(0, null));

        $this->assertTrue($result->ruletraces[0]->matched);
        $this->assertStringContainsString('Anonymous / not logged in', $result->ruletraces[0]->conditionlines[0]['text']);
    }

    public function test_profilefield_condition_is_traced_with_readable_text(): void {
        $user = $this->getDataGenerator()->create_user(['institution' => 'UTAD']);
        $this->create_rule([
            'expressionjson' => json_encode(['type' => 'condition', 'condition' => 'profilefield',
                'operator' => 'is', 'field' => 'institution', 'value' => 'UTAD']),
        ]);

        $result = simulator::run(fact_provider::create_for_user_and_course((int) $user->id, null));

        $this->assertTrue($result->ruletraces[0]->matched);
        $this->assertStringContainsString('UTAD', $result->ruletraces[0]->conditionlines[0]['text']);
        $this->assertStringContainsString('Institution', $result->ruletraces[0]->conditionlines[0]['text']);
    }

    public function test_profilefield_condition_custom_field_shows_its_name_not_shortname(): void {
        $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'text', 'shortname' => 'employeeid', 'name' => 'Employee ID',
        ]);
        $user = $this->getDataGenerator()->create_user(['profile_field_employeeid' => '12345']);
        $this->create_rule([
            'expressionjson' => json_encode(['type' => 'condition', 'condition' => 'profilefield',
                'operator' => 'is', 'field' => 'employeeid', 'customfield' => true, 'value' => '12345']),
        ]);

        $result = simulator::run(fact_provider::create_for_user_and_course((int) $user->id, null));

        $this->assertTrue($result->ruletraces[0]->matched);
        $this->assertStringContainsString('Employee ID', $result->ruletraces[0]->conditionlines[0]['text']);
    }
}
