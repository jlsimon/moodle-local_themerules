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
 * Maps action identifiers (as used in action JSON) to their implementation.
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules\local\action;

/**
 * Maps action identifiers (as used in action JSON) to their implementation.
 */
class action_registry {
    /** @var array<string, class-string<action_interface>> */
    private const ACTIONS = [
        'theme' => theme_action::class,
    ];

    public static function has(string $identifier): bool {
        return isset(self::ACTIONS[$identifier]);
    }

    public static function get(string $identifier): action_interface {
        if (!self::has($identifier)) {
            throw new \coding_exception('local_themerules: unknown action identifier: ' . $identifier);
        }

        $class = self::ACTIONS[$identifier];
        return new $class();
    }
}
