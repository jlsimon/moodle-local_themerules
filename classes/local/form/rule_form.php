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
 * Create/edit form for a single rule. "Basic Moodle form first" per
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules\local\form;

use local_themerules\local\repository\logo_repository;
use local_themerules\local\validation\rule_validator;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Create/edit form for a single rule. "Basic Moodle form first" per
 * SPECIFICATIONS.md section 64 - the condition expression is entered as JSON
 * for now; the visual nested-group builder (section 22/65) is Phase 5.
 */
class rule_form extends \moodleform {
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'name', get_string('form_name', 'local_themerules'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        $mform->addElement(
            'textarea',
            'description',
            get_string('form_description', 'local_themerules'),
            ['rows' => 3, 'cols' => 60]
        );
        $mform->setType('description', PARAM_TEXT);

        $mform->addElement('advcheckbox', 'enabled', get_string('form_enabled', 'local_themerules'));
        $mform->setType('enabled', PARAM_BOOL);

        $mform->addElement('text', 'priority', get_string('form_priority', 'local_themerules'));
        $mform->setType('priority', PARAM_INT);
        $mform->addRule('priority', get_string('required'), 'required', null, 'client');
        $mform->setDefault('priority', 0);

        // Theme and logo are each optional (DECISIONS.md: independent axes) - server-side
        // validation (rule_validator::validate()) requires at least one of the two, but neither
        // can carry a client-side "required" rule on its own without wrongly blocking a
        // logo-only or theme-only rule.
        $mform->addElement('select', 'theme', get_string('form_theme', 'local_themerules'), $this->theme_options());

        $mform->addElement('select', 'logoid', get_string('form_logo', 'local_themerules'), $this->logo_options());
        $mform->setType('logoid', PARAM_INT);

        $mform->addElement(
            'textarea',
            'expressionjson',
            get_string('form_expression', 'local_themerules'),
            ['rows' => 10, 'cols' => 60]
        );
        $mform->setType('expressionjson', PARAM_RAW);
        $mform->addRule('expressionjson', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('expressionjson', 'form_expression', 'local_themerules');

        $mform->addElement(
            'date_time_selector',
            'timestart',
            get_string('form_timestart', 'local_themerules'),
            ['optional' => true]
        );
        $mform->addElement(
            'date_time_selector',
            'timeend',
            get_string('form_timeend', 'local_themerules'),
            ['optional' => true]
        );

        $this->add_action_buttons(true, get_string('form_save', 'local_themerules'));
    }

    private function theme_options(): array {
        $options = ['' => get_string('choosedots')];
        foreach (array_keys(\core_component::get_plugin_list('theme')) as $theme) {
            $options[$theme] = $theme;
        }
        return $options;
    }

    private function logo_options(): array {
        $options = ['0' => get_string('form_logo_none', 'local_themerules')];
        foreach ((new logo_repository())->get_all_records_ordered() as $logo) {
            $options[(string) $logo->id] = $logo->name;
        }
        return $options;
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        return array_merge($errors, rule_validator::validate($data));
    }
}
