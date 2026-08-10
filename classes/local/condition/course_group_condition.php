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
 * "User is/is not a member of course group <groupid>" condition.
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules\local\condition;

use local_themerules\local\engine\evaluation_context;

/**
 * "User is/is not a member of course group <groupid>" condition.
 *
 * Mirrors Moodle's own `availability_group` (Restrict Access's "Group" condition): a group id of
 * `0` means "any group in the course" (member of at least one), matching that same special case
 * rather than inventing a different one. Deliberately does not mirror
 * `availability_group::is_available()`'s `moodle/site:accessallgroups` bypass (a user with that
 * capability satisfies the Restrict Access group condition regardless of actual membership) -
 * that bypass exists because Restrict Access controls *visibility of content* to someone who
 * needs to manage/grade it either way, whereas this plugin picks *branding* from facts about the
 * user, so a manager's theme should reflect their actual group membership, not a capability-based
 * exemption. Moodle's "grouping" concept (a named collection of groups) is out of scope, same as
 * `profilefield` deliberately scoping out `availability_profile`'s full operator set - see
 * DECISIONS.md.
 *
 * Only resolvable once a real course is known (see fact_provider/evaluation_context), same
 * constraint as course_condition/course_category_condition/course_tag_condition -
 * DECISIONS.md "Phase 2".
 */
class course_group_condition implements condition_interface {
    /** @var string[] Operators this condition currently accepts. */
    const OPERATORS = ['member', 'not_member'];

    public function get_name(): string {
        return get_string('condition_coursegroup', 'local_themerules');
    }

    public function get_identifier(): string {
        return 'coursegroup';
    }

    public function validate(array $config): void {
        if (!in_array($config['operator'] ?? null, self::OPERATORS, true)) {
            throw new \coding_exception('local_themerules: unknown operator for coursegroup condition: ' .
                ($config['operator'] ?? ''));
        }
        if (!is_numeric($config['value'] ?? null) || (int) $config['value'] < 0) {
            throw new \coding_exception(
                'local_themerules: coursegroup condition value must be a non-negative group id (0 = any group)'
            );
        }
    }

    public function evaluate(array $config, evaluation_context $context): bool {
        if ($context->get_courseid() === null) {
            // No real course known for this request yet (SPECIFICATIONS.md section 11 / DECISIONS.md "Phase 2").
            return false;
        }

        $groupid = (int) $config['value'];
        $groupids = $context->get_coursegroupids();
        $ismember = $groupid === 0 ? !empty($groupids) : in_array($groupid, $groupids, true);

        return $config['operator'] === 'member' ? $ismember : !$ismember;
    }

    public function get_editor_schema(): array {
        return [
            'identifier' => $this->get_identifier(),
            'name' => $this->get_name(),
            'operators' => self::OPERATORS,
            'valuetype' => 'coursegroup',
        ];
    }
}
