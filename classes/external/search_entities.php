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
 * AJAX search for the rule editor's entity pickers (user/course/coursegroup).
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use context_system;
use local_themerules\local\entity\entity_search;

/**
 * AJAX search for the rule editor's entity pickers, backing the type-ahead widgets that replace
 * a raw numeric id text input for `user`/`course`/`coursegroup` condition values. See
 * DECISIONS.md for why only these three condition types get a search-backed picker (`cohort`/
 * `coursecategory` are small enough to render as a plain populated `<select>` instead).
 */
class search_entities extends external_api {
    /**
     * Parameter definition for execute().
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'entitytype' => new external_value(PARAM_ALPHA, 'One of: ' . implode(', ', entity_search::TYPES)),
            'query' => new external_value(PARAM_RAW, 'Free-text search query, empty for the first page', VALUE_DEFAULT, ''),
            'courseid' => new external_value(
                PARAM_INT,
                'Course id to scope the search to - required for entitytype "coursegroup", ignored otherwise',
                VALUE_DEFAULT,
                0
            ),
        ]);
    }

    /**
     * Searches for entities of the given type matching the query.
     *
     * @param string $entitytype
     * @param string $query
     * @param int $courseid
     * @return array{value: int, label: string}[]
     */
    public static function execute(string $entitytype, string $query = '', int $courseid = 0): array {
        [
            'entitytype' => $entitytype,
            'query' => $query,
            'courseid' => $courseid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'entitytype' => $entitytype,
            'query' => $query,
            'courseid' => $courseid,
        ]);

        self::validate_context(context_system::instance());
        require_capability('local/themerules:manage', context_system::instance());

        if (!in_array($entitytype, entity_search::TYPES, true)) {
            throw new \invalid_parameter_exception('Unknown entity type: ' . $entitytype);
        }

        return entity_search::search($entitytype, $query, $courseid);
    }

    /**
     * Return definition for execute().
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(new external_single_structure([
            'value' => new external_value(PARAM_INT, 'Entity id'),
            'label' => new external_value(PARAM_RAW, 'Human-readable display label'),
        ]));
    }
}
