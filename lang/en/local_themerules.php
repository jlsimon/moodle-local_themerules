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
 * Language strings.
 *
 * @package    local_themerules
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['actions'] = 'Actions';
$string['condition_cohort'] = 'Cohort';
$string['condition_course'] = 'Course';
$string['condition_coursecategory'] = 'Course category';
$string['condition_coursegroup'] = 'Course group';
$string['condition_coursetag'] = 'Course tag';
$string['condition_device'] = 'Device type';
$string['condition_profilefield'] = 'Profile field';
$string['condition_user'] = 'User';
$string['confirm_delete'] = 'Delete rule "{$a}"? This cannot be undone.';
$string['confirm_delete_logo'] = 'Delete logo "{$a}"? Any rule still referencing it will simply stop applying a logo, same as an uninstalled theme.';
$string['createrule'] = 'Create rule';
$string['device_default'] = 'Desktop/default';
$string['device_legacy'] = 'Legacy browser';
$string['device_mobile'] = 'Mobile';
$string['device_tablet'] = 'Tablet';
$string['disable'] = 'Disable';
$string['duplicate'] = 'Duplicate';
$string['duplicate_name'] = '{$a} (copy)';
$string['editlogo'] = 'Edit logo';
$string['editor_addcondition'] = 'Add condition';
$string['editor_addgroup'] = 'Add group';
$string['editor_choosecourse'] = 'Choose a course first';
$string['editor_condition'] = 'Condition';
$string['editor_coursegroup_any'] = 'Any group in this course';
$string['editor_field'] = 'Field';
$string['editor_includechildren'] = 'Include subcategories';
$string['editor_loading'] = 'Loading…';
$string['editor_matchall'] = 'ALL of the following';
$string['editor_matchany'] = 'ANY of the following';
$string['editor_matchlabel'] = 'Match';
$string['editor_noresults'] = 'No results';
$string['editor_operator'] = 'Operator';
$string['editor_removecondition'] = 'Remove condition';
$string['editor_removegroup'] = 'Remove group';
$string['editor_searchplaceholder'] = 'Type to search…';
$string['editor_value'] = 'Value';
$string['editrule'] = 'Edit rule';
$string['enable'] = 'Enable';
$string['error_action_required'] = 'Choose a theme, a logo, or both - a rule with neither would do nothing.';
$string['error_expression_invalid'] = 'Invalid condition expression: {$a}';
$string['error_logo_invalid'] = 'Invalid logo: {$a}';
$string['error_name_required'] = 'Please give this rule a name.';
$string['error_theme_invalid'] = 'Invalid theme: {$a}';
$string['error_timeend_before_timestart'] = '"Valid until" must be after "Valid from".';
$string['event_rule_created'] = 'Theme rule created';
$string['event_rule_deleted'] = 'Theme rule deleted';
$string['event_rule_disabled'] = 'Theme rule disabled';
$string['event_rule_enabled'] = 'Theme rule enabled';
$string['event_rule_updated'] = 'Theme rule updated';
$string['export'] = 'Export';
$string['form_description'] = 'Description';
$string['form_enabled'] = 'Enabled';
$string['form_expression'] = 'Condition expression (JSON)';
$string['form_expression_help'] = 'A logical condition tree in JSON. Example:

{"type": "group", "operator": "and", "children": [
  {"type": "condition", "condition": "coursecategory", "operator": "in_category", "value": 12, "includechildren": true},
  {"type": "condition", "condition": "cohort", "operator": "member", "value": 7}
]}

Available condition identifiers: user (operator "is"), course (operator "is"), coursecategory (operator "in_category", optional "includechildren"), cohort (operator "member" or "not_member"), coursegroup (operator "member" or "not_member", value a group id, 0 = any group), device (operator "is" or "is_not", value one of "default", "mobile", "tablet", "legacy"), coursetag (operator "has" or "not_has", value a tag name), profilefield (operator "is" or "is_not", "field" a standard or custom user profile field shortname, optional "customfield": true for a custom one, "value" a string). Group operators: "and", "or". A visual editor for this will be available in a future version.';
$string['form_logo'] = 'Apply logo';
$string['form_logo_none'] = "Don't change the logo";
$string['form_logofile'] = 'Logo image';
$string['form_logoname'] = 'Logo name';
$string['form_name'] = 'Rule name';
$string['form_rulesfile'] = 'Rules file (JSON)';
$string['form_save'] = 'Save rule';
$string['form_theme'] = 'Apply theme';
$string['form_timeend'] = 'Valid until';
$string['form_timestart'] = 'Valid from';
$string['import'] = 'Import';
$string['import_error_format'] = 'This file is not a valid theme rules export (expected {"format": 1, "rules": [...]}).';
$string['import_error_named'] = 'Rule "{$a->name}": {$a->message}';
$string['import_help'] = 'Upload a JSON file previously produced by Export. Imported rules are always added as new, disabled by default so they never silently start affecting live traffic - review and enable them from the rule list afterwards. User/course/cohort/group ids and theme/logo references only mean the same thing again on this same site (or another site sharing the same ids, e.g. a staging copy) - a reference that does not exist here is skipped safely, the same as any other rule pointing at a deleted entity.';
$string['import_summary'] = 'Imported {$a->imported} of {$a->total} rule(s).';
$string['lastmodified'] = 'Last modified';
$string['logopreview'] = 'Preview';
$string['logos'] = 'Logos';
$string['movedown'] = 'Move down';
$string['moveup'] = 'Move up';
$string['nologos'] = 'No logos have been uploaded yet.';
$string['norules'] = 'No rules have been created yet. Moodle\'s normal theme selection applies to everyone.';
$string['notify_deleted'] = 'Rule deleted.';
$string['notify_duplicated'] = 'Rule duplicated (created disabled).';
$string['notify_logo_deleted'] = 'Logo deleted.';
$string['notify_logo_saved'] = 'Logo saved.';
$string['notify_reordered'] = 'Rule order updated.';
$string['notify_saved'] = 'Rule saved.';
$string['notify_updated'] = 'Rule updated.';
$string['pluginname'] = 'Theme rules';
$string['privacy:metadata'] = 'The Theme rules plugin stores rule configuration (name, conditions,
    action, evaluation order, validity dates) and, for each rule, the id of the administrator who last
    created or modified it. That id is an audit trail of an administrative configuration action,
    not personal data about that administrator collected or processed by this plugin, so no
    export/deletion is implemented for it.';
