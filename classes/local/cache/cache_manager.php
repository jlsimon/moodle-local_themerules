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
 * Wraps the "rules" MUC cache (db/caches.php). See SPECIFICATIONS.md section 28.
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules\local\cache;

/**
 * Wraps the "rules" MUC cache (db/caches.php). See SPECIFICATIONS.md section 28.
 *
 * Caches only the raw enabled-rule DB records, not parsed expression trees:
 * this already satisfies the stated performance target (section 30 - "0 extra
 * DB queries when required facts are already available") for the actual cost
 * driver, a database round trip. Parsing an already-decoded, small (max 100
 * nodes, section 17) JSON tree is pure in-memory PHP with no I/O; adding a
 * second "compiledrules" cache layer on top of that, before any profiling
 * showed it necessary, would be optimizing an unmeasured problem (section 29:
 * "Optimize only after profiling"; section 76 favours simple data over
 * premature machinery). Revisit only if profiling on a real deployment shows
 * expression parsing itself as a hot spot.
 */
class cache_manager {
    /** @var string MUC cache area defined in db/caches.php. */
    private const CACHE_AREA = 'rules';

    /** @var string Single static key: there is only one global rule set. */
    private const KEY_ENABLED_RULES = 'enabled';

    /**
     * Reads the cached enabled-rule records.
     *
     * @return \stdClass[]|null Cached records, or null on a cache miss.
     */
    public static function get_enabled_rule_records(): ?array {
        $value = self::cache()->get(self::KEY_ENABLED_RULES);

        return $value === false ? null : $value;
    }

    /**
     * Populates the cache after a miss.
     *
     * @param \stdClass[] $records
     */
    public static function set_enabled_rule_records(array $records): void {
        self::cache()->set(self::KEY_ENABLED_RULES, $records);
    }

    /**
     * Must be called after any write that could change what the enabled-rules query
     * returns: create, update (including priority/enabled changes), delete.
     */
    public static function purge(): void {
        self::cache()->purge();
    }

    private static function cache(): \cache {
        return \cache::make('local_themerules', self::CACHE_AREA);
    }
}
