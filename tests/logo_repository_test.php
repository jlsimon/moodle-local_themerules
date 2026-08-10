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

use local_themerules\local\repository\logo_repository;

/**
 * Unit tests for logo_repository.
 */
#[\PHPUnit\Framework\Attributes\CoversClass(logo_repository::class)]
final class logo_repository_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->setAdminUser();
    }

    /**
     * Creates a draft-area file, as if the admin had just picked one in the filemanager element.
     *
     * @return int The draft area itemid containing exactly that one uploaded file.
     */
    private function create_draft_file(string $filename = 'test.png'): int {
        global $USER;

        $draftitemid = file_get_unused_draft_itemid();
        $usercontext = \context_user::instance($USER->id);

        get_file_storage()->create_file_from_string([
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => $filename,
        ], 'fake image bytes');

        return $draftitemid;
    }

    public function test_create_moves_draft_file_into_permanent_area(): void {
        $draftitemid = $this->create_draft_file('client-a-logo.png');

        $logoid = (new logo_repository())->create('Client A logo', $draftitemid);

        $record = (new logo_repository())->get_record($logoid);
        $this->assertSame('Client A logo', $record->name);
        $this->assertSame('client-a-logo.png', $record->filename);

        $file = get_file_storage()->get_file(
            \context_system::instance()->id,
            'local_themerules',
            'logo',
            $logoid,
            '/',
            'client-a-logo.png'
        );
        $this->assertNotFalse($file);
        $this->assertSame('fake image bytes', $file->get_content());
    }

    public function test_get_all_records_ordered_returns_newest_first(): void {
        $repository = new logo_repository();
        $first = $repository->create('First', $this->create_draft_file('first.png'));
        $second = $repository->create('Second', $this->create_draft_file('second.png'));

        $ids = array_keys($repository->get_all_records_ordered());

        $this->assertSame([$second, $first], $ids);
    }

    public function test_delete_removes_record_and_file(): void {
        $repository = new logo_repository();
        $logoid = $repository->create('To delete', $this->create_draft_file());

        $repository->delete($logoid);

        global $DB;
        $this->assertFalse($DB->record_exists('local_themerules_logo', ['id' => $logoid]));
        $this->assertEmpty(get_file_storage()->get_area_files(
            \context_system::instance()->id,
            'local_themerules',
            'logo',
            $logoid,
            'id',
            false
        ));
    }

    public function test_get_record_throws_for_unknown_id(): void {
        $this->expectException(\dml_missing_record_exception::class);
        (new logo_repository())->get_record(999999);
    }
}
