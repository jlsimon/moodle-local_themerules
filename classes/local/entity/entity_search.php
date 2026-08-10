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
 * Search/resolve support for the rule editor's entity pickers (user/course/coursegroup).
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules\local\entity;

/**
 * Search/resolve support for the rule editor's entity pickers.
 *
 * Deliberately a plain class rather than logic embedded in the external_api classes
 * (`classes/external/search_entities.php`/`resolve_entity.php`): keeps the actual query logic
 * directly unit-testable without going through the external API call machinery, same split as
 * `simulate.php`/`simulator.php` elsewhere in this plugin. Also deliberately independent of
 * `simulator.php`'s own private `user_name()`/`course_name()`/`group_name()` helpers - those are
 * diagnostics-scoped and private by design; this is a fresh, small implementation for a different
 * caller, not a shared one, matching this project's established preference for small independent
 * copies over forcing an artificial shared dependency (see `decode_actions()`'s docblock
 * elsewhere in this codebase for the same reasoning applied previously).
 *
 * Limited to the three entity types the visual rule editor actually needs a picker for
 * (`user`/`course`/`coursegroup`) - `cohort`/`coursecategory` are small enough lists to render as
 * a plain populated `<select>` instead (see their `get_editor_schema()`), no search needed.
 */
class entity_search {
    /** @var string[] Entity types this class knows how to search/resolve. */
    const TYPES = ['user', 'course', 'coursegroup'];

    /** Maximum rows returned by a search, generous enough for a human to scan a dropdown. */
    const LIMIT = 20;

    /**
     * Searches for entities of the given type matching a free-text query. An empty query returns
     * the first page ordered alphabetically, so the picker is browsable, not just searchable.
     *
     * @return array{value: int, label: string}[]
     */
    public static function search(string $entitytype, string $query, int $courseid = 0): array {
        switch ($entitytype) {
            case 'user':
                return self::search_users($query);

            case 'course':
                return self::search_courses($query);

            case 'coursegroup':
                return $courseid > 0 ? self::search_groups($courseid, $query) : [];

            default:
                throw new \coding_exception('local_themerules: unknown entity type for search: ' . $entitytype);
        }
    }

    /**
     * Resolves a single id to a display label, for pre-filling the picker when a rule with an
     * existing value is opened for editing. Returns null if the entity no longer exists (a
     * deleted user/course/group a rule still references - SPECIFICATIONS.md section 50).
     *
     * @return array{value: int, label: string, courseid?: int, coursename?: string}|null
     */
    public static function resolve(string $entitytype, int $id): ?array {
        global $DB;

        switch ($entitytype) {
            case 'user':
                $user = $DB->get_record('user', ['id' => $id, 'deleted' => 0], 'id, ' .
                    implode(', ', \core_user\fields::get_name_fields()), IGNORE_MISSING);
                return $user ? ['value' => $id, 'label' => self::user_label($user)] : null;

            case 'course':
                $course = $DB->get_record('course', ['id' => $id], 'id, fullname', IGNORE_MISSING);
                return $course ? ['value' => $id, 'label' => self::course_label($course)] : null;

            case 'coursegroup':
                $group = $DB->get_record('groups', ['id' => $id], 'id, courseid, name', IGNORE_MISSING);
                if (!$group) {
                    return null;
                }
                $course = $DB->get_record('course', ['id' => $group->courseid], 'id, fullname', IGNORE_MISSING);
                return [
                    'value' => $id,
                    'label' => self::group_label($group),
                    'courseid' => (int) $group->courseid,
                    'coursename' => $course ? self::course_label($course) : (string) $group->courseid,
                ];

            default:
                throw new \coding_exception('local_themerules: unknown entity type for resolve: ' . $entitytype);
        }
    }

    /**
     * Matches on firstname/lastname/email.
     *
     * @return array{value: int, label: string}[]
     */
    private static function search_users(string $query): array {
        global $DB, $CFG;

        $params = ['guestid' => $CFG->siteguest];
        $where = 'deleted = 0 AND id <> :guestid';

        $query = trim($query);
        if ($query !== '') {
            $like = '%' . $DB->sql_like_escape($query) . '%';
            $where .= ' AND (' . $DB->sql_like('firstname', ':queryfirst', false, false) . ' OR ' .
                $DB->sql_like('lastname', ':querylast', false, false) . ' OR ' .
                $DB->sql_like('email', ':queryemail', false, false) . ')';
            $params += ['queryfirst' => $like, 'querylast' => $like, 'queryemail' => $like];
        }

        $users = $DB->get_records_select(
            'user',
            $where,
            $params,
            'firstname, lastname',
            'id, ' . implode(', ', \core_user\fields::get_name_fields()) . ', email',
            0,
            self::LIMIT
        );

        return array_values(array_map(
            fn (\stdClass $user): array => ['value' => (int) $user->id, 'label' => self::user_label($user)],
            $users
        ));
    }

    /**
     * Matches on fullname/shortname, excluding the site course.
     *
     * @return array{value: int, label: string}[]
     */
    private static function search_courses(string $query): array {
        global $DB;

        $params = ['siteid' => SITEID];
        $where = 'id <> :siteid';

        $query = trim($query);
        if ($query !== '') {
            $like = $DB->sql_like('fullname', ':query1', false, false) . ' OR ' .
                $DB->sql_like('shortname', ':query2', false, false);
            $where .= " AND ({$like})";
            $params['query1'] = '%' . $DB->sql_like_escape($query) . '%';
            $params['query2'] = '%' . $DB->sql_like_escape($query) . '%';
        }

        $courses = $DB->get_records_select(
            'course',
            $where,
            $params,
            'fullname',
            'id, fullname',
            0,
            self::LIMIT
        );

        return array_values(array_map(
            fn (\stdClass $course): array => ['value' => (int) $course->id, 'label' => self::course_label($course)],
            $courses
        ));
    }

    /**
     * Matches on name, scoped to one course.
     *
     * @return array{value: int, label: string}[]
     */
    private static function search_groups(int $courseid, string $query): array {
        global $DB;

        $params = ['courseid' => $courseid];
        $where = 'courseid = :courseid';

        $query = trim($query);
        if ($query !== '') {
            $where .= ' AND ' . $DB->sql_like('name', ':query', false, false);
            $params['query'] = '%' . $DB->sql_like_escape($query) . '%';
        }

        $groups = $DB->get_records_select('groups', $where, $params, 'name', 'id, courseid, name', 0, self::LIMIT);

        return array_values(array_map(
            fn (\stdClass $group): array => ['value' => (int) $group->id, 'label' => self::group_label($group)],
            $groups
        ));
    }

    private static function user_label(\stdClass $user): string {
        return fullname($user) . " (id {$user->id})";
    }

    private static function course_label(\stdClass $course): string {
        return format_string($course->fullname) . " (id {$course->id})";
    }

    private static function group_label(\stdClass $group): string {
        return format_string($group->name) . " (id {$group->id})";
    }
}
