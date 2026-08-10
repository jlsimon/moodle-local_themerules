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
 * Data-access layer for local_themerules_rule: the read path used by the runtime resolver
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules\local\repository;

use local_themerules\local\cache\cache_manager;
use local_themerules\local\entity\rule;

/**
 * Data-access layer for local_themerules_rule: the read path used by the runtime resolver
 * (cached, enabled rules only) and the write/admin path used by the CRUD pages.
 */
class rule_repository {
    /**
     * Enabled rules, evaluated in list order - the rule shown first (lowest sortorder) is
     * evaluated first, per SPECIFICATIONS.md section 7. Backed by the "rules" MUC cache
     * (section 28) - see cache_manager for why only the raw records are cached, not parsed
     * expression trees.
     *
     * @return rule[]
     */
    public function get_enabled_rules_ordered(): array {
        $records = cache_manager::get_enabled_rule_records();

        if ($records === null) {
            global $DB;
            $records = array_values($DB->get_records('local_themerules_rule', ['enabled' => 1], 'sortorder ASC, id ASC'));
            cache_manager::set_enabled_rule_records($records);
        }

        return array_map([rule::class, 'from_record'], $records);
    }

    /**
     * All rules (enabled or not), for the administration list. Not cached: only used on the
     * low-traffic admin list page, not the runtime resolver.
     *
     * @return \stdClass[]
     */
    public function get_all_records_ordered(): array {
        global $DB;

        return $DB->get_records('local_themerules_rule', null, 'sortorder ASC, id ASC');
    }

    /**
     * Fetches a single rule's raw DB record.
     *
     * @throws \dml_missing_record_exception if no rule has this id.
     */
    public function get_record(int $id): \stdClass {
        global $DB;

        return $DB->get_record('local_themerules_rule', ['id' => $id], '*', MUST_EXIST);
    }

    public function create(\stdClass $data): int {
        global $DB, $USER;

        $now = time();
        $data->timecreated = $now;
        $data->timemodified = $now;
        $data->usermodified = $USER->id;
        // Always appended at the end, regardless of what the caller passed - sortorder is an
        // ordering concern this repository owns (see move_up()/move_down()), not something a
        // caller (or duplicate(), cloning a source row) should set directly.
        $data->sortorder = $this->next_sortorder();

        $id = (int) $DB->insert_record('local_themerules_rule', $data);
        cache_manager::purge();

        \local_themerules\event\rule_created::create([
            'objectid' => $id,
            'context' => \context_system::instance(),
            'other' => ['rulename' => $data->name],
        ])->trigger();

        return $id;
    }

    public function update(int $id, \stdClass $data): void {
        global $DB, $USER;

        $data->id = $id;
        $data->timemodified = time();
        $data->usermodified = $USER->id;

        $DB->update_record('local_themerules_rule', $data);
        cache_manager::purge();

        \local_themerules\event\rule_updated::create([
            'objectid' => $id,
            'context' => \context_system::instance(),
            'other' => ['rulename' => $data->name],
        ])->trigger();
    }

    public function delete(int $id): void {
        global $DB;

        $rule = $this->get_record($id);

        $DB->delete_records('local_themerules_rule', ['id' => $id]);
        cache_manager::purge();

        \local_themerules\event\rule_deleted::create([
            'objectid' => $id,
            'context' => \context_system::instance(),
            'other' => ['rulename' => $rule->name],
        ])->trigger();
    }

    public function set_enabled(int $id, bool $enabled): void {
        global $DB, $USER;

        $rule = $this->get_record($id);

        $DB->update_record('local_themerules_rule', (object) [
            'id' => $id,
            'enabled' => $enabled ? 1 : 0,
            'timemodified' => time(),
            'usermodified' => $USER->id,
        ]);
        cache_manager::purge();

        $eventclass = $enabled ? \local_themerules\event\rule_enabled::class : \local_themerules\event\rule_disabled::class;
        $eventclass::create([
            'objectid' => $id,
            'context' => \context_system::instance(),
            'other' => ['rulename' => $rule->name],
        ])->trigger();
    }

    /**
     * Duplicates a rule, disabled by default so duplicating never silently
     * doubles an active rule's effect.
     *
     * @return int The new rule's id.
     */
    public function duplicate(int $id): int {
        $source = $this->get_record($id);

        $copy = clone $source;
        unset($copy->id);
        $copy->name = get_string('duplicate_name', 'local_themerules', $source->name);
        $copy->enabled = 0;

        return $this->create($copy);
    }

    /**
     * Moves a rule one position earlier in the evaluation order (swaps sortorder with its
     * immediate predecessor). A no-op if the rule is already first, or does not exist.
     */
    public function move_up(int $id): void {
        $this->swap_with_neighbour($id, -1);
    }

    /**
     * Moves a rule one position later in the evaluation order. A no-op if the rule is already
     * last, or does not exist.
     */
    public function move_down(int $id): void {
        $this->swap_with_neighbour($id, 1);
    }

    /**
     * Swaps the given rule's sortorder with the rule $offset positions away in the current list
     * order. Reads the whole ordered list rather than computing a neighbour via a MIN/MAX query
     * on sortorder directly, so this stays correct even if sortorder values ever have gaps or
     * (briefly, mid-migration) duplicates - list *position* is always well-defined, raw sortorder
     * values are only ever a means to encode it.
     */
    private function swap_with_neighbour(int $id, int $offset): void {
        global $DB;

        $ordered = array_values($this->get_all_records_ordered());

        $index = null;
        foreach ($ordered as $i => $record) {
            if ((int) $record->id === $id) {
                $index = $i;
                break;
            }
        }

        if ($index === null) {
            return;
        }

        $targetindex = $index + $offset;
        if ($targetindex < 0 || $targetindex >= count($ordered)) {
            return;
        }

        $current = $ordered[$index];
        $neighbour = $ordered[$targetindex];

        $DB->set_field('local_themerules_rule', 'sortorder', $neighbour->sortorder, ['id' => $current->id]);
        $DB->set_field('local_themerules_rule', 'sortorder', $current->sortorder, ['id' => $neighbour->id]);
        cache_manager::purge();
    }

    /**
     * The sortorder value that places a new rule after every existing one.
     */
    private function next_sortorder(): int {
        global $DB;

        $max = $DB->get_field_sql('SELECT MAX(sortorder) FROM {local_themerules_rule}');

        return $max === null ? 0 : ((int) $max) + 1;
    }
}
