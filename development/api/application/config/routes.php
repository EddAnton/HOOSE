<?php
defined('BASEPATH') or exit('No direct script access allowed');

$route['default_controller'] = 'bienvenida';
// $route['404_override'] = $route['default_controller'];
$route['404_override'] = '404';
$route['translate_uri_dashes'] = false;

// Dejar (:any) sólo para get's

/**
 * Estatus de las recaudaciones
 */
$route['estatus-recaudaciones']['get'] = 'Estatus_Recaudaciones/listar';
$route['estatus-recaudaciones/activos']['get'] = 'Estatus_Recaudaciones/listar/true';

/**
 * Estatus de las quejas
 */
$route['estatus-quejas']['get'] = 'Estatus_Quejas/listar';
$route['estatus-quejas/activos']['get'] = 'Estatus_Quejas/listar/true';

/**
 * Tipos de fondos monetarios
 */
$route['tipos-fondos-monetarios']['get'] = 'Tipos_Fondos_Monetarios/listar';
$route['tipos-fondos-monetarios/activos']['get'] = 'Tipos_Fondos_Monetarios/listar/true';

/**
 * Tipos de asambleas
 */
$route['tipos-asambleas']['get'] = 'Tipos_Asambleas/listar';
$route['tipos-asambleas/activos']['get'] = 'Tipos_Asambleas/listar/true';

/**
 * Tipos de movimientos de los fondos
 */
$route['tipos-movimientos-fondos']['get'] = 'Tipos_Fondos_Monetarios/listar';
$route['tipos-movimientos-fondos/activos']['get'] = 'Tipos_Movimientos_Fondos/listar/true';

/**
 * Formas de pago
 */
$route['formas-pago']['get'] = 'Formas_Pago/listar';
$route['formas-pago/activos']['get'] = 'Formas_Pago/listar/true';

/**
 * Dashboard
 */
$route['dashboard']['post'] = 'Dashboard/listar';
$route['dashboard/graph/tmp']['get'] = 'Dashboard/grpTmp';

/**
 * Condominios
 */
$route['condominios']['get'] = 'Condominios/listar';
$route['condominios/activos']['get'] = 'Condominios/listar/true';
$route['condominios/(:num)']['get'] = 'Condominios/listar';
$route['condominios/insertar']['post'] = 'Condominios/insertar';
$route['condominios/actualizar/(:num)']['post'] = 'Condominios/actualizar';
$route['condominios/alternar-estatus/(:num)']['post'] = 'Condominios/alternar_estatus';

/**
 * Edificios
 */
$route['edificios']['get'] = 'Edificios/listar';
$route['edificios/activos']['get'] = 'Edificios/listar/true';
$route['edificios/(:num)']['get'] = 'Edificios/listar';
$route['edificios/insertar']['post'] = 'Edificios/insertar';
$route['edificios/actualizar/(:num)']['post'] = 'Edificios/actualizar';
$route['edificios/alternar-estatus/(:num)']['post'] = 'Edificios/alternar_estatus';
$route['edificios/deshabilitar/(:num)']['post'] = 'Edificios/alternar_estatus/0';

/**
 * Unidades
 */
$route['unidades']['get'] = 'Unidades/listar';
$route['unidades/activos']['get'] = 'Unidades/listar/true';
$route['unidades/(:num)']['get'] = 'Unidades/listar';
$route['unidades/insertar']['post'] = 'Unidades/insertar';
$route['unidades/actualizar/(:num)']['post'] = 'Unidades/actualizar';
$route['unidades/alternar-estatus/(:num)']['post'] = 'Unidades/alternar_estatus';
$route['unidades/deshabilitar/(:num)']['post'] = 'Unidades/alternar_estatus/0';
$route['unidades/sin-propietario']['get'] = 'Unidades/listar_sin_propietario';
$route['unidades/disponibles-renta']['get'] = 'Unidades/listar_disponibles_renta';
$route['unidades/para-recaudaciones']['get'] = 'Unidades/listar_para_recaudaciones';
$route['unidades/para-visita']['get'] = 'Unidades/listar_para_visita';

