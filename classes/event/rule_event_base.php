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
 * Shared behaviour for the five administrative rule events (section 34):
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules\event;

/**
 * Shared behaviour for the five administrative rule events (section 34):
 * rule_created, rule_updated, rule_deleted, rule_enabled, rule_disabled.
 * These are configuration-change events (LEVEL_OTHER, not course activity),
 * fired only on admin writes, never during runtime theme evaluation
 * (section 34: "Avoid firing runtime events on every page/theme evaluation").
 */
abstract class rule_event_base extends \core\event\base {
    protected function init(): void {
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'local_themerules_rule';
    }

    public function get_url(): \moodle_url {
        return new \moodle_url('/local/themerules/index.php');
    }
}
