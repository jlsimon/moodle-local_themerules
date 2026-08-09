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
 * Contract every rule action type (currently only "theme") implements. See
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules\local\action;

use local_themerules\local\engine\evaluation_context;

/**
 * Contract every rule action type (currently only "theme") implements. See
 * SPECIFICATIONS.md section 18.
 */
interface action_interface {
    /**
     * Identifier used in action JSON, e.g. "theme".
     */
    public function get_identifier(): string;

    /**
     * Validate an action node's configuration, throwing on error.
     *
     * @param array $config The action node (type, ...).
     * @throws \coding_exception
     */
    public function validate(array $config): void;

    /**
     * Applies this action for the given facts.
     *
     * @param array $config The action node (type, ...).
     * @return string|null The theme to use, or null if this action does not select a theme.
     */
    public function apply(array $config, evaluation_context $context): ?string;
}
