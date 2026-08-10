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

$string['pluginname'] = 'Theme rules';
$string['condition_user'] = 'User';
$string['condition_course'] = 'Course';
$string['condition_coursecategory'] = 'Course category';
$string['condition_cohort'] = 'Cohort';
$string['condition_device'] = 'Device type';
$string['condition_coursetag'] = 'Course tag';

// Device type values (shared by the condition, the simulator device selector, and traces).
$string['device_default'] = 'Desktop/default';
$string['device_mobile'] = 'Mobile';
$string['device_tablet'] = 'Tablet';
$string['device_legacy'] = 'Legacy browser';

// Capabilities.
$string['themerules:view'] = 'View theme rules';
$string['themerules:manage'] = 'Manage theme rules';
$string['themerules:simulate'] = 'Simulate theme rules';

// Administration list page.
$string['createrule'] = 'Create rule';
$string['editrule'] = 'Edit rule';
$string['norules'] = 'No rules have been created yet. Moodle\'s normal theme selection applies to everyone.';
$string['actions'] = 'Actions';
$string['lastmodified'] = 'Last modified';
$string['status_enabled'] = 'Enabled';
$string['status_disabled'] = 'Disabled';
$string['enable'] = 'Enable';
$string['disable'] = 'Disable';
$string['moveup'] = 'Move up';
$string['movedown'] = 'Move down';
$string['duplicate'] = 'Duplicate';
$string['duplicate_name'] = '{$a} (copy)';
$string['confirm_delete'] = 'Delete rule "{$a}"? This cannot be undone.';

// Notifications.
$string['notify_saved'] = 'Rule saved.';
$string['notify_updated'] = 'Rule updated.';
$string['notify_duplicated'] = 'Rule duplicated (created disabled).';
$string['notify_deleted'] = 'Rule deleted.';
$string['notify_reordered'] = 'Rule order updated.';
$string['notify_logo_saved'] = 'Logo saved.';
$string['notify_logo_deleted'] = 'Logo deleted.';

// Logo library (logos.php).
$string['logos'] = 'Logos';
$string['editlogo'] = 'Edit logo';
$string['nologos'] = 'No logos have been uploaded yet.';
$string['logopreview'] = 'Preview';
$string['form_logoname'] = 'Logo name';
$string['form_logofile'] = 'Logo image';
$string['confirm_delete_logo'] = 'Delete logo "{$a}"? Any rule still referencing it will simply stop applying a logo, same as an uninstalled theme.';

// Form.
$string['form_name'] = 'Rule name';
$string['form_description'] = 'Description';
$string['form_enabled'] = 'Enabled';
$string['form_theme'] = 'Apply theme';
$string['form_logo'] = 'Apply logo';
$string['form_logo_none'] = "Don't change the logo";
$string['form_expression'] = 'Condition expression (JSON)';
$string['form_expression_help'] = 'A logical condition tree in JSON. Example:

{"type": "group", "operator": "and", "children": [
  {"type": "condition", "condition": "coursecategory", "operator": "in_category", "value": 12, "includechildren": true},
  {"type": "condition", "condition": "cohort", "operator": "member", "value": 7}
]}

Available condition identifiers: user (operator "is"), course (operator "is"), coursecategory (operator "in_category", optional "includechildren"), cohort (operator "member" or "not_member"), device (operator "is" or "is_not", value one of "default", "mobile", "tablet", "legacy"), coursetag (operator "has" or "not_has", value a tag name). Group operators: "and", "or". A visual editor for this will be available in a future version.';
$string['form_timestart'] = 'Valid from';
$string['form_timeend'] = 'Valid until';
$string['form_save'] = 'Save rule';

// Validation errors.
$string['error_name_required'] = 'Please give this rule a name.';
$string['error_expression_invalid'] = 'Invalid condition expression: {$a}';
$string['error_theme_invalid'] = 'Invalid theme: {$a}';
$string['error_logo_invalid'] = 'Invalid logo: {$a}';
$string['error_action_required'] = 'Choose a theme, a logo, or both - a rule with neither would do nothing.';
$string['error_timeend_before_timestart'] = '"Valid until" must be after "Valid from".';

