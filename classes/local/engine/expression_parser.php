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
 * Decodes and validates a rule's condition-tree JSON. See SPECIFICATIONS.md section 17.
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules\local\engine;

use local_themerules\local\condition\condition_registry;

/**
 * Decodes and validates a rule's condition-tree JSON. See SPECIFICATIONS.md section 17.
 */
class expression_parser {
    /** @var int Recommended safety limit (SPECIFICATIONS.md section 17). */
    const MAX_DEPTH = 10;

    /** @var int Recommended safety limit (SPECIFICATIONS.md section 17). */
    const MAX_NODES = 100;

    /**
     * Decodes and validates a rule's expression JSON into its tree form.
     *
     * @param string $json
     * @return array
     * @throws \coding_exception on invalid JSON or an invalid/unsafe expression tree.
     */
    public function parse(string $json): array {
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            throw new \coding_exception('local_themerules: invalid rule expression JSON: ' .
                json_last_error_msg());
        }

        $nodecount = 0;
        $this->validate_node($data, 1, $nodecount);

        return $data;
    }

    /**
     * Recursively validates one node's shape/depth/count, throwing on the first violation.
     *
     * @param array $node
     * @param int $depth
     * @param int $nodecount Running total across the whole tree, passed by reference.
     */
    private function validate_node(array $node, int $depth, int &$nodecount): void {
        $nodecount++;
        if ($nodecount > self::MAX_NODES) {
            throw new \coding_exception('local_themerules: rule expression exceeds maximum number of nodes');
        }
        if ($depth > self::MAX_DEPTH) {
            throw new \coding_exception('local_themerules: rule expression exceeds maximum nesting depth');
        }

        $type = $node['type'] ?? null;

        if ($type === 'condition') {
            $identifier = $node['condition'] ?? '';
            if ($identifier === '' || !condition_registry::has($identifier)) {
                throw new \coding_exception('local_themerules: unknown condition identifier: ' . $identifier);
            }
            if (empty($node['operator'])) {
                throw new \coding_exception('local_themerules: condition node missing "operator"');
            }
            if (!array_key_exists('value', $node)) {
                throw new \coding_exception('local_themerules: condition node missing "value"');
            }
            condition_registry::get($identifier)->validate($node);
            return;
        }

        if ($type === 'group') {
            $operator = $node['operator'] ?? null;
            if (!in_array($operator, ['and', 'or'], true)) {
                throw new \coding_exception('local_themerules: group node has unknown operator: ' . $operator);
            }
            if (empty($node['children']) || !is_array($node['children'])) {
                throw new \coding_exception('local_themerules: group node must have at least one child');
            }
            foreach ($node['children'] as $child) {
                if (!is_array($child)) {
                    throw new \coding_exception('local_themerules: group child must be an object');
                }
                $this->validate_node($child, $depth + 1, $nodecount);
            }
            return;
        }

        throw new \coding_exception('local_themerules: unknown expression node type: ' . $type);
    }
}
