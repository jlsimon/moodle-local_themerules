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
 * "Course category is <categoryid>[, including descendants]" condition.
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules\local\condition;

use local_themerules\local\engine\evaluation_context;

/**
 * "Course category is <categoryid>[, including descendants]" condition.
 * See SPECIFICATIONS.md section 12.
 */
class course_category_condition implements condition_interface {
    /** @var string[] Operators this condition currently accepts. */
    const OPERATORS = ['in_category'];

    /**
     * Human-readable name for this condition (course category membership), shown in the editor's condition dropdown.
     */
    public function get_name(): string {
        return get_string('condition_coursecategory', 'local_themerules');
    }

    /**
     * Identifier used in expression JSON, e.g. {"condition": "..."}.
     */
    public function get_identifier(): string {
        return 'coursecategory';
    }

    /**
     * Validates a condition node's operator/value, throwing on error.
     *
     * @param array $config
     */
    public function validate(array $config): void {
        if (!in_array($config['operator'] ?? null, self::OPERATORS, true)) {
            throw new \coding_exception('local_themerules: unknown operator for coursecategory condition: ' .
                ($config['operator'] ?? ''));
        }
        if (!is_numeric($config['value'] ?? null)) {
            throw new \coding_exception('local_themerules: coursecategory condition value must be a category id');
        }
        if (array_key_exists('includechildren', $config) && !is_bool($config['includechildren'])) {
            throw new \coding_exception('local_themerules: coursecategory condition "includechildren" must be boolean');
        }
    }

    /**
     * Whether this condition (course category membership) holds for the given facts.
     *
     * @param array $config
     * @param evaluation_context $context
     */
    public function evaluate(array $config, evaluation_context $context): bool {
        if ($context->get_coursecategoryid() === null) {
            // No real course/category known for this request yet (DECISIONS.md "Phase 2").
            return false;
        }

        $target = (int) $config['value'];

        if (!empty($config['includechildren'])) {
            return in_array($target, $context->get_coursecategorypath(), true);
        }

        return $context->get_coursecategoryid() === $target;
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
            'valuetype' => 'coursecategory',
            'supportsincludechildren' => true,
            // A real, populated <select> rather than a raw id input - see cohort_condition's
            // schema for the same reasoning (small, browsable list, no search needed).
            'options' => self::category_options(),
            'numericvalue' => true,
        ];
    }

    /**
     * Every course category, labelled with its full ancestry path so two categories with the
     * same name under different parents (e.g. two clients each with their own "IT" category)
     * are still distinguishable in the dropdown.
     *
     * @return array{value: int, label: string}[]
     */
    private static function category_options(): array {
        $options = [];
        foreach (\core_course_category::make_categories_list() as $id => $path) {
            $options[] = ['value' => (int) $id, 'label' => $path];
        }
        return $options;
    }
}
