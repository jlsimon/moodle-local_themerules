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
 * Recursively evaluates a parsed expression tree. See SPECIFICATIONS.md section 16.
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_themerules\local\engine;

use local_themerules\local\condition\condition_registry;

/**
 * Recursively evaluates a parsed expression tree. See SPECIFICATIONS.md section 16.
 *
 * Expects a tree already validated by expression_parser (unknown node types/operators
 * are treated as a coding error here, not re-validated defensively).
 */
class evaluator {
    /**
     * Recursively evaluates one expression node (a condition leaf, or an and/or group).
     *
     * @param array $node
     * @param evaluation_context $context
     * @return bool
     */
    public function evaluate(array $node, evaluation_context $context): bool {
        $type = $node['type'] ?? null;

        if ($type === 'condition') {
            return condition_registry::get($node['condition'])->evaluate($node, $context);
        }

        if ($type === 'group') {
            if ($node['operator'] === 'and') {
                foreach ($node['children'] as $child) {
                    // Short-circuit: stop at first false.
                    if (!$this->evaluate($child, $context)) {
                        return false;
                    }
                }
                return true;
            }

            if ($node['operator'] === 'or') {
                foreach ($node['children'] as $child) {
                    // Short-circuit: stop at first true.
                    if ($this->evaluate($child, $context)) {
                        return true;
                    }
                }
                return false;
            }
        }

        throw new \coding_exception('local_themerules: cannot evaluate malformed expression node');
    }
}
