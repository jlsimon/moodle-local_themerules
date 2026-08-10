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
 * Builds an evaluation_context from Moodle data. See SPECIFICATIONS.md section 11.
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules\local\engine;

/**
 * Builds an evaluation_context from Moodle data. See SPECIFICATIONS.md section 11.
 *
 * Two entry points, matching the two integration tiers documented in DECISIONS.md
 * "Phase 2": create_for_current_user() is usable everywhere (no course required),
 * create_for_course() additionally resolves real course/category facts and is only
 * called where a real course is actually known (see lib.php's
 * local_themerules_after_require_login()).
 */
class fact_provider {
    /**
     * User + cohort facts only. Safe to call before any course is known.
     */
    public static function create_for_current_user(): evaluation_context {
        global $USER;

        return self::create_for_user_and_course((int) ($USER->id ?? 0), null);
    }

    /**
     * Full facts, including the real course/category. Only call this once the real
     * course for the current page is known (i.e. after require_login($course)).
     */
    public static function create_for_course(int $userid, \stdClass $course): evaluation_context {
        return self::create_for_user_and_course($userid, $course);
    }

    /**
     * General-purpose entry point, also used by the simulator (section 31) to build a
     * context for an arbitrary user/course pair rather than the current session's.
     *
     * @param string|null $devicetype Override for the simulator (SPECIFICATIONS.md section 31),
     *        one of \core_useragent::DEVICETYPE_*. Null (the default, and always the case for
     *        Tier A/B production callers) means "use the real current request's device".
     */
    public static function create_for_user_and_course(
        int $userid,
        ?\stdClass $course,
        ?string $devicetype = null
    ): evaluation_context {
        $devicetype ??= \core_useragent::get_user_device_type();

        if ($course === null) {
            return new evaluation_context($userid, null, null, [], self::get_cohort_ids($userid), $devicetype);
        }

        $categorypath = self::get_category_path((int) ($course->category ?? 0));
        $coursecategoryid = empty($categorypath) ? null : end($categorypath);

        return new evaluation_context(
            $userid,
            (int) $course->id,
            $coursecategoryid,
            $categorypath,
            self::get_cohort_ids($userid),
            $devicetype,
            self::get_course_tags((int) $course->id),
            self::get_course_group_ids($userid, (int) $course->id)
        );
    }

    /**
     * The ids of every cohort the given user is a member of.
     *
     * @return int[]
     */
    private static function get_cohort_ids(int $userid): array {
        global $DB;

        if (empty($userid)) {
            return [];
        }

        return array_values(array_map('intval', $DB->get_fieldset_select(
            'cohort_members',
            'cohortid',
            'userid = :userid',
            ['userid' => $userid]
        )));
    }

    /**
     * The ids of every group the user belongs to within the given course.
     *
     * Deliberately a direct query against {groups}/{groups_members}, not core's
     * groups_get_user_groups() - that function applies `moodle/course:viewhiddengroups` and
     * group-visibility rules meant for the "which members can this user see in the group
     * selector" UI concern, which has nothing to do with resolving the plain fact "is this user
     * a member of group X". Same reasoning as get_cohort_ids() below not using any cohort UI
     * helper either.
     *
     * @return int[]
     */
    private static function get_course_group_ids(int $userid, int $courseid): array {
        global $DB;

        if (empty($userid) || empty($courseid)) {
            return [];
        }

        return array_values(array_map('intval', $DB->get_fieldset_sql(
            'SELECT g.id FROM {groups} g JOIN {groups_members} gm ON gm.groupid = g.id
              WHERE g.courseid = :courseid AND gm.userid = :userid',
            ['courseid' => $courseid, 'userid' => $userid]
        )));
    }

    /**
     * Category id and all its ancestors, root first, e.g. [1, 5, 12] for
     * "FUNDAE > Administration > IT" (SPECIFICATIONS.md section 12).
     *
     * @return int[]
     */
    private static function get_category_path(int $categoryid): array {
        if (empty($categoryid)) {
            return [];
        }

        $category = \core_course_category::get($categoryid, IGNORE_MISSING, true);
        if (!$category) {
            return [];
        }

        $path = trim($category->path, '/');

        return $path === '' ? [] : array_map('intval', explode('/', $path));
    }

    /**
     * Normalized (\core_tag_tag::normalize()) names of a course's tags.
     *
     * @return string[]
     */
    private static function get_course_tags(int $courseid): array {
        if (empty($courseid)) {
            return [];
        }

        return array_values(array_map(
            fn (\core_tag_tag $tag): string => $tag->name,
            \core_tag_tag::get_item_tags('core', 'course', $courseid)
        ));
    }
}