/**
 * Usuarios
 */
/*
$route['usuarios']['get'] = 'Usuarios/listar';
$route['usuarios/(:num)']['get'] = 'Usuarios/listar';
$route['usuarios/insertar']['post'] = 'Usuarios/insertar';
$route['usuarios/actualizar/(:num)']['post'] = 'Usuarios/actualizar';
*/
$route['iniciar-sesion']['post'] = 'Usuarios/iniciar_sesion';
$route['seleccionar-condominio']['post'] = 'Usuarios/seleccionar_condominio';
$route['usuarios/alternar-estatus/(:num)']['post'] = 'Usuarios/alternar_estatus';
$route['usuarios/cambiar-contrasenia/(:num)']['post'] = 'Usuarios/cambiar_contrasenia';
$route['usuarios/reiniciar-contrasenia/(:num)']['post'] = 'Usuarios/reiniciar_contrasenia';
$route['usuarios/identificacion-anverso/(:num)']['get'] = 'Usuarios/identificacion_anverso';
$route['usuarios/perfiles-tablero-avisos']['get'] = 'Usuarios/listar_perfiles_usuarios_tablero_avisos';
$route['usuarios/propietarios-y-condominos']['get'] = 'Usuarios/listar_propietarios_condominos';
$route['usuarios/para-notificaciones']['get'] = 'Usuarios/listar_usuarios_notificaciones';
$route['usuarios/para-acta-asamblea']['get'] = 'Usuarios/listar_usuarios_acta_asamblea';


/**
 * Propietarios
 */
$route['propietarios']['get'] = 'Usuarios_Propietarios/listar';
$route['propietarios/(:num)']['get'] = 'Usuarios_Propietarios/listar';
$route['propietarios/activos']['get'] = 'Usuarios_Propietarios/listar/true';
$route['propietarios/insertar']['post'] = 'Usuarios_Propietarios/insertar';
$route['propietarios/actualizar/(:num)']['post'] = 'Usuarios_Propietarios/actualizar';
$route['propietarios/deshabilitar/(:num)']['post'] = 'Usuarios_Propietarios/deshabilitar';

/**
 * Condominos
 */
$route['condominos']['get'] = 'Usuarios_Condominos/listar';
$route['condominos/(:num)']['get'] = 'Usuarios_Condominos/listar';
$route['condominos/activos']['get'] = 'Usuarios_Condominos/listar/true';
$route['condominos/insertar']['post'] = 'Usuarios_Condominos/insertar';
$route['condominos/actualizar/(:num)']['post'] = 'Usuarios_Condominos/actualizar';
$route['condominos/alternar-estatus/(:num)']['post'] = 'Usuarios_Condominos/alternar_estatus';
$route['condominos/finalizar-contrato/(:num)']['post'] = 'Usuarios_Condominos/finalizar_contrato';

/**
 * Colaboradores
 */
$route['colaboradores']['get'] = 'Usuarios_Colaboradores/listar';
$route['colaboradores/(:num)']['get'] = 'Usuarios_Colaboradores/listar';
$route['colaboradores/activos']['get'] = 'Usuarios_Colaboradores/listar/true';
$route['colaboradores/insertar']['post'] = 'Usuarios_Colaboradores/insertar';
$route['colaboradores/actualizar/(:num)']['post'] = 'Usuarios_Colaboradores/actualizar';
$route['colaboradores/deshabilitar/(:num)']['post'] = 'Usuarios_Colaboradores/deshabilitar';

/**
 * Administradores
 */
$route['administradores']['get'] = 'Usuarios_Administradores/listar';
$route['administradores/(:num)']['get'] = 'Usuarios_Administradores/listar';
$route['administradores/activos']['get'] = 'Usuarios_Administradores/listar/true';
$route['administradores/insertar']['post'] = 'Usuarios_Administradores/insertar';
$route['administradores/actualizar/(:num)']['post'] = 'Usuarios_Administradores/actualizar';

