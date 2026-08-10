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
 * Rule list / administration entry point. See SPECIFICATIONS.md section 21.
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_themerules\local\repository\logo_repository;
use local_themerules\local\repository\rule_repository;
use local_themerules\local\validation\rule_validator;

$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

$context = context_system::instance();
$url = new moodle_url('/local/themerules/index.php');

// Also runs require_login() and checks the 'local/themerules:view' capability the page was
// registered with in settings.php.
admin_externalpage_setup('local_themerules');

$repository = new rule_repository();

if ($action !== '' && $id > 0) {
    require_capability('local/themerules:manage', $context);
    require_sesskey();

    if ($action === 'enable' || $action === 'disable') {
        $repository->set_enabled($id, $action === 'enable');
        redirect($url, get_string('notify_updated', 'local_themerules'), null, \core\output\notification::NOTIFY_SUCCESS);
    }

    if ($action === 'duplicate') {
        $repository->duplicate($id);
        redirect($url, get_string('notify_duplicated', 'local_themerules'), null, \core\output\notification::NOTIFY_SUCCESS);
    }

    if ($action === 'moveup' || $action === 'movedown') {
        $action === 'moveup' ? $repository->move_up($id) : $repository->move_down($id);
        redirect($url, get_string('notify_reordered', 'local_themerules'), null, \core\output\notification::NOTIFY_SUCCESS);
    }

    if ($action === 'delete') {
        $rule = $repository->get_record($id);

        if (!$confirm) {
            echo $OUTPUT->header();
            echo $OUTPUT->confirm(
                get_string('confirm_delete', 'local_themerules', format_string($rule->name)),
                new moodle_url($url, ['action' => 'delete', 'id' => $id, 'confirm' => 1, 'sesskey' => sesskey()]),
                $url
            );
            echo $OUTPUT->footer();
            exit;
        }

        $repository->delete($id);
        redirect($url, get_string('notify_deleted', 'local_themerules'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

echo $OUTPUT->header();

echo $OUTPUT->single_button(new moodle_url('/local/themerules/edit.php'), get_string('createrule', 'local_themerules'));
if (has_capability('local/themerules:simulate', $context)) {
    echo $OUTPUT->single_button(
        new moodle_url('/local/themerules/simulate.php'),
        get_string('simulator', 'local_themerules'),
        'get'
    );
}
if (has_capability('local/themerules:manage', $context)) {
    echo $OUTPUT->single_button(
        new moodle_url('/local/themerules/logos.php'),
        get_string('logos', 'local_themerules'),
        'get'
    );
}

$rules = $repository->get_all_records_ordered();

if (empty($rules)) {
    echo $OUTPUT->notification(get_string('norules', 'local_themerules'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = [
    get_string('form_name', 'local_themerules'),
    get_string('form_enabled', 'local_themerules'),
    get_string('form_theme', 'local_themerules'),
    get_string('form_logo', 'local_themerules'),
    get_string('lastmodified', 'local_themerules'),
    get_string('actions', 'local_themerules'),
];

$canmanage = has_capability('local/themerules:manage', $context);

// One lookup for every rule's logo name, instead of one query per rule (this page is
// low-traffic admin-only, but there is no reason to make it N+1 when a single query does).
$logonames = [];
foreach ((new logo_repository())->get_all_records_ordered() as $logo) {
    $logonames[$logo->id] = $logo->name;
}

$orderedrules = array_values($rules);

foreach ($orderedrules as $index => $rule) {
    $editurl = new moodle_url('/local/themerules/edit.php', ['id' => $rule->id]);

    $statustext = $rule->enabled
        ? get_string('status_enabled', 'local_themerules')
        : get_string('status_disabled', 'local_themerules');

    $actions = '';
    if ($canmanage) {
        $actionurl = fn (string $action, array $extra = []) => new moodle_url(
            $url,
            array_merge(['action' => $action, 'id' => $rule->id, 'sesskey' => sesskey()], $extra)
        );

        // The order in this list *is* the evaluation order (SPECIFICATIONS.md section 7), so
        // reordering lives here rather than as a typed "priority" field - no up arrow on the
        // first row, no down arrow on the last, same convention as Moodle's own admin lists
        // (e.g. Manage authentication).
        if ($index > 0) {
            $actions .= $OUTPUT->action_icon(
                $actionurl('moveup'),
                new pix_icon('t/up', get_string('moveup', 'local_themerules'))
            );
        }
        if ($index < count($orderedrules) - 1) {
            $actions .= $OUTPUT->action_icon(
                $actionurl('movedown'),
                new pix_icon('t/down', get_string('movedown', 'local_themerules'))
            );
        }

        $actions .= $OUTPUT->action_icon($editurl, new pix_icon('t/edit', get_string('edit')));
        $actions .= $OUTPUT->action_icon(
            $actionurl('duplicate'),
            new pix_icon('t/copy', get_string('duplicate', 'local_themerules'))
        );
        $toggleaction = $rule->enabled ? 'disable' : 'enable';
        $actions .= $OUTPUT->action_icon(
            $actionurl($toggleaction),
            new pix_icon($rule->enabled ? 't/hide' : 't/show', get_string($toggleaction, 'local_themerules'))
        );
        $actions .= $OUTPUT->action_icon($actionurl('delete'), new pix_icon('t/delete', get_string('delete')));
    }

    $logoid = rule_validator::extract_logoid($rule->actionjson);

    $table->data[] = [
        format_string($rule->name),
        $statustext,
        s(rule_validator::extract_theme($rule->actionjson)),
        $logoid !== null ? s($logonames[$logoid] ?? get_string('trace_notfound', 'local_themerules', $logoid)) : '',
        userdate($rule->timemodified),
        $actions,
    ];
}

echo html_writer::table($table);

echo $OUTPUT->footer();
