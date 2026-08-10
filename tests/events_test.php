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

use local_themerules\event\rule_created;
use local_themerules\event\rule_deleted;
use local_themerules\event\rule_disabled;
use local_themerules\event\rule_enabled;
use local_themerules\event\rule_updated;
use local_themerules\local\repository\rule_repository;

/**
 * SPECIFICATIONS.md section 34: rule_created/updated/deleted/enabled/disabled.
 */
final class events_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->setAdminUser();
    }

    private function sample_record(array $overrides = []): \stdClass {
        return (object) array_merge([
            'name' => 'Event test rule',
            'description' => '',
            'enabled' => 1,
            'expressionjson' => json_encode(['type' => 'condition', 'condition' => 'user',
                'operator' => 'is', 'value' => 123]),
            'actionjson' => json_encode(['type' => 'theme', 'theme' => 'boost']),
            'timestart' => 0,
            'timeend' => 0,
        ], $overrides);
    }

    public function test_create_triggers_rule_created(): void {
        $sink = $this->redirectEvents();

        $repository = new rule_repository();
        $id = $repository->create($this->sample_record(['name' => 'Created rule']));

        $events = array_filter($sink->get_events(), fn ($e) => $e instanceof rule_created);
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertSame($id, $event->objectid);
        $this->assertSame('Created rule', $event->other['rulename']);
    }

    public function test_update_triggers_rule_updated(): void {
        $repository = new rule_repository();
        $id = $repository->create($this->sample_record());

        $sink = $this->redirectEvents();
        $repository->update($id, $this->sample_record(['name' => 'Updated rule']));

        $events = array_filter($sink->get_events(), fn ($e) => $e instanceof rule_updated);
        $this->assertCount(1, $events);
        $this->assertSame('Updated rule', reset($events)->other['rulename']);
    }

    public function test_delete_triggers_rule_deleted(): void {
        $repository = new rule_repository();
        $id = $repository->create($this->sample_record(['name' => 'To delete']));

        $sink = $this->redirectEvents();
        $repository->delete($id);

        $events = array_filter($sink->get_events(), fn ($e) => $e instanceof rule_deleted);
        $this->assertCount(1, $events);
        $this->assertSame('To delete', reset($events)->other['rulename']);
    }

    public function test_set_enabled_triggers_enabled_and_disabled_events(): void {
        $repository = new rule_repository();
        $id = $repository->create($this->sample_record(['enabled' => 1]));

        $sink = $this->redirectEvents();
        $repository->set_enabled($id, false);
        $disabled = array_filter($sink->get_events(), fn ($e) => $e instanceof rule_disabled);
        $this->assertCount(1, $disabled);

        $sink = $this->redirectEvents();
        $repository->set_enabled($id, true);
        $enabled = array_filter($sink->get_events(), fn ($e) => $e instanceof rule_enabled);
        $this->assertCount(1, $enabled);
    }
}