/**
 * Tipo de Miembros
 */
$route['tipos-miembros']['get'] = 'Tipos_Miembros/listar';
$route['tipos-miembros/activos']['get'] = 'Tipos_Miembros/listar/0/true';
$route['tipos-miembros/(:num)']['get'] = 'Tipos_Miembros/listar';
$route['tipos-miembros/insertar']['post'] = 'Tipos_Miembros/insertar';
$route['tipos-miembros/actualizar/(:num)']['post'] = 'Tipos_Miembros/actualizar';
$route['tipos-miembros/alternar-estatus/(:num)']['post'] = 'Tipos_Miembros/alternar_estatus';
$route['tipos-miembros-colaboradores']['get'] = 'Tipos_Miembros/listar/1';
$route['tipos-miembros-colaboradores/activos']['get'] = 'Tipos_Miembros/listar/1/true';
$route['tipos-miembros-no-colaboradores']['get'] = 'Tipos_Miembros/listar/2';
$route['tipos-miembros-no-colaboradores/activos']['get'] = 'Tipos_Miembros/listar/2/true';

/**
 * Gastos Fijos
 */
$route['gastos-fijos']['get'] = 'Gastos_Fijos/listar';
$route['gastos-fijos/activos']['get'] = 'Gastos_Fijos/listar/true';
$route['gastos-fijos/(:num)']['get'] = 'Gastos_Fijos/listar';
$route['gastos-fijos/insertar']['post'] = 'Gastos_Fijos/insertar';
$route['gastos-fijos/actualizar/(:num)']['post'] = 'Gastos_Fijos/actualizar';
$route['gastos-fijos/alternar-estatus/(:num)']['post'] = 'Gastos_Fijos/alternar_estatus';
$route['gastos-fijos/alternar-estatus/(:num)']['post'] = 'Gastos_Fijos/alternar_estatus';

/**
 * Áreas comunes
 */
$route['areas-comunes']['get'] = 'Areas_Comunes/listar';
$route['areas-comunes/activos']['get'] = 'Areas_Comunes/listar/true';
$route['areas-comunes/(:num)']['get'] = 'Areas_Comunes/listar';
$route['areas-comunes/para-reservaciones']['get'] = 'Areas_Comunes/listar_para_reservaciones';
$route['areas-comunes/insertar']['post'] = 'Areas_Comunes/insertar';
$route['areas-comunes/actualizar/(:num)']['post'] = 'Areas_Comunes/actualizar';
$route['areas-comunes/alternar-estatus/(:num)']['post'] = 'Areas_Comunes/alternar_estatus';
$route['areas-comunes/reservaciones']['post'] = 'Areas_Comunes/listar_reservaciones';
$route['areas-comunes/reservacion/(:num)']['get'] = 'Areas_Comunes/listar_reservacion';
$route['areas-comunes/reservaciones/insertar/(:num)']['post'] = 'Areas_Comunes/insertar_reservacion';
$route['areas-comunes/reservaciones/actualizar/(:num)']['post'] = 'Areas_Comunes/actualizar_reservacion';
$route['areas-comunes/reservaciones/registrar-pago/(:num)']['post'] = 'Areas_Comunes/registrar_pago_reservacion';
$route['areas-comunes/reservaciones/cancelar/(:num)']['post'] = 'Areas_Comunes/cancelar_reservacion';

/**
 * Recaudaciones
 */
$route['recaudaciones']['get'] = 'Recaudaciones/listar';
$route['recaudaciones/(:num)']['get'] = 'Recaudaciones/listar';
$route['recaudaciones/activos']['get'] = 'Recaudaciones/listar/true';
$route['recaudaciones/insertar']['post'] = 'Recaudaciones/insertar';
$route['recaudaciones/actualizar/(:num)']['post'] = 'Recaudaciones/actualizar';
$route['recaudaciones/registrar-pago/(:num)']['post'] = 'Recaudaciones/registrar_pago';
$route['recaudaciones/recibo-pago/(:num)']['get'] = 'Recaudaciones/listar_recibo_pago';
$route['recaudaciones/eliminar/(:num)']['post'] = 'Recaudaciones/eliminar';

