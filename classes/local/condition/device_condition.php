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
 * "Device type is/is not <default|mobile|tablet|legacy>" condition.
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules\local\condition;

use local_themerules\local\engine\evaluation_context;

/**
 * "Device type is/is not <default|mobile|tablet|legacy>" condition.
 *
 * Device type is a fact of the current request (which browser/app is asking), not of
 * a specific user or course, so unlike the other conditions it is always resolvable -
 * evaluation_context::get_devicetype() never returns null (see fact_provider). Values
 * match \core_useragent::DEVICETYPE_* exactly, including the user's own "view full
 * site" override (core_useragent::get_user_device_type() already accounts for that
 * preference, so this condition does too without extra work).
 */
class device_condition implements condition_interface {
    /** @var string[] Operators this condition currently accepts. */
    const OPERATORS = ['is', 'is_not'];

    /** @var string[] Valid device type values, matching \core_useragent::DEVICETYPE_*. */
    const VALUES = ['default', 'mobile', 'tablet', 'legacy'];

    /**
     * Human-readable name for this condition (device type), shown in the editor's condition dropdown.
     */
    public function get_name(): string {
        return get_string('condition_device', 'local_themerules');
    }

    /**
     * Identifier used in expression JSON, e.g. {"condition": "..."}.
     */
    public function get_identifier(): string {
        return 'device';
    }

    /**
     * Validates a condition node's operator/value, throwing on error.
     *
     * @param array $config
     */
    public function validate(array $config): void {
        if (!in_array($config['operator'] ?? null, self::OPERATORS, true)) {
            throw new \coding_exception('local_themerules: unknown operator for device condition: ' .
                ($config['operator'] ?? ''));
        }
        if (!in_array($config['value'] ?? null, self::VALUES, true)) {
            throw new \coding_exception('local_themerules: device condition value must be one of: ' .
                implode(', ', self::VALUES));
        }
    }

    /**
     * Whether this condition (device type) holds for the given facts.
     *
     * @param array $config
     * @param evaluation_context $context
     */
    public function evaluate(array $config, evaluation_context $context): bool {
        $matches = $context->get_devicetype() === (string) $config['value'];

        return $config['operator'] === 'is' ? $matches : !$matches;
    }

    /**
     * Editor schema consumed by the JS rule builder.
     *
     * @return array
     */
    public function get_editor_schema(): array {
        return [
            'identifier' => $this->get_identifier(),
            'name' => $this->get_name(),
            'operators' => self::OPERATORS,
            'valuetype' => 'device',
            // Value/label pairs, not bare values: lets the JS builder render a <select> for a
            // fixed enum (a free-text field invites typos that silently never match) without
            // hardcoding device-type labels client-side.
            'options' => array_map(
                fn (string $value): array => ['value' => $value, 'label' => get_string('device_' . $value, 'local_themerules')],
                self::VALUES
            ),
        ];
    }
}
