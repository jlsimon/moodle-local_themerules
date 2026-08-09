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
 * Privacy Subsystem implementation for local_themerules.
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules\privacy;

/**
 * Privacy Subsystem implementation for local_themerules.
 *
 * See SPECIFICATIONS.md section 33: the only user reference stored is
 * `usermodified` on local_themerules_rule - the administrator who last
 * created/edited a rule, an audit trail of a configuration action, not
 * personal data about that user being processed by the plugin. No other
 * user-identifying data is stored (condition values are opaque numeric ids
 * of users/courses/cohorts referenced *by* rules, not data *about* any
 * particular user collected by this plugin). null_provider is therefore the
 * correct declaration - see get_string('privacy:metadata', ...) for the
 * admin-facing explanation.
 */
class provider implements \core_privacy\local\metadata\null_provider {
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
