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
 * Validates rule data submitted through the administration form (or any other
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules\local\validation;

use local_themerules\local\action\theme_action;
use local_themerules\local\engine\expression_parser;

/**
 * Validates rule data submitted through the administration form (or any other
 * future entry point, e.g. import/export) before it reaches the database.
 * See SPECIFICATIONS.md section 17 and section 24 ("Never trust
 * browser-generated JSON without server-side validation").
 */
class rule_validator {
    /**
     * Validates raw rule submission data.
     *
     * @param array $data Raw form/submission data: name, priority, expressionjson, theme,
     *                     timestart, timeend.
     * @return array<string, string> Element name => error message. Empty if valid.
     */
    public static function validate(array $data): array {
        $errors = [];

        if (trim((string) ($data['name'] ?? '')) === '') {
            $errors['name'] = get_string('error_name_required', 'local_themerules');
        }

        if (!is_numeric($data['priority'] ?? null)) {
            $errors['priority'] = get_string('error_priority_invalid', 'local_themerules');
        }

        try {
            (new expression_parser())->parse((string) ($data['expressionjson'] ?? ''));
        } catch (\Throwable $e) {
            $errors['expressionjson'] = get_string('error_expression_invalid', 'local_themerules', $e->getMessage());
        }

        if (empty($data['theme'])) {
            $errors['theme'] = get_string('error_theme_required', 'local_themerules');
        } else {
            try {
                (new theme_action())->validate(['theme' => $data['theme']]);
            } catch (\Throwable $e) {
                $errors['theme'] = get_string('error_theme_invalid', 'local_themerules', $e->getMessage());
            }
        }

        if (!empty($data['timestart']) && !empty($data['timeend']) && (int) $data['timeend'] < (int) $data['timestart']) {
            $errors['timeend'] = get_string('error_timeend_before_timestart', 'local_themerules');
        }

        return $errors;
    }

    /**
     * Builds the actionjson value from the form's plain "theme" field.
     */
    public static function build_action_json(string $theme): string {
        return json_encode(['type' => 'theme', 'theme' => $theme]);
    }

    /**
     * Extracts the plain theme name back out of an actionjson value, for prefilling the form.
     */
    public static function extract_theme(string $actionjson): string {
        $action = json_decode($actionjson, true);

        return is_array($action) ? (string) ($action['theme'] ?? '') : '';
    }
}
