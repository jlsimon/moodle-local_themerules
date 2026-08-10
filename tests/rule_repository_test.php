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

use local_themerules\local\repository\rule_repository;

/**
 * Unit tests for rule_repository.
 */
#[\PHPUnit\Framework\Attributes\CoversClass(rule_repository::class)]
final class rule_repository_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->setAdminUser();
    }

    private function sample_record(array $overrides = []): \stdClass {
        return (object) array_merge([
            'name' => 'Sample rule',
            'description' => '',
            'enabled' => 0,
            'expressionjson' => json_encode(['type' => 'condition', 'condition' => 'user',
                'operator' => 'is', 'value' => 123]),
            'actionjson' => json_encode(['type' => 'theme', 'theme' => 'boost']),
            'timestart' => 0,
            'timeend' => 0,
        ], $overrides);
    }

    public function test_create_and_get_record(): void {
        $repository = new rule_repository();

        $id = $repository->create($this->sample_record(['name' => 'My rule']));
        $record = $repository->get_record($id);

        $this->assertSame('My rule', $record->name);
        $this->assertGreaterThan(0, $record->timecreated);
        $this->assertSame($record->timecreated, $record->timemodified);
    }

    public function test_update_changes_timemodified_but_not_timecreated(): void {
        $repository = new rule_repository();
        $id = $repository->create($this->sample_record());
        $before = $repository->get_record($id);

        $this->waitForSecond();
        $repository->update($id, $this->sample_record(['name' => 'Renamed']));
        $after = $repository->get_record($id);

        $this->assertSame('Renamed', $after->name);
        $this->assertSame($before->timecreated, $after->timecreated);
        $this->assertGreaterThan($before->timemodified, $after->timemodified);
    }

    public function test_delete_removes_the_record(): void {
        $repository = new rule_repository();
        $id = $repository->create($this->sample_record());

        $repository->delete($id);

        $this->expectException(\dml_missing_record_exception::class);
        $repository->get_record($id);
    }

    public function test_set_enabled_toggles_flag(): void {
        $repository = new rule_repository();
        $id = $repository->create($this->sample_record(['enabled' => 0]));

        $repository->set_enabled($id, true);
        $this->assertSame(1, (int) $repository->get_record($id)->enabled);

        $repository->set_enabled($id, false);
        $this->assertSame(0, (int) $repository->get_record($id)->enabled);
    }

    /**
     * SPECIFICATIONS.md section 64: duplicating a rule must never silently double an
     * active rule's effect.
     */
    public function test_duplicate_creates_a_disabled_copy(): void {
        $repository = new rule_repository();
        $id = $repository->create($this->sample_record(['name' => 'Original', 'enabled' => 1]));

        $newid = $repository->duplicate($id);
        $copy = $repository->get_record($newid);

        $this->assertNotSame($id, $newid);
        $this->assertSame(0, (int) $copy->enabled);
        $this->assertStringContainsString('Original', $copy->name);
        // Original left untouched.
        $this->assertSame(1, (int) $repository->get_record($id)->enabled);
    }

    public function test_get_all_records_ordered_includes_disabled_rules(): void {
        $repository = new rule_repository();
        $repository->create($this->sample_record(['name' => 'Enabled', 'enabled' => 1]));
        $repository->create($this->sample_record(['name' => 'Disabled', 'enabled' => 0]));

        $all = $repository->get_all_records_ordered();

        $this->assertCount(2, $all);
    }

    /**
     * create() always appends at the end (SPECIFICATIONS.md section 7 / DECISIONS.md: sortorder
     * is owned by the repository, not settable by callers) - new rules show up last, in the
     * order they were created, until an admin moves them with move_up()/move_down().
     */
    public function test_get_all_records_ordered_reflects_creation_order(): void {
        $repository = new rule_repository();
        $repository->create($this->sample_record(['name' => 'First created']));
        $repository->create($this->sample_record(['name' => 'Second created']));

        $all = array_values($repository->get_all_records_ordered());

        $this->assertCount(2, $all);
        $this->assertSame('First created', $all[0]->name);
        $this->assertSame('Second created', $all[1]->name);
    }

    public function test_create_ignores_caller_supplied_sortorder(): void {
        $repository = new rule_repository();
        $repository->create($this->sample_record(['name' => 'First created']));
        // A caller passing an explicit sortorder (e.g. duplicate() cloning a source row) must
        // not be able to jump the queue - create() always computes its own append-at-end value.
        $repository->create($this->sample_record(['name' => 'Should still be last', 'sortorder' => 0]));

        $all = array_values($repository->get_all_records_ordered());

        $this->assertSame('Should still be last', $all[1]->name);
    }

    public function test_move_up_swaps_with_previous_rule(): void {
        $repository = new rule_repository();
        $firstid = $repository->create($this->sample_record(['name' => 'First']));
        $secondid = $repository->create($this->sample_record(['name' => 'Second']));

        $repository->move_up($secondid);

        $all = array_values($repository->get_all_records_ordered());
        $this->assertSame('Second', $all[0]->name);
        $this->assertSame('First', $all[1]->name);
        // Ids are stable - only the order changed, not identity.
        $this->assertSame($secondid, (int) $all[0]->id);
        $this->assertSame($firstid, (int) $all[1]->id);
    }

    public function test_move_down_swaps_with_next_rule(): void {
        $repository = new rule_repository();
        $repository->create($this->sample_record(['name' => 'First']));
        $repository->create($this->sample_record(['name' => 'Second']));

        $ordered = $repository->get_all_records_ordered();
        $firstrecord = reset($ordered);
        $repository->move_down((int) $firstrecord->id);

        $all = array_values($repository->get_all_records_ordered());
        $this->assertSame('Second', $all[0]->name);
        $this->assertSame('First', $all[1]->name);
    }

    public function test_move_up_on_first_rule_is_a_noop(): void {
        $repository = new rule_repository();
        $firstid = $repository->create($this->sample_record(['name' => 'First']));
        $repository->create($this->sample_record(['name' => 'Second']));

        $repository->move_up($firstid);

        $all = array_values($repository->get_all_records_ordered());
        $this->assertSame('First', $all[0]->name);
        $this->assertSame('Second', $all[1]->name);
    }

    public function test_move_down_on_last_rule_is_a_noop(): void {
        $repository = new rule_repository();
        $repository->create($this->sample_record(['name' => 'First']));
        $secondid = $repository->create($this->sample_record(['name' => 'Second']));

        $repository->move_down($secondid);

        $all = array_values($repository->get_all_records_ordered());
        $this->assertSame('First', $all[0]->name);
        $this->assertSame('Second', $all[1]->name);
    }

    public function test_move_up_on_unknown_id_is_a_noop(): void {
        $repository = new rule_repository();
        $repository->create($this->sample_record(['name' => 'Only rule']));

        // Must not throw.
        $repository->move_up(999999);
        $repository->move_down(999999);

        $this->assertCount(1, $repository->get_all_records_ordered());
    }
}
