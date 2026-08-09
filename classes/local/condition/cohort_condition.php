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
        ];
    }
}
