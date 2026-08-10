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
 * Upload form for a single logo asset, used for both create and edit (an "id" hidden field
 * distinguishes the two, same pattern as rule_form.php). Always exactly one image file per
 * logo: the filemanager element caps maxfiles at 1, and logo_repository::create()/update()
 * throw if the draft area somehow ends up with anything other than exactly one file.
 *
 * When editing, logos.php prefills the filemanager with the logo's existing file via
 * file_prepare_draft_area() (using file_options() below, so the prefill and the element always
 * agree on maxfiles/subdirs) - the admin can then either leave it as-is or replace it.
 */
class logo_form extends \moodleform {
    /**
     * Filemanager element options, shared between the element definition here and logos.php's
     * file_prepare_draft_area() call so both always agree on what "one logo image" allows.
     */
    public static function file_options(): array {
        return [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['web_image'],
        ];
    }

    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'name', get_string('form_logoname', 'local_themerules'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        $mform->addElement(
            'filemanager',
            'logofile',
            get_string('form_logofile', 'local_themerules'),
            null,
            self::file_options()
        );
        $mform->addRule('logofile', get_string('required'), 'required', null, 'client');

        $this->add_action_buttons(true, get_string('form_save', 'local_themerules'));
    }
}
