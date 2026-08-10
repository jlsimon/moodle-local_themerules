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
 * Resolves every action axis (theme, logo, ...) for the given facts, or leaves
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules\local\engine;

use local_themerules\local\action\action_registry;
use local_themerules\local\repository\rule_repository;

/**
 * Resolves every action axis (theme, logo, ...) for the given facts, independently, walking the
 * same priority-ordered rule list once. See SPECIFICATIONS.md section 7 and DECISIONS.md for why
 * a single rule can carry more than one action and why each axis resolves independently: a
 * lower-priority rule can still fill in an axis a higher-priority matching rule left unset,
 * without ever overriding an axis that rule already claimed.
 */
class resolver {
    /**
     * Resolves every axis at once.
     *
     * @param evaluation_context $context
     * @param rule_repository|null $repository
     * @return array<string, string> Axis identifier (action_interface::get_identifier(), e.g.
     *         "theme"/"logo") => resolved value. An axis with no matching rule, or where every
     *         matching rule's action for it declined (SPECIFICATIONS.md section 18), is simply
     *         absent from the array - callers must use `$resolved['theme'] ?? null`, not assume
     *         every axis is present.
     */
    public static function resolve(evaluation_context $context, ?rule_repository $repository = null): array {
        $repository ??= new rule_repository();
        $evaluator = new evaluator();
        $now = time();
        $resolved = [];

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

                foreach (self::decode_actions($rule->get_action_json()) as $actionconfig) {
                    $type = $actionconfig['type'] ?? '';
                    if ($type === '' || array_key_exists($type, $resolved)) {
                        // No type, or this axis was already claimed by a higher-priority rule:
                        // nothing for this action to contribute.
                        continue;
                    }
                    if (!action_registry::has($type)) {
                        throw new \coding_exception('local_themerules: unknown action identifier: ' . $type);
                    }

                    $value = action_registry::get($type)->apply($actionconfig, $context);
                    if ($value !== null) {
                        $resolved[$type] = $value;
                    }
                    // Action declined (e.g. theme/logo no longer exists): leave this axis open for
                    // a lower-priority rule, per SPECIFICATIONS.md section 18.
                }
            } catch (\Throwable $e) {
                // A single corrupt/invalid rule must never break the page (SPECIFICATIONS.md
                // section 49): log it and keep evaluating the remaining rules.
                debugging('local_themerules: skipping rule #' . $rule->get_id() . ' (' . $rule->get_name() .
                    ') due to error: ' . $e->getMessage(), DEBUG_DEVELOPER);
                continue;
            }
        }

        return $resolved;
    }

    /**
     * Convenience wrapper for the (still by far the most common) theme-only case.
     *
     * @param evaluation_context $context
     * @param rule_repository|null $repository
     * @return string|null
     */
    public static function resolve_theme(evaluation_context $context, ?rule_repository $repository = null): ?string {
        return self::resolve($context, $repository)['theme'] ?? null;
    }

    /**
     * A rule's actionjson is either a single action node (the format every rule used before the
     * `logo` action existed - kept working forever, not just as a migration window, since a
     * theme-only rule never needs the list form) or a list of action nodes (needed once a rule
     * sets more than one axis, e.g. theme + logo together).
     *
     * @param string $actionjson
     * @return array[] Action nodes, each with at least a "type" key.
     */
    private static function decode_actions(string $actionjson): array {
        $decoded = json_decode($actionjson, true);

        if (!is_array($decoded)) {
            throw new \coding_exception('local_themerules: invalid action JSON');
        }

        // A single action node is a JSON object (decodes to an assoc array with a "type" key);
        // a list of nodes is a JSON array (decodes to a sequential array of such objects).
        $actions = array_key_exists('type', $decoded) ? [$decoded] : $decoded;

        foreach ($actions as $action) {
            if (!is_array($action)) {
                throw new \coding_exception('local_themerules: invalid action node');
            }
        }

        return $actions;
    }
}
