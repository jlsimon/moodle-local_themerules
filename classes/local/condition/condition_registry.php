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
 * Maps condition identifiers (as used in expression JSON) to their implementation.
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules\local\condition;

/**
 * Maps condition identifiers (as used in expression JSON) to their implementation.
 *
 * Adding a new condition type (SPECIFICATIONS.md section 39) means adding a
 * class implementing condition_interface and one entry here; nothing else in
 * the engine needs to change.
 */
class condition_registry {
    /** @var array<string, class-string<condition_interface>> */
    private const CONDITIONS = [
        'user' => user_condition::class,
        'course' => course_condition::class,
        'coursecategory' => course_category_condition::class,
        'cohort' => cohort_condition::class,
        'device' => device_condition::class,
        'coursetag' => course_tag_condition::class,
    ];

    public static function has(string $identifier): bool {
        return isset(self::CONDITIONS[$identifier]);
    }

    public static function get(string $identifier): condition_interface {
        if (!self::has($identifier)) {
            throw new \coding_exception('local_themerules: unknown condition identifier: ' . $identifier);
        }

        $class = self::CONDITIONS[$identifier];
        return new $class();
    }

    /**
     * Editor schema for every registered condition, consumed by the JS rule builder
     * (SPECIFICATIONS.md section 65).
     *
     * @return array[]
     */
    public static function get_all_editor_schemas(): array {
        return array_map(
            fn (string $identifier): array => self::get($identifier)->get_editor_schema(),
            array_keys(self::CONDITIONS)
        );
    }
}