/**
 * Nomina
 */
$route['nomina']['get'] = 'Nomina/listar';
$route['nomina/(:num)']['get'] = 'Nomina/listar';
$route['nomina/activos']['get'] = 'Nomina/listar/true';
$route['nomina/insertar']['post'] = 'Nomina/insertar';
// $route['nomina/actualizar/(:num)']['post'] = 'Nomina/actualizar';
$route['nomina/eliminar/(:num)']['post'] = 'Nomina/eliminar';

/**
 * Gastos mantenimiento
 */
$route['gastos-mantenimiento']['get'] = 'Gastos_Mantenimiento/listar';
$route['gastos-mantenimiento/(:num)']['get'] = 'Gastos_Mantenimiento/listar';
$route['gastos-mantenimiento/activos']['get'] = 'Gastos_Mantenimiento/listar/true';
$route['gastos-mantenimiento/insertar']['post'] = 'Gastos_Mantenimiento/insertar';
// $route['gastos-mantenimiento/actualizar/(:num)']['post'] = 'Gastos_Mantenimiento/actualizar';
$route['gastos-mantenimiento/eliminar/(:num)']['post'] = 'Gastos_Mantenimiento/eliminar';

/**
 * Cuotas de mantenimiento
 */
$route['cuotas-mantenimiento']['get'] = 'Cuotas_Mantenimiento/listar';
$route['cuotas-mantenimiento/(:num)']['get'] = 'Cuotas_Mantenimiento/listar';
$route['cuotas-mantenimiento/activos']['get'] = 'Cuotas_Mantenimiento/listar/true';
$route['cuotas-mantenimiento/insertar']['post'] = 'Cuotas_Mantenimiento/insertar';
$route['cuotas-mantenimiento/total-para-generacion-masiva']['post'] = 'Cuotas_Mantenimiento/generacion_masiva/true';
$route['cuotas-mantenimiento/generacion-masiva']['post'] = 'Cuotas_Mantenimiento/generacion_masiva';
$route['cuotas-mantenimiento/actualizar/(:num)']['post'] = 'Cuotas_Mantenimiento/actualizar';
$route['cuotas-mantenimiento/registrar-pago/(:num)']['post'] = 'Cuotas_Mantenimiento/registrar_pago';
$route['cuotas-mantenimiento/eliminar/(:num)']['post'] = 'Cuotas_Mantenimiento/eliminar';
$route['cuotas-mantenimiento/eliminar-pago/(:num)']['post'] = 'Cuotas_Mantenimiento/eliminar_pago';
$route['cuotas-mantenimiento/recibo-pago/(:num)']['get'] = 'Cuotas_Mantenimiento/listar_recibo_pago';

/**
 * Fondos monetarios
 */
$route['fondos-monetarios']['get'] = 'Fondos_Monetarios/listar';
$route['fondos-monetarios/(:num)']['get'] = 'Fondos_Monetarios/listar';
$route['fondos-monetarios/activos']['get'] = 'Fondos_Monetarios/listar/true';
$route['fondos-monetarios/insertar']['post'] = 'Fondos_Monetarios/insertar';
$route['fondos-monetarios/actualizar/(:num)']['post'] = 'Fondos_Monetarios/actualizar';
$route['fondos-monetarios/eliminar/(:num)']['post'] = 'Fondos_Monetarios/eliminar';
$route['fondos-monetarios/traspaso/(:num)']['post'] = 'Fondos_Monetarios/traspaso';
$route['fondos-monetarios/movimientos/(:num)']['get'] = 'Fondos_Monetarios/listar_movimientos';
$route['fondos-monetarios/movimientos/(:num)/(:num)']['get'] = 'Fondos_Monetarios/listar_movimientos';
$route['fondos-monetarios/registrar-movimiento/(:num)']['post'] = 'Fondos_Monetarios/registrar_movimiento';
$route['fondos-monetarios/eliminar-movimiento/(:num)']['post'] = 'Fondos_Monetarios/eliminar_movimiento';

