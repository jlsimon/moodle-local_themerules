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
 * Upload form for rule import (classes/local/io/rule_export_import.php).
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules\local\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Upload form for rule import. Deliberately a `filepicker`, not a `filemanager` like
 * logo_form.php's permanent asset upload - import.php reads the uploaded JSON's content once via
 * $this->get_file_content() and never keeps the file itself, so there is nothing here that needs
 * logo_form.php's file_prepare_draft_area()/permanent-storage machinery.
 */
class import_form extends \moodleform {
    /**
     * Builds the form: a single JSON file upload field.
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement(
            'filepicker',
            'rulesfile',
            get_string('form_rulesfile', 'local_themerules'),
            null,
            ['accepted_types' => ['.json']]
        );
        $mform->addRule('rulesfile', get_string('required'), 'required', null, 'client');

        $this->add_action_buttons(true, get_string('import', 'local_themerules'));
    }
}
