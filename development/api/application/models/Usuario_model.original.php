<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Modelo: Usuario
 *
 * Este modelo realiza las operaciones necesarias para el almacenamiento de la información de
 * los Usuarios
 *
 * @package   CodeIgniter
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Usuario_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	/* public function index()
	{
		$this->response(APP_NAME . ' API / Usuario :: Model');
	} */

	/*
    Obtener información del identificador especificado o todos los registros.
      $id => ID especifico a obtener, si se requiere
      $soloActivos => Determinar si se requieren todos los registros o sólo los activos
	*/
	public function listar($id = 0, $soloActivos = false)
	{
		try {
			$this->db
				->select(
					'u.id_usuario,
          u.usuario,
          u.email,
          u.primer_apellido,
          u.segundo_apellido,
          u.nombre,
          pu.id_perfil_usuario,
          pu.perfil_usuario,
          u.debe_cambiar_contrasenia,
          u.activo'
				)
				->join('cat_perfiles_usuarios pu', 'pu.id_perfil_usuario = u.fk_id_perfil_usuario');

			if ($id > 0) {
				$this->db->where(['u.id_usuario' => $id]);
			} elseif ($soloActivos) {
				$this->db->where(['u.activo' => 1]);
			}

			$response = $this->db
				->order_by('u.primer_apellido, u.segundo_apellido, u.nombre')
				->get('cat_usuarios u')
				->result_array();

			return $response;
		} catch (Exception $e) {
			throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
			die();
		}
	}

	/*
    Insertar registro
      $data => Información a insertar
	*/
	public function insertar($data)
	{
		$response = [
			'error' => true,
			'msg' => null,
			'usuario' => null,
		];

		try {
			// Validar que la información a insertar sea proporcionada
			if (empty($data)) {
				$response['msg'] = 'Debe especificar la información a insertar.';
				return $response;
			}

			// Genera la contraseña si esta no es un MD5 válido
			if (!md5Valido($data['contrasenia'])) {
				$data['contrasenia'] = md5($data['contrasenia']);
			}

			// Obtener identificador para el archivo del anverso de la identificación
			$archivo_identificacion_anverso = !empty($data['identificacion_anverso_archivo'])
				? $data['identificacion_anverso_archivo']
				: null;
			unset($data['identificacion_anverso_archivo']);
			// Obtener identificador para el archivo del reverso de la identificación
			$archivo_identificacion_reverso = !empty($data['identificacion_reverso_archivo'])
				? $data['identificacion_reverso_archivo']
				: null;
			unset($data['identificacion_reverso_archivo']);
			// Obtener identificador para la imagen de perfil
			$archivo_imagen = !empty($data['imagen_archivo']) ? $data['imagen_archivo'] : null;
			unset($data['imagen_archivo']);

			// Validar que los campos existan en la tabla
			if (!validar_campos('usuarios', $data)) {
				$response['msg'] = 'Error de integridad de la información con respecto a la base de datos.';
				return $response;
			}

			// Almacenar información en BD
			$response['error'] = !$this->db->insert('usuarios', $data);
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if ($response['err']) {
				$response['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $response;
			}
			$nuevoId = $this->db->insert_id();
			$respuesta['msg'] = 'Información registrada con éxito';

			$ruta_cargar_archivos = PATH_ARCHIVOS_USUARIOS . '/' . $nuevoId . '/';
			$campos_actualizacion_archivos_cargados = [
				'identificacion_anverso_archivo' => null,
				'identificacion_reverso_archivo' => null,
				'imagen' => null,
			];
			$error_cargando_archivos = [
				'identificacion_anverso_archivo' => false,
				'identificacion_reverso_archivo' => false,
				'imagen' => false,
			];
			$error_actualizando_archivos_cargados = false;

			/*
			  Si se especificó anverso de la identificación:
          - Intenta la carga del archivo
				  - Si no ocurrió algún error, establece actualizar el campo identificacion_anverso_archivo
      */
			if (!empty($archivo_identificacion_anverso)) {
				$cargar_archivo = subir_documento($ruta_cargar_archivos, $archivo_identificacion_anverso, 'id_anverso');
				$error_cargando_archivos['identificacion_anverso_archivo'] = $cargar_archivo['err'];

				if (!$cargar_archivo['err']) {
					$campos_actualizacion_archivos_cargados['identificacion_anverso_archivo'] =
						$cargar_archivo['archivo_servidor'];
				}
			}

			/*
			  Si se especificó reverso de la identificación:
          - Intenta la carga del archivo
				  - Si no ocurrió algún error, establece actualizar el campo identificacion_reverso_archivo
      */
			if (!empty($archivo_identificacion_reverso)) {
				$cargar_archivo = subir_documento($ruta_cargar_archivos, $archivo_identificacion_reverso, 'id_reverso');
				$error_cargando_archivos['identificacion_reverso_archivo'] = $cargar_archivo['err'];

				if (!$cargar_archivo['err']) {
					$campos_actualizacion_archivos_cargados['identificacion_reverso_archivo'] =
						$cargar_archivo['archivo_servidor'];
				}
			}

			/*
			  Si se especificó imagen del usuario:
          - Intenta la carga del archivo
				  - Si no ocurrió algún error, establece actualizar el campo imagen
      */
			if (!empty($archivo_imagen)) {
				$cargar_archivo = subir_imagen($ruta_cargar_archivos, $archivo_imagen, 'img_profile');
				$error_cargando_archivos['imagen'] = $cargar_archivo['err'];

				if (!$cargar_archivo['err']) {
					$campos_actualizacion_archivos_cargados['imagen'] = $cargar_archivo['archivo_servidor'];
				}
			}

			// No ocurrieron errores al cargar archivos, actualiza info en tabla
			if (
				!$error_cargando_archivos['identificacion_anverso_archivo'] &&
				!$error_cargando_archivos['identificacion_reverso_archivo'] &&
				!$error_cargando_archivos['imagen']
			) {
				// Expediente de personal cargado con éxito, actualiza info en tabla
				$error_actualizando_archivos_cargados = !$this->db->update(
					'usuarios',
					$campos_actualizacion_archivos_cargados,
					[
						'id_personal' => $id_personal,
					]
				);
			}

			// Si no se pudieron actualizar los nombres de los archivos cargados, borra estos archivos
			if ($error_actualizando_archivos_cargados) {
				if (!empty($campos_actualizacion_archivos_cargados['identificacion_anverso_archivo'])) {
					unlink($ruta_cargar_archivos . $campos_actualizacion_archivos_cargados['identificacion_anverso_archivo']);
				}
				if (!empty($campos_actualizacion_archivos_cargados['identificacion_reverso_archivo'])) {
					unlink($ruta_cargar_archivos . $campos_actualizacion_archivos_cargados['identificacion_reverso_archivo']);
				}
				if (!empty($campos_actualizacion_archivos_cargados['imagen'])) {
					unlink($ruta_cargar_archivos . $campos_actualizacion_archivos_cargados['imagen']);
				}
				$respuesta['msg'] .=
					', pero no se pudo almacenar:' .
					PHP_EOL .
					(!empty($campos_actualizacion_archivos_cargados['identificacion_anverso_archivo'])
						? ' - Anverso de la identificación' . PHP_EOL
						: '') .
					(!empty($campos_actualizacion_archivos_cargados['identificacion_reverso_archivo'])
						? ' - Reverso de la identificación' . PHP_EOL
						: '') .
					(!empty($campos_actualizacion_archivos_cargados['imagen']) ? ' - Imagen de perfil' . PHP_EOL : '') .
					PHP_EOL .
					'Código: ' .
					$this->db->error()['code'] .
					' - ' .
					$this->db->error()['message'];
			} elseif (
				$error_cargando_archivos['identificacion_anverso_archivo'] ||
				$error_cargando_archivos['identificacion_reverso_archivo'] ||
				$error_cargando_archivos['imagen']
			) {
				$respuesta['msg'] .=
					', pero' .
					PHP_EOL .
					($error_cargando_archivos['identificacion_anverso_archivo']
						? ' * No se pudo almacenar el anverso de la identificación.' . PHP_EOL
						: '') .
					($error_cargando_archivos['identificacion_reverso_archivo']
						? ' * No se pudo almacenar el reverso de la identificación.' . PHP_EOL
						: '') .
					($error_cargando_archivos['imagen'] ? ' * No se pudo almacenar la imagen de perfil.' . PHP_EOL : '');
			} else {
				$respuesta['mensaje'] .= '.';
			}

			// $usuario = $this->listar($nuevoId);
		} catch (Exception $e) {
			throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
			die();
		}
		return $response;
	}

	/*
    Actualizar registro
      $data => Información a actualizar
	*/
	public function actualizar($data)
	{
		$response = [
			'error' => true,
			'msg' => null,
			'usuario' => null,
		];

		try {
			// Validar que la información a actualizar sea proporcionada
			if (empty($data)) {
				$response['msg'] = 'Debe especificar la información a actualizar.';
				return $response;
			}

			// Normalizar la información a almacenar
			$id_usuario = $data['id_usuario'];
			unset($data['id_usuario']);
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');

			// Validar que los campos existan en la tabla
			if (!validar_campos('cat_usuarios', $data)) {
				$response['msg'] = 'Error de integridad de la información con la base de datos.';
				return $response;
			}

			// Verificar cuantos registros serán actualizados
			$registroExistente = $this->db->get_where('cat_usuarios', ['id_usuario' => $id_usuario])->num_rows();

			if ($registroExistente != 1) {
				if ($registroExistente == 0) {
					$response['msg'] = 'No se encontró información para actualizar.';
				} elseif ($registroExistente > 1) {
					$response['msg'] = 'Se detectó más de un registro coincidente. Solicita soporte.';
				}
				return $response;
			}

			if (
				$this->db->get_where('cat_usuarios', ['id_usuario !=' => $id_usuario, 'email' => $data['email']])->num_rows() >
				0
			) {
				$response['msg'] = 'Email ya capturado en otro usuario.';
				return $response;
			}

			// Almacenar información en BD
			$response['error'] = !$this->db->update('cat_usuarios', $data, [
				'id_usuario' => $id_usuario,
			]);
			// Si no existió error al almacenar el registro, obtiene la información correspondiente desde la BBDD
			if (!$response['error']) {
				$usuario = $this->listar($id_usuario);
				$response['usuario'] = !empty($usuario) ? $usuario : null;
			}
			$response['msg'] = $response['error']
				? 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message']
				: 'Información actualizada con éxito.';
		} catch (Exception $e) {
			throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
			die();
		}
		return $response;
	}

	/*
    Alterna el estatus del registro
      $data => Información del registro a actualizar
	*/
	public function alternar_estatus($data)
	{
		$response = [
			'error' => true,
			'msg' => null,
		];

		try {
			// Validar que sea proporcionada la información requerida para la actualizacion
			if (empty($data)) {
				$response['msg'] = 'Debe especificar la información a actualizar.';
				return $response;
			}

			$id_usuario = $data['id_usuario'];

			// Verificar cuantos registros serán actualizados
			$registroExistente = $this->db->get_where('cat_usuarios', [
				'id_usuario' => $id_usuario,
			]);

			if ($registroExistente->num_rows() != 1) {
				if ($registroExistente->num_rows() == 0) {
					$response['msg'] = 'No se encontró información para actualizar.';
				} elseif ($registroExistente->num_rows() > 1) {
					$response['msg'] = 'Se detectó más de un registro coincidente. Solicita soporte.';
				}
				return $response;
			}

			$activo = 0;
			// Si no se especifica valor para activo, se alterna el valor posible
			if (!empty($data['activo']) && intval($data['activo'])) {
				$activo = intval($data['activo']) != 0 ? 1 : 0;
			} else {
				$activo = !$registroExistente->row()->activo;
			}

			$data = [
				'activo' => $activo,
				'fk_id_usuario_modifico' => $data['fk_id_usuario_modifico'],
				'fecha_modificacion' => date('Y-m-d H:i:s'),
			];

			// Almacenar información en BD
			$response['error'] = !$this->db->update('cat_usuarios', $data, [
				'id_usuario' => $id_usuario,
			]);
			$response['msg'] = $response['error']
				? 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message']
				: 'Estatus modificado con éxito.';
		} catch (Exception $e) {
			throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
			die();
		}
		return $response;
	}

	/*
    Valida el inicio de sesión
    $datos =>
      Información para el inicio de sesión
	*/
	public function iniciar_sesion($data)
	{
		$response = [
			'error' => true,
			'msg' => null,
			'usuario' => null,
		];

		// Validar que sea proporcionada la información requerida el inicio de sesión
		if (empty($data)) {
			$response['msg'] = 'Debe especificar información requerida.';
			return $response;
		}

		try {
			// Obtiene registro del usuario
			$usuario = $this->db
				->select(
					'u.id_usuario,
          u.usuario,
          u.primer_apellido,
          u.segundo_apellido,
          u.nombre,
          u.email,
					pu.id_perfil_usuario,
          pu.perfil_usuario,
          pu.visualiza_todos_clientes,
          u.activo,
          u.debe_cambiar_contrasenia'
				)
				->join('cat_perfiles_usuarios pu', 'pu.id_perfil_usuario = u.fk_id_perfil_usuario')
				->where([
					'usuario' => $data['usuario'],
					'contrasenia' => $data['contrasenia'],
				])
				->get('cat_usuarios u')
				->row();

			if ($usuario == null) {
				$response['msg'] = 'Usuario y/o contraseña no válidos.';
			} else {
				if ($usuario->activo != 1) {
					$response['msg'] = 'Usuario no activo. Solicita soporte.';
				} else {
					unset($usuario->activo);

					$response['usuario'] = $usuario;

					$this->db->select('id_cliente, cliente')->where(['activo' => 1]);
					if ($usuario->id_perfil_usuario != 1) {
						$this->db->where(['fk_id_usuario_administrador' => $usuario->id_usuario]);
					}
					$clientes = $this->db->get('cat_clientes')->result_array();
					$response['usuario']->clientes = $clientes;
					$response['error'] = false;
					$response['msg'] = 'Inicio de sesión válido.';
				}
			}
		} catch (Exception $e) {
			throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
			die();
		}
		return $response;
	}

	/*
    Modifica la contraseña del usuario
    $datos =>
      Información del registro a actualizar
	*/
	public function cambiar_contrasenia($data)
	{
		$response = [
			'error' => true,
			'msg' => null,
		];

		try {
			// Validar que sea proporcionada la información requerida para la actualizacion
			if (empty($data)) {
				$response['msg'] = 'Debe especificar información requerida.';
				return $response;
			}

			// Almacenar información en BD
			$id_usuario = $data['id_usuario'];

			//Obtener la contraseña actual
			$registroExistente = $this->db
				->select('usuario, contrasenia')
				->get_where('cat_usuarios', ['id_usuario' => $id_usuario]);

			if ($registroExistente->num_rows() != 1) {
				if ($registroExistente->num_rows() == 0) {
					$response['msg'] = 'No se encontró información para actualizar.';
				} elseif ($registroExistente->num_rows() > 1) {
					$response['msg'] = 'Se detectó más de un registro coincidente. Solicita soporte.';
				}
				return $response;
			}

			// Verificar si incluye la nueva contraseña. Si no se incluye, se trata de un reinicio
			$debe_cambiar_contrasenia = 0;
			if (empty($data['contrasenia_nueva'])) {
				$data['contrasenia'] = md5($registroExistente->row()->usuario);
				$debe_cambiar_contrasenia = 1;
			} else {
				// Validar que la contraseña actual sea igual a la almacenada
				if ($data['contrasenia_actual'] != $registroExistente->row()->contrasenia) {
					$response['msg'] = 'La contraseña actual no corresponde a la registrada.';
					return $response;
				} else {
					$data['contrasenia'] = $data['contrasenia_nueva'];
				}
			}

			$data = [
				'contrasenia' => $data['contrasenia'],
				'debe_cambiar_contrasenia' => $debe_cambiar_contrasenia,
				'fk_id_usuario_modifico' => $id_usuario,
				'fecha_modificacion' => date('Y-m-d H:i:s'),
			];

			// Almacenar información en BD
			$response['error'] = !$this->db->update('cat_usuarios', $data, [
				'id_usuario' => $id_usuario,
			]);

			$response['msg'] = $response['error']
				? 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message']
				: 'Contraseña modificada con éxito.';
		} catch (Exception $e) {
			throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
			die();
		}
		return $response;
	}
}

/* End of file Usuario_model.php */
/* Location: ./application/models/Usuario_model.php */
