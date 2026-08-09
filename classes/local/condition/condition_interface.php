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
 * Contract every rule condition type (user, course, coursecategory, cohort, ...) implements.
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules\local\condition;

use local_themerules\local\engine\evaluation_context;

/**
 * Contract every rule condition type (user, course, coursecategory, cohort, ...) implements.
 * See SPECIFICATIONS.md section 10.
 */
interface condition_interface {
    /**
     * Human-readable name for the rule editor UI.
     */
    public function get_name(): string;

    /**
     * Identifier used in expression JSON, e.g. "user".
     */
    public function get_identifier(): string;

    /**
     * Validate a condition node's configuration, throwing on error.
     *
     * @param array $config The condition node (type, condition, operator, value, ...).
     * @throws \coding_exception
     */
    public function validate(array $config): void;

    /**
     * Whether this condition holds for the given facts.
     *
     * @param array $config The condition node (type, condition, operator, value, ...).
     */
    public function evaluate(array $config, evaluation_context $context): bool;

    /**
     * Editor schema consumed by the (future) JS rule builder.
     */
    public function get_editor_schema(): array;
}
