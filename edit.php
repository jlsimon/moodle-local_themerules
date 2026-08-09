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
 * Create/edit a single rule. See SPECIFICATIONS.md section 64.
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_themerules\local\condition\condition_registry;
use local_themerules\local\form\rule_form;
use local_themerules\local\repository\rule_repository;
use local_themerules\local\validation\rule_validator;

$id = optional_param('id', 0, PARAM_INT);

$context = context_system::instance();
$indexurl = new moodle_url('/local/themerules/index.php');
$editurl = new moodle_url('/local/themerules/edit.php', $id ? ['id' => $id] : []);

require_login();
require_capability('local/themerules:manage', $context);

$PAGE->set_url($editurl);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->navbar->add(get_string('pluginname', 'local_themerules'), $indexurl);

$repository = new rule_repository();
$record = null;

if ($id) {
    $record = $repository->get_record($id);
    $PAGE->set_title(get_string('editrule', 'local_themerules'));
    $PAGE->set_heading(get_string('editrule', 'local_themerules'));
} else {
    $PAGE->set_title(get_string('createrule', 'local_themerules'));
    $PAGE->set_heading(get_string('createrule', 'local_themerules'));
}

$form = new rule_form($editurl);

if ($form->is_cancelled()) {
    redirect($indexurl);
}

if ($data = $form->get_data()) {
    $record = (object) [
        'name' => trim($data->name),
        'description' => $data->description ?? '',
        'enabled' => !empty($data->enabled) ? 1 : 0,
        'priority' => (int) $data->priority,
        'expressionjson' => trim($data->expressionjson),
        'actionjson' => rule_validator::build_action_json($data->theme),
        'timestart' => (int) $data->timestart,
        'timeend' => (int) $data->timeend,
    ];

    if (!empty($data->id)) {
        $repository->update((int) $data->id, $record);
    } else {
        $repository->create($record);
    }

    redirect(
        $indexurl,
        get_string('notify_saved', 'local_themerules'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

if ($record) {
    $form->set_data((object) [
        'id' => $record->id,
        'name' => $record->name,
        'description' => $record->description,
        'enabled' => $record->enabled,
        'priority' => $record->priority,
        'theme' => rule_validator::extract_theme($record->actionjson),
        'expressionjson' => $record->expressionjson,
        'timestart' => $record->timestart,
        'timeend' => $record->timeend,
    ]);
}

$PAGE->requires->js_call_amd('local_themerules/rule_editor', 'init', [
    'id_expressionjson',
    array_values(condition_registry::get_all_editor_schemas()),
    [
        'matchlabel' => get_string('editor_matchlabel', 'local_themerules'),
        'matchall' => get_string('editor_matchall', 'local_themerules'),
        'matchany' => get_string('editor_matchany', 'local_themerules'),
        'addcondition' => get_string('editor_addcondition', 'local_themerules'),
        'addgroup' => get_string('editor_addgroup', 'local_themerules'),
        'removecondition' => get_string('editor_removecondition', 'local_themerules'),
        'removegroup' => get_string('editor_removegroup', 'local_themerules'),
        'condition' => get_string('editor_condition', 'local_themerules'),
        'operator' => get_string('editor_operator', 'local_themerules'),
        'value' => get_string('editor_value', 'local_themerules'),
        'includechildren' => get_string('editor_includechildren', 'local_themerules'),
    ],
]);

echo $OUTPUT->header();
echo $OUTPUT->heading($id ? get_string('editrule', 'local_themerules') : get_string('createrule', 'local_themerules'));
$form->display();
echo $OUTPUT->footer();