/**
 * Comité Administración
 */
$route['miembros-comite-administracion']['get'] = 'Miembros_Comite_Administracion/listar';
$route['miembros-comite-administracion/(:num)']['get'] = 'Miembros_Comite_Administracion/listar';
$route['miembros-comite-administracion/activos']['get'] = 'Miembros_Comite_Administracion/listar/true';
$route['miembros-comite-administracion/insertar']['post'] = 'Miembros_Comite_Administracion/insertar';
$route['miembros-comite-administracion/actualizar/(:num)']['post'] = 'Miembros_Comite_Administracion/actualizar';
$route['miembros-comite-administracion/alternar-estatus/(:num)']['post'] =
  'Miembros_Comite_Administracion/alternar_estatus';

/**
 * Asambleas
 */
$route['asambleas']['get'] = 'Asambleas/listar';
$route['asambleas/(:num)']['get'] = 'Asambleas/listar';
$route['asambleas/activos']['get'] = 'Asambleas/listar/true';
$route['asambleas/detalle/(:num)']['get'] = 'Asambleas/detalle';
$route['asambleas/orden-dia/(:num)']['get'] = 'Asambleas/listar_orden_dia';
$route['asambleas/insertar']['post'] = 'Asambleas/insertar';
$route['asambleas/actualizar/(:num)']['post'] = 'Asambleas/actualizar';
$route['asambleas/eliminar/(:num)']['post'] = 'Asambleas/eliminar';
// $route['asambleas/acta/usuarios-pase-lista']['get'] = 'Asambleas/listar_usuarios_pase_lista';
$route['asambleas/(:num)/acta']['get'] = 'Asambleas/listar_acta';
$route['asambleas/(:num)/acta']['post'] = 'Asambleas/guardar_acta';
// $route['asambleas/acta/finalizar/(:num)']['post'] = 'Asambleas/finalizar_acta';

/**
 * Visitas
 */
$route['visitas']['get'] = 'Visitas/listar';
$route['visitas/activos']['get'] = 'Visitas/listar/true';
$route['visitas/(:num)']['get'] = 'Visitas/listar';
$route['visitas/insertar']['post'] = 'Visitas/insertar';
$route['visitas/actualizar/(:num)']['post'] = 'Visitas/actualizar';
$route['visitas/registrar-salida/(:num)']['post'] = 'Visitas/registrar_salida';
$route['visitas/eliminar/(:num)']['post'] = 'Visitas/eliminar';

/**
 * Proyectos
 */
$route['proyectos']['get'] = 'Proyectos/listar';
$route['proyectos/(:num)']['get'] = 'Proyectos/listar';
$route['proyectos/activos']['get'] = 'Proyectos/listar/true';
$route['proyectos/insertar']['post'] = 'Proyectos/insertar';
$route['proyectos/actualizar/(:num)']['post'] = 'Proyectos/actualizar';
$route['proyectos/eliminar/(:num)']['post'] = 'Proyectos/eliminar';

/**
 * Avisos
 */
$route['tablero-avisos']['get'] = 'Tableros_Avisos/listar';
// $route['tablero-avisos/activos']['get'] = 'Tableros_Avisos/listar/true';
$route['tablero-avisos/perfil/(:num)']['get'] = 'Tableros_Avisos/listar';
$route['tablero-avisos/perfil/(:num)/activos']['get'] = 'Tableros_Avisos/listar/true';
$route['tablero-avisos/perfil/(:num)/publicados']['get'] = 'Tableros_Avisos/listar/true/true';
$route['tablero-avisos/(:num)']['get'] = 'Tableros_Avisos/listar';
$route['tablero-avisos/insertar']['post'] = 'Tableros_Avisos/insertar';
$route['tablero-avisos/actualizar/(:num)']['post'] = 'Tableros_Avisos/actualizar';
$route['tablero-avisos/eliminar/(:num)']['post'] = 'Tableros_Avisos/eliminar';
$route['tablero-avisos/alternar-estatus-publicado/(:num)']['post'] = 'Tableros_Avisos/alternar_estatus_publicado';

