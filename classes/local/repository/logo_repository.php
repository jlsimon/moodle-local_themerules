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
 * Data-access layer for local_themerules_logo: the small admin-managed library of logo
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules\local\repository;

/**
 * Data-access layer for local_themerules_logo: the small admin-managed library of logo
 * assets a rule's `logo` action can reference by id.
 *
 * Deliberately not cached (unlike rule_repository): this is only read from the low-traffic
 * admin UI (the logos list page and the rule form's logo picker), never from the runtime
 * resolver, which only ever needs a single already-known logoid (logo_action::apply() does a
 * targeted record_exists()/get_field() lookup, not a full list).
 *
 * File bytes live in Moodle's File API (component local_themerules, filearea logo, itemid =
 * this table's id), not in this table - create()/delete() keep both in sync.
 */
class logo_repository {
    /**
     * All logo assets, newest first, for the admin list page and the rule form's picker.
     *
     * @return \stdClass[]
     */
    public function get_all_records_ordered(): array {
        global $DB;

        return $DB->get_records('local_themerules_logo', null, 'id DESC');
    }

    /**
     * Fetches a single logo's raw DB record.
     *
     * @throws \dml_missing_record_exception if no logo has this id.
     */
    public function get_record(int $id): \stdClass {
        global $DB;

        return $DB->get_record('local_themerules_logo', ['id' => $id], '*', MUST_EXIST);
    }

    /**
     * Creates a logo asset and stores its file, in one step so the two can never go out of sync.
     *
     * @param string $name Admin-facing label.
     * @param int $draftitemid A filemanager/filepicker draft area id containing exactly the
     *        uploaded image (see logo_form.php).
     * @return int The new logo's id.
     */
    public function create(string $name, int $draftitemid): int {
        global $DB, $USER;

        $now = time();
        $id = (int) $DB->insert_record('local_themerules_logo', (object) [
            'name' => $name,
            'filename' => '',
            'timecreated' => $now,
            'timemodified' => $now,
            'usermodified' => $USER->id,
        ]);

        $filename = self::save_draft_file($draftitemid, $id);

        $DB->update_record('local_themerules_logo', (object) [
            'id' => $id,
            'filename' => $filename,
            'timemodified' => $now,
        ]);

        return $id;
    }

    /**
     * Updates a logo's name and/or file. The draft area is expected to already contain the
     * file that should end up stored - either the existing one, untouched, prefilled via
     * file_prepare_draft_area() (see logos.php), or a new one the admin uploaded to replace it.
     *
     * @param string $name Admin-facing label.
     * @param int $draftitemid See create()'s $draftitemid.
     */
    public function update(int $id, string $name, int $draftitemid): void {
        global $DB, $USER;

        $filename = self::save_draft_file($draftitemid, $id);

        $DB->update_record('local_themerules_logo', (object) [
            'id' => $id,
            'name' => $name,
            'filename' => $filename,
            'timemodified' => time(),
            'usermodified' => $USER->id,
        ]);
    }

    public function delete(int $id): void {
        global $DB;

        $fs = get_file_storage();
        $fs->delete_area_files(\context_system::instance()->id, 'local_themerules', 'logo', $id);

        $DB->delete_records('local_themerules_logo', ['id' => $id]);
    }

    /**
     * Moves the single file out of a filemanager draft area into this logo's permanent
     * (component, filearea, itemid) location, replacing whatever was there before (an edit that
     * re-uploads a new image for the same logo id).
     *
     * @return string The stored file's filename.
     * @throws \moodle_exception if the draft area does not contain exactly one file.
     */
    private static function save_draft_file(int $draftitemid, int $logoid): string {
        global $USER;

        $context = \context_system::instance();
        $fs = get_file_storage();

        $fs->delete_area_files($context->id, 'local_themerules', 'logo', $logoid);

        $usercontext = \context_user::instance($USER->id);
        $draftfiles = array_filter(
            $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid),
            fn (\stored_file $file): bool => !$file->is_directory()
        );

        if (count($draftfiles) !== 1) {
            throw new \moodle_exception('local_themerules: expected exactly one uploaded logo file');
        }

        $draftfile = reset($draftfiles);

        $fs->create_file_from_storedfile([
            'contextid' => $context->id,
            'component' => 'local_themerules',
            'filearea' => 'logo',
            'itemid' => $logoid,
            'filepath' => '/',
            'filename' => $draftfile->get_filename(),
        ], $draftfile);

        return $draftfile->get_filename();
    }
}
