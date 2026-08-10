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
 * Rule export/import, SPECIFICATIONS.md section 38.
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules\local\io;

use local_themerules\local\repository\rule_repository;
use local_themerules\local\validation\rule_validator;

/**
 * Rule export/import. Container shape follows SPECIFICATIONS.md section 38's original sketch:
 * `{"format": 1, "rules": [...]}`.
 *
 * A rule's `expression`/`theme`/`logoid` values are exported as-is: user/course/cohort/
 * coursegroup ids, theme names and logoid references only mean the same thing again on
 * *this* site (or another site that happens to share the same users/courses/cohorts/groups/
 * logo library, e.g. a staging copy of production) - this is a backup/restore and same-site
 * bulk-editing tool, not a portable "rule marketplace" format. A theme or logo that doesn't
 * exist on the importing site is skipped safely at evaluation time (same as any other rule
 * pointing at a deleted entity, SPECIFICATIONS.md section 50) rather than rejected at import
 * time - the rule still imports, it just would not resolve that axis until fixed.
 *
 * Import is best-effort per rule, not all-or-nothing: one malformed rule in an otherwise-good
 * file must not lose the rest, so every rule is independently validated (reusing
 * rule_validator::validate() - the exact same check manual entry through edit.php goes through,
 * so an imported rule can never be less trustworthy than a hand-typed one) and only valid rules
 * are actually created.
 */
class rule_export_import {
    /** The only container format version this class currently understands. */
    const FORMAT = 1;

    /**
     * Builds the exportable structure for every rule on this site, in evaluation order.
     *
     * @param rule_repository $repository
     * @return array{format: int, rules: array[]}
     */
    public static function export_all(rule_repository $repository): array {
        $rules = [];

        foreach ($repository->get_all_records_ordered() as $record) {
            $rules[] = [
                'name' => $record->name,
                'description' => $record->description,
                'enabled' => (bool) $record->enabled,
                'expression' => json_decode($record->expressionjson, true),
                'theme' => rule_validator::extract_theme($record->actionjson),
                'logoid' => rule_validator::extract_logoid($record->actionjson),
                'timestart' => (int) $record->timestart,
                'timeend' => (int) $record->timeend,
            ];
        }

        return ['format' => self::FORMAT, 'rules' => $rules];
    }

    /**
     * Imports every valid rule in the given structure, skipping (and reporting) invalid ones
     * rather than aborting the whole batch. Imported rules are always new rows, appended at the
     * end of the evaluation order and disabled by default - same "never silently start affecting
     * live traffic" precedent as rule_repository::duplicate().
     *
     * @param mixed $data Decoded JSON (json_decode($json, true)) - typed mixed since this is
     *        exactly the boundary SPECIFICATIONS.md section 24 means by "never trust
     *        browser/file-provided JSON without server-side validation": the shape itself is
     *        unverified until this method checks it.
     * @param rule_repository $repository
     * @return array{imported: int, total: int, errors: array{index: int, name: string, message: string}[]}
     */
    public static function import($data, rule_repository $repository): array {
        if (!is_array($data) || (int) ($data['format'] ?? 0) !== self::FORMAT) {
            return ['imported' => 0, 'total' => 0, 'errors' => [
                ['index' => -1, 'name' => '', 'message' => get_string('import_error_format', 'local_themerules')],
            ]];
        }

        $rules = $data['rules'] ?? null;
        if (!is_array($rules)) {
            return ['imported' => 0, 'total' => 0, 'errors' => [
                ['index' => -1, 'name' => '', 'message' => get_string('import_error_format', 'local_themerules')],
            ]];
        }

        $imported = 0;
        $errors = [];

        foreach (array_values($rules) as $index => $entry) {
            if (!is_array($entry)) {
                $errors[] = ['index' => $index, 'name' => '', 'message' => get_string('import_error_format', 'local_themerules')];
                continue;
            }

            $name = trim((string) ($entry['name'] ?? ''));
            $submission = [
                'name' => $name,
                'expressionjson' => json_encode($entry['expression'] ?? null),
                'theme' => (string) ($entry['theme'] ?? ''),
                'logoid' => $entry['logoid'] ?? null,
                'timestart' => (int) ($entry['timestart'] ?? 0),
                'timeend' => (int) ($entry['timeend'] ?? 0),
            ];

            $validationerrors = rule_validator::validate($submission);
            if (!empty($validationerrors)) {
                $errors[] = [
                    'index' => $index,
                    'name' => $name,
                    'message' => implode(' ', array_values($validationerrors)),
                ];
                continue;
            }

            $repository->create((object) [
                'name' => $name,
                'description' => (string) ($entry['description'] ?? ''),
                // Imported disabled by default, same reasoning as duplicate(): importing a large
                // rule set should never silently start affecting live traffic before an admin has
                // reviewed it.
                'enabled' => 0,
                'expressionjson' => $submission['expressionjson'],
                'actionjson' => rule_validator::build_action_json(
                    $submission['theme'],
                    !empty($submission['logoid']) ? (int) $submission['logoid'] : null
                ),
                'timestart' => $submission['timestart'],
                'timeend' => $submission['timeend'],
            ]);
            $imported++;
        }

        return ['imported' => $imported, 'total' => count($rules), 'errors' => $errors];
    }
}
