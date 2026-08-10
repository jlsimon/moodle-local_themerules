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
 * "User is/is not a member of cohort <cohortid>" condition. See SPECIFICATIONS.md section 15.
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules\local\condition;

use local_themerules\local\engine\evaluation_context;

/**
 * "User is/is not a member of cohort <cohortid>" condition. See SPECIFICATIONS.md section 15.
 */
class cohort_condition implements condition_interface {
    /** @var string[] Operators this condition currently accepts. */
    const OPERATORS = ['member', 'not_member'];

    public function get_name(): string {
        return get_string('condition_cohort', 'local_themerules');
    }

    public function get_identifier(): string {
        return 'cohort';
    }

    public function validate(array $config): void {
        if (!in_array($config['operator'] ?? null, self::OPERATORS, true)) {
            throw new \coding_exception('local_themerules: unknown operator for cohort condition: ' .
                ($config['operator'] ?? ''));
        }
        if (!is_numeric($config['value'] ?? null)) {
            throw new \coding_exception('local_themerules: cohort condition value must be a cohort id');
        }
    }

    public function evaluate(array $config, evaluation_context $context): bool {
        $ismember = in_array((int) $config['value'], $context->get_cohortids(), true);

        return $config['operator'] === 'member' ? $ismember : !$ismember;
    }

    public function get_editor_schema(): array {
        return [
            'identifier' => $this->get_identifier(),
            'name' => $this->get_name(),
            'operators' => self::OPERATORS,
            'valuetype' => 'cohort',
            // A real, populated <select> rather than a raw id input or a search picker - sites
            // typically have a small, browsable number of cohorts (unlike user/course), so a
            // plain dropdown of everything this site actually has is simpler than adding search.
            'options' => self::cohort_options(),
            // A <select>'s value is always a string in the DOM; this condition's value must stay
            // a JSON number (validate() uses is_numeric(), evaluate() casts with (int)) - tells
            // the JS builder to parseInt() on selection instead of storing the raw string, the
            // same concern stringvalue solves in the opposite direction for profilefield.
            'numericvalue' => true,
        ];
    }

    /**
     * Every real cohort on this site, for the editor's populated <select>.
     *
     * @return array{value: int, label: string}[]
     */
    private static function cohort_options(): array {
        global $DB;

        $cohorts = $DB->get_records('cohort', null, 'name', 'id, name');

        return array_values(array_map(
            fn (\stdClass $cohort): array => ['value' => (int) $cohort->id, 'label' => format_string($cohort->name)],
            $cohorts
        ));
    }
}