// Visual rule editor (JS).
$string['editor_matchlabel'] = 'Match';
$string['editor_matchall'] = 'ALL of the following';
$string['editor_matchany'] = 'ANY of the following';
$string['editor_addcondition'] = 'Add condition';
$string['editor_addgroup'] = 'Add group';
$string['editor_removecondition'] = 'Remove condition';
$string['editor_removegroup'] = 'Remove group';
$string['editor_condition'] = 'Condition';
$string['editor_operator'] = 'Operator';
$string['editor_value'] = 'Value';
$string['editor_includechildren'] = 'Include subcategories';

// Simulator.
$string['simulator'] = 'Simulate';
$string['simulate'] = 'Simulate';
$string['simulator_facts'] = 'Resolved facts';
$string['simulator_fact'] = 'Fact';
$string['simulator_value'] = 'Value';
$string['simulator_evaluation'] = 'Rule evaluation';
$string['simulator_matched'] = 'Matched';
$string['simulator_notmatched'] = 'Did not match';
$string['simulator_resulttrue'] = 'Result: TRUE';
$string['simulator_resultfalse'] = 'Result: FALSE';
$string['simulator_wouldselect'] = '→ would select theme "{$a}"';
$string['simulator_wouldselectlogo'] = '→ would apply logo "{$a}"';
$string['simulator_selectedtheme'] = 'Selected theme';
$string['simulator_selectedthemevalue'] = 'Theme "{$a}" would be selected.';
$string['simulator_nomatch'] = 'No rule matches. Normal Moodle theme resolution would apply.';
$string['simulator_selectedlogo'] = 'Selected logo';
$string['simulator_selectedlogovalue'] = 'Logo "{$a}" would be applied.';
$string['simulator_nologomatch'] = 'No rule matches. The site\'s normal logo would be shown.';
$string['simulator_devicetype'] = 'Device type';
$string['simulator_devicetype_auto'] = 'Auto-detect (your current browser)';

// Trace text (simulator condition lines and facts).
$string['trace_fact_user'] = 'User';
$string['trace_fact_course'] = 'Course';
$string['trace_fact_category'] = 'Category';
$string['trace_fact_cohorts'] = 'Cohorts';
$string['trace_fact_none'] = 'None';
$string['trace_fact_device'] = 'Device type';
$string['trace_fact_coursetags'] = 'Course tags';
$string['trace_user'] = 'User is {$a}';
$string['trace_course'] = 'Course is {$a}';
$string['trace_coursecategory'] = 'Course category is {$a}';
$string['trace_includingdescendants'] = '(including subcategories)';
$string['trace_cohort'] = 'User is {$a->verb} cohort {$a->name}';
$string['trace_member'] = 'a member of';
$string['trace_notmember'] = 'not a member of';
$string['trace_device'] = 'Device type {$a->verb} {$a->name}';
$string['trace_is'] = 'is';
$string['trace_isnot'] = 'is not';
$string['trace_coursetag'] = 'Course {$a->verb} the tag {$a->name}';
$string['trace_has'] = 'has';
$string['trace_nothas'] = 'does not have';
$string['trace_notfound'] = '#{$a} (not found)';
$string['trace_error'] = 'Could not evaluate this rule: {$a}';

// Privacy API.
$string['privacy:metadata'] = 'The Theme rules plugin stores rule configuration (name, conditions,
    action, evaluation order, validity dates) and, for each rule, the id of the administrator who last
    created or modified it. That id is an audit trail of an administrative configuration action,
    not personal data about that administrator collected or processed by this plugin, so no
    export/deletion is implemented for it.';

// Events.
$string['event_rule_created'] = 'Theme rule created';
$string['event_rule_updated'] = 'Theme rule updated';
$string['event_rule_deleted'] = 'Theme rule deleted';
$string['event_rule_enabled'] = 'Theme rule enabled';
$string['event_rule_disabled'] = 'Theme rule disabled';
