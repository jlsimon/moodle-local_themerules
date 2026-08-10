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
 * Full output of one simulator run. See SPECIFICATIONS.md section 31.
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules\local\diagnostics;

/**
 * Full output of one simulator run. See SPECIFICATIONS.md section 31.
 */
class simulation_result {
    /** @var array<string, string> Display facts (resolved names), e.g. "User" => "Jane Doe (id 123)". */
    public array $facts;

    /** @var rule_trace[] All enabled, time-active rules, in evaluation order. */
    public array $ruletraces;

    public ?string $selectedtheme;

    /** @var string|null Display name of the logo that would be applied, or null. */
    public ?string $selectedlogo;

    public function __construct(array $facts, array $ruletraces, ?string $selectedtheme, ?string $selectedlogo = null) {
        $this->facts = $facts;
        $this->ruletraces = $ruletraces;
        $this->selectedtheme = $selectedtheme;
        $this->selectedlogo = $selectedlogo;
    }
}
