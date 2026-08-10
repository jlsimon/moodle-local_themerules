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

use local_themerules\local\action\logo_action;
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
     *                     logoid, timestart, timeend. theme and logoid are each individually
     *                     optional (DECISIONS.md: independent axes), but at least one of the two
     *                     must be set - a rule with neither would never do anything.
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

        $hastheme = !empty($data['theme']);
        $haslogo = !empty($data['logoid']);

        if (!$hastheme && !$haslogo) {
            $errors['theme'] = get_string('error_action_required', 'local_themerules');
        }

        if ($hastheme) {
            try {
                (new theme_action())->validate(['theme' => $data['theme']]);
            } catch (\Throwable $e) {
                $errors['theme'] = get_string('error_theme_invalid', 'local_themerules', $e->getMessage());
            }
        }

        if ($haslogo) {
            try {
                (new logo_action())->validate(['logoid' => $data['logoid']]);
            } catch (\Throwable $e) {
                $errors['logoid'] = get_string('error_logo_invalid', 'local_themerules', $e->getMessage());
            }
        }

        if (!empty($data['timestart']) && !empty($data['timeend']) && (int) $data['timeend'] < (int) $data['timestart']) {
            $errors['timeend'] = get_string('error_timeend_before_timestart', 'local_themerules');
        }

        return $errors;
    }

    /**
     * Builds the actionjson value from the form's plain "theme"/"logoid" fields. Always a list
     * (even when only one axis is set), the canonical shape going forward - see resolver.php's
     * decode_actions() for why the single-object shape from before the `logo` action existed is
     * still accepted when reading, just never written by this method anymore.
     */
    public static function build_action_json(string $theme, ?int $logoid = null): string {
        $actions = [];

        if ($theme !== '') {
            $actions[] = ['type' => 'theme', 'theme' => $theme];
        }
        if (!empty($logoid)) {
            $actions[] = ['type' => 'logo', 'logoid' => $logoid];
        }

        return json_encode($actions);
    }

    /**
     * Extracts the plain theme name back out of an actionjson value, for prefilling the form.
     * Accepts both the legacy single-object shape and the current list shape.
     */
    public static function extract_theme(string $actionjson): string {
        foreach (self::decode_actions($actionjson) as $action) {
            if (($action['type'] ?? '') === 'theme') {
                return (string) ($action['theme'] ?? '');
            }
        }

        return '';
    }

    /**
     * Extracts the logo asset id back out of an actionjson value, for prefilling the form.
     */
    public static function extract_logoid(string $actionjson): ?int {
        foreach (self::decode_actions($actionjson) as $action) {
            if (($action['type'] ?? '') === 'logo') {
                return (int) ($action['logoid'] ?? 0) ?: null;
            }
        }

        return null;
    }

    /**
     * Decodes a rule's actionjson into a list of action nodes.
     *
     * @return array[] Action nodes, tolerating both the legacy single-object shape and the
     *         current list shape (same tolerance as resolver.php's decode_actions() - kept as a
     *         separate small copy here rather than shared, since this class only ever reads a
     *         handful of known keys back out for form prefilling, not the general action dispatch
     *         resolver.php does).
     */
    private static function decode_actions(string $actionjson): array {
        $decoded = json_decode($actionjson, true);

        if (!is_array($decoded)) {
            return [];
        }

        return array_key_exists('type', $decoded) ? [$decoded] : $decoded;
    }
}
