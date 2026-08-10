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
 * Rule import. See SPECIFICATIONS.md section 38.
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_themerules\local\form\import_form;
use local_themerules\local\io\rule_export_import;
use local_themerules\local\repository\rule_repository;

$context = context_system::instance();
$url = new moodle_url('/local/themerules/import.php');
$indexurl = new moodle_url('/local/themerules/index.php');

require_login();
require_capability('local/themerules:manage', $context);

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('import', 'local_themerules'));
$PAGE->set_heading(get_string('import', 'local_themerules'));
$PAGE->navbar->add(get_string('pluginname', 'local_themerules'), $indexurl);
$PAGE->navbar->add(get_string('import', 'local_themerules'), $url);

$form = new import_form($url);

if ($form->is_cancelled()) {
    redirect($indexurl);
}

$result = null;

if ($form->get_data()) {
    $content = $form->get_file_content('rulesfile');

    if ($content === false) {
        $result = ['imported' => 0, 'total' => 0, 'errors' => [
            ['index' => -1, 'name' => '', 'message' => get_string('import_error_format', 'local_themerules')],
        ]];
    } else {
        // Decoding itself is the first line of defence against a malformed file (invalid JSON
        // never reaches rule_export_import::import() at all); that method then independently
        // validates the container shape and every individual rule through the same
        // rule_validator::validate() a hand-typed rule goes through - SPECIFICATIONS.md section
        // 24, never trust file-provided JSON without server-side validation.
        $decoded = json_decode($content, true);
        $result = rule_export_import::import($decoded, new rule_repository());
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('import', 'local_themerules'));
echo $OUTPUT->single_button($indexurl, get_string('pluginname', 'local_themerules'), 'get');

if ($result !== null) {
    if ($result['imported'] > 0) {
        echo $OUTPUT->notification(
            get_string('import_summary', 'local_themerules', (object) [
                'imported' => $result['imported'],
                'total' => $result['total'],
            ]),
            $result['imported'] === $result['total'] ? 'success' : 'warning'
        );
    }

    foreach ($result['errors'] as $error) {
        $errora = (object) ['name' => $error['name'], 'message' => $error['message']];
        $label = $error['name'] !== ''
            ? get_string('import_error_named', 'local_themerules', $errora)
            : $error['message'];
        echo $OUTPUT->notification($label, 'error');
    }

    if ($result['imported'] > 0) {
        echo $OUTPUT->single_button($indexurl, get_string('pluginname', 'local_themerules'), 'get');
        echo $OUTPUT->footer();
        exit;
    }
}

echo $OUTPUT->box(get_string('import_help', 'local_themerules'));
$form->display();

echo $OUTPUT->footer();
