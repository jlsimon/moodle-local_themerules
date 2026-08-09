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
 * Tier B integration point (see DECISIONS.md "Phase 2"): legacy plugin callback,
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function local_themerules_after_require_login(
    $courseorid = null,
    $autologinguest = null,
    $cm = null,
    $setwantsurltome = null,
    $preventredirect = null
): void {

    global $USER;

    if (CLI_SCRIPT || (defined('WS_SERVER') && WS_SERVER) || during_initial_install() || empty($courseorid)) {
        return;
    }

    try {
        $course = is_object($courseorid) ? $courseorid : get_course((int) $courseorid);
    } catch (\Throwable $e) {
        // Non-existent course id: leave Tier A's decision (if any) in place rather than failing.
        debugging(
            'local_themerules: could not load course for after_require_login: ' . $e->getMessage(),
            DEBUG_DEVELOPER
        );
        return;
    }

    \local_themerules\local\engine\theme_resolution::apply(
        \local_themerules\local\engine\fact_provider::create_for_course((int) ($USER->id ?? 0), $course)
    );
}
