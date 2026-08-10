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
use local_themerules\local\engine\fact_provider;
use local_themerules\local\engine\resolver;

/**
 * Unit tests for resolver.
 */
#[\PHPUnit\Framework\Attributes\CoversClass(resolver::class)]
final class resolver_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    private function create_rule(array $overrides = []): int {
        global $DB, $USER;

        $now = time();
        $record = array_merge([
            'name' => 'Test rule',
            'enabled' => 1,
            'expressionjson' => json_encode(['type' => 'condition', 'condition' => 'user',
                'operator' => 'is', 'value' => 123]),
            'actionjson' => json_encode(['type' => 'theme', 'theme' => 'boost']),
            'timestart' => 0,
            'timeend' => 0,
            'sortorder' => 0,
            'timecreated' => $now,
            'timemodified' => $now,
            'usermodified' => $USER->id ?? 0,
        ], $overrides);

        return (int) $DB->insert_record('local_themerules_rule', (object) $record);
    }

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

    /**
     * SPECIFICATIONS.md section 61, Phase 1 acceptance test:
     * Rule: user = 123 -> boost. User 123: boost selected. Other user: normal Moodle theme.
     */
    public function test_matching_user_gets_the_rule_theme(): void {
        $this->create_rule();

        $theme = resolver::resolve_theme(new evaluation_context(123));

        $this->assertSame('boost', $theme);
    }

    public function test_non_matching_user_gets_null(): void {
        $this->create_rule();

        $theme = resolver::resolve_theme(new evaluation_context(456));

        $this->assertNull($theme);
    }

    public function test_no_rules_returns_null(): void {
        $theme = resolver::resolve_theme(new evaluation_context(123));

        $this->assertNull($theme);
    }

    public function test_disabled_rule_is_ignored(): void {
        $this->create_rule(['enabled' => 0]);

        $theme = resolver::resolve_theme(new evaluation_context(123));

        $this->assertNull($theme);
    }

    /**
     * SPECIFICATIONS.md section 46, Test D / section 7: the rule earlier in the evaluation
     * order wins even if a later one also matches - and specifically, that this is governed by
     * sortorder, not insertion/id order (the "later in list" rule here is inserted *first*, so
     * an id-based tiebreak alone would pick the wrong winner).
     */
    public function test_rule_earlier_in_list_wins(): void {
        $this->create_rule([
            'name' => 'Later in list',
            'sortorder' => 1,
            'expressionjson' => json_encode(['type' => 'condition', 'condition' => 'user',
                'operator' => 'is', 'value' => 123]),
            'actionjson' => json_encode(['type' => 'theme', 'theme' => 'classic']),
        ]);
        $this->create_rule([
            'name' => 'Earlier in list',
            'sortorder' => 0,
            'expressionjson' => json_encode(['type' => 'condition', 'condition' => 'user',
                'operator' => 'is', 'value' => 123]),
            'actionjson' => json_encode(['type' => 'theme', 'theme' => 'boost']),
        ]);

        $theme = resolver::resolve_theme(new evaluation_context(123));

        $this->assertSame('boost', $theme);
    }

    public function test_invalid_theme_rule_is_skipped_safely(): void {
        $this->create_rule([
            'actionjson' => json_encode(['type' => 'theme', 'theme' => 'this_theme_does_not_exist']),
        ]);
        $this->create_rule([
            'actionjson' => json_encode(['type' => 'theme', 'theme' => 'boost']),
        ]);

        $theme = resolver::resolve_theme(new evaluation_context(123));

        $this->assertSame('boost', $theme);
        $this->assertDebuggingCalled();
    }

    public function test_corrupt_expression_json_is_skipped_safely(): void {
        $this->create_rule([
            'expressionjson' => '{not valid json',
        ]);
        $this->create_rule([]);

        $theme = resolver::resolve_theme(new evaluation_context(123));

        $this->assertSame('boost', $theme);
        $this->assertDebuggingCalled();
    }

    public function test_rule_outside_its_time_window_is_ignored(): void {
        $this->create_rule(['timestart' => time() + DAYSECS]);

        $this->assertNull(resolver::resolve_theme(new evaluation_context(123)));
    }

    public function test_rule_within_its_time_window_matches(): void {
        $this->create_rule([
            'timestart' => time() - DAYSECS,
            'timeend' => time() + DAYSECS,
        ]);

        $this->assertSame('boost', resolver::resolve_theme(new evaluation_context(123)));
    }

    /**
     * SPECIFICATIONS.md section 46, Test B: course = 5 AND cohort = 7 -> true.
     */
    public function test_course_and_cohort_and_expression(): void {
        $category = $this->getDataGenerator()->create_category();
        $course = $this->getDataGenerator()->create_course(['category' => $category->id]);
        $user = $this->getDataGenerator()->create_user();
        $cohort = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort->id, $user->id);

        $this->create_rule([
            'expressionjson' => json_encode(['type' => 'group', 'operator' => 'and', 'children' => [
                ['type' => 'condition', 'condition' => 'course', 'operator' => 'is', 'value' => (int) $course->id],
                ['type' => 'condition', 'condition' => 'cohort', 'operator' => 'member', 'value' => (int) $cohort->id],
            ]]),
        ]);

        $context = fact_provider::create_for_course((int) $user->id, $course);

        $this->assertSame('boost', resolver::resolve_theme($context));
    }

    /**
     * SPECIFICATIONS.md sections 1/72/79, the plugin's canonical example:
     * course category = FUNDAE (including descendants) AND
     * (cohort = Company A OR cohort = Company B) -> theme_cigales.
     */
    public function test_canonical_fundae_company_example(): void {
        $fundae = $this->getDataGenerator()->create_category(['name' => 'FUNDAE']);
        $compliance = $this->getDataGenerator()->create_category(['name' => 'Compliance', 'parent' => $fundae->id]);
        $course = $this->getDataGenerator()->create_course(['category' => $compliance->id]);
        $user = $this->getDataGenerator()->create_user();
        $companya = $this->getDataGenerator()->create_cohort();
        cohort_add_member($companya->id, $user->id);

        $this->create_rule([
            'name' => 'Corporate branding',
            'actionjson' => json_encode(['type' => 'theme', 'theme' => 'cigales']),
            'expressionjson' => json_encode(['type' => 'group', 'operator' => 'and', 'children' => [
                ['type' => 'condition', 'condition' => 'coursecategory', 'operator' => 'in_category',
                    'value' => (int) $fundae->id, 'includechildren' => true],
                ['type' => 'group', 'operator' => 'or', 'children' => [
                    ['type' => 'condition', 'condition' => 'cohort', 'operator' => 'member', 'value' => (int) $companya->id],
                    ['type' => 'condition', 'condition' => 'cohort', 'operator' => 'member', 'value' => 999999],
                ]],
            ]]),
        ]);

        $context = fact_provider::create_for_course((int) $user->id, $course);

        $this->assertSame('cigales', resolver::resolve_theme($context));
    }

    public function test_course_condition_does_not_match_without_course_context(): void {
        $this->create_rule([
            'expressionjson' => json_encode(['type' => 'condition', 'condition' => 'course',
                'operator' => 'is', 'value' => 5]),
        ]);

        // Tier A context (DECISIONS.md "Phase 2"): no course known yet.
        $this->assertNull(resolver::resolve_theme(new evaluation_context(123)));
    }

    /**
     * DECISIONS.md "Phase 9 (partial, continued): logo action": theme and logo are independent
     * axes - a single rule can carry both, in the list-of-action-nodes format.
     */
    public function test_single_rule_resolves_theme_and_logo_together(): void {
        $logoid = $this->create_logo();
        $this->create_rule([
            'actionjson' => json_encode([
                ['type' => 'theme', 'theme' => 'boost'],
                ['type' => 'logo', 'logoid' => $logoid],
            ]),
        ]);

        $resolved = resolver::resolve(new evaluation_context(123));

        $this->assertSame('boost', $resolved['theme']);
        $this->assertSame((string) $logoid, $resolved['logo']);
    }

    /**
     * A rule later in the evaluation order can still fill in an axis an earlier one left unset,
     * without ever overriding the axis that earlier rule already claimed.
     */
    public function test_later_rule_fills_in_axis_earlier_rule_left_unset(): void {
        $logoid = $this->create_logo();
        $this->create_rule([
            'name' => 'Theme only, evaluated first',
            'actionjson' => json_encode([['type' => 'theme', 'theme' => 'boost']]),
        ]);
        $this->create_rule([
            'name' => 'Logo only, evaluated second',
            'actionjson' => json_encode([['type' => 'logo', 'logoid' => $logoid]]),
        ]);

        $resolved = resolver::resolve(new evaluation_context(123));

        $this->assertSame('boost', $resolved['theme']);
        $this->assertSame((string) $logoid, $resolved['logo']);
    }

    /**
     * An earlier rule's own theme still wins even when a later rule also sets a theme - the
     * per-axis "first match wins" guarantee must hold, not just "first rule wins".
     */
    public function test_earlier_rule_theme_is_not_overridden_by_later_rule(): void {
        $logoid = $this->create_logo();
        $this->create_rule([
            'name' => 'Theme, evaluated first',
            'actionjson' => json_encode([['type' => 'theme', 'theme' => 'boost']]),
        ]);
        $this->create_rule([
            'name' => 'Theme + logo, evaluated second',
            'actionjson' => json_encode([
                ['type' => 'theme', 'theme' => 'classic'],
                ['type' => 'logo', 'logoid' => $logoid],
            ]),
        ]);

        $resolved = resolver::resolve(new evaluation_context(123));

        $this->assertSame('boost', $resolved['theme']);
        $this->assertSame((string) $logoid, $resolved['logo']);
    }

    public function test_logo_axis_absent_when_no_rule_sets_it(): void {
        $this->create_rule(['actionjson' => json_encode([['type' => 'theme', 'theme' => 'boost']])]);

        $resolved = resolver::resolve(new evaluation_context(123));

        $this->assertArrayNotHasKey('logo', $resolved);
    }

    public function test_invalid_logo_rule_is_skipped_safely(): void {
        $this->create_rule([
            'actionjson' => json_encode([['type' => 'logo', 'logoid' => 999999]]),
        ]);
        $logoid = $this->create_logo();
        $this->create_rule([
            'actionjson' => json_encode([['type' => 'logo', 'logoid' => $logoid]]),
        ]);

        $resolved = resolver::resolve(new evaluation_context(123));

        $this->assertSame((string) $logoid, $resolved['logo']);
        $this->assertDebuggingCalled();
    }

    /**
     * A rule created before the `logo` action existed stores actionjson as a single object, not
     * a list - resolve() must keep accepting that shape forever, not just during a migration
     * window (a theme-only rule never needs the list form).
     */
    public function test_legacy_single_object_actionjson_still_resolves(): void {
        $this->create_rule([
            'actionjson' => json_encode(['type' => 'theme', 'theme' => 'boost']),
        ]);

        $resolved = resolver::resolve(new evaluation_context(123));

        $this->assertSame('boost', $resolved['theme']);
    }
}
