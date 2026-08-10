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
 * Resolves a single entity id to a display label, for the rule editor's entity pickers.
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use context_system;
use local_themerules\local\entity\entity_search;

/**
 * Resolves a single entity id to a display label, for pre-filling an entity picker with a
 * saved rule's existing value when the rule editor loads - the JSON only ever stores the raw
 * id (SPECIFICATIONS.md: the textarea is the real data channel), so the picker needs one lookup
 * per entity-typed value present in the rule being edited to show a human label instead of a
 * bare number on first paint.
 */
class resolve_entity extends external_api {
    /**
     * Parameter definition for execute().
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'entitytype' => new external_value(PARAM_ALPHA, 'One of: ' . implode(', ', entity_search::TYPES)),
            'id' => new external_value(PARAM_INT, 'Entity id to resolve'),
        ]);
    }

    /**
     * Resolves one entity id to a label (and, for "coursegroup", its owning course).
     *
     * @param string $entitytype
     * @param int $id
     * @return array{found: bool, value: int, label: string, courseid: int, coursename: string}
     */
    public static function execute(string $entitytype, int $id): array {
        [
            'entitytype' => $entitytype,
            'id' => $id,
        ] = self::validate_parameters(self::execute_parameters(), ['entitytype' => $entitytype, 'id' => $id]);

        self::validate_context(context_system::instance());
        require_capability('local/themerules:manage', context_system::instance());

        if (!in_array($entitytype, entity_search::TYPES, true)) {
            throw new \invalid_parameter_exception('Unknown entity type: ' . $entitytype);
        }

        $resolved = entity_search::resolve($entitytype, $id);

        // A deleted user/course/group a rule still references must not break the editor
        // (SPECIFICATIONS.md section 50) - "not found" is a valid, expected result, not an error.
        if ($resolved === null) {
            return ['found' => false, 'value' => $id, 'label' => '', 'courseid' => 0, 'coursename' => ''];
        }

        return [
            'found' => true,
            'value' => $resolved['value'],
            'label' => $resolved['label'],
            'courseid' => $resolved['courseid'] ?? 0,
            'coursename' => $resolved['coursename'] ?? '',
        ];
    }

    /**
     * Return definition for execute().
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'found' => new external_value(PARAM_BOOL, 'Whether the entity still exists'),
            'value' => new external_value(PARAM_INT, 'Entity id (echoed back)'),
            'label' => new external_value(PARAM_RAW, 'Human-readable display label, empty if not found'),
            'courseid' => new external_value(PARAM_INT, 'Owning course id, only set for entitytype "coursegroup"'),
            'coursename' => new external_value(PARAM_RAW, 'Owning course label, only set for entitytype "coursegroup"'),
        ]);
    }
}
