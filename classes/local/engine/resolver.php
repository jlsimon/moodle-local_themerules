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
 * Resolves the theme to use for the given facts, or null to leave Moodle's
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules\local\engine;

use local_themerules\local\action\action_registry;
use local_themerules\local\repository\rule_repository;

/**
 * Resolves the theme to use for the given facts, or null to leave Moodle's
 * normal theme resolution untouched. See SPECIFICATIONS.md section 7.
 */
class resolver {
    public static function resolve_theme(evaluation_context $context, ?rule_repository $repository = null): ?string {
        $repository ??= new rule_repository();
        $evaluator = new evaluator();
        $now = time();

        foreach ($repository->get_enabled_rules_ordered() as $rule) {
            if (!$rule->is_active_at($now)) {
                continue;
            }

            try {
                $parser = new expression_parser();
                $expression = $parser->parse($rule->get_expression_json());

                if (!$evaluator->evaluate($expression, $context)) {
                    continue;
                }

                $actionconfig = json_decode($rule->get_action_json(), true);
                if (!is_array($actionconfig) || empty($actionconfig['type'])) {
                    throw new \coding_exception('local_themerules: invalid action JSON');
                }

                $theme = action_registry::get($actionconfig['type'])->apply($actionconfig, $context);
                if ($theme !== null) {
                    return $theme;
                }
                // Action declined (e.g. theme no longer installed): fall through to the next rule
                // rather than stopping, per SPECIFICATIONS.md section 18.
            } catch (\Throwable $e) {
                // A single corrupt/invalid rule must never break the page (SPECIFICATIONS.md
                // section 49): log it and keep evaluating the remaining rules.
                debugging('local_themerules: skipping rule #' . $rule->get_id() . ' (' . $rule->get_name() .
                    ') due to error: ' . $e->getMessage(), DEBUG_DEVELOPER);
                continue;
            }
        }

        return null;
    }
}
