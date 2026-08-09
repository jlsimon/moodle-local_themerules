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
 * "Set Moodle theme" action. See SPECIFICATIONS.md section 18.
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules\local\action;

use local_themerules\local\engine\evaluation_context;

/**
 * "Set Moodle theme" action. See SPECIFICATIONS.md section 18.
 */
class theme_action implements action_interface {
    public function get_identifier(): string {
        return 'theme';
    }

    public function validate(array $config): void {
        $theme = $config['theme'] ?? '';
        if ($theme === '' || !is_string($theme)) {
            throw new \coding_exception('local_themerules: theme action missing "theme"');
        }
        $installed = \core_component::get_plugin_list('theme');
        if (!array_key_exists($theme, $installed)) {
            throw new \coding_exception('local_themerules: theme not installed: ' . $theme);
        }
    }

    public function apply(array $config, evaluation_context $context): ?string {
        $theme = $config['theme'] ?? '';

        // Fail safe (SPECIFICATIONS.md section 18): a rule pointing at a theme that has since
        // been uninstalled must be ignored, not break the page.
        $installed = \core_component::get_plugin_list('theme');
        if (!is_string($theme) || $theme === '' || !array_key_exists($theme, $installed)) {
            debugging(
                'local_themerules: rule references a theme that is not installed: ' . $theme,
                DEBUG_DEVELOPER
            );
            return null;
        }

        return $theme;
    }
}
