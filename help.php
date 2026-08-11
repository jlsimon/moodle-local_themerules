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
 * In-app quick reference for the rule editor's conditions and actions.
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$context = context_system::instance();
$indexurl = new moodle_url('/local/themerules/index.php');
$helpurl = new moodle_url('/local/themerules/help.php');

require_login();
require_capability('local/themerules:view', $context);

$PAGE->set_url($helpurl);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('quickreference', 'local_themerules'));
$PAGE->set_heading(get_string('quickreference', 'local_themerules'));
$PAGE->navbar->add(get_string('pluginname', 'local_themerules'), $indexurl);
$PAGE->navbar->add(get_string('quickreference', 'local_themerules'), $helpurl);

// The full illustrated guide is bilingual on GitHub Pages; link to whichever half matches the
// current Moodle language rather than always sending Spanish-speaking admins to the English one.
$guideslug = (substr(current_language(), 0, 2) === 'es') ? 'user_guide.es.html' : 'user_guide.html';
$guideurl = 'https://jlsimon.github.io/moodle-local_themerules/' . $guideslug;

// Same order as condition_registry::CONDITIONS, so this list matches the dropdown in the rule editor.
$conditions = [
    'user', 'course', 'coursecategory', 'cohort', 'coursegroup', 'device', 'coursetag', 'profilefield',
];

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('quickreference', 'local_themerules'));

echo html_writer::tag('p', get_string('help_intro', 'local_themerules'));

echo $OUTPUT->heading(get_string('help_evaluation_heading', 'local_themerules'), 3);
echo html_writer::tag('p', get_string('help_evaluation_body', 'local_themerules'));

echo $OUTPUT->heading(get_string('help_conditions_heading', 'local_themerules'), 3);
echo html_writer::start_tag('ul');
foreach ($conditions as $condition) {
    echo html_writer::tag(
        'li',
        html_writer::tag('strong', get_string('condition_' . $condition, 'local_themerules'))
        . ' — ' . get_string('help_cond_' . $condition . '_desc', 'local_themerules')
    );
}
echo html_writer::end_tag('ul');

echo $OUTPUT->heading(get_string('help_actions_heading', 'local_themerules'), 3);
echo html_writer::tag('p', get_string('help_actions_body', 'local_themerules'));

echo $OUTPUT->heading(get_string('help_tools_heading', 'local_themerules'), 3);
echo html_writer::start_tag('ul');
echo html_writer::tag(
    'li',
    html_writer::tag('strong', get_string('simulator', 'local_themerules'))
    . ' — ' . get_string('help_tools_simulator_desc', 'local_themerules')
);
echo html_writer::tag(
    'li',
    html_writer::tag('strong', get_string('export', 'local_themerules') . ' / ' . get_string('import', 'local_themerules'))
    . ' — ' . get_string('help_tools_importexport_desc', 'local_themerules')
);
echo html_writer::end_tag('ul');

echo html_writer::tag('p', get_string('help_fullguide', 'local_themerules', $guideurl));

echo $OUTPUT->single_button($indexurl, get_string('pluginname', 'local_themerules'), 'get');

echo $OUTPUT->footer();
