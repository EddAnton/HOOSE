<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Display Debug backtrace
|--------------------------------------------------------------------------
|
| If set to TRUE, a backtrace will be displayed along with php errors. If
| error_reporting is disabled, the backtrace will not display, regardless
| of this setting
|
*/
defined('SHOW_DEBUG_BACKTRACE') or define('SHOW_DEBUG_BACKTRACE', false);

/*
|--------------------------------------------------------------------------
| File and Directory Modes
|--------------------------------------------------------------------------
|
| These prefs are used when checking and setting modes when working
| with the file system.  The defaults are fine on servers with proper
| security, but you may wish (or even need) to change the values in
| certain environments (Apache running a separate process for each
| user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
| always be used to set the mode correctly.
|
*/
defined('FILE_READ_MODE') or define('FILE_READ_MODE', 0644);
defined('FILE_WRITE_MODE') or define('FILE_WRITE_MODE', 0666);
defined('DIR_READ_MODE') or define('DIR_READ_MODE', 0755);
defined('DIR_WRITE_MODE') or define('DIR_WRITE_MODE', 0755);

/*
|--------------------------------------------------------------------------
| File Stream Modes
|--------------------------------------------------------------------------
|
| These modes are used when working with fopen()/popen()
|
*/
defined('FOPEN_READ') or define('FOPEN_READ', 'rb');
defined('FOPEN_READ_WRITE') or define('FOPEN_READ_WRITE', 'r+b');
defined('FOPEN_WRITE_CREATE_DESTRUCTIVE') or define('FOPEN_WRITE_CREATE_DESTRUCTIVE', 'wb'); // truncates existing file data, use with care
defined('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE') or define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE', 'w+b'); // truncates existing file data, use with care
defined('FOPEN_WRITE_CREATE') or define('FOPEN_WRITE_CREATE', 'ab');
defined('FOPEN_READ_WRITE_CREATE') or define('FOPEN_READ_WRITE_CREATE', 'a+b');
defined('FOPEN_WRITE_CREATE_STRICT') or define('FOPEN_WRITE_CREATE_STRICT', 'xb');
defined('FOPEN_READ_WRITE_CREATE_STRICT') or define('FOPEN_READ_WRITE_CREATE_STRICT', 'x+b');

/*
|--------------------------------------------------------------------------
| Exit Status Codes
|--------------------------------------------------------------------------
|
| Used to indicate the conditions under which the script is exit()ing.
| While there is no universal standard for error codes, there are some
| broad conventions.  Three such conventions are mentioned below, for
| those who wish to make use of them.  The CodeIgniter defaults were
| chosen for the least overlap with these conventions, while still
| leaving room for others to be defined in future versions and user
| applications.
|
| The three main conventions used for determining exit status codes
| are as follows:
|
|    Standard C/C++ Library (stdlibc):
|       http://www.gnu.org/software/libc/manual/html_node/Exit-Status.html
|       (This link also contains other GNU-specific conventions)
|    BSD sysexits.h:
|       http://www.gsp.com/cgi-bin/man.cgi?section=3&topic=sysexits
|    Bash scripting:
|       http://tldp.org/LDP/abs/html/exitcodes.html
|
*/
defined('EXIT_SUCCESS') or define('EXIT_SUCCESS', 0); // no errors
defined('EXIT_ERROR') or define('EXIT_ERROR', 1); // generic error
defined('EXIT_CONFIG') or define('EXIT_CONFIG', 3); // configuration error
defined('EXIT_UNKNOWN_FILE') or define('EXIT_UNKNOWN_FILE', 4); // file not found
defined('EXIT_UNKNOWN_CLASS') or define('EXIT_UNKNOWN_CLASS', 5); // unknown class
defined('EXIT_UNKNOWN_METHOD') or define('EXIT_UNKNOWN_METHOD', 6); // unknown class member
defined('EXIT_USER_INPUT') or define('EXIT_USER_INPUT', 7); // invalid user input
defined('EXIT_DATABASE') or define('EXIT_DATABASE', 8); // database error
defined('EXIT__AUTO_MIN') or define('EXIT__AUTO_MIN', 9); // lowest automatically-assigned error code
defined('EXIT__AUTO_MAX') or define('EXIT__AUTO_MAX', 125); // highest automatically-assigned error code

