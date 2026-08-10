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
 * Upload form for a single logo asset (classes/local/repository/logo_repository.php).
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
 * Upload form for a single logo asset. Always exactly one image file per logo: the filemanager
 * element caps maxfiles at 1, and logo_repository::create() throws if the draft area somehow
 * ends up with anything other than exactly one file.
 */
class logo_form extends \moodleform {
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('text', 'name', get_string('form_logoname', 'local_themerules'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        $mform->addElement('filemanager', 'logofile', get_string('form_logofile', 'local_themerules'), null, [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['web_image'],
        ]);
        $mform->addRule('logofile', get_string('required'), 'required', null, 'client');

        $this->add_action_buttons(true, get_string('form_save', 'local_themerules'));
    }
}
