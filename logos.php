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
 * Logo asset library: upload/list/delete the logos a rule's `logo` action can reference.
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_themerules\local\form\logo_form;
use local_themerules\local\repository\logo_repository;

$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

$context = context_system::instance();
$url = new moodle_url('/local/themerules/logos.php');
$indexurl = new moodle_url('/local/themerules/index.php');

require_login();
require_capability('local/themerules:manage', $context);

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('logos', 'local_themerules'));
$PAGE->set_heading(get_string('logos', 'local_themerules'));
$PAGE->navbar->add(get_string('pluginname', 'local_themerules'), $indexurl);
$PAGE->navbar->add(get_string('logos', 'local_themerules'), $url);

$repository = new logo_repository();

if ($action === 'delete' && $id > 0) {
    require_sesskey();

    $logo = $repository->get_record($id);

    if (!$confirm) {
        echo $OUTPUT->header();
        echo $OUTPUT->confirm(
            get_string('confirm_delete_logo', 'local_themerules', format_string($logo->name)),
            new moodle_url($url, ['action' => 'delete', 'id' => $id, 'confirm' => 1, 'sesskey' => sesskey()]),
            $url
        );
        echo $OUTPUT->footer();
        exit;
    }

    $repository->delete($id);
    redirect($url, get_string('notify_logo_deleted', 'local_themerules'), null, \core\output\notification::NOTIFY_SUCCESS);
}

$form = new logo_form($url);

if ($data = $form->get_data()) {
    $repository->create(trim($data->name), $data->logofile);
    redirect($url, get_string('notify_logo_saved', 'local_themerules'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('logos', 'local_themerules'));
echo $OUTPUT->single_button($indexurl, get_string('pluginname', 'local_themerules'), 'get');

$form->display();

$logos = $repository->get_all_records_ordered();

if (empty($logos)) {
    echo $OUTPUT->notification(get_string('nologos', 'local_themerules'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = [
    get_string('form_logoname', 'local_themerules'),
    get_string('logopreview', 'local_themerules'),
    get_string('lastmodified', 'local_themerules'),
    get_string('actions', 'local_themerules'),
];

foreach ($logos as $logo) {
    $previewurl = moodle_url::make_pluginfile_url(
        $context->id,
        'local_themerules',
        'logo',
        $logo->id,
        '/',
        $logo->filename
    );

    $deleteurl = new moodle_url($url, ['action' => 'delete', 'id' => $logo->id, 'sesskey' => sesskey()]);

    $table->data[] = [
        format_string($logo->name),
        html_writer::empty_tag('img', ['src' => $previewurl, 'alt' => '', 'style' => 'max-height: 2rem; max-width: 8rem;']),
        userdate($logo->timemodified),
        $OUTPUT->action_icon($deleteurl, new pix_icon('t/delete', get_string('delete'))),
    ];
}

echo html_writer::table($table);

echo $OUTPUT->footer();
