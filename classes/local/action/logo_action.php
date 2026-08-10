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
 * "Set navbar logo" action. See DECISIONS.md for the design (independent axis from theme).
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules\local\action;

use local_themerules\local\engine\evaluation_context;

/**
 * "Set navbar logo" action.
 *
 * Unlike theme_action, apply() returns the logo asset's id (as a string, per the
 * action_interface contract), not the logo itself - session_resolution turns that id into an
 * actual pluginfile URL only once, at the point it writes $SESSION->themerules_logo, so this
 * class stays a thin "does this reference still exist" check rather than duplicating URL-building
 * logic that belongs with the rest of the file-serving code (lib.php's pluginfile callback,
 * hook_listener's CSS injection).
 */
class logo_action implements action_interface {
    /**
     * Identifier used in action JSON: "logo".
     *
     * @return string
     */
    public function get_identifier(): string {
        return 'logo';
    }

    /**
     * Validates an action node's "logoid", throwing on error.
     *
     * @param array $config
     */
    public function validate(array $config): void {
        $logoid = $config['logoid'] ?? null;
        if (!is_numeric($logoid) || (int) $logoid <= 0) {
            throw new \coding_exception('local_themerules: logo action missing/invalid "logoid"');
        }

        global $DB;
        if (!$DB->record_exists('local_themerules_logo', ['id' => (int) $logoid])) {
            throw new \coding_exception('local_themerules: logo not found: ' . $logoid);
        }
    }

    /**
     * Resolves the logo axis to a logo asset id, or null if it no longer exists.
     *
     * @param array $config
     * @param evaluation_context $context
     * @return string|null
     */
    public function apply(array $config, evaluation_context $context): ?string {
        global $DB;

        $logoid = (int) ($config['logoid'] ?? 0);

        // Fail safe (mirrors theme_action): a rule pointing at a logo that has since been
        // deleted must be ignored, not break the page.
        if ($logoid <= 0 || !$DB->record_exists('local_themerules_logo', ['id' => $logoid])) {
            debugging(
                'local_themerules: rule references a logo that no longer exists: ' . $logoid,
                DEBUG_DEVELOPER
            );
            return null;
        }

        return (string) $logoid;
    }
}
