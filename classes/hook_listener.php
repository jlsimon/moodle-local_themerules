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
 * Tier A integration point (see DECISIONS.md "Phase 2"): listens on
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules;

use local_themerules\local\engine\fact_provider;
use local_themerules\local\engine\session_resolution;

/**
 * Tier A integration point (see DECISIONS.md "Phase 2"): listens on
 * \core\hook\after_config, the earliest point in the request lifecycle
 * where $PAGE exists and $USER/$SESSION are populated, but before any real
 * course is known. Runs on every request, with user/cohort facts only.
 *
 * On requests where require_login($course) later runs with a real course,
 * lib.php's local_themerules_after_require_login() (Tier B) re-resolves
 * with full course/category facts and overrides the decision made here -
 * it is a strict superset of what this listener can see, so it always wins
 * when it fires.
 *
 * $PAGE->force_theme() was tried first and rejected: at after_config time
 * $PAGE->course has not been set yet (require_login() runs later, inside
 * the page script, and calls moodle_page::set_course(), which itself calls
 * ensure_theme_not_set()). Locking the theme this early with force_theme()
 * made every later require_login($course) call throw a coding_exception
 * ("The theme has already been set up for this page ready for output"),
 * breaking virtually every authenticated page. Confirmed live against the
 * test platform - see DECISIONS.md.
 */
class hook_listener {
    /**
     * Resolves Tier A theme/logo facts (user/cohort only, no course yet) on every request.
     *
     * @param \core\hook\after_config $hook
     */
    public static function after_config(\core\hook\after_config $hook): void {
        if (CLI_SCRIPT || (defined('WS_SERVER') && WS_SERVER) || during_initial_install()) {
            return;
        }

        session_resolution::apply(fact_provider::create_for_current_user());
    }

    /**
     * Injects a CSS override for the navbar logo when a `logo` rule resolved this request
     * (see DECISIONS.md). Moodle has no per-request logo resolution slot equivalent to
     * $CFG->theme's $SESSION->theme override (the site logo is a single global core_admin
     * config value - `renderer_base::get_compact_logo_url()`), so this plugin cannot reuse the
     * theme mechanism; instead it targets the `<img class="logo">` element every Boost-family
     * theme's navbar template renders (verified against theme/boost/templates/navbar.mustache)
     * via CSS `content: url(...)`, which swaps the displayed image without needing a renderer
     * override for every individual theme.
     *
     * @param \core\hook\output\before_standard_head_html_generation $hook
     */
    public static function before_standard_head_html_generation(
        \core\hook\output\before_standard_head_html_generation $hook
    ): void {
        global $DB, $SESSION;

        if (empty($SESSION->themerules_logo)) {
            return;
        }

        // Only queried on requests where a logo rule actually resolved (the common case, no
        // session_resolution logo match, returns above before touching the DB) - see
        // local_themerules_logo.filename's docblock in db/install.xml for why the filename
        // itself (not just the id) is needed to build a working pluginfile URL.
        $logoid = (int) $SESSION->themerules_logo;
        $filename = $DB->get_field('local_themerules_logo', 'filename', ['id' => $logoid]);
        if ($filename === false) {
            // Deleted between session_resolution::apply() and this request's head rendering
            // (e.g. an admin removed the logo mid-session): nothing to override with.
            return;
        }

        $url = \moodle_url::make_pluginfile_url(
            \context_system::instance()->id,
            'local_themerules',
            'logo',
            $logoid,
            '/',
            $filename
        );

        $hook->add_html(\html_writer::tag(
            'style',
            'img.logo { content: url("' . $url->out(false) . '") !important; }'
        ));
    }
}
