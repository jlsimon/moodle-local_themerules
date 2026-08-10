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
 * "Course has/does not have tag <name>" condition.
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules\local\condition;

use local_themerules\local\engine\evaluation_context;

/**
 * "Course has/does not have tag <name>" condition.
 *
 * Unlike coursecategory/cohort, tags are an open-ended admin-typed value (there is no fixed id
 * to reference), so the value is a tag name rather than an id - matched via the same
 * \core_tag_tag::normalize() Moodle itself uses for tag lookups, so "Exam Mode", "exam mode" and
 * "exam-mode " all match the same tag regardless of how the admin typed it here versus how it
 * was typed when tagging the course.
 *
 * Only resolvable once a real course is known (see fact_provider/evaluation_context), same
 * constraint as course_condition/course_category_condition - DECISIONS.md "Phase 2".
 */
class course_tag_condition implements condition_interface {
    /** @var string[] Operators this condition currently accepts. */
    const OPERATORS = ['has', 'not_has'];

    public function get_name(): string {
        return get_string('condition_coursetag', 'local_themerules');
    }

    public function get_identifier(): string {
        return 'coursetag';
    }

    public function validate(array $config): void {
        if (!in_array($config['operator'] ?? null, self::OPERATORS, true)) {
            throw new \coding_exception('local_themerules: unknown operator for coursetag condition: ' .
                ($config['operator'] ?? ''));
        }
        if (trim((string) ($config['value'] ?? '')) === '') {
            throw new \coding_exception('local_themerules: coursetag condition value must be a non-empty tag name');
        }
    }

    public function evaluate(array $config, evaluation_context $context): bool {
        if ($context->get_courseid() === null) {
            // No real course known for this request yet (SPECIFICATIONS.md section 11 / DECISIONS.md "Phase 2").
            return false;
        }

        $normalized = self::normalize_tag((string) $config['value']);
        $has = in_array($normalized, $context->get_coursetags(), true);

        return $config['operator'] === 'has' ? $has : !$has;
    }

    public function get_editor_schema(): array {
        return [
            'identifier' => $this->get_identifier(),
            'name' => $this->get_name(),
            'operators' => self::OPERATORS,
            'valuetype' => 'coursetag',
        ];
    }

    /**
     * Normalizes a raw tag name the same way \core_tag_tag does, so admin-typed values match
     * regardless of case/whitespace/punctuation differences from how the tag was actually created.
     */
    public static function normalize_tag(string $rawtag): string {
        $normalized = \core_tag_tag::normalize([$rawtag]);

        return $normalized ? reset($normalized) : '';
    }
}
