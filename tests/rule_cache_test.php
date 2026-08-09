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
 * Tests.
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules;

use local_themerules\local\repository\rule_repository;

/**
 * SPECIFICATIONS.md section 28: the enabled-rules cache must avoid a DB query
 * on every request once warm, and must invalidate on every write.
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\local_themerules\local\cache\cache_manager::class)]
final class rule_cache_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->setAdminUser();
    }

    private function sample_record(array $overrides = []): \stdClass {
        return (object) array_merge([
            'name' => 'Cache test rule',
            'description' => '',
            'enabled' => 1,
            'priority' => 0,
            'expressionjson' => json_encode(['type' => 'condition', 'condition' => 'user',
                'operator' => 'is', 'value' => 123]),
            'actionjson' => json_encode(['type' => 'theme', 'theme' => 'boost']),
            'timestart' => 0,
            'timeend' => 0,
        ], $overrides);
    }

    public function test_second_call_does_not_hit_the_database(): void {
        global $DB;

        $repository = new rule_repository();
        $repository->create($this->sample_record());

        $repository->get_enabled_rules_ordered(); // Warms the cache.
        $readsbefore = $DB->perf_get_reads();

        $rules = $repository->get_enabled_rules_ordered();

        $this->assertSame($readsbefore, $DB->perf_get_reads());
        $this->assertCount(1, $rules);
    }

    public function test_create_invalidates_the_cache(): void {
        $repository = new rule_repository();
        $this->assertCount(0, $repository->get_enabled_rules_ordered()); // Warms an empty cache.

        $repository->create($this->sample_record());

        $this->assertCount(1, $repository->get_enabled_rules_ordered());
    }

    public function test_update_invalidates_the_cache(): void {
        $repository = new rule_repository();
        $id = $repository->create($this->sample_record(['priority' => 1]));
        $repository->get_enabled_rules_ordered(); // Warms the cache.

        $repository->update($id, $this->sample_record(['priority' => 99]));

        $rules = $repository->get_enabled_rules_ordered();
        $this->assertSame(99, $rules[0]->get_priority());
    }

    public function test_delete_invalidates_the_cache(): void {
        $repository = new rule_repository();
        $id = $repository->create($this->sample_record());
        $repository->get_enabled_rules_ordered(); // Warms the cache.

        $repository->delete($id);

        $this->assertCount(0, $repository->get_enabled_rules_ordered());
    }

    public function test_set_enabled_invalidates_the_cache(): void {
        $repository = new rule_repository();
        $id = $repository->create($this->sample_record(['enabled' => 1]));
        $repository->get_enabled_rules_ordered(); // Warms the cache.

        $repository->set_enabled($id, false);

        $this->assertCount(0, $repository->get_enabled_rules_ordered());
    }
}
