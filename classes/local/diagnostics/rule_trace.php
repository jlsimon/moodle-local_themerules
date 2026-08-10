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
 * One evaluated rule's trace, for the simulator (SPECIFICATIONS.md section 31/32).
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules\local\diagnostics;

/**
 * One evaluated rule's trace, for the simulator (SPECIFICATIONS.md section 31/32).
 * Authorized administrators only - never exposed to unauthorized users.
 */
class rule_trace {
    /** @var int */
    public int $ruleid;
    /** @var string */
    public string $rulename;

    /** @var int 1-indexed position in the evaluation order (list position, not a stored value - see DECISIONS.md). */
    public int $position;

    /** @var bool */
    public bool $matched;

    /** @var array{text: string, result: bool}[] Flattened per-condition checks, in tree order. */
    public array $conditionlines;

    /** @var string|null The theme this rule would apply, if it matched and won that axis; null otherwise. */
    public ?string $theme;

    /** @var string|null The logo name this rule would apply, if it matched and won that axis; null otherwise. */
    public ?string $logo;

    /**
     * Assembles one rule's trace.
     *
     * @param int $ruleid
     * @param string $rulename
     * @param int $position
     * @param bool $matched
     * @param array $conditionlines
     * @param string|null $theme
     * @param string|null $logo
     */
    public function __construct(
        int $ruleid,
        string $rulename,
        int $position,
        bool $matched,
        array $conditionlines,
        ?string $theme,
        ?string $logo = null
    ) {
        $this->ruleid = $ruleid;
        $this->rulename = $rulename;
        $this->position = $position;
        $this->matched = $matched;
        $this->conditionlines = $conditionlines;
        $this->theme = $theme;
        $this->logo = $logo;
    }
}
