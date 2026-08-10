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
 * "User is <userid>" condition. See SPECIFICATIONS.md section 13.
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules\local\condition;

use local_themerules\local\engine\evaluation_context;

/**
 * "User is <userid>" condition. See SPECIFICATIONS.md section 13.
 */
class user_condition implements condition_interface {
    /** @var string[] Operators this condition currently accepts. */
    const OPERATORS = ['is'];

    /**
     * Human-readable name for this condition (the current user), shown in the editor's condition dropdown.
     */
    public function get_name(): string {
        return get_string('condition_user', 'local_themerules');
    }

    /**
     * Identifier used in expression JSON, e.g. {"condition": "..."}.
     */
    public function get_identifier(): string {
        return 'user';
    }

    /**
     * Validates a condition node's operator/value, throwing on error.
     *
     * @param array $config
     */
    public function validate(array $config): void {
        if (!in_array($config['operator'] ?? null, self::OPERATORS, true)) {
            throw new \coding_exception('local_themerules: unknown operator for user condition: ' .
                ($config['operator'] ?? ''));
        }
        if (!is_numeric($config['value'] ?? null)) {
            throw new \coding_exception('local_themerules: user condition value must be a user id');
        }
    }

    /**
     * Whether this condition (the current user) holds for the given facts.
     *
     * @param array $config
     * @param evaluation_context $context
     */
    public function evaluate(array $config, evaluation_context $context): bool {
        return $context->get_userid() === (int) $config['value'];
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
            'valuetype' => 'user',
            // Tells the JS builder to render a type-ahead search picker (backed by
            // local_themerules_search_entities/resolve_entity) instead of a raw numeric id
            // input - a site can have far too many users for a plain <select>, unlike
            // cohort/coursecategory's small fixed lists. The picker always offers a synthetic
            // "Anonymous / not logged in" choice (id 0) alongside real search results - id 0 is
            // a real, resolvable fact for this condition (SPECIFICATIONS.md/DECISIONS.md), not
            // just an id the entity search would ever itself return.
            'entitytype' => 'user',
            'entityzerolabel' => get_string('trace_anonymous', 'local_themerules'),
        ];
    }
}
