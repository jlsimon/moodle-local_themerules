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
 * `core/ajax` is the only Moodle dependency the entity pickers need (a
 * lightweight fetch-based module, not the jQuery-heavy `core/form-
 * autocomplete` widget - deliberately not used here since it has no clean
 * teardown/rebuild lifecycle for a tree that fully re-renders on every
 * structural change the way this file's render() already does; see
 * DECISIONS.md for the fuller reasoning).
 *
 * @module     local_themerules/rule_editor
 * @copyright  2026 Jose Luis Simon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax'], function(Ajax) {

    var defaultConditionNode = function(schema) {
        var node = {
            type: 'condition',
            condition: schema.identifier,
            operator: schema.operators[0],
            value: ''
        };
        applyFieldDefaults(node, schema);
        return node;
    };

    var defaultGroupNode = function() {
        return {type: 'group', operator: 'and', children: []};
    };

    /**
     * Conditions with a "field" selector (e.g. profilefield's standard-vs-custom user profile
     * field picker) need node.field/node.customfield seeded to the first option, the same way
     * defaultConditionNode seeds operator/value - shared here since this runs both when a
     * brand new condition node is created and when an existing node's condition type is
     * switched to one that has fieldoptions (renderCondition()'s conditionSelect handler).
     *
     * @param {Object} node
     * @param {Object} schema
     */
    function applyFieldDefaults(node, schema) {
        if (schema.fieldoptions && schema.fieldoptions.length) {
            node.field = schema.fieldoptions[0].value;
            node.customfield = schema.fieldoptions[0].customfield;
        }
    }

    /**
     * Thin wrapper around the plugin's two AJAX entity-picker endpoints.
     */
    var entityRepository = {
        /**
         * @param {string} entitytype 'user', 'course' or 'coursegroup'
         * @param {string} query
         * @param {Number} courseid Only meaningful for entitytype 'coursegroup'.
         * @return {Promise}
         */
        search: function(entitytype, query, courseid) {
            return Ajax.call([{
                methodname: 'local_themerules_search_entities',
                args: {entitytype: entitytype, query: query || '', courseid: courseid || 0}
            }])[0];
        },

        /**
         * @param {string} entitytype
         * @param {Number} id
         * @return {Promise}
         */
        resolve: function(entitytype, id) {
            return Ajax.call([{
                methodname: 'local_themerules_resolve_entity',
                args: {entitytype: entitytype, id: id}
            }])[0];
        }
    };

    /**
     * @param {string} textareaId DOM id of the expressionjson textarea.
     * @param {string} schemasElementId DOM id of the <script type="application/json"> element
     *        holding condition_registry::get_all_editor_schemas() - embedded in the page rather
     *        than passed as a js_call_amd() argument, since that payload is easily over the
     *        1024-character threshold core warns about once real site data (every cohort/
     *        category/custom field...) is included; see edit.php for where it's rendered.
     * @param {Object} strings UI labels, see edit.php for the exact keys passed.
     */
    var init = function(textareaId, schemasElementId, strings) {
        var textarea = document.getElementById(textareaId);
        var schemasElement = document.getElementById(schemasElementId);
        if (!textarea || !schemasElement) {
            return;
        }
        var conditionSchemas = JSON.parse(schemasElement.textContent);

        var schemasByIdentifier = {};
        conditionSchemas.forEach(function(schema) {
            schemasByIdentifier[schema.identifier] = schema;
        });

        // Transient editor-only state: which course a coursegroup condition's group picker is
        // currently scoped to. Never serialized into the JSON (only the resulting group id is,
        // as node.value, same as every other condition) - keyed by node object identity, which
        // stays stable across render() calls since render() re-walks the same `tree` object
        // rather than rebuilding it from scratch.
        var coursegroupCourseIds = new WeakMap();

        // Resolved display labels, populated by the pre-render resolve pass at the bottom of
        // init() (and updated live as the admin picks new values). entityLabels holds the label
        // for a condition node's own value (the group, for a coursegroup node); coursegroupCourseLabels
        // separately holds the label for a coursegroup node's associated *course* leg of the
        // cascade, which is a different entity than the node's value.
        var entityLabels = new WeakMap();
        var coursegroupCourseLabels = new WeakMap();

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
                parsed : defaultConditionNode(conditionSchemas[0]);
            var group = defaultGroupNode();
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
                node.children.push(defaultConditionNode(conditionSchemas[0]));
                render();
            }));
            addRow.appendChild(makeButton(strings.addgroup, 'btn-outline-secondary', function() {
                node.children.push(defaultGroupNode());
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
                delete node.field;
                delete node.customfield;
                node.value = '';
                coursegroupCourseIds.delete(node);
                coursegroupCourseLabels.delete(node);
                entityLabels.delete(node);
                applyFieldDefaults(node, schema);
                render();
            });
            row.appendChild(conditionSelect);

            var schema = schemasByIdentifier[node.condition] || conditionSchemas[0];

            if (schema.fieldoptions) {
                // Which field this condition checks (e.g. "Institution" vs a custom profile
                // field) - a second picker alongside the condition type, not the value: offering
                // every field this site actually has avoids an admin needing to already know
                // and correctly type a field's internal shortname.
                var fieldSelect = document.createElement('select');
                fieldSelect.className = 'form-select form-select-sm w-auto';
                fieldSelect.setAttribute('aria-label', strings.field);
                schema.fieldoptions.forEach(function(option) {
                    var optionEl = document.createElement('option');
                    optionEl.value = option.value;
                    optionEl.textContent = option.label;
                    optionEl.selected = node.field === option.value;
                    fieldSelect.appendChild(optionEl);
                });
                fieldSelect.addEventListener('change', function() {
                    var chosen = schema.fieldoptions.filter(function(option) {
                        return option.value === fieldSelect.value;
                    })[0];
                    node.field = fieldSelect.value;
                    node.customfield = chosen ? chosen.customfield : false;
                    sync();
                });
                row.appendChild(fieldSelect);
            }

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

            if (schema.entitytype === 'coursegroup') {
                row.appendChild(renderCoursegroupCascade(node, schema));
            } else if (schema.entitytype) {
                row.appendChild(renderEntityPicker(
                    schema.entitytype,
                    schema.entityzerolabel,
                    0,
                    initialEntityLabel(node, schema, entityLabels.get(node)),
                    function(value, label) {
                        node.value = value;
                        entityLabels.set(node, label);
                        sync();
                    }
                ));
            } else if (schema.options) {
                row.appendChild(renderSelectValue(node, schema));
            } else {
                row.appendChild(renderTextValue(node, schema));
            }

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

        /**
         * A fixed-enum <select> (schema.options), e.g. device, or a real populated <select> of
         * everything this site currently has (schema.options built server-side from the DB),
         * e.g. cohort/coursecategory - both share the same value/label option shape and DOM
         * structure, only the coercion on selection differs (see schema.numericvalue below).
         *
         * @param {Object} node
         * @param {Object} schema
         * @return {HTMLElement}
         */
        function renderSelectValue(node, schema) {
            var valueInput = document.createElement('select');
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
                // <select>.value is always a string in the DOM - schema.numericvalue tells us
                // whether this condition's JSON value must stay a number (cohort/coursecategory
                // ids) or a string (device's fixed enum values).
                node.value = schema.numericvalue ? parseInt(valueInput.value, 10) : valueInput.value;
                sync();
            });
            if (node.value === undefined || node.value === '') {
                node.value = schema.options[0] ? schema.options[0].value : '';
            }
            return valueInput;
        }

        /**
         * The plain free-text/number input used by every condition without a picker of its own
         * (SPECIFICATIONS.md section 65's original, still-supported baseline).
         *
         * @param {Object} node
         * @param {Object} schema
         * @return {HTMLElement}
         */
        function renderTextValue(node, schema) {
            var valueInput = document.createElement('input');
            valueInput.type = 'text';
            valueInput.className = 'form-control form-control-sm w-auto';
            valueInput.style.maxWidth = '10rem';
            valueInput.setAttribute('aria-label', strings.value);
            valueInput.placeholder = strings.value;
            valueInput.value = node.value === undefined ? '' : node.value;
            valueInput.addEventListener('input', function() {
                if (schema.stringvalue) {
                    // Must stay a string even if it looks numeric (e.g. an idnumber value) -
                    // see profile_field_condition's editor schema for why.
                    node.value = valueInput.value;
                } else {
                    var numeric = parseInt(valueInput.value, 10);
                    node.value = isNaN(numeric) ? valueInput.value : numeric;
                }
                sync();
            });
            return valueInput;
        }

        /**
         * A single type-ahead search picker: a text input that shows the currently chosen
         * entity's label, and a dropdown of matches while typing. Self-contained (its own
         * debounce timer, its own open/close state) so it survives being torn down and rebuilt
         * by render() with no external lifecycle management needed.
         *
         * @param {string} entitytype 'user', 'course' or 'coursegroup'.
         * @param {string|undefined} zeroLabel If set, a synthetic id-0 choice is always offered
         *        first (e.g. "Anonymous" for user, "Any group in this course" for coursegroup).
         * @param {Number} scopeCourseId For entitytype 'coursegroup', which course to search
         *        within; 0 (no course chosen yet) disables the picker.
         * @param {string} initialLabel What to show in the box before any new selection is made
         *        - the resolved label for an existing saved value, or '' for a brand new/empty
         *        condition node.
         * @param {Function} onCommit Called as onCommit(value, label) with the chosen id and its
         *        display label when the admin picks a result - the caller decides what to do
         *        with it (store on node.value and sync(), or something else, e.g. the cascade's
         *        course leg stores it in a side map and triggers a full render() instead).
         * @return {HTMLElement}
         */
        function renderEntityPicker(entitytype, zeroLabel, scopeCourseId, initialLabel, onCommit) {
            var wrapper = document.createElement('span');
            wrapper.className = 'local-themerules-entitypicker position-relative d-inline-block';

            var input = document.createElement('input');
            input.type = 'text';
            input.className = 'form-control form-control-sm w-auto';
            input.style.minWidth = '14rem';
            input.setAttribute('aria-label', strings.value);
            input.placeholder = strings.searchplaceholder;
            input.value = initialLabel || '';
            wrapper.appendChild(input);

            var dropdown = document.createElement('ul');
            dropdown.className = 'local-themerules-entitypicker-results list-unstyled border rounded bg-white shadow-sm';
            dropdown.style.position = 'absolute';
            dropdown.style.zIndex = '1000';
            dropdown.style.display = 'none';
            dropdown.style.maxHeight = '16rem';
            dropdown.style.overflowY = 'auto';
            dropdown.style.minWidth = '100%';
            wrapper.appendChild(dropdown);

            var debounceTimer = null;

            var disabled = entitytype === 'coursegroup' && !scopeCourseId;
            input.disabled = disabled;
            input.placeholder = disabled ? strings.choosecourse : strings.searchplaceholder;

            /**
             * Hides and clears the results dropdown.
             */
            function closeDropdown() {
                dropdown.style.display = 'none';
                dropdown.textContent = '';
            }

            /**
             * Commits a chosen search result: updates the box's displayed text and hands the
             * value/label off to the caller via onCommit.
             *
             * @param {Object} result {value, label}
             */
            function selectResult(result) {
                input.value = result.label;
                closeDropdown();
                onCommit(result.value, result.label);
            }

            /**
             * Renders a fetched result list into the dropdown, prefixing the synthetic
             * zero-label choice (if this picker has one) and showing a "no results" row when
             * empty.
             *
             * @param {Array} results {value, label} pairs from entityRepository.search().
             */
            function renderResults(results) {
                dropdown.textContent = '';
                var items = results.slice();
                if (zeroLabel) {
                    items.unshift({value: 0, label: zeroLabel});
                }
                if (!items.length) {
                    var empty = document.createElement('li');
                    empty.className = 'px-2 py-1 text-muted';
                    empty.textContent = strings.noresults;
                    dropdown.appendChild(empty);
                } else {
                    items.forEach(function(result) {
                        var item = document.createElement('li');
                        item.className = 'local-themerules-entitypicker-result px-2 py-1';
                        item.style.cursor = 'pointer';
                        item.textContent = result.label;
                        item.addEventListener('mousedown', function(event) {
                            // Mousedown (not click) fires before the input's blur handler closes
                            // the dropdown, so the click still lands on the intended item.
                            event.preventDefault();
                            selectResult(result);
                        });
                        dropdown.appendChild(item);
                    });
                }
                dropdown.style.display = 'block';
            }

            /**
             * Shows a loading placeholder, then fetches and renders matches for the given query.
             *
             * @param {string} query
             */
            function search(query) {
                dropdown.textContent = '';
                var loading = document.createElement('li');
                loading.className = 'px-2 py-1 text-muted';
                loading.textContent = strings.loading;
                dropdown.appendChild(loading);
                dropdown.style.display = 'block';

                entityRepository.search(entitytype, query, scopeCourseId).then(function(results) {
                    renderResults(results);
                    return null;
                }).catch(function() {
                    renderResults([]);
                });
            }

            input.addEventListener('input', function() {
                window.clearTimeout(debounceTimer);
                var query = input.value;
                debounceTimer = window.setTimeout(function() {
                    search(query);
                }, 300);
            });
            input.addEventListener('focus', function() {
                if (!disabled) {
                    search(input.value);
                }
            });
            input.addEventListener('blur', function() {
                window.setTimeout(closeDropdown, 150);
            });
            input.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    closeDropdown();
                }
            });

            return wrapper;
        }

        /**
         * The two-field course -> group cascade coursegroup needs, since a bare group id isn't
         * identifiable without knowing which course it belongs to (DECISIONS.md). The course
         * choice is transient editor state (coursegroupCourseIds), never written into the JSON;
         * only the resulting group id is ever stored as node.value.
         *
         * @param {Object} node
         * @param {Object} schema
         * @return {HTMLElement}
         */
        function renderCoursegroupCascade(node, schema) {
            var wrapper = document.createElement('span');
            wrapper.className = 'local-themerules-coursegroup-cascade d-flex flex-wrap align-items-center gap-2';

            var courseId = coursegroupCourseIds.get(node) || 0;

            var coursePicker = renderEntityPicker(
                'course',
                undefined,
                0,
                coursegroupCourseLabels.get(node) || '',
                function(value, label) {
                    if (value !== courseId) {
                        coursegroupCourseIds.set(node, value);
                        coursegroupCourseLabels.set(node, label);
                        node.value = '';
                        entityLabels.delete(node);
                        render();
                    }
                }
            );
            wrapper.appendChild(coursePicker);

            var groupPicker = renderEntityPicker(
                'coursegroup',
                schema.entityzerolabel,
                courseId,
                initialEntityLabel(node, schema, entityLabels.get(node)),
                function(value, label) {
                    node.value = value;
                    entityLabels.set(node, label);
                    sync();
                }
            );
            wrapper.appendChild(groupPicker);

            return wrapper;
        }

        /**
         * What an entity picker should show before any new selection is made: a previously
         * resolved label if one is cached, the schema's synthetic zero-label if the node's value
         * is already 0 (Anonymous / Any group - never needs a network round trip), or the raw
         * value as a last-resort fallback (a not-yet-resolved or no-longer-existing entity still
         * shows *something* rather than a mysteriously empty box).
         *
         * @param {Object} node
         * @param {Object} schema
         * @param {string|undefined} cachedLabel
         * @return {string}
         */
        function initialEntityLabel(node, schema, cachedLabel) {
            if (cachedLabel) {
                return cachedLabel;
            }
            if (node.value === 0 && schema.entityzerolabel) {
                return schema.entityzerolabel;
            }
            return node.value === undefined || node.value === '' ? '' : String(node.value);
        }

        /**
         * @param {Object} node
         * @return {?{entitytype: string, id: Number}} What needs resolving to a label before
         *         first paint, or null if this node has nothing to resolve (no entitytype
         *         schema, or an empty/not-yet-set value).
         */
        function entityResolveTarget(node) {
            if (node.type !== 'condition') {
                return null;
            }
            var schema = schemasByIdentifier[node.condition];
            if (!schema || !schema.entitytype || node.value === '' || node.value === undefined) {
                return null;
            }
            var id = parseInt(node.value, 10);
            if (isNaN(id) || id === 0) {
                // 0 is always the synthetic zero-label choice where one exists (Anonymous / Any
                // group) - never needs a network round trip to resolve.
                return null;
            }
            return {node: node, entitytype: schema.entitytype, id: id};
        }

        /**
         * Walks the whole tree collecting every entity-typed value that needs a label resolved
         * before the picker can show something better than a bare id on first paint.
         *
         * @param {Object} node
         * @param {Array} targets
         */
        function collectEntityTargets(node, targets) {
            if (node.type === 'group') {
                node.children.forEach(function(child) {
                    collectEntityTargets(child, targets);
                });
                return;
            }
            var target = entityResolveTarget(node);
            if (target) {
                targets.push(target);
            }
        }

        var targets = [];
        collectEntityTargets(tree, targets);

        if (!targets.length) {
            render();
            return;
        }

        // Resolve every entity-typed value's label (and, for coursegroup, its owning course)
        // before the first paint, so the pickers open already showing real names instead of
        // bare ids that then jump to a label a moment later.
        Promise.all(targets.map(function(target) {
            return entityRepository.resolve(target.entitytype, target.id).then(function(resolved) {
                if (resolved && resolved.found) {
                    entityLabels.set(target.node, resolved.label);
                    if (target.entitytype === 'coursegroup') {
                        coursegroupCourseIds.set(target.node, resolved.courseid);
                        coursegroupCourseLabels.set(target.node, resolved.coursename);
                    }
                }
                return null;
            }).catch(function() {
                return null;
            });
        })).then(function() {
            render();
            return null;
        }).catch(function() {
            render();
        });
    };

    return {
        init: init
    };
});