/*
|   Constantes personalizadas
*/
// Nombre de la aplicación
defined('APP_FULLNAME') or define('APP_FULLNAME', 'Pontevedra REST');
defined('APP_NAME') or define('APP_NAME', 'pontevedra_rest');

// Tipos de archivo de imagen permitidos
defined('IMAGE_FILE_TYPE') or define('IMAGE_FILE_TYPE', serialize(['png', 'jpg', 'jpeg']));
defined('WORD_FILE_TYPE') or define('WORD_FILE_TYPE', serialize(['doc', 'docx']));
defined('DOCUMENT_FILE_TYPE') or define('DOCUMENT_FILE_TYPE', serialize(['pdf']));
defined('CLOUD_FILE_TYPE') or
	define('CLOUD_FILE_TYPE', serialize(['png', 'jpg', 'jpeg', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip']));

// Indicar si se debe efectuar validación de token de usuario
defined('VALIDATE_TOKEN') or define('VALIDATE_TOKEN', ENVIRONMENT === 'production');
// define('VALIDATE_TOKEN', true);

// Definir los perfiles de usuario que pueden visualizar el catálogo de usuarios
defined('PERFIL_USUARIO_SUPER_ADMINISTRADOR') or define('PERFIL_USUARIO_SUPER_ADMINISTRADOR', 1);
defined('PERFIL_USUARIO_ADMINISTRADOR') or define('PERFIL_USUARIO_ADMINISTRADOR', 2);
defined('PERFIL_USUARIO_COLABORADOR') or define('PERFIL_USUARIO_COLABORADOR', 3);
defined('PERFIL_USUARIO_PROPIETARIO') or define('PERFIL_USUARIO_PROPIETARIO', 4);
defined('PERFIL_USUARIO_CONDOMINO') or define('PERFIL_USUARIO_CONDOMINO', 5);

// Ruta donde se almacenaran los documentos
defined('PATH_ARCHIVOS_USUARIOS') or define('PATH_ARCHIVOS_USUARIOS', 'uploads/usuarios');
defined('PATH_ARCHIVOS_CONDOMINIOS') or define('PATH_ARCHIVOS_CONDOMINIOS', 'uploads/condominios');
defined('PATH_ARCHIVOS_UNIDADES') or define('PATH_ARCHIVOS_UNIDADES', 'uploads/unidades');
defined('PATH_ARCHIVOS_MIEMBROS_COMITES_ADMINISTRACION') or
	define('PATH_ARCHIVOS_MIEMBROS_COMITES_ADMINISTRACION', 'uploads/miembros_comites_administracion');
defined('PATH_ARCHIVOS_CLOUD') or define('PATH_ARCHIVOS_CLOUD', 'uploads/cloud');
defined('PATH_ARCHIVOS_GASTOS_MANTENIMIENTO') or
	define('PATH_ARCHIVOS_GASTOS_MANTENIMIENTO', 'uploads/gastos_mantenimiento');
defined('PATH_ARCHIVOS_FONDOS_MONETARIOS') or define('PATH_ARCHIVOS_FONDOS_MONETARIOS', 'uploads/fondos_monetarios');
defined('PATH_ARCHIVOS_PROYECTOS') or define('PATH_ARCHIVOS_PROYECTOS', 'uploads/proyectos');
defined('PATH_ARCHIVOS_QUEJAS') or define('PATH_ARCHIVOS_QUEJAS', 'uploads/quejas');
// defined('PLANTILLAS_PATH') or define('PLANTILLAS_PATH', 'documentos/plantillas/');

defined('PRUEBAS_ID_USUARIO') or define('PRUEBAS_ID_USUARIO', 1);
defined('VALIDATE_TOKEN') or define('VALIDATE_TOKEN', false);

defined('PRUEBAS_ID_PERFIL_USUARIO') or define('PRUEBAS_ID_PERFIL_USUARIO', 1);

defined('PRUEBAS_ID_CONDOMINIO_USUARIO') or define('PRUEBAS_ID_CONDOMINIO_USUARIO', 1);