$string['simulate'] = 'Simulate';
$string['simulator'] = 'Simulate';
$string['simulator_devicetype'] = 'Device type';
$string['simulator_devicetype_auto'] = 'Auto-detect (your current browser)';
$string['simulator_evaluation'] = 'Rule evaluation';
$string['simulator_fact'] = 'Fact';
$string['simulator_facts'] = 'Resolved facts';
$string['simulator_matched'] = 'Matched';
$string['simulator_nologomatch'] = 'No rule matches. The site\'s normal logo would be shown.';
$string['simulator_nomatch'] = 'No rule matches. Normal Moodle theme resolution would apply.';
$string['simulator_notmatched'] = 'Did not match';
$string['simulator_resultfalse'] = 'Result: FALSE';
$string['simulator_resulttrue'] = 'Result: TRUE';
$string['simulator_selectedlogo'] = 'Selected logo';
$string['simulator_selectedlogovalue'] = 'Logo "{$a}" would be applied.';
$string['simulator_selectedtheme'] = 'Selected theme';
$string['simulator_selectedthemevalue'] = 'Theme "{$a}" would be selected.';
$string['simulator_userid_placeholder'] = '0 or blank = anonymous/not logged in';
$string['simulator_value'] = 'Value';
$string['simulator_wouldselect'] = '→ would select theme "{$a}"';
$string['simulator_wouldselectlogo'] = '→ would apply logo "{$a}"';
$string['status_disabled'] = 'Disabled';
$string['status_enabled'] = 'Enabled';
$string['themerules:manage'] = 'Manage theme rules';
$string['themerules:simulate'] = 'Simulate theme rules';
$string['themerules:view'] = 'View theme rules';
$string['trace_anonymous'] = 'Anonymous / not logged in';
$string['trace_cohort'] = 'User is {$a->verb} cohort {$a->name}';
$string['trace_course'] = 'Course is {$a}';
$string['trace_coursecategory'] = 'Course category is {$a}';
$string['trace_coursegroup'] = 'User is {$a->verb} {$a->name}';
$string['trace_coursegroup_any_member'] = 'User belongs to at least one group in the course';
$string['trace_coursegroup_any_notmember'] = 'User does not belong to any group in the course';
$string['trace_coursegroup_member'] = 'a member of group';
$string['trace_coursegroup_notmember'] = 'not a member of group';
$string['trace_coursetag'] = 'Course {$a->verb} the tag {$a->name}';
$string['trace_device'] = 'Device type {$a->verb} {$a->name}';
$string['trace_error'] = 'Could not evaluate this rule: {$a}';
$string['trace_fact_category'] = 'Category';
$string['trace_fact_cohorts'] = 'Cohorts';
$string['trace_fact_course'] = 'Course';
$string['trace_fact_coursegroups'] = 'Course groups';
$string['trace_fact_coursetags'] = 'Course tags';
$string['trace_fact_device'] = 'Device type';
$string['trace_fact_none'] = 'None';
$string['trace_fact_user'] = 'User';
$string['trace_fieldnotfound'] = 'field "{$a}" (not found)';
$string['trace_has'] = 'has';
$string['trace_includingdescendants'] = '(including subcategories)';
$string['trace_is'] = 'is';
$string['trace_isnot'] = 'is not';
$string['trace_member'] = 'a member of';
$string['trace_notfound'] = '#{$a} (not found)';
$string['trace_nothas'] = 'does not have';
$string['trace_notmember'] = 'not a member of';
$string['trace_profilefield'] = 'Profile field "{$a->field}" {$a->verb} "{$a->value}"';
$string['trace_user'] = 'User is {$a}';
