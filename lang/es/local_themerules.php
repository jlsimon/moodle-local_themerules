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

$string['pluginname'] = 'Reglas de tema';
$string['condition_user'] = 'Usuario';
$string['condition_course'] = 'Curso';
$string['condition_coursecategory'] = 'Categoría de curso';
$string['condition_cohort'] = 'Cohorte';
$string['condition_device'] = 'Tipo de dispositivo';
$string['condition_coursetag'] = 'Etiqueta de curso';

// Device type values (shared by the condition, the simulator device selector, and traces).
$string['device_default'] = 'Escritorio/por defecto';
$string['device_mobile'] = 'Móvil';
$string['device_tablet'] = 'Tablet';
$string['device_legacy'] = 'Navegador antiguo';

// Capabilities.
$string['themerules:view'] = 'Ver reglas de tema';
$string['themerules:manage'] = 'Gestionar reglas de tema';
$string['themerules:simulate'] = 'Simular reglas de tema';

// Administration list page.
$string['createrule'] = 'Crear regla';
$string['editrule'] = 'Editar regla';
$string['norules'] = 'Todavía no se ha creado ninguna regla. Se aplica la selección de tema normal de Moodle.';
$string['actions'] = 'Acciones';
$string['lastmodified'] = 'Última modificación';
$string['status_enabled'] = 'Activada';
$string['status_disabled'] = 'Desactivada';
$string['enable'] = 'Activar';
$string['disable'] = 'Desactivar';
$string['duplicate'] = 'Duplicar';
$string['duplicate_name'] = '{$a} (copia)';
$string['confirm_delete'] = '¿Eliminar la regla "{$a}"? Esta acción no se puede deshacer.';

// Notifications.
$string['notify_saved'] = 'Regla guardada.';
$string['notify_updated'] = 'Regla actualizada.';
$string['notify_duplicated'] = 'Regla duplicada (creada desactivada).';
$string['notify_deleted'] = 'Regla eliminada.';

// Form.
$string['form_name'] = 'Nombre de la regla';
$string['form_description'] = 'Descripción';
$string['form_enabled'] = 'Activada';
$string['form_priority'] = 'Prioridad';
$string['form_theme'] = 'Aplicar tema';
$string['form_expression'] = 'Expresión de condición (JSON)';
$string['form_expression_help'] = 'Un árbol de condiciones lógicas en JSON. Ejemplo:

{"type": "group", "operator": "and", "children": [
  {"type": "condition", "condition": "coursecategory", "operator": "in_category", "value": 12, "includechildren": true},
  {"type": "condition", "condition": "cohort", "operator": "member", "value": 7}
]}

Identificadores de condición disponibles: user (operador "is"), course (operador "is"), coursecategory (operador "in_category", opcionalmente "includechildren"), cohort (operador "member" o "not_member"), device (operador "is" o "is_not", valor "default", "mobile", "tablet" o "legacy"), coursetag (operador "has" o "not_has", valor el nombre de una etiqueta). Operadores de grupo: "and", "or". Un editor visual estará disponible en una futura versión.';
$string['form_timestart'] = 'Válida desde';
$string['form_timeend'] = 'Válida hasta';
$string['form_save'] = 'Guardar regla';

// Validation errors.
$string['error_name_required'] = 'Indica un nombre para esta regla.';
$string['error_priority_invalid'] = 'La prioridad debe ser un número.';
$string['error_expression_invalid'] = 'Expresión de condición no válida: {$a}';
$string['error_theme_required'] = 'Elige un tema.';
$string['error_theme_invalid'] = 'Tema no válido: {$a}';
$string['error_timeend_before_timestart'] = '"Válida hasta" debe ser posterior a "Válida desde".';

// Visual rule editor (JS).
$string['editor_matchlabel'] = 'Coincidir con';
$string['editor_matchall'] = 'TODAS las siguientes';
$string['editor_matchany'] = 'CUALQUIERA de las siguientes';
$string['editor_addcondition'] = 'Añadir condición';
$string['editor_addgroup'] = 'Añadir grupo';
$string['editor_removecondition'] = 'Eliminar condición';
$string['editor_removegroup'] = 'Eliminar grupo';
$string['editor_condition'] = 'Condición';
$string['editor_operator'] = 'Operador';
$string['editor_value'] = 'Valor';
$string['editor_includechildren'] = 'Incluir subcategorías';

// Simulator.
$string['simulator'] = 'Simular';
$string['simulate'] = 'Simular';
$string['simulator_facts'] = 'Hechos resueltos';
$string['simulator_fact'] = 'Hecho';
$string['simulator_value'] = 'Valor';
$string['simulator_evaluation'] = 'Evaluación de reglas';
$string['simulator_matched'] = 'Coincide';
$string['simulator_notmatched'] = 'No coincide';
$string['simulator_resulttrue'] = 'Resultado: VERDADERO';
$string['simulator_resultfalse'] = 'Resultado: FALSO';
$string['simulator_wouldselect'] = '→ seleccionaría el tema "{$a}"';
$string['simulator_selectedtheme'] = 'Tema seleccionado';
$string['simulator_selectedthemevalue'] = 'Se seleccionaría el tema "{$a}".';
$string['simulator_nomatch'] = 'Ninguna regla coincide. Se aplicaría la resolución de tema normal de Moodle.';
$string['simulator_devicetype'] = 'Tipo de dispositivo';
$string['simulator_devicetype_auto'] = 'Detectar automáticamente (tu navegador actual)';

// Trace text (simulator condition lines and facts).
$string['trace_fact_user'] = 'Usuario';
$string['trace_fact_course'] = 'Curso';
$string['trace_fact_category'] = 'Categoría';
$string['trace_fact_cohorts'] = 'Cohortes';
$string['trace_fact_none'] = 'Ninguna';
$string['trace_fact_device'] = 'Tipo de dispositivo';
$string['trace_fact_coursetags'] = 'Etiquetas del curso';
$string['trace_user'] = 'El usuario es {$a}';
$string['trace_course'] = 'El curso es {$a}';
$string['trace_coursecategory'] = 'La categoría de curso es {$a}';
$string['trace_includingdescendants'] = '(incluyendo subcategorías)';
$string['trace_cohort'] = 'El usuario {$a->verb} la cohorte {$a->name}';
$string['trace_member'] = 'es miembro de';
$string['trace_notmember'] = 'no es miembro de';
$string['trace_device'] = 'El tipo de dispositivo {$a->verb} {$a->name}';
$string['trace_is'] = 'es';
$string['trace_isnot'] = 'no es';
$string['trace_coursetag'] = 'El curso {$a->verb} la etiqueta {$a->name}';
$string['trace_has'] = 'tiene';
$string['trace_nothas'] = 'no tiene';
$string['trace_notfound'] = '#{$a} (no encontrado)';
$string['trace_error'] = 'No se pudo evaluar esta regla: {$a}';

// Privacy API.
$string['privacy:metadata'] = 'El plugin Reglas de tema almacena la configuración de las reglas
    (nombre, condiciones, acción, prioridad, fechas de validez) y, para cada regla, el id del
    administrador que la creó o modificó por última vez. Ese id es un registro de auditoría de
    una acción de configuración administrativa, no datos personales sobre ese administrador
    procesados por este plugin, por lo que no se implementa exportación/eliminación para él.';

// Events.
$string['event_rule_created'] = 'Regla de tema creada';
$string['event_rule_updated'] = 'Regla de tema actualizada';
$string['event_rule_deleted'] = 'Regla de tema eliminada';
$string['event_rule_enabled'] = 'Regla de tema activada';
$string['event_rule_disabled'] = 'Regla de tema desactivada';