/**
 * Notificaciones
 */
// $route['notificaciones/correo-prueba']['get'] = 'Notificaciones/correo_prueba';
$route['notificaciones']['get'] = 'Notificaciones/listar';
$route['notificaciones/activos']['get'] = 'Notificaciones/listar/true';
$route['notificaciones/(:num)']['get'] = 'Notificaciones/listar';
$route['notificaciones/detalle/(:num)']['get'] = 'Notificaciones/listar_detalle';
$route['notificaciones/insertar']['post'] = 'Notificaciones/insertar';
$route['notificaciones/actualizar/(:num)']['post'] = 'Notificaciones/actualizar';
$route['notificaciones/eliminar/(:num)']['post'] = 'Notificaciones/eliminar';
$route['notificaciones/enviar/(:num)']['post'] = 'Notificaciones/enviar';

/**
 * Quejas
 */
$route['quejas']['get'] = 'Quejas/listar';
$route['quejas/activos']['get'] = 'Quejas/listar/true';
$route['quejas/(:num)']['get'] = 'Quejas/listar';
$route['quejas/insertar']['post'] = 'Quejas/insertar';
$route['quejas/actualizar/(:num)']['post'] = 'Quejas/actualizar';
$route['quejas/eliminar/(:num)']['post'] = 'Quejas/eliminar';
$route['quejas/asignar-colaborador/(:num)']['post'] = 'Quejas/asignar_colaborador';
$route['quejas/actualizar-estatus/(:num)']['post'] = 'Quejas/actualizar_estatus';
$route['quejas/seguimiento/(:num)']['get'] = 'Quejas/listar_seguimiento';
$route['quejas/seguimiento/insertar/(:num)']['post'] = 'Quejas/insertar_seguimiento';
$route['quejas/seguimiento/actualizar/(:num)']['post'] = 'Quejas/actualizar_seguimiento';
$route['quejas/seguimiento/eliminar/(:num)']['post'] = 'Quejas/eliminar_seguimiento';

/**
 * Cloud
 */
$route['cloud']['get'] = 'Cloud/listar_carpeta';
$route['cloud/(:num)']['get'] = 'Cloud/listar_carpeta';
$route['cloud/carpeta/crear/(:num)']['post'] = 'Cloud/crear_carpeta';
$route['cloud/carpeta/renombrar/(:num)']['post'] = 'Cloud/renombrar_carpeta';
$route['cloud/carpeta/alternar-estatus/(:num)']['post'] = 'Cloud/alternar_estatus_carpeta';
$route['cloud/archivo/subir/(:num)']['post'] = 'Cloud/subir_archivo';
$route['cloud/archivo/renombrar/(:num)']['post'] = 'Cloud/renombrar_archivo';
$route['cloud/archivo/alternar-estatus/(:num)']['post'] = 'Cloud/alternar_estatus_archivo';

/**
 * Propósito general
 */
$route['proposito-general/login-imagenes']['get'] = 'Proposito_General/login_imagenes';
$route['proposito-general/condominio-default']['get'] = 'Proposito_General/condominio_default';
$route['proposito-general/respaldar-db']['post'] = 'Proposito_General/respaldar_db';

/**
 * Tareas
 */
$route['tareas']['get'] = 'Tareas/listar';
$route['tareas/(:num)']['get'] = 'Tareas/listar';
$route['tareas/insertar']['post'] = 'Tareas/insertar';
$route['tareas/actualizar/(:num)']['post'] = 'Tareas/actualizar';
$route['tareas/cambiar-estatus/(:num)']['post'] = 'Tareas/cambiar_estatus';
$route['tareas/eliminar/(:num)']['post'] = 'Tareas/eliminar';

/**
 * Tareas - usuarios asignables
 */
$route['tareas/usuarios-asignables']['get'] = 'Tareas/usuarios_asignables';
