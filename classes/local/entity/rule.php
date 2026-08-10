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
 * Read-only view of a local_themerules_rule database row.
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules\local\entity;

/**
 * Read-only view of a local_themerules_rule database row.
 */
class rule {
    private int $id;
    private string $name;
    private bool $enabled;
    private string $expressionjson;
    private string $actionjson;
    private int $timestart;
    private int $timeend;

    private function __construct(
        int $id,
        string $name,
        bool $enabled,
        string $expressionjson,
        string $actionjson,
        int $timestart,
        int $timeend
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->enabled = $enabled;
        $this->expressionjson = $expressionjson;
        $this->actionjson = $actionjson;
        $this->timestart = $timestart;
        $this->timeend = $timeend;
    }

    public static function from_record(\stdClass $record): self {
        return new self(
            (int) $record->id,
            (string) $record->name,
            (bool) $record->enabled,
            (string) $record->expressionjson,
            (string) $record->actionjson,
            (int) $record->timestart,
            (int) $record->timeend
        );
    }

    public function get_id(): int {
        return $this->id;
    }

    public function get_name(): string {
        return $this->name;
    }

    public function is_enabled(): bool {
        return $this->enabled;
    }

    public function get_expression_json(): string {
        return $this->expressionjson;
    }

    public function get_action_json(): string {
        return $this->actionjson;
    }

    /**
     * Whether this rule is within its (optional) validity window at the given time.
     * See SPECIFICATIONS.md section 56: 0 means "no limit" on either bound.
     */
    public function is_active_at(int $now): bool {
        if ($this->timestart !== 0 && $now < $this->timestart) {
            return false;
        }
        if ($this->timeend !== 0 && $now > $this->timeend) {
            return false;
        }
        return true;
    }
}
