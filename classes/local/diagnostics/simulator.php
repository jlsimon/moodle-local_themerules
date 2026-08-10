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
 * Builds a human-readable trace of how the resolver would decide every action axis
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules\local\diagnostics;

use local_themerules\local\action\action_registry;
use local_themerules\local\condition\condition_registry;
use local_themerules\local\engine\evaluation_context;
use local_themerules\local\engine\expression_parser;
use local_themerules\local\repository\rule_repository;

/**
 * Builds a human-readable trace of how the resolver would decide every action axis (theme,
 * logo, ...) for a given user/course, for the "Simulate" admin page. See SPECIFICATIONS.md
 * section 31.
 *
 * Deliberately separate from engine\resolver/evaluator rather than adding a trace
 * mode to them: this keeps the hot runtime path (resolver, run on every page load)
 * free of any diagnostics-only branching, at the cost of evaluating each condition
 * a second time here. That is an acceptable trade-off because the simulator is an
 * admin-only, on-demand tool, never called from the request path that resolves the
 * live theme (SPECIFICATIONS.md section 76: runtime evaluation must stay cheap and
 * deterministic; this class does not run there).
 *
 * Mirrors resolver.php's per-axis resolution algorithm (a rule can win one axis while a
 * higher-priority rule already claimed another) so the trace accurately reflects what would
 * really happen, not an approximation of it.
 */
class simulator {
    public static function run(evaluation_context $context, ?rule_repository $repository = null): simulation_result {
        $repository ??= new rule_repository();
        $now = time();
        $resolved = []; // Axis type => resolved value, same shape as resolver::resolve().
        $traces = [];

        foreach ($repository->get_enabled_rules_ordered() as $rank => $rule) {
            if (!$rule->is_active_at($now)) {
                continue;
            }

            try {
                $expression = (new expression_parser())->parse($rule->get_expression_json());
                $traced = self::trace_node($expression, $context);

                $rulewontheme = null;
                $rulewonlogo = null;

                if ($traced['result']) {
                    foreach (self::decode_actions($rule->get_action_json()) as $actionconfig) {
                        $type = $actionconfig['type'] ?? '';
                        if ($type === '' || array_key_exists($type, $resolved) || !action_registry::has($type)) {
                            continue;
                        }

                        $value = action_registry::get($type)->apply($actionconfig, $context);
                        if ($value === null) {
                            continue;
                        }

                        $resolved[$type] = $value;
                        if ($type === 'theme') {
                            $rulewontheme = $value;
                        } else if ($type === 'logo') {
                            $rulewonlogo = self::logo_name((int) $value);
                        }
                    }
                }

                $traces[] = new rule_trace(
                    $rule->get_id(),
                    $rule->get_name(),
                    $rank + 1,
                    $traced['result'],
                    $traced['lines'],
                    $rulewontheme,
                    $rulewonlogo
                );
            } catch (\Throwable $e) {
                $traces[] = new rule_trace(
                    $rule->get_id(),
                    $rule->get_name(),
                    $rank + 1,
                    false,
                    [['text' => get_string('trace_error', 'local_themerules', $e->getMessage()), 'result' => false]],
                    null
                );
            }
        }

        $selectedlogo = array_key_exists('logo', $resolved) ? self::logo_name((int) $resolved['logo']) : null;

        return new simulation_result(self::describe_facts($context), $traces, $resolved['theme'] ?? null, $selectedlogo);
    }

    /**
     * Decodes a rule's actionjson into a list of action nodes.
     *
     * @return array[] Action nodes, tolerating both the legacy single-object shape and the
     *         current list shape - same small independent copy as rule_validator.php's
     *         decode_actions(), see that method's docblock for why it is not shared.
     */
    private static function decode_actions(string $actionjson): array {
        $decoded = json_decode($actionjson, true);

        if (!is_array($decoded)) {
            return [];
        }

        return array_key_exists('type', $decoded) ? [$decoded] : $decoded;
    }

    /**
     * Recursively evaluates one expression node while recording a flat trace line per condition.
     *
     * @return array{result: bool, lines: array{text: string, result: bool}[]}
     */
    private static function trace_node(array $node, evaluation_context $context): array {
        if ($node['type'] === 'condition') {
            $result = condition_registry::get($node['condition'])->evaluate($node, $context);
            return ['result' => $result, 'lines' => [
                ['text' => self::describe_condition($node), 'result' => $result],
            ]];
        }

        $childresults = array_map(fn (array $child) => self::trace_node($child, $context), $node['children']);
        $result = $node['operator'] === 'and'
            ? array_reduce($childresults, fn (bool $carry, array $c) => $carry && $c['result'], true)
            : array_reduce($childresults, fn (bool $carry, array $c) => $carry || $c['result'], false);

        $lines = [];
        foreach ($childresults as $child) {
            $lines = array_merge($lines, $child['lines']);
        }

        return ['result' => $result, 'lines' => $lines];
    }

