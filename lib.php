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

    \local_themerules\local\engine\session_resolution::apply(
        \local_themerules\local\engine\fact_provider::create_for_course((int) ($USER->id ?? 0), $course)
    );
}

/**
 * Serves an uploaded logo asset (component local_themerules, filearea "logo" - see
 * db/install.xml's local_themerules_logo table and hook_listener::before_standard_head_html_generation()).
 *
 * No require_login() call, deliberately: the logo is shown in the navbar of every page,
 * including to guests and unauthenticated visitors on the login page, so it must be servable
 * without a session - same treatment as core_admin_pluginfile()'s own site logo/logocompact
 * fileareas (admin/lib.php), which carries the identical "anyone, including guests, can view
 * the logos" comment. This is a system-context file area, so file_pluginfile() (lib/filelib.php)
 * dispatches straight to this function with no access check of its own.
 *
 * @param stdClass|null $course
 * @param stdClass|null $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 */
function local_themerules_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel !== CONTEXT_SYSTEM || $filearea !== 'logo') {
        send_file_not_found();
    }

    $itemid = (int) array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_themerules', 'logo', $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        send_file_not_found();
    }

    send_stored_file($file, DAYSECS, 0, $forcedownload, ['cacheability' => 'public']);
}
