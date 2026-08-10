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
 * "User's profile field <field> is/is not <value>" condition.
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules\local\condition;

use local_themerules\local\engine\evaluation_context;

/**
 * "User's profile field <field> is/is not <value>" condition.
 *
 * Deliberately mirrors the field set and exact-match semantics of Moodle's own
 * `availability_profile` (the "User profile field" condition already familiar to admins from
 * Restrict Access) rather than inventing a new one: same standard-field list
 * (`STANDARD_FIELDS`), same "standard field vs custom field" distinction, and the same
 * case-sensitive strict-equality comparison for the `is` operator. Scoped to `is`/`is_not` only
 * for this first pass, not availability_profile's full contains/starts-with/ends-with/is-empty
 * set - this plugin's other conditions are similarly minimal (e.g. course_condition only has
 * `is`), and a value of `""` with `is` already covers the "field is empty" case without a
 * dedicated operator.
 */
class profile_field_condition implements condition_interface {
    /** @var string[] Operators this condition currently accepts. */
    const OPERATORS = ['is', 'is_not'];

    /**
     * Standard (not custom) user table columns this condition can reference - the same list
     * `availability_profile\condition::get_standard_profile_fields()` uses, so admins see a
     * familiar set of choices rather than a different one per plugin.
     *
     * @var string[]
     */
    const STANDARD_FIELDS = [
        'firstname', 'lastname', 'email', 'city', 'country', 'idnumber',
        'institution', 'department', 'phone1', 'phone2', 'address',
    ];

    public function get_name(): string {
        return get_string('condition_profilefield', 'local_themerules');
    }

    public function get_identifier(): string {
        return 'profilefield';
    }

    public function validate(array $config): void {
        if (!in_array($config['operator'] ?? null, self::OPERATORS, true)) {
            throw new \coding_exception('local_themerules: unknown operator for profilefield condition: ' .
                ($config['operator'] ?? ''));
        }
        if (trim((string) ($config['field'] ?? '')) === '') {
            throw new \coding_exception('local_themerules: profilefield condition missing "field"');
        }
        if (!is_string($config['value'] ?? null)) {
            throw new \coding_exception('local_themerules: profilefield condition value must be a string');
        }
    }

    public function evaluate(array $config, evaluation_context $context): bool {
        $uservalue = self::get_field_value(
            $context->get_userid(),
            (string) $config['field'],
            !empty($config['customfield'])
        );

        // A field the user has no value for (or does not exist at all - SPECIFICATIONS.md
        // section 50, a deleted custom field must not break the page) can never satisfy `is`,
        // and correctly *does* satisfy `is_not` (the user genuinely doesn't have that value).
        $matches = $uservalue !== null && $uservalue === (string) $config['value'];

        return $config['operator'] === 'is' ? $matches : !$matches;
    }

    public function get_editor_schema(): array {
        return [
            'identifier' => $this->get_identifier(),
            'name' => $this->get_name(),
            'operators' => self::OPERATORS,
            'valuetype' => 'profilefield',
            // A profile field's value must stay a JSON string even if it looks numeric (e.g. an
            // idnumber of "12345") - validate() requires is_string(). Tells the JS builder not
            // to apply its usual "looks numeric -> store as a number" coercion for course/user/
            // cohort/category id values.
            'stringvalue' => true,
            // Value/label/customfield triples: lets the JS builder offer every field this
            // site actually has (standard + real custom fields) via a dropdown instead of a
            // free-text shortname an admin would have to already know and type correctly.
            'fieldoptions' => self::field_options(),
        ];
    }

    /**
     * Builds the editor schema's list of pickable fields, standard and custom.
     *
     * @return array{value: string, label: string, customfield: bool}[]
     */
    private static function field_options(): array {
        global $CFG;

        $options = [];
        foreach (self::STANDARD_FIELDS as $shortname) {
            $options[] = [
                'value' => $shortname,
                'label' => \core_user\fields::get_display_name($shortname),
                'customfield' => false,
            ];
        }

        require_once($CFG->dirroot . '/user/profile/lib.php');
        foreach (profile_get_custom_fields() as $field) {
            $options[] = [
                'value' => $field->shortname,
                'label' => format_string($field->name),
                'customfield' => true,
            ];
        }

        return $options;
    }

    /**
     * Looks up one user's value for one profile field, standard or custom.
     */
    private static function get_field_value(int $userid, string $field, bool $iscustomfield): ?string {
        global $DB, $CFG;

        if (empty($userid)) {
            // Anonymous visitor (DECISIONS.md: userid 0 is a real, resolvable fact, not a
            // missing one) - has no profile field values at all, by definition.
            return null;
        }

        if ($iscustomfield) {
            require_once($CFG->dirroot . '/user/profile/lib.php');
            // Passing $onlyinuserobject = false: a direct, authoritative lookup for an arbitrary user
            // (this plugin never assumes $userid is the current $USER, unlike
            // availability_profile - the simulator routinely asks about a different user), so
            // this must not rely on what happens to already be cached on $USER.
            $profile = profile_user_record($userid, false);
            return property_exists($profile, $field) ? (string) $profile->$field : null;
        }

        if (!in_array($field, self::STANDARD_FIELDS, true)) {
            // Not a whitelisted column name - refuse rather than pass an arbitrary string into
            // $DB->get_field()'s field-name argument.
            return null;
        }

        $value = $DB->get_field('user', $field, ['id' => $userid], IGNORE_MISSING);

        return $value === false ? null : (string) $value;
    }
}
