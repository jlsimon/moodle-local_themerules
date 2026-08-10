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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Visual nested AND/OR rule builder (SPECIFICATIONS.md section 22/65).
 *
 * Progressively enhances the plain "condition expression (JSON)" textarea
 * added in Phase 4: on init, the textarea is hidden (not removed - if this
 * script fails to load, the admin can still enter/edit raw JSON, since
 * theme selection must never depend on client-side JavaScript and neither
 * should the rule editor be unusable without it, SPECIFICATIONS.md section
 * 1/44) and replaced with an interactive tree of add/remove condition and
 * group controls, kept in sync with the textarea's value on every change
 * and again on submit.
 *
 * @module     local_themerules/rule_editor
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {

    var DEFAULT_CONDITION_NODE = function(schema) {
        return {
            type: 'condition',
            condition: schema.identifier,
            operator: schema.operators[0],
            value: ''
        };
    };

    var DEFAULT_GROUP_NODE = function() {
        return {type: 'group', operator: 'and', children: []};
    };

    /**
     * @param {string} textareaId DOM id of the expressionjson textarea.
     * @param {Array} conditionSchemas from condition_registry::get_all_editor_schemas().
     * @param {Object} strings UI labels, see edit.php for the exact keys passed.
     */
    var init = function(textareaId, conditionSchemas, strings) {
        var textarea = document.getElementById(textareaId);
        if (!textarea) {
            return;
        }

        var schemasByIdentifier = {};
        conditionSchemas.forEach(function(schema) {
            schemasByIdentifier[schema.identifier] = schema;
        });

        /**
         * The root must be a "group" so the add-condition/add-group controls (only rendered by
         * renderGroup()) are always reachable - a bare "condition" root, as produced by older
         * saved rules or a fresh textarea, would otherwise leave the admin with a single
         * condition and no way to chain more with AND/OR.
         *
         * @param {Object|null} parsed Parsed textarea JSON, or null/invalid.
         * @return {Object} A "group" node.
         */
        function normalizeRoot(parsed) {
            if (parsed && parsed.type === 'group') {
                return parsed;
            }
            var conditionNode = (parsed && parsed.type === 'condition') ?
                parsed : DEFAULT_CONDITION_NODE(conditionSchemas[0]);
            var group = DEFAULT_GROUP_NODE();
            group.children.push(conditionNode);
            return group;
        }

        var tree;
        try {
            tree = normalizeRoot(JSON.parse(textarea.value));
        } catch (e) {
            tree = normalizeRoot(null);
        }

        var container = document.createElement('div');
        container.className = 'local-themerules-rule-editor';

        textarea.style.display = 'none';
        textarea.setAttribute('aria-hidden', 'true');
        textarea.parentNode.insertBefore(container, textarea);

        var form = textarea.closest('form');
        if (form) {
            form.addEventListener('submit', sync);
        }

        /**
         * Writes the current tree back into the textarea, so a plain form submit (JS-driven or
         * not) always sends what the visual editor currently shows.
         */
        function sync() {
            textarea.value = JSON.stringify(tree);
        }

        /**
         * Fully re-renders the tree from scratch. Simpler and fast enough at the expression sizes
         * this plugin allows (max 100 nodes, SPECIFICATIONS.md section 17) than incremental DOM
         * patching would be.
         */
        function render() {
            container.textContent = '';
            container.appendChild(renderNode(tree, null));
            sync();
        }

        /**
         * @param {Object} node
         * @param {Function|null} onRemove called with no arguments to remove this node from its
         *        parent, or null if this is the tree's root (which cannot be removed).
         * @return {HTMLElement}
         */
        function renderNode(node, onRemove) {
            return node.type === 'group' ? renderGroup(node, onRemove) : renderCondition(node, onRemove);
        }

        /**
         * @param {Object} node A "group" expression node.
         * @param {Function|null} onRemove see renderNode().
         * @return {HTMLElement}
         */
        function renderGroup(node, onRemove) {
            var wrapper = document.createElement('fieldset');
            wrapper.className = 'local-themerules-group border rounded p-2 mb-2';

            var legend = document.createElement('legend');
            legend.className = 'local-themerules-group-legend d-flex align-items-center gap-2';
            legend.style.fontSize = '1rem';

            var label = document.createElement('span');
            label.textContent = strings.matchlabel;
            legend.appendChild(label);

            var operatorSelect = document.createElement('select');
            operatorSelect.className = 'form-select form-select-sm d-inline-block w-auto mx-2';
            operatorSelect.setAttribute('aria-label', strings.matchlabel);
            [['and', strings.matchall], ['or', strings.matchany]].forEach(function(pair) {
                var option = document.createElement('option');
                option.value = pair[0];
                option.textContent = pair[1];
                option.selected = node.operator === pair[0];
                operatorSelect.appendChild(option);
            });
            operatorSelect.addEventListener('change', function() {
                node.operator = operatorSelect.value;
                sync();
            });
            legend.appendChild(operatorSelect);

            if (onRemove) {
                legend.appendChild(makeButton(strings.removegroup, 'btn-outline-danger', function() {
                    onRemove();
                    render();
                }));
            }

            wrapper.appendChild(legend);

            var childrenContainer = document.createElement('div');
            childrenContainer.className = 'local-themerules-group-children ms-3';
            node.children.forEach(function(child, index) {
                // A group must keep at least one child (enforced server-side too), so hide the
                // remove control on the last remaining one instead of allowing an empty group.
                var canRemove = node.children.length > 1;
                childrenContainer.appendChild(renderNode(child, canRemove ? function() {
                    node.children.splice(index, 1);
                } : null));
            });
            wrapper.appendChild(childrenContainer);

            var addRow = document.createElement('div');
            addRow.className = 'local-themerules-add-row d-flex gap-2 mt-1';
            addRow.appendChild(makeButton(strings.addcondition, 'btn-outline-secondary', function() {
                node.children.push(DEFAULT_CONDITION_NODE(conditionSchemas[0]));
                render();
            }));
            addRow.appendChild(makeButton(strings.addgroup, 'btn-outline-secondary', function() {
                node.children.push(DEFAULT_GROUP_NODE());
                render();
            }));
            wrapper.appendChild(addRow);

            return wrapper;
        }

        /**
         * @param {Object} node A "condition" expression node.
         * @param {Function|null} onRemove see renderNode().
         * @return {HTMLElement}
         */
        function renderCondition(node, onRemove) {
            var row = document.createElement('div');
            row.className = 'local-themerules-condition d-flex flex-wrap align-items-center gap-2 mb-2';

            var conditionSelect = document.createElement('select');
            conditionSelect.className = 'form-select form-select-sm w-auto';
            conditionSelect.setAttribute('aria-label', strings.condition);
            conditionSchemas.forEach(function(schema) {
                var option = document.createElement('option');
                option.value = schema.identifier;
                option.textContent = schema.name;
                option.selected = node.condition === schema.identifier;
                conditionSelect.appendChild(option);
            });
            conditionSelect.addEventListener('change', function() {
                var schema = schemasByIdentifier[conditionSelect.value];
                node.condition = schema.identifier;
                node.operator = schema.operators[0];
                delete node.includechildren;
                render();
            });
            row.appendChild(conditionSelect);

            var schema = schemasByIdentifier[node.condition] || conditionSchemas[0];

            var operatorSelect = document.createElement('select');
            operatorSelect.className = 'form-select form-select-sm w-auto';
            operatorSelect.setAttribute('aria-label', strings.operator);
            schema.operators.forEach(function(operator) {
                var option = document.createElement('option');
                option.value = operator;
                option.textContent = operator;
                option.selected = node.operator === operator;
                operatorSelect.appendChild(option);
            });
            operatorSelect.addEventListener('change', function() {
                node.operator = operatorSelect.value;
                sync();
            });
            row.appendChild(operatorSelect);

            var valueInput;
            if (schema.options) {
                // Fixed enum (e.g. device type): a free-text field would invite typos that
                // silently never match, so offer exactly the valid values instead.
                valueInput = document.createElement('select');
                valueInput.className = 'form-select form-select-sm w-auto';
                valueInput.setAttribute('aria-label', strings.value);
                schema.options.forEach(function(option) {
                    var optionEl = document.createElement('option');
                    optionEl.value = option.value;
                    optionEl.textContent = option.label;
                    optionEl.selected = node.value === option.value;
                    valueInput.appendChild(optionEl);
                });
                valueInput.addEventListener('change', function() {
                    node.value = valueInput.value;
                    sync();
                });
                if (node.value === undefined || node.value === '') {
                    node.value = schema.options[0] ? schema.options[0].value : '';
                }
            } else {
                valueInput = document.createElement('input');
                valueInput.type = 'text';
                valueInput.className = 'form-control form-control-sm w-auto';
                valueInput.style.maxWidth = '10rem';
                valueInput.setAttribute('aria-label', strings.value);
                valueInput.placeholder = strings.value;
                valueInput.value = node.value === undefined ? '' : node.value;
                valueInput.addEventListener('input', function() {
                    var numeric = parseInt(valueInput.value, 10);
                    node.value = isNaN(numeric) ? valueInput.value : numeric;
                    sync();
                });
            }
            row.appendChild(valueInput);

            if (schema.supportsincludechildren) {
                var checkboxLabel = document.createElement('label');
                checkboxLabel.className = 'form-check-label d-flex align-items-center gap-1 mb-0';
                var checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.className = 'form-check-input';
                checkbox.checked = !!node.includechildren;
                checkbox.addEventListener('change', function() {
                    node.includechildren = checkbox.checked;
                    sync();
                });
                checkboxLabel.appendChild(checkbox);
                checkboxLabel.appendChild(document.createTextNode(strings.includechildren));
                row.appendChild(checkboxLabel);
            }

            if (onRemove) {
                row.appendChild(makeButton(strings.removecondition, 'btn-outline-danger', function() {
                    onRemove();
                    render();
                }));
            }

            return row;
        }

        /**
         * @param {string} text Visible button label.
         * @param {string} extraClass Additional Bootstrap button class.
         * @param {Function} onClick
         * @return {HTMLElement}
         */
        function makeButton(text, extraClass, onClick) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-sm ' + extraClass;
            button.textContent = text;
            button.addEventListener('click', onClick);
            return button;
        }

        render();
    };

    return {
        init: init
    };
});
