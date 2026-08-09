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
 * Event triggered when an administrator disables a theme rule.
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules\event;

/**
 * Event triggered when an administrator disables a theme rule.
 */
class rule_disabled extends rule_event_base {
    protected function init(): void {
        parent::init();
        $this->data['crud'] = 'u';
    }

    public static function get_name(): string {
        return get_string('event_rule_disabled', 'local_themerules');
    }

    public function get_description(): string {
        return "The user with id '{$this->userid}' disabled the theme rule " .
            "'{$this->other['rulename']}' (id '{$this->objectid}').";
    }
}
