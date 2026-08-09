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

use local_themerules\privacy\provider;

/**
 * SPECIFICATIONS.md section 33.
 */
#[\PHPUnit\Framework\Attributes\CoversClass(provider::class)]
final class privacy_provider_test extends \advanced_testcase {
    public function test_is_a_null_provider(): void {
        $this->assertInstanceOf(\core_privacy\local\metadata\null_provider::class, new provider());
    }

    public function test_reason_string_exists(): void {
        $reason = provider::get_reason();

        $this->assertSame('privacy:metadata', $reason);
        // A missing string would make get_string() throw, so not throwing is the real assertion.
        $this->assertNotEmpty(get_string($reason, 'local_themerules'));
    }
}
