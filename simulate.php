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
 * Rule simulator. See SPECIFICATIONS.md section 31.
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_themerules\local\condition\device_condition;
use local_themerules\local\diagnostics\simulator;
use local_themerules\local\engine\fact_provider;

$submitted = optional_param('submitted', 0, PARAM_BOOL);
$userid = optional_param('userid', 0, PARAM_INT); // 0 = anonymous/not logged in - a real value, not "no input yet".
$courseid = optional_param('courseid', 0, PARAM_INT);
$devicetype = optional_param('devicetype', '', PARAM_ALPHA);

$context = context_system::instance();
$indexurl = new moodle_url('/local/themerules/index.php');
$simulateurl = new moodle_url('/local/themerules/simulate.php');

require_login();
require_capability('local/themerules:simulate', $context);

$PAGE->set_url($simulateurl);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('simulator', 'local_themerules'));
$PAGE->set_heading(get_string('simulator', 'local_themerules'));
$PAGE->navbar->add(get_string('pluginname', 'local_themerules'), $indexurl);
$PAGE->navbar->add(get_string('simulator', 'local_themerules'), $simulateurl);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('simulator', 'local_themerules'));

echo html_writer::start_tag('form', ['method' => 'get', 'action' => $simulateurl, 'class' => 'form-inline mb-3']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'submitted', 'value' => '1']);
echo html_writer::tag(
    'label',
    get_string('trace_fact_user', 'local_themerules'),
    ['for' => 'themerules-sim-userid', 'class' => 'me-1']
);
echo html_writer::empty_tag('input', ['type' => 'number', 'name' => 'userid', 'id' => 'themerules-sim-userid',
    'value' => $userid ?: '', 'placeholder' => get_string('simulator_userid_placeholder', 'local_themerules'),
    'class' => 'form-control d-inline-block w-auto me-3']);
echo html_writer::tag(
    'label',
    get_string('trace_fact_course', 'local_themerules'),
    ['for' => 'themerules-sim-courseid', 'class' => 'me-1']
);
echo html_writer::empty_tag('input', ['type' => 'number', 'name' => 'courseid', 'id' => 'themerules-sim-courseid',
    'value' => $courseid ?: '', 'class' => 'form-control d-inline-block w-auto me-3']);
echo html_writer::tag(
    'label',
    get_string('simulator_devicetype', 'local_themerules'),
    ['for' => 'themerules-sim-devicetype', 'class' => 'me-1']
);
echo html_writer::start_tag('select', ['name' => 'devicetype', 'id' => 'themerules-sim-devicetype',
    'class' => 'form-select d-inline-block w-auto me-3']);
echo html_writer::tag('option', get_string('simulator_devicetype_auto', 'local_themerules'), ['value' => '']);
foreach (device_condition::VALUES as $value) {
    echo html_writer::tag(
        'option',
        get_string('device_' . $value, 'local_themerules'),
        ['value' => $value, 'selected' => $devicetype === $value ? 'selected' : null]
    );
}
echo html_writer::end_tag('select');
echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('simulate', 'local_themerules'),
    'class' => 'btn btn-primary']);
echo html_writer::end_tag('form');

if ($submitted) {
    $course = null;
    if ($courseid > 0) {
        $course = $DB->get_record('course', ['id' => $courseid], '*', IGNORE_MISSING) ?: null;
        if (!$course) {
            echo $OUTPUT->notification(get_string('trace_notfound', 'local_themerules', $courseid), 'warning');
        }
    }

    $simcontext = fact_provider::create_for_user_and_course($userid, $course, $devicetype !== '' ? $devicetype : null);
    $result = simulator::run($simcontext);

    echo $OUTPUT->heading(get_string('simulator_facts', 'local_themerules'), 3);
    $factstable = new html_table();
    $factstable->head = [get_string('simulator_fact', 'local_themerules'), get_string('simulator_value', 'local_themerules')];
    foreach ($result->facts as $label => $value) {
        $factstable->data[] = [s($label), s($value)];
    }
    echo html_writer::table($factstable);

    echo $OUTPUT->heading(get_string('simulator_evaluation', 'local_themerules'), 3);

    if (empty($result->ruletraces)) {
        echo $OUTPUT->notification(get_string('norules', 'local_themerules'), 'info');
    }

    foreach ($result->ruletraces as $trace) {
        $badgeclass = $trace->matched ? 'badge-success' : 'badge-secondary';
        $resulttext = $trace->matched
            ? get_string('simulator_resulttrue', 'local_themerules')
            : get_string('simulator_resultfalse', 'local_themerules');

        echo html_writer::start_div('local-themerules-trace card mb-2');
        echo html_writer::start_div('card-body');
        echo html_writer::tag('h4', '#' . $trace->position . ' ' . s($trace->rulename), ['class' => 'card-title h6']);

        $lines = '';
        foreach ($trace->conditionlines as $line) {
            $icon = $line['result']
                ? $OUTPUT->pix_icon('i/valid', get_string('simulator_matched', 'local_themerules'))
                : $OUTPUT->pix_icon('i/invalid', get_string('simulator_notmatched', 'local_themerules'));
            $lines .= html_writer::tag('li', $icon . ' ' . s($line['text']));
        }
        echo html_writer::tag('ul', $lines, ['class' => 'list-unstyled']);

        echo html_writer::tag('span', $resulttext, ['class' => 'badge ' . $badgeclass]);
        if ($trace->theme !== null) {
            echo ' ' . html_writer::tag(
                'span',
                get_string('simulator_wouldselect', 'local_themerules', s($trace->theme)),
                ['class' => 'ms-2 fw-bold']
            );
        }
        if ($trace->logo !== null) {
            echo ' ' . html_writer::tag(
                'span',
                get_string('simulator_wouldselectlogo', 'local_themerules', s($trace->logo)),
                ['class' => 'ms-2 fw-bold']
            );
        }
        echo html_writer::end_div();
        echo html_writer::end_div();
    }

    echo $OUTPUT->heading(get_string('simulator_selectedtheme', 'local_themerules'), 3);
    echo $OUTPUT->notification(
        $result->selectedtheme !== null
            ? get_string('simulator_selectedthemevalue', 'local_themerules', s($result->selectedtheme))
            : get_string('simulator_nomatch', 'local_themerules'),
        $result->selectedtheme !== null ? 'success' : 'info'
    );

    echo $OUTPUT->heading(get_string('simulator_selectedlogo', 'local_themerules'), 3);
    echo $OUTPUT->notification(
        $result->selectedlogo !== null
            ? get_string('simulator_selectedlogovalue', 'local_themerules', s($result->selectedlogo))
            : get_string('simulator_nologomatch', 'local_themerules'),
        $result->selectedlogo !== null ? 'success' : 'info'
    );
}

echo $OUTPUT->footer();
