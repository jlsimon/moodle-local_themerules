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
 * "Course is <courseid>" condition. See SPECIFICATIONS.md section 14.
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules\local\condition;

use local_themerules\local\engine\evaluation_context;

/**
 * "Course is <courseid>" condition. See SPECIFICATIONS.md section 14.
 */
class course_condition implements condition_interface {
    /** @var string[] Operators this condition currently accepts. */
    const OPERATORS = ['is'];

    /**
     * Human-readable name for this condition (the current course), shown in the editor's condition dropdown.
     */
    public function get_name(): string {
        return get_string('condition_course', 'local_themerules');
    }

    /**
     * Identifier used in expression JSON, e.g. {"condition": "..."}.
     */
    public function get_identifier(): string {
        return 'course';
    }

    /**
     * Validates a condition node's operator/value, throwing on error.
     *
     * @param array $config
     */
    public function validate(array $config): void {
        if (!in_array($config['operator'] ?? null, self::OPERATORS, true)) {
            throw new \coding_exception('local_themerules: unknown operator for course condition: ' .
                ($config['operator'] ?? ''));
        }
        if (!is_numeric($config['value'] ?? null)) {
            throw new \coding_exception('local_themerules: course condition value must be a course id');
        }
    }

    /**
     * Whether this condition (the current course) holds for the given facts.
     *
     * @param array $config
     * @param evaluation_context $context
     */
    public function evaluate(array $config, evaluation_context $context): bool {
        if ($context->get_courseid() === null) {
            // No real course known for this request yet (SPECIFICATIONS.md section 11 / DECISIONS.md "Phase 2").
            return false;
        }

        return $context->get_courseid() === (int) $config['value'];
    }

    /**
     * Editor schema consumed by the JS rule builder.
     *
     * @return array
     */
    public function get_editor_schema(): array {
        return [
            'identifier' => $this->get_identifier(),
            'name' => $this->get_name(),
            'operators' => self::OPERATORS,
            'valuetype' => 'course',
            // Type-ahead search picker, same reasoning as user_condition's - a site can have far
            // too many courses for a plain <select>.
            'entitytype' => 'course',
        ];
    }
}
