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

$string['actions'] = 'Acciones';
$string['condition_cohort'] = 'Cohorte';
$string['condition_course'] = 'Curso';
$string['condition_coursecategory'] = 'Categoría de curso';
$string['condition_coursegroup'] = 'Grupo de curso';
$string['condition_coursetag'] = 'Etiqueta de curso';
$string['condition_device'] = 'Tipo de dispositivo';
$string['condition_profilefield'] = 'Campo de perfil';
$string['condition_user'] = 'Usuario';
$string['confirm_delete'] = '¿Eliminar la regla "{$a}"? Esta acción no se puede deshacer.';
$string['confirm_delete_logo'] = '¿Eliminar el logo "{$a}"? Cualquier regla que todavía lo use simplemente dejará de aplicar un logo, igual que con un tema desinstalado.';
$string['createrule'] = 'Crear regla';
$string['device_default'] = 'Escritorio/por defecto';
$string['device_legacy'] = 'Navegador antiguo';
$string['device_mobile'] = 'Móvil';
$string['device_tablet'] = 'Tablet';
$string['disable'] = 'Desactivar';
$string['duplicate'] = 'Duplicar';
$string['duplicate_name'] = '{$a} (copia)';
$string['editlogo'] = 'Editar logo';
$string['editor_addcondition'] = 'Añadir condición';
$string['editor_addgroup'] = 'Añadir grupo';
$string['editor_choosecourse'] = 'Elige primero un curso';
$string['editor_condition'] = 'Condición';
$string['editor_coursegroup_any'] = 'Cualquier grupo de este curso';
$string['editor_field'] = 'Campo';
$string['editor_includechildren'] = 'Incluir subcategorías';
$string['editor_loading'] = 'Cargando…';
$string['editor_matchall'] = 'TODAS las siguientes';
$string['editor_matchany'] = 'CUALQUIERA de las siguientes';
$string['editor_matchlabel'] = 'Coincidir con';
$string['editor_noresults'] = 'Sin resultados';
$string['editor_operator'] = 'Operador';
$string['editor_removecondition'] = 'Eliminar condición';
$string['editor_removegroup'] = 'Eliminar grupo';
$string['editor_searchplaceholder'] = 'Escribe para buscar…';
$string['editor_value'] = 'Valor';
$string['editrule'] = 'Editar regla';
$string['enable'] = 'Activar';
$string['error_action_required'] = 'Elige un tema, un logo, o ambos - una regla sin ninguno de los dos no haría nada.';
$string['error_expression_invalid'] = 'Expresión de condición no válida: {$a}';
$string['error_logo_invalid'] = 'Logo no válido: {$a}';
$string['error_name_required'] = 'Indica un nombre para esta regla.';
$string['error_theme_invalid'] = 'Tema no válido: {$a}';
$string['error_timeend_before_timestart'] = '"Válida hasta" debe ser posterior a "Válida desde".';
$string['event_rule_created'] = 'Regla de tema creada';
$string['event_rule_deleted'] = 'Regla de tema eliminada';
$string['event_rule_disabled'] = 'Regla de tema desactivada';
$string['event_rule_enabled'] = 'Regla de tema activada';
$string['event_rule_updated'] = 'Regla de tema actualizada';
$string['export'] = 'Exportar';
$string['form_description'] = 'Descripción';
$string['form_enabled'] = 'Activada';
$string['form_expression'] = 'Expresión de condición (JSON)';
$string['form_expression_help'] = 'Un árbol de condiciones lógicas en JSON. Ejemplo:

{"type": "group", "operator": "and", "children": [
  {"type": "condition", "condition": "coursecategory", "operator": "in_category", "value": 12, "includechildren": true},
  {"type": "condition", "condition": "cohort", "operator": "member", "value": 7}
]}

Identificadores de condición disponibles: user (operador "is"), course (operador "is"), coursecategory (operador "in_category", opcionalmente "includechildren"), cohort (operador "member" o "not_member"), coursegroup (operador "member" o "not_member", valor el id de un grupo, 0 = cualquier grupo), device (operador "is" o "is_not", valor "default", "mobile", "tablet" o "legacy"), coursetag (operador "has" o "not_has", valor el nombre de una etiqueta), profilefield (operador "is" o "is_not", "field" el nombre corto de un campo de perfil estándar o personalizado, opcionalmente "customfield": true si es personalizado, "value" una cadena). Operadores de grupo: "and", "or". Un editor visual estará disponible en una futura versión.';
$string['form_logo'] = 'Aplicar logo';
$string['form_logo_none'] = 'No cambiar el logo';
$string['form_logofile'] = 'Imagen del logo';
$string['form_logoname'] = 'Nombre del logo';
$string['form_name'] = 'Nombre de la regla';
$string['form_rulesfile'] = 'Archivo de reglas (JSON)';
$string['form_save'] = 'Guardar regla';
$string['form_theme'] = 'Aplicar tema';
$string['form_timeend'] = 'Válida hasta';
$string['form_timestart'] = 'Válida desde';
$string['import'] = 'Importar';
$string['import_error_format'] = 'Este archivo no es una exportación válida de reglas de tema (se esperaba {"format": 1, "rules": [...]}).';
$string['import_error_named'] = 'Regla "{$a->name}": {$a->message}';
$string['import_help'] = 'Sube un archivo JSON generado previamente con Exportar. Las reglas importadas siempre se añaden como nuevas, desactivadas por defecto, para que nunca empiecen a afectar al tráfico real sin revisión - actívalas desde el listado de reglas cuando corresponda. Los ids de usuario/curso/cohorte/grupo y las referencias a tema/logo solo significan lo mismo en este mismo sitio (o en otro que comparta esos mismos ids, por ejemplo una copia de preproducción) - una referencia que no exista aquí se omite de forma segura, igual que cualquier otra regla que apunte a una entidad eliminada.';
$string['import_summary'] = 'Se han importado {$a->imported} de {$a->total} regla(s).';
$string['lastmodified'] = 'Última modificación';
$string['logopreview'] = 'Vista previa';
$string['logos'] = 'Logos';
$string['movedown'] = 'Bajar';
$string['moveup'] = 'Subir';
$string['nologos'] = 'Todavía no se ha subido ningún logo.';
$string['norules'] = 'Todavía no se ha creado ninguna regla. Se aplica la selección de tema normal de Moodle.';
$string['notify_deleted'] = 'Regla eliminada.';
$string['notify_duplicated'] = 'Regla duplicada (creada desactivada).';
$string['notify_logo_deleted'] = 'Logo eliminado.';
$string['notify_logo_saved'] = 'Logo guardado.';
$string['notify_reordered'] = 'Orden de la regla actualizado.';
$string['notify_saved'] = 'Regla guardada.';
$string['notify_updated'] = 'Regla actualizada.';
$string['pluginname'] = 'Reglas de tema';
$string['privacy:metadata'] = 'El plugin Reglas de tema almacena la configuración de las reglas
    (nombre, condiciones, acción, orden de evaluación, fechas de validez) y, para cada regla, el id del
    administrador que la creó o modificó por última vez. Ese id es un registro de auditoría de
    una acción de configuración administrativa, no datos personales sobre ese administrador
    procesados por este plugin, por lo que no se implementa exportación/eliminación para él.';
