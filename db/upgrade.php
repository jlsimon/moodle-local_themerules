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
 * Upgrade steps.
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Runs the plugin's upgrade steps.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_themerules_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026080900) {
        // Phase 1: the rule table did not exist yet in the Phase 0 skeleton.
        $table = new xmldb_table('local_themerules_rule');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null);
        $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('priority', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('expressionjson', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null);
        $table->add_field('actionjson', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null);
        $table->add_field('timestart', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timeend', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodifiedfk', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_index('enabledidx', XMLDB_INDEX_NOTUNIQUE, ['enabled']);
        $table->add_index('priorityidx', XMLDB_INDEX_NOTUNIQUE, ['priority']);
        $table->add_index('timestartidx', XMLDB_INDEX_NOTUNIQUE, ['timestart']);
        $table->add_index('timeendidx', XMLDB_INDEX_NOTUNIQUE, ['timeend']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026080900, 'local', 'themerules');
    }

    if ($oldversion < 2026081002) {
        // Logo action support: a rule can now select an uploaded logo asset, independently of
        // (or instead of) a theme. File bytes live in the File API (component local_themerules,
        // filearea logo, itemid = this table's id), not in this table - see DECISIONS.md.
        $table = new xmldb_table('local_themerules_logo');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodifiedfk', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026081002, 'local', 'themerules');
    }

    if ($oldversion < 2026081003) {
        // Added one step after the table itself: the CSS-override hook needs the stored file's
        // actual filename (not just its itemid) to build a working pluginfile URL - see
        // hook_listener::before_standard_head_html_generation() and DECISIONS.md.
        $table = new xmldb_table('local_themerules_logo');
        $field = new xmldb_field('filename', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'name');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026081003, 'local', 'themerules');
    }

    if ($oldversion < 2026081100) {
        // Replaced the numeric "priority" field with list-position ordering (drag/reorder via
        // move up/down, backed by the "sortorder" field that already existed but was never
        // used) - see DECISIONS.md. Backfill sortorder from the current priority order first,
        // so any existing rule's effective evaluation order is preserved across the switch,
        // before priority itself is dropped.
        $rules = $DB->get_records('local_themerules_rule', null, 'priority DESC, id ASC', 'id');
        $sortorder = 0;
        foreach ($rules as $rule) {
            $DB->set_field('local_themerules_rule', 'sortorder', $sortorder, ['id' => $rule->id]);
            $sortorder++;
        }

        $table = new xmldb_table('local_themerules_rule');

        $priorityindex = new xmldb_index('priorityidx', XMLDB_INDEX_NOTUNIQUE, ['priority']);
        if ($dbman->index_exists($table, $priorityindex)) {
            $dbman->drop_index($table, $priorityindex);
        }

        $priorityfield = new xmldb_field('priority', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if ($dbman->field_exists($table, $priorityfield)) {
            $dbman->drop_field($table, $priorityfield);
        }

        $sortorderindex = new xmldb_index('sortorderidx', XMLDB_INDEX_NOTUNIQUE, ['sortorder']);
        if (!$dbman->index_exists($table, $sortorderindex)) {
            $dbman->add_index($table, $sortorderindex);
        }

        upgrade_plugin_savepoint(true, 2026081100, 'local', 'themerules');
    }

    return true;
}
