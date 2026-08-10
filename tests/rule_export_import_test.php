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

use local_themerules\local\io\rule_export_import;
use local_themerules\local\repository\rule_repository;

#[\PHPUnit\Framework\Attributes\CoversClass(rule_export_import::class)]
/**
 * Unit tests for rule_export_import.
 *
 * @covers \local_themerules\local\io\rule_export_import
 */
final class rule_export_import_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Creates a minimal valid rule via rule_repository::create(), for export tests.
     *
     * @param array $overrides
     * @return int
     */
    private function create_rule(array $overrides = []): int {
        global $USER;

        $record = array_merge([
            'name' => 'Exportable rule',
            'description' => 'A rule for export tests',
            'enabled' => 1,
            'expressionjson' => json_encode(['type' => 'condition', 'condition' => 'user', 'operator' => 'is', 'value' => 5]),
            'actionjson' => json_encode([['type' => 'theme', 'theme' => 'boost']]),
            'timestart' => 0,
            'timeend' => 0,
        ], $overrides);

        return (new rule_repository())->create((object) $record);
    }

    public function test_export_all_empty(): void {
        $result = rule_export_import::export_all(new rule_repository());

        $this->assertSame(['format' => 1, 'rules' => []], $result);
    }

    public function test_export_all_decodes_expression_and_extracts_action_fields(): void {
        $this->setAdminUser();
        $this->create_rule([
            'name' => 'My export',
            'expressionjson' => json_encode(['type' => 'condition', 'condition' => 'course', 'operator' => 'is', 'value' => 7]),
            'actionjson' => json_encode([['type' => 'theme', 'theme' => 'boost'], ['type' => 'logo', 'logoid' => 3]]),
        ]);

        $result = rule_export_import::export_all(new rule_repository());

        $this->assertCount(1, $result['rules']);
        $exported = $result['rules'][0];
        $this->assertSame('My export', $exported['name']);
        $expectedexpr = ['type' => 'condition', 'condition' => 'course', 'operator' => 'is', 'value' => 7];
        $this->assertSame($expectedexpr, $exported['expression']);
        $this->assertSame('boost', $exported['theme']);
        $this->assertSame(3, $exported['logoid']);
    }

    public function test_import_rejects_wrong_format(): void {
        $result = rule_export_import::import(['format' => 2, 'rules' => []], new rule_repository());

        $this->assertSame(0, $result['imported']);
        $this->assertNotEmpty($result['errors']);
    }

    public function test_import_rejects_non_array_data(): void {
        $result = rule_export_import::import('not an array', new rule_repository());

        $this->assertSame(0, $result['imported']);
        $this->assertNotEmpty($result['errors']);
    }

    public function test_import_rejects_missing_rules_key(): void {
        $result = rule_export_import::import(['format' => 1], new rule_repository());

        $this->assertSame(0, $result['imported']);
        $this->assertNotEmpty($result['errors']);
    }

    public function test_import_creates_a_valid_rule_disabled_by_default(): void {
        $this->setAdminUser();
        $data = [
            'format' => 1,
            'rules' => [[
                'name' => 'Imported rule',
                'description' => 'desc',
                'enabled' => true,
                'expression' => ['type' => 'condition', 'condition' => 'user', 'operator' => 'is', 'value' => 9],
                'theme' => 'boost',
                'logoid' => null,
                'timestart' => 0,
                'timeend' => 0,
            ]],
        ];

        $result = rule_export_import::import($data, new rule_repository());

        $this->assertSame(1, $result['imported']);
        $this->assertSame(1, $result['total']);
        $this->assertSame([], $result['errors']);

        $rules = (new rule_repository())->get_all_records_ordered();
        $imported = reset($rules);
        $this->assertSame('Imported rule', $imported->name);
        // Imported disabled by default, same as duplicate() - never silently affects live traffic.
        $this->assertSame(0, (int) $imported->enabled);
    }

    public function test_import_skips_invalid_rule_but_keeps_valid_ones(): void {
        $this->setAdminUser();
        $data = [
            'format' => 1,
            'rules' => [
                [
                    'name' => '',
                    'expression' => ['type' => 'condition', 'condition' => 'user', 'operator' => 'is', 'value' => 1],
                    'theme' => 'boost',
                ],
                [
                    'name' => 'Good rule',
                    'expression' => ['type' => 'condition', 'condition' => 'user', 'operator' => 'is', 'value' => 2],
                    'theme' => 'boost',
                ],
            ],
        ];

        $result = rule_export_import::import($data, new rule_repository());

        $this->assertSame(1, $result['imported']);
        $this->assertSame(2, $result['total']);
        $this->assertCount(1, $result['errors']);
        $this->assertSame(0, $result['errors'][0]['index']);

        $rules = (new rule_repository())->get_all_records_ordered();
        $this->assertCount(1, $rules);
        $this->assertSame('Good rule', reset($rules)->name);
    }

    public function test_import_rejects_rule_with_no_theme_and_no_logo(): void {
        $this->setAdminUser();
        $data = [
            'format' => 1,
            'rules' => [[
                'name' => 'No action rule',
                'expression' => ['type' => 'condition', 'condition' => 'user', 'operator' => 'is', 'value' => 1],
            ]],
        ];

        $result = rule_export_import::import($data, new rule_repository());

        $this->assertSame(0, $result['imported']);
        $this->assertCount(1, $result['errors']);
        $this->assertSame('No action rule', $result['errors'][0]['name']);
    }

    public function test_import_rejects_invalid_expression(): void {
        $this->setAdminUser();
        $data = [
            'format' => 1,
            'rules' => [[
                'name' => 'Bad expression rule',
                'expression' => ['type' => 'condition', 'condition' => 'not_a_real_condition', 'operator' => 'is', 'value' => 1],
                'theme' => 'boost',
            ]],
        ];

        $result = rule_export_import::import($data, new rule_repository());

        $this->assertSame(0, $result['imported']);
        $this->assertCount(1, $result['errors']);
    }

    public function test_export_then_import_round_trip(): void {
        $this->setAdminUser();
        $expression = ['type' => 'condition', 'condition' => 'coursetag', 'operator' => 'has', 'value' => 'exam-mode'];
        $this->create_rule([
            'name' => 'Round trip rule',
            'expressionjson' => json_encode($expression),
            'actionjson' => json_encode([['type' => 'theme', 'theme' => 'boost']]),
        ]);

        $exported = rule_export_import::export_all(new rule_repository());

        // Simulate downloading and re-uploading the file: JSON round trip, not just PHP arrays.
        $reloaded = json_decode(json_encode($exported), true);

        $repository = new rule_repository();
        // Delete the source rule first, so the only "Round trip rule" left after import is the
        // freshly imported one - proves import(), not create()'s own defaults, produced it.
        foreach ($repository->get_all_records_ordered() as $rule) {
            $repository->delete((int) $rule->id);
        }

        $result = rule_export_import::import($reloaded, $repository);

        $this->assertSame(1, $result['imported']);
        $importedrules = $repository->get_all_records_ordered();
        $imported = reset($importedrules);
        $this->assertSame('Round trip rule', $imported->name);
        $this->assertSame($expression, json_decode($imported->expressionjson, true));
    }
}
