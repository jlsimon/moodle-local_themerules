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

use local_themerules\local\condition\profile_field_condition;
use local_themerules\local\engine\evaluation_context;

#[\PHPUnit\Framework\Attributes\CoversClass(profile_field_condition::class)]
/**
 * Unit tests for profile_field_condition.
 *
 * @covers \local_themerules\local\condition\profile_field_condition
 */
final class profile_field_condition_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_is_matches_standard_field(): void {
        $user = $this->getDataGenerator()->create_user(['institution' => 'UTAD']);
        $condition = new profile_field_condition();
        $context = new evaluation_context((int) $user->id);

        $this->assertTrue($condition->evaluate(
            ['operator' => 'is', 'field' => 'institution', 'value' => 'UTAD'],
            $context
        ));
    }

    public function test_is_does_not_match_different_value(): void {
        $user = $this->getDataGenerator()->create_user(['institution' => 'UTAD']);
        $condition = new profile_field_condition();
        $context = new evaluation_context((int) $user->id);

        $this->assertFalse($condition->evaluate(
            ['operator' => 'is', 'field' => 'institution', 'value' => 'Other'],
            $context
        ));
    }

    public function test_is_matches_case_sensitively(): void {
        // Deliberately mirrors availability_profile's own OP_IS_EQUAL_TO semantics (strict
        // string equality), not this plugin's own coursetag normalization - a different,
        // Moodle-precedented condition warrants matching Moodle's own behaviour, not this
        // plugin's other conventions.
        $user = $this->getDataGenerator()->create_user(['institution' => 'UTAD']);
        $condition = new profile_field_condition();
        $context = new evaluation_context((int) $user->id);

        $this->assertFalse($condition->evaluate(
            ['operator' => 'is', 'field' => 'institution', 'value' => 'utad'],
            $context
        ));
    }

    public function test_is_not_matches_when_different(): void {
        $user = $this->getDataGenerator()->create_user(['institution' => 'UTAD']);
        $condition = new profile_field_condition();
        $context = new evaluation_context((int) $user->id);

        $this->assertTrue($condition->evaluate(
            ['operator' => 'is_not', 'field' => 'institution', 'value' => 'Other'],
            $context
        ));
    }

    public function test_is_not_fails_when_same(): void {
        $user = $this->getDataGenerator()->create_user(['institution' => 'UTAD']);
        $condition = new profile_field_condition();
        $context = new evaluation_context((int) $user->id);

        $this->assertFalse($condition->evaluate(
            ['operator' => 'is_not', 'field' => 'institution', 'value' => 'UTAD'],
            $context
        ));
    }

    public function test_is_matches_custom_field(): void {
        $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'text', 'shortname' => 'employeeid', 'name' => 'Employee ID',
        ]);
        $user = $this->getDataGenerator()->create_user(['profile_field_employeeid' => '12345']);

        $condition = new profile_field_condition();
        $context = new evaluation_context((int) $user->id);

        $this->assertTrue($condition->evaluate(
            ['operator' => 'is', 'field' => 'employeeid', 'customfield' => true, 'value' => '12345'],
            $context
        ));
    }

    /**
     * The JS builder must never let this happen (a numeric-looking value stored as a JSON
     * number rather than a string - see rule_editor.js's `stringvalue` schema flag), but the
     * engine itself must still behave correctly if a numeric idnumber-style value arrives as a
     * PHP int rather than a string, since evaluate() casts $config['value'] with (string).
     */
    public function test_is_matches_numeric_looking_value_cast_from_int(): void {
        $user = $this->getDataGenerator()->create_user(['idnumber' => '12345']);
        $condition = new profile_field_condition();
        $context = new evaluation_context((int) $user->id);

        $this->assertTrue($condition->evaluate(
            ['operator' => 'is', 'field' => 'idnumber', 'value' => 12345],
            $context
        ));
    }

    public function test_anonymous_userid_zero_never_matches_is(): void {
        $condition = new profile_field_condition();
        $context = new evaluation_context(0);

        $this->assertFalse($condition->evaluate(
            ['operator' => 'is', 'field' => 'institution', 'value' => 'UTAD'],
            $context
        ));
    }

    public function test_anonymous_userid_zero_matches_is_not(): void {
        // No value at all is correctly "not equal to" any specific value.
        $condition = new profile_field_condition();
        $context = new evaluation_context(0);

        $this->assertTrue($condition->evaluate(
            ['operator' => 'is_not', 'field' => 'institution', 'value' => 'UTAD'],
            $context
        ));
    }

    public function test_unknown_standard_field_never_matches(): void {
        $user = $this->getDataGenerator()->create_user();
        $condition = new profile_field_condition();
        $context = new evaluation_context((int) $user->id);

        // Not in STANDARD_FIELDS and not flagged as a custom field: must not silently query an
        // arbitrary column name.
        $this->assertFalse($condition->evaluate(
            ['operator' => 'is', 'field' => 'password', 'value' => 'anything'],
            $context
        ));
    }

    public function test_deleted_custom_field_never_matches_is(): void {
        $user = $this->getDataGenerator()->create_user();
        $condition = new profile_field_condition();
        $context = new evaluation_context((int) $user->id);

        $this->assertFalse($condition->evaluate(
            ['operator' => 'is', 'field' => 'doesnotexist', 'customfield' => true, 'value' => 'x'],
            $context
        ));
    }

    public function test_validate_rejects_unknown_operator(): void {
        $this->expectException(\coding_exception::class);
        (new profile_field_condition())->validate(['operator' => 'contains', 'field' => 'institution', 'value' => 'x']);
    }

    public function test_validate_rejects_empty_field(): void {
        $this->expectException(\coding_exception::class);
        (new profile_field_condition())->validate(['operator' => 'is', 'field' => '  ', 'value' => 'x']);
    }

    public function test_validate_rejects_non_string_value(): void {
        $this->expectException(\coding_exception::class);
        (new profile_field_condition())->validate(['operator' => 'is', 'field' => 'institution', 'value' => 123]);
    }

    public function test_validate_accepts_valid_config(): void {
        // Should not throw.
        (new profile_field_condition())->validate(['operator' => 'is', 'field' => 'institution', 'value' => 'UTAD']);
        $this->addToAssertionCount(1);
    }
}
