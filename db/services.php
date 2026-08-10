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
 * External functions (AJAX web services) for local_themerules.
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_themerules_search_entities' => [
        'classname' => 'local_themerules\external\search_entities',
        'methodname' => 'execute',
        'description' => 'Type-ahead search for the rule editor\'s user/course/coursegroup pickers.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'local_themerules_resolve_entity' => [
        'classname' => 'local_themerules\external\resolve_entity',
        'methodname' => 'execute',
        'description' => 'Resolves an entity id to a display label, to pre-fill a picker when editing an existing rule.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
];