    private static function describe_condition(array $node): string {
        $value = (int) ($node['value'] ?? 0);

        switch ($node['condition']) {
            case 'user':
                return get_string('trace_user', 'local_themerules', self::user_name($value));

            case 'course':
                return get_string('trace_course', 'local_themerules', self::course_name($value));

            case 'coursecategory':
                $text = get_string('trace_coursecategory', 'local_themerules', self::category_name($value));
                if (!empty($node['includechildren'])) {
                    $text .= ' ' . get_string('trace_includingdescendants', 'local_themerules');
                }
                return $text;

            case 'cohort':
                return get_string('trace_cohort', 'local_themerules', (object) [
                    'verb' => get_string(($node['operator'] ?? 'member') === 'member'
                        ? 'trace_member' : 'trace_notmember', 'local_themerules'),
                    'name' => self::cohort_name($value),
                ]);

            case 'device':
                $devicevalue = (string) ($node['value'] ?? '');
                return get_string('trace_device', 'local_themerules', (object) [
                    'verb' => get_string(($node['operator'] ?? 'is') === 'is'
                        ? 'trace_is' : 'trace_isnot', 'local_themerules'),
                    'name' => get_string('device_' . $devicevalue, 'local_themerules'),
                ]);

            case 'coursetag':
                return get_string('trace_coursetag', 'local_themerules', (object) [
                    'verb' => get_string(($node['operator'] ?? 'has') === 'has'
                        ? 'trace_has' : 'trace_nothas', 'local_themerules'),
                    'name' => (string) ($node['value'] ?? ''),
                ]);

            case 'profilefield':
                return get_string('trace_profilefield', 'local_themerules', (object) [
                    'field' => self::profilefield_label((string) ($node['field'] ?? '')),
                    'verb' => get_string(($node['operator'] ?? 'is') === 'is'
                        ? 'trace_is' : 'trace_isnot', 'local_themerules'),
                    'value' => (string) ($node['value'] ?? ''),
                ]);

            default:
                return $node['condition'] . ' ' . $value;
        }
    }

    private static function describe_facts(evaluation_context $context): array {
        global $DB;

        $facts = [
            get_string('trace_fact_user', 'local_themerules') => self::user_name($context->get_userid()),
        ];

        if ($context->get_courseid() !== null) {
            $facts[get_string('trace_fact_course', 'local_themerules')] = self::course_name($context->get_courseid());
        }
        if ($context->get_coursecategorypath() !== []) {
            $names = array_map([self::class, 'category_name'], $context->get_coursecategorypath());
            $facts[get_string('trace_fact_category', 'local_themerules')] = implode(' > ', $names);
        }

        $cohortids = $context->get_cohortids();
        $facts[get_string('trace_fact_cohorts', 'local_themerules')] = empty($cohortids)
            ? get_string('trace_fact_none', 'local_themerules')
            : implode(', ', array_map([self::class, 'cohort_name'], $cohortids));

        $facts[get_string('trace_fact_device', 'local_themerules')] =
            get_string('device_' . $context->get_devicetype(), 'local_themerules');

        if ($context->get_courseid() !== null) {
            $coursetags = $context->get_coursetags();
            $facts[get_string('trace_fact_coursetags', 'local_themerules')] = empty($coursetags)
                ? get_string('trace_fact_none', 'local_themerules')
                : implode(', ', $coursetags);
        }

        return $facts;
    }

    /**
     * Id 0 is not "not found" - it is what a genuinely anonymous, not-logged-in visitor's
     * userid actually is at Tier A (fact_provider::create_for_current_user() reads
     * `$USER->id ?? 0`), so a `user is 0` condition already matches real anonymous visitors in
     * production today. Described accordingly rather than as a broken reference.
     */
    private static function user_name(int $id): string {
        if ($id === 0) {
            return get_string('trace_anonymous', 'local_themerules');
        }
        global $DB;
        $fields = 'id, ' . implode(', ', \core_user\fields::get_name_fields());
        $user = $DB->get_record('user', ['id' => $id], $fields, IGNORE_MISSING);
        return $user ? fullname($user) . " (id {$id})" : self::not_found($id);
    }

    private static function course_name(int $id): string {
        global $DB;
        $course = $DB->get_record('course', ['id' => $id], 'id, fullname', IGNORE_MISSING);
        return $course ? format_string($course->fullname) . " (id {$id})" : self::not_found($id);
    }

    private static function category_name(int $id): string {
        global $DB;
        $category = $DB->get_record('course_categories', ['id' => $id], 'id, name', IGNORE_MISSING);
        return $category ? format_string($category->name) . " (id {$id})" : self::not_found($id);
    }

    private static function cohort_name(int $id): string {
        global $DB;
        $cohort = $DB->get_record('cohort', ['id' => $id], 'id, name', IGNORE_MISSING);
        return $cohort ? format_string($cohort->name) . " (id {$id})" : self::not_found($id);
    }

    private static function logo_name(int $id): string {
        global $DB;
        $logo = $DB->get_record('local_themerules_logo', ['id' => $id], 'id, name', IGNORE_MISSING);
        return $logo ? format_string($logo->name) . " (id {$id})" : self::not_found($id);
    }

    /**
     * Resolves a profilefield condition's field shortname to a human label, trying a custom
     * field first (its name lives in the DB, unlike a standard field's) and falling back to
     * core's own display name for a standard field - self-contained rather than depending on
     * the condition's own `customfield` flag, so this stays correct even for a stale/corrupt one.
     */
    private static function profilefield_label(string $field): string {
        global $DB;

        $custom = $DB->get_record('user_info_field', ['shortname' => $field], 'name', IGNORE_MISSING);
        if ($custom) {
            return format_string($custom->name);
        }

        if (in_array($field, \local_themerules\local\condition\profile_field_condition::STANDARD_FIELDS, true)) {
            return \core_user\fields::get_display_name($field);
        }

        // Neither a real custom field nor a known standard one (e.g. a deleted custom field, or
        // hand-edited JSON) - SPECIFICATIONS.md section 50: show it is broken, don't fabricate a
        // label or emit core's raw "[[shortname]]" missing-string marker.
        return self::not_found_field($field);
    }

    private static function not_found_field(string $field): string {
        return get_string('trace_fieldnotfound', 'local_themerules', $field);
    }

    /**
     * SPECIFICATIONS.md section 50: a reference to a deleted entity must not break anything,
     * and the admin-facing surface (here, the simulator) should indicate it is broken.
     */
    private static function not_found(int $id): string {
        return get_string('trace_notfound', 'local_themerules', $id);
    }
}
