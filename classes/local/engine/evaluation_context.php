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
 * Normalized facts about the current request, passed to every condition's evaluate().
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules\local\engine;

/**
 * Normalized facts about the current request, passed to every condition's evaluate().
 * See SPECIFICATIONS.md section 11.
 *
 * Course/category facts are nullable: on requests where no real course is known yet
 * (see fact_provider::create_for_current_user(), used at \core\hook\after_config time,
 * before require_login() has run - DECISIONS.md "Phase 2" for why), course/category
 * conditions simply cannot match, while user/cohort conditions still can.
 */
class evaluation_context {
    private int $userid;
    private ?int $courseid;
    private ?int $coursecategoryid;

    /** @var int[] Category id and all its ancestors, root first, e.g. [1, 5, 12]. */
    private array $coursecategorypath;

    /** @var int[] */
    private array $cohortids;

    /** One of \core_useragent::DEVICETYPE_* - unlike the other facts, always resolvable. */
    private string $devicetype;

    /** @var string[] Normalized (\core_tag_tag::normalize()) names of the course's tags. */
    private array $coursetags;

    /** @var int[] Ids of the groups the user belongs to, within the current course. */
    private array $coursegroupids;

    public function __construct(
        int $userid,
        ?int $courseid = null,
        ?int $coursecategoryid = null,
        array $coursecategorypath = [],
        array $cohortids = [],
        string $devicetype = 'default',
        array $coursetags = [],
        array $coursegroupids = []
    ) {
        $this->userid = $userid;
        $this->courseid = $courseid;
        $this->coursecategoryid = $coursecategoryid;
        $this->coursecategorypath = $coursecategorypath;
        $this->cohortids = $cohortids;
        $this->devicetype = $devicetype;
        $this->coursetags = $coursetags;
        $this->coursegroupids = $coursegroupids;
    }

    public function get_userid(): int {
        return $this->userid;
    }

    public function get_courseid(): ?int {
        return $this->courseid;
    }

    public function get_coursecategoryid(): ?int {
        return $this->coursecategoryid;
    }

    /**
     * Category id and all its ancestors, root first.
     *
     * @return int[]
     */
    public function get_coursecategorypath(): array {
        return $this->coursecategorypath;
    }

    /**
     * Every cohort the user is a member of.
     *
     * @return int[]
     */
    public function get_cohortids(): array {
        return $this->cohortids;
    }

    /**
     * One of \core_useragent::DEVICETYPE_* ('default', 'mobile', 'tablet', 'legacy').
     */
    public function get_devicetype(): string {
        return $this->devicetype;
    }

    /**
     * Normalized (\core_tag_tag::normalize()) names of the current course's tags. Empty when no
     * real course is known yet, same constraint as get_coursecategoryid().
     *
     * @return string[]
     */
    public function get_coursetags(): array {
        return $this->coursetags;
    }

    /**
     * Ids of every group the user belongs to within the current course. Empty when no real
     * course is known yet, same constraint as get_coursecategoryid()/get_coursetags().
     *
     * @return int[]
     */
    public function get_coursegroupids(): array {
        return $this->coursegroupids;
    }
}
