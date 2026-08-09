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
 * Runs the resolver for a given evaluation_context and writes the outcome to
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules\local\engine;

/**
 * Runs the resolver for a given evaluation_context and writes the outcome to
 * $SESSION->theme (see DECISIONS.md for why $SESSION->theme, not
 * $PAGE->force_theme()). Shared by both integration tiers documented in
 * DECISIONS.md "Phase 2": \local_themerules\hook_listener (Tier A, runs on
 * every request but without course facts) and
 * local_themerules_after_require_login() in lib.php (Tier B, runs once a
 * real course is known and therefore takes precedence for that request).
 */
class theme_resolution {
    public static function apply(evaluation_context $context): void {
        global $SESSION;

        try {
            $theme = resolver::resolve_theme($context);

            if ($theme !== null) {
                $SESSION->theme = $theme;
            } else {
                unset($SESSION->theme);
            }
        } catch (\Throwable $e) {
            debugging('local_themerules: theme resolution failed, falling back to normal ' .
                'Moodle theme resolution: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}
