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
     * Enabled rules, highest priority first (SPECIFICATIONS.md section 7).
     * Backed by the "rules" MUC cache (section 28) - see cache_manager for why only the raw
     * records are cached, not parsed expression trees.
     *
     * @return rule[]
     */
    public function get_enabled_rules_ordered(): array {
        $records = cache_manager::get_enabled_rule_records();

        if ($records === null) {
            global $DB;
            $records = array_values($DB->get_records('local_themerules_rule', ['enabled' => 1], 'priority DESC, id ASC'));
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

        return $DB->get_records('local_themerules_rule', null, 'priority DESC, id ASC');
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
}