$string['simulate'] = 'Simular';
$string['simulator'] = 'Simular';
$string['simulator_devicetype'] = 'Tipo de dispositivo';
$string['simulator_devicetype_auto'] = 'Detectar automáticamente (tu navegador actual)';
$string['simulator_evaluation'] = 'Evaluación de reglas';
$string['simulator_fact'] = 'Hecho';
$string['simulator_facts'] = 'Hechos resueltos';
$string['simulator_matched'] = 'Coincide';
$string['simulator_nologomatch'] = 'Ninguna regla coincide. Se mostraría el logo normal del sitio.';
$string['simulator_nomatch'] = 'Ninguna regla coincide. Se aplicaría la resolución de tema normal de Moodle.';
$string['simulator_notmatched'] = 'No coincide';
$string['simulator_resultfalse'] = 'Resultado: FALSO';
$string['simulator_resulttrue'] = 'Resultado: VERDADERO';
$string['simulator_selectedlogo'] = 'Logo seleccionado';
$string['simulator_selectedlogovalue'] = 'Se aplicaría el logo "{$a}".';
$string['simulator_selectedtheme'] = 'Tema seleccionado';
$string['simulator_selectedthemevalue'] = 'Se seleccionaría el tema "{$a}".';
$string['simulator_userid_placeholder'] = '0 o en blanco = anónimo/sin sesión iniciada';
$string['simulator_value'] = 'Valor';
$string['simulator_wouldselect'] = '→ seleccionaría el tema "{$a}"';
$string['simulator_wouldselectlogo'] = '→ aplicaría el logo "{$a}"';
$string['status_disabled'] = 'Desactivada';
$string['status_enabled'] = 'Activada';
$string['themerules:manage'] = 'Gestionar reglas de tema';
$string['themerules:simulate'] = 'Simular reglas de tema';
$string['themerules:view'] = 'Ver reglas de tema';
$string['trace_anonymous'] = 'Anónimo / sin sesión iniciada';
$string['trace_cohort'] = 'El usuario {$a->verb} la cohorte {$a->name}';
$string['trace_course'] = 'El curso es {$a}';
$string['trace_coursecategory'] = 'La categoría de curso es {$a}';
$string['trace_coursegroup'] = 'El usuario {$a->verb} {$a->name}';
$string['trace_coursegroup_any_member'] = 'El usuario pertenece a algún grupo del curso';
$string['trace_coursegroup_any_notmember'] = 'El usuario no pertenece a ningún grupo del curso';
$string['trace_coursegroup_member'] = 'es miembro del grupo';
$string['trace_coursegroup_notmember'] = 'no es miembro del grupo';
$string['trace_coursetag'] = 'El curso {$a->verb} la etiqueta {$a->name}';
$string['trace_device'] = 'El tipo de dispositivo {$a->verb} {$a->name}';
$string['trace_error'] = 'No se pudo evaluar esta regla: {$a}';
$string['trace_fact_category'] = 'Categoría';
$string['trace_fact_cohorts'] = 'Cohortes';
$string['trace_fact_course'] = 'Curso';
$string['trace_fact_coursegroups'] = 'Grupos del curso';
$string['trace_fact_coursetags'] = 'Etiquetas del curso';
$string['trace_fact_device'] = 'Tipo de dispositivo';
$string['trace_fact_none'] = 'Ninguna';
$string['trace_fact_user'] = 'Usuario';
$string['trace_fieldnotfound'] = 'campo "{$a}" (no encontrado)';
$string['trace_has'] = 'tiene';
$string['trace_includingdescendants'] = '(incluyendo subcategorías)';
$string['trace_is'] = 'es';
$string['trace_isnot'] = 'no es';
$string['trace_member'] = 'es miembro de';
$string['trace_notfound'] = '#{$a} (no encontrado)';
$string['trace_nothas'] = 'no tiene';
$string['trace_notmember'] = 'no es miembro de';
$string['trace_profilefield'] = 'El campo de perfil "{$a->field}" {$a->verb} "{$a->value}"';
$string['trace_user'] = 'El usuario es {$a}';
