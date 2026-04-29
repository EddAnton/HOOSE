<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Modelo: Usuario_Administrador
 *
 * Este modelo realiza las operaciones necesarias para el almacenamiento de la información de
 * los Usuarios Administradores
 *
 * @package   CodeIgniter
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Usuario_Administrador_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
		// $this->load->model('Usuario_model');
		$this->load->helper('File_Upload');
	}

	/*
    Obtener información del identificador especificado.
	*/
	public function listar($idUsuario = 0, $idCondominio = 0)
	{
		try {
			// Si no se proporciona ID de registro ni ID condominio, aborta el proceso
			/* if (empty($idUsuario) || empty($idCondominio)) {
				return [];
			} */

			/* return $this->db
				->select(
					'u.id_usuario,
            u.usuario,
            u.nombre,
            u.email,
            u.telefono,
            u.domicilio,
            u.identificacion_folio,
            u.identificacion_domicilio,
            IF(u.identificacion_anverso_archivo IS NOT NULL,
            CONCAT("' .
						PATH_ARCHIVOS_USUARIOS .
						'/", u.id_usuario, "/", u.identificacion_anverso_archivo),
            NULL) identificacion_anverso_archivo,
            IF(u.identificacion_reverso_archivo IS NOT NULL,
            CONCAT("' .
						PATH_ARCHIVOS_USUARIOS .
						'/", u.id_usuario, "/", u.identificacion_reverso_archivo),
            NULL) identificacion_reverso_archivo,
            IF(u.imagen_archivo IS NOT NULL,
            CONCAT("' .
						PATH_ARCHIVOS_USUARIOS .
						'/", u.id_usuario, "/", u.imagen_archivo),
            NULL) imagen_archivo,
            u.estatus'
				)
				->where([
					'u.id_usuario' => $idUsuario,
					'u.fk_id_perfil_usuario' => PERFIL_USUARIO_ADMINISTRADOR,
					'u.fk_id_condominio' => $idCondominio,
				])
				->get('usuarios u')
				->row_array(); */
			return $this->db
				->select(
					'u.id_usuario,
            u.usuario,
            u.nombre,
            u.email,
            u.telefono,
            u.domicilio,
            u.identificacion_folio,
            u.identificacion_domicilio,
            u.imagen_archivo imagen,
            u.identificacion_anverso_archivo identificacion_anverso,
            u.identificacion_reverso_archivo identificacion_reverso,
            u.estatus'
				)
				->where([
					'u.id_usuario' => $idUsuario,
					'u.fk_id_perfil_usuario' => PERFIL_USUARIO_ADMINISTRADOR,
					'u.fk_id_condominio' => $idCondominio,
				])
				->get('usuarios u')
				->row_array();
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
		$respuesta = [
			'error' => true,
			'msg' => null,
		];

		try {
			// Validar que la información a insertar sea proporcionada
			if (empty($data)) {
				$respuesta['msg'] = 'Debe especificar la información a insertar.';
				return $respuesta;
			}

			// Verificar si ya existe un usuario para el nombre de usuario o email
			$validarExisteUsuario = $this->Usuario_model->validar_existe_usuario([
				'usuario' => $data['usuario'],
				'email' => $data['email'],
				'id_condominio' => $data['fk_id_condominio'],
			]);
			if ($validarExisteUsuario['error']) {
				return $validarExisteUsuario;
			}

			// Genera la contraseña si esta no es un MD5 válido
			if (!md5Valido($data['contrasenia'])) {
				$data['contrasenia'] = md5($data['contrasenia']);
			}

			// Obtener identificador para la imagen de perfil
			$archivo_imagen = !empty($data['archivo_imagen']) ? $data['archivo_imagen'] : null;
			// Obtener identificador para el archivo del anverso de la identificación
			$archivo_identificacion_anverso = !empty($data['archivo_identificacion_anverso'])
				? $data['archivo_identificacion_anverso']
				: null;
			// Obtener identificador para el archivo del reverso de la identificación
			$archivo_identificacion_reverso = !empty($data['archivo_identificacion_reverso'])
				? $data['archivo_identificacion_reverso']
				: null;
			unset($data['archivo_identificacion_anverso']);
			unset($data['archivo_identificacion_reverso']);
			unset($data['archivo_imagen']);
			$data['fk_id_perfil_usuario'] = PERFIL_USUARIO_ADMINISTRADOR;

			// Validar que los campos existan en la tabla
			if (!validar_campos('usuarios', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con respecto a la base de datos.';
				return $respuesta;
			}

			// Define la carpeta temporal para la carga de los archivos
			$carpeta_temporal_cargar_archivos = PATH_ARCHIVOS_USUARIOS . '/' . uniqid() . '/';
			$se_cargaron_archivos = false;

			/*
			  Si se especificó imagen de perfil intenta la carga del archivo y agrega el nombre del archivo cargado a la data.
				Si no se pudo cargar el archivo, borra carpeta temporal y aborta el proceso
      */
			if (!empty($archivo_imagen)) {
				$cargar_archivo = subir_imagen($carpeta_temporal_cargar_archivos, $archivo_imagen, 'profile');

				if ($cargar_archivo['error']) {
					borrar_directorio($carpeta_temporal_cargar_archivos);
					$respuesta['msg'] = 'Error al cargar la imagen de perfil.' . PHP_EOL . $cargar_archivo['msg'];
					return $respuesta;
				}

				$redimensionar_imagen = redimensionar_imagen(
					$cargar_archivo['ruta_archivo'] . $cargar_archivo['archivo_servidor'],
					320,
					240
				);
				if ($redimensionar_imagen['error']) {
					borrar_directorio($carpeta_temporal_cargar_archivos);
					$respuesta['msg'] =
						'Error al cargar el reverso de la identificación.' . PHP_EOL . $redimensionar_imagen['msg'];
					return $respuesta;
				}
				$se_cargaron_archivos = true;
				$data['imagen_archivo'] = $cargar_archivo['archivo_servidor'];
			}

			/*
			  Si se especificó anverso de la identificación intenta la carga del archivo y agrega el nombre del archivo cargado a la data.
				Si no se pudo cargar el archivo, aborta el proceso
      */
			if (!empty($archivo_identificacion_anverso)) {
				$cargar_archivo = subir_imagen(
					$carpeta_temporal_cargar_archivos,
					$archivo_identificacion_anverso,
					'id_anverso'
				);

				if ($cargar_archivo['error']) {
					$respuesta['msg'] = 'Error al cargar el anverso de la identificación.' . PHP_EOL . $cargar_archivo['msg'];
					return $respuesta;
				}

				$redimensionar_imagen = redimensionar_imagen(
					$cargar_archivo['ruta_archivo'] . $cargar_archivo['archivo_servidor'],
					640,
					480
				);
				if ($redimensionar_imagen['error']) {
					borrar_directorio($carpeta_temporal_cargar_archivos);
					$respuesta['msg'] =
						'Error al cargar el anverso de la identificación.' . PHP_EOL . $redimensionar_imagen['msg'];
					return $respuesta;
				}

				$se_cargaron_archivos = true;
				$data['identificacion_anverso_archivo'] = $cargar_archivo['archivo_servidor'];
			}

			/*
			  Si se especificó reverso de la identificación intenta la carga del archivo y agrega el nombre del archivo cargado a la data.
				Si no se pudo cargar el archivo, borra carpeta temporal y aborta el proceso
      */
			if (!empty($archivo_identificacion_reverso)) {
				$cargar_archivo = subir_imagen(
					$carpeta_temporal_cargar_archivos,
					$archivo_identificacion_reverso,
					'id_reverso'
				);

				if ($cargar_archivo['error']) {
					borrar_directorio($carpeta_temporal_cargar_archivos);
					$respuesta['msg'] = 'Error al cargar el reverso de la identificación.' . PHP_EOL . $cargar_archivo['msg'];
					return $respuesta;
				}

				$redimensionar_imagen = redimensionar_imagen(
					$cargar_archivo['ruta_archivo'] . $cargar_archivo['archivo_servidor'],
					640,
					480
				);
				if ($redimensionar_imagen['error']) {
					borrar_directorio($carpeta_temporal_cargar_archivos);
					$respuesta['msg'] =
						'Error al cargar el reverso de la identificación.' . PHP_EOL . $redimensionar_imagen['msg'];
					return $respuesta;
				}
				$se_cargaron_archivos = true;
				$data['identificacion_reverso_archivo'] = $cargar_archivo['archivo_servidor'];
			}

			// Almacenar información en BD
			// Inicializar la transaccion
			$this->db->trans_start();
			$this->db->trans_strict(false);

			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if (!$this->db->insert('usuarios', $data)) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				$this->db->trans_rollback();
				borrar_directorio($carpeta_temporal_cargar_archivos);
				return $respuesta;
			}

			// Obtener nuevo ID del registro
			$nuevoID = $this->db->insert_id();

			// Si no existió error al almacenar la información y se cargaron archivos, renombra la carpeta temporal
			if ($se_cargaron_archivos) {
				$carpeta_cargar_archivos = PATH_ARCHIVOS_USUARIOS . '/' . $nuevoID . '/';
				// Renombrar el directorio temporal de carga de archivos
				if (!rename($carpeta_temporal_cargar_archivos, $carpeta_cargar_archivos)) {
					$this->db->trans_rollback();
					$respuesta['msg'] = 'Error interno FOLDER_RENAME.';
					borrar_directorio($carpeta_temporal_cargar_archivos);
					borrar_directorio($carpeta_cargar_archivos);
					return $respuesta;
				}
			}

			// Confirmar la transacción
			$this->db->trans_complete();
			// Confirmar la transacción y verificar el estatus de la misma
			if ($this->db->trans_status() === false) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $respuesta;
			} else {
				$respuesta['msg'] = 'Información almacenada con éxito.';
			}

			$respuesta['administrador'] = $this->listar($nuevoID, $data['fk_id_condominio']);
			$respuesta['error'] = false;
		} catch (Exception $e) {
			throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
			die();
		}
		return $respuesta;
	}

	/*
    Actualizar registro
      $data => Información a insertar
	*/
	public function actualizar($data)
	{
		$respuesta = [
			'error' => true,
			'msg' => null,
		];

		try {
			// Validar que la información a insertar sea proporcionada
			if (empty($data)) {
				$respuesta['msg'] = 'Debe especificar la información a insertar.';
				return $respuesta;
			}

			// Obtener ID del registro a actualizar
			$idCondominio = $data['id_condominio'];
			$idUsuario = $data['id_usuario'];
			// Obtener identificador para la imagen de perfil
			$archivo_imagen = !empty($data['archivo_imagen']) ? $data['archivo_imagen'] : null;
			$borrar_imagen = $data['borrar_imagen'];
			// Obtener identificador para el archivo del anverso de la identificación
			$archivo_identificacion_anverso = !empty($data['archivo_identificacion_anverso'])
				? $data['archivo_identificacion_anverso']
				: null;
			$borrar_identificacion_anverso = $data['borrar_identificacion_anverso'];
			// Obtener identificador para el archivo del reverso de la identificación
			$archivo_identificacion_reverso = !empty($data['archivo_identificacion_reverso'])
				? $data['archivo_identificacion_reverso']
				: null;
			$borrar_identificacion_reverso = $data['borrar_identificacion_reverso'];
			// Eliminar elementos no necesarios de la data
			unset($data['borrar_identificacion_reverso']);
			unset($data['archivo_identificacion_reverso']);
			unset($data['borrar_identificacion_anverso']);
			unset($data['archivo_identificacion_anverso']);
			unset($data['borrar_imagen']);
			unset($data['archivo_imagen']);
			unset($data['id_usuario']);
			unset($data['id_condominio']);
			// Establecer la fecha de modificación
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');

			// Validar que los campos existan en la tabla
			if (!validar_campos('usuarios', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con respecto a la base de datos.';
				return $respuesta;
			}

			// Verificar si ya existe otro usuario para el email especificado
			if (
				$this->db
					->get_where('usuarios', [
						'email' => $data['email'],
						'id_usuario !=' => $idUsuario,
						'fk_id_condominio' => $idCondominio,
						// 'estatus' => 1,
					])
					->num_rows() > 0
			) {
				$respuesta['msg'] = 'Ya existe un usuario para el email especificado.';
				return $respuesta;
			}

			// Verificar cuantos registros serán actualizados
			$usuario = $this->db
				->select(
					'identificacion_anverso_archivo,
            identificacion_reverso_archivo,
            imagen_archivo'
				)
				->get_where('usuarios', ['id_usuario' => $idUsuario]);

			if ($usuario->num_rows() != 1) {
				if ($usuario->num_rows() == 0) {
					$respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
				} elseif ($usuario->num_rows() > 1) {
					$respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
				}
				return $respuesta;
			}

			// Define la carpeta para la carga de los archivos
			$carpeta_cargar_archivos = PATH_ARCHIVOS_USUARIOS . '/' . $idUsuario . '/';
			$se_cargaron_archivos = false;
			$imagen_archivo_existente = $usuario->row()->imagen_archivo;
			$identificacion_anverso_archivo_existente = $usuario->row()->identificacion_anverso_archivo;
			$identificacion_reverso_archivo_existente = $usuario->row()->identificacion_reverso_archivo;
			/* 	$identificacion_anverso_archivo_existente = null;
			$identificacion_reverso_archivo_existente = null;
			$imagen_archivo_existente = null; */

			/*
			  Si se especificó imagen de perfil intenta la carga del archivo y agrega el nombre del archivo cargado a la data.
				Si no se pudo cargar el archivo, borra los archivos cargados anteriormente y aborta el proceso
      */
			if (!empty($archivo_imagen)) {
				$cargar_archivo = subir_imagen($carpeta_cargar_archivos, $archivo_imagen, 'profile');

				if ($cargar_archivo['error']) {
					$respuesta['msg'] = 'Error al cargar la imagen de perfil.' . PHP_EOL . $cargar_archivo['msg'];
					return $respuesta;
				}

				$redimensionar_imagen = redimensionar_imagen(
					$cargar_archivo['ruta_archivo'] . $cargar_archivo['archivo_servidor'],
					320,
					240
				);
				if ($redimensionar_imagen['error']) {
					unlink($carpeta_cargar_archivos . $cargar_archivo['archivo_servidor']);
					$respuesta['msg'] = 'Error al cargar la imagen de perfil.' . PHP_EOL . $redimensionar_imagen['msg'];
					return $respuesta;
				}

				$se_cargaron_archivos = true;
				$data['imagen_archivo'] = $cargar_archivo['archivo_servidor'];
			} elseif ($imagen_archivo_existente != '' && $borrar_imagen) {
				$se_cargaron_archivos = true;
				$data['imagen_archivo'] = null;
			}

			/*
			  Si se especificó anverso de la identificación intenta la carga del archivo y agrega el nombre del archivo cargado a la data.
				Si no se pudo cargar el archivo, aborta el proceso
      */
			if (!empty($archivo_identificacion_anverso)) {
				$cargar_archivo = subir_imagen($carpeta_cargar_archivos, $archivo_identificacion_anverso, 'id_anverso');

				if ($cargar_archivo['error']) {
					if (!empty($data['imagen_archivo'])) {
						unlink($carpeta_cargar_archivos . $data['imagen_archivo']);
					}

					$respuesta['msg'] = 'Error al cargar anverso de la identificación.' . PHP_EOL . $cargar_archivo['msg'];
					return $respuesta;
				}

				$redimensionar_imagen = redimensionar_imagen(
					$cargar_archivo['ruta_archivo'] . $cargar_archivo['archivo_servidor'],
					640,
					480
				);
				if ($redimensionar_imagen['error']) {
					if (!empty($data['imagen_archivo'])) {
						unlink($carpeta_cargar_archivos . $data['archivo_imagen']);
					}
					unlink($carpeta_cargar_archivos . $cargar_archivo['archivo_servidor']);
					$respuesta['msg'] = 'Error al cargar anverso de la identificación.' . PHP_EOL . $redimensionar_imagen['msg'];
					return $respuesta;
				}

				$se_cargaron_archivos = true;
				$data['identificacion_anverso_archivo'] = $cargar_archivo['archivo_servidor'];
			} elseif ($identificacion_anverso_archivo_existente != '' && $borrar_identificacion_anverso) {
				$se_cargaron_archivos = true;
				$data['identificacion_anverso_archivo'] = null;
			}

			/*
			  Si se especificó reverso de la identificación intenta la carga del archivo y agrega el nombre del archivo cargado a la data.
				Si no se pudo cargar el archivo, borra el archivo cargado anteriormente y aborta el proceso
      */
			if (!empty($archivo_identificacion_reverso)) {
				$cargar_archivo = subir_imagen($carpeta_cargar_archivos, $archivo_identificacion_reverso, 'id_reverso');

				if ($cargar_archivo['error']) {
					if (!empty($data['imagen_archivo'])) {
						unlink($carpeta_cargar_archivos . $data['imagen_archivo']);
					}
					if (!empty($data['identificacion_anverso_archivo'])) {
						unlink($carpeta_cargar_archivos . $data['identificacion_anverso_archivo']);
					}
					$respuesta['msg'] = 'Error al cargar reverso de la identificación.' . PHP_EOL . $cargar_archivo['msg'];
					return $respuesta;
				}

				$redimensionar_imagen = redimensionar_imagen(
					$cargar_archivo['ruta_archivo'] . $cargar_archivo['archivo_servidor'],
					640,
					480
				);
				if ($redimensionar_imagen['error']) {
					if (!empty($data['imagen_archivo'])) {
						unlink($carpeta_cargar_archivos . $data['archivo_imagen']);
					}
					if (!empty($data['identificacion_anverso_archivo'])) {
						unlink($carpeta_cargar_archivos . $data['archivo_identificacion_anverso']);
					}
					unlink($carpeta_cargar_archivos . $cargar_archivo['archivo_servidor']);
					$respuesta['msg'] = 'Error al cargar reverso de la identificación.' . PHP_EOL . $redimensionar_imagen['msg'];
					return $respuesta;
				}

				$se_cargaron_archivos = true;
				$data['identificacion_reverso_archivo'] = $cargar_archivo['archivo_servidor'];
			} elseif ($identificacion_reverso_archivo_existente != '' && $borrar_identificacion_reverso) {
				$se_cargaron_archivos = true;
				$data['identificacion_reverso_archivo'] = null;
			}

			// Almacenar información en BD
			// Inicializar la transaccion
			$this->db->trans_start();
			$this->db->trans_strict(false);

			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if (!$this->db->update('usuarios', $data, ['id_usuario' => $idUsuario])) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				$this->db->trans_rollback();
				// Borrar archivos cargados
				if (!empty($data['identificacion_anverso_archivo'])) {
					unlink($carpeta_cargar_archivos . $data['identificacion_anverso_archivo']);
				}
				if (!empty($data['identificacion_reverso_archivo'])) {
					unlink($carpeta_cargar_archivos . $data['identificacion_reverso_archivo']);
				}
				if (!empty($data['imagen_archivo'])) {
					unlink($carpeta_cargar_archivos . $data['imagen_archivo']);
				}
				return $respuesta;
			}

			// Si no existió error al almacenar la información y se cargaron archivos, borra los archivos cargados anteriormente
			if ($se_cargaron_archivos) {
				if (!empty($imagen_archivo_existente) && $borrar_imagen) {
					unlink($carpeta_cargar_archivos . $imagen_archivo_existente);
				}
				if (!empty($identificacion_anverso_archivo_existente) && $borrar_identificacion_anverso) {
					unlink($carpeta_cargar_archivos . $identificacion_anverso_archivo_existente);
				}
				if (!empty($identificacion_reverso_archivo_existente) && $borrar_identificacion_reverso) {
					unlink($carpeta_cargar_archivos . $identificacion_reverso_archivo_existente);
				}
			}

			// Confirmar la transacción
			$this->db->trans_complete();
			// Confirmar la transacción y verificar el estatus de la misma
			if ($this->db->trans_status() === false) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $respuesta;
			} else {
				$respuesta['msg'] = 'Información almacenada con éxito.';
			}

			$respuesta['administrador'] = $this->listar($idUsuario, $idCondominio);
			$respuesta['error'] = false;
		} catch (Exception $e) {
			throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
			die();
		}
		return $respuesta;
	}
}

/* End of file Usuario_Administrador_model.php */
/* Location: ./application/models/Usuario_Administrador_model.php */
