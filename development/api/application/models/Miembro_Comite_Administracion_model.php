<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Modelo: Miembro_Comite_Administracion_model
 *
 * Este modelo realiza las operaciones necesarias para el almacenamiento de los miembros
 * del Comité de Administración
 *
 * @package   CodeIgniter
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Miembro_Comite_Administracion_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper('File_Upload');
	}

	/*
    Obtener información del identificador especificado.
	*/
	public function listar($idMiembro = 0, $idCondominio = 0, $soloActivos = false)
	{
		try {
			// Si no se proporciona ID de registro ni ID condominio, aborta el proceso
			if (empty($idMiembro) && empty($idCondominio)) {
				return null;
			}

			if (!empty($idMiembro)) {
				return $this->db
					->select(
						'm.id_miembro,
            m.nombre,
            m.email,
            m.telefono,
            m.domicilio,
            m.identificacion_folio,
            m.identificacion_domicilio,
            m.imagen_archivo imagen,
            m.identificacion_anverso_archivo identificacion_anverso,
            m.identificacion_reverso_archivo identificacion_reverso,
            tm.id_tipo_miembro,
            tm.tipo_miembro,
            m.fecha_inicio,
            m.estatus'
					)
					->join('cat_tipos_miembros tm', 'tm.id_tipo_miembro = m.fk_id_tipo_miembro')
					->get_where('miembros_comite_administracion m', [
						'm.id_miembro' => $idMiembro,
					])
					->row_array();
			} else {
				$this->db
					->select(
						'm.id_miembro,
            m.nombre,
            m.email,
            m.telefono,
            m.domicilio,
            m.identificacion_folio,
            m.identificacion_domicilio,
            m.imagen_archivo imagen,
            tm.id_tipo_miembro,
            tm.tipo_miembro,
            m.fecha_inicio,
            m.fecha_fin,
            m.estatus'
					)
					->join('cat_tipos_miembros tm', 'tm.id_tipo_miembro = m.fk_id_tipo_miembro');
				if ($soloActivos) {
					$this->db->where(['m.estatus' => 1]);
				}
				return $this->db
					->get_where('miembros_comite_administracion m', [
						'm.fk_id_condominio' => $idCondominio,
					])
					->result_array();
			}
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
			unset($data['archivo_imagen']);
			unset($data['archivo_identificacion_reverso']);
			unset($data['archivo_identificacion_anverso']);

			// Validar que los campos existan en la tabla miembros_comite_administracion
			if (!validar_campos('miembros_comite_administracion', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con respecto a la base de datos (Usuario).';
				return $respuesta;
			}

			// Define la carpeta temporal para la carga de los archivos
			$carpeta_temporal_cargar_archivos = PATH_ARCHIVOS_MIEMBROS_COMITES_ADMINISTRACION . '/' . uniqid() . '/';
			$se_cargaron_archivos = false;

			/*
			  Si se especificó imagen de perfil intenta la carga del archivo y agrega el nombre del archivo cargado a la data.
				Si no se pudo cargar el archivo, borra carpeta temporal y aborta el proceso
      */
			if (!empty($archivo_imagen)) {
				$cargar_archivo = subir_imagen($carpeta_temporal_cargar_archivos, $archivo_imagen, 'profile');

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
					borrar_directorio($carpeta_temporal_cargar_archivos);
					$respuesta['msg'] = 'Error al cargar la imagen de perfil.' . PHP_EOL . $redimensionar_imagen['msg'];
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
					borrar_directorio($carpeta_temporal_cargar_archivos);
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

			// Insertar registro en miembros_comite_administracion
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if (!$this->db->insert('miembros_comite_administracion', $data)) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				$this->db->trans_rollback();
				borrar_directorio($carpeta_temporal_cargar_archivos);
				return $respuesta;
			}

			// Obtener nuevo ID del registro
			$nuevoID = $this->db->insert_id();

			// Si no existió error al almacenar la información y se cargaron archivos, renombra la carpeta temporal
			if ($se_cargaron_archivos) {
				$carpeta_cargar_archivos = PATH_ARCHIVOS_MIEMBROS_COMITES_ADMINISTRACION . '/' . $nuevoID . '/';
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

			$respuesta['miembro'] = $this->listar($nuevoID);
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
			$idMiembro = $data['id_miembro'];

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
			unset($data['archivo_identificacion_reverso']);
			unset($data['borrar_identificacion_reverso']);
			unset($data['archivo_identificacion_anverso']);
			unset($data['borrar_identificacion_anverso']);
			unset($data['archivo_imagen']);
			unset($data['borrar_imagen']);
			unset($data['id_miembro']);
			// Establecer la fecha de modificación
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');

			// Validar que los campos existan en la tabla
			if (!validar_campos('miembros_comite_administracion', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con respecto a la base de datos.';
				return $respuesta;
			}

			// Verificar cuantos registros serán actualizados
			$miembro = $this->db
				->select(
					'identificacion_anverso_archivo,
            identificacion_reverso_archivo,
            imagen_archivo'
				)
				->get_where('miembros_comite_administracion', [
					'id_miembro' => $idMiembro,
					'estatus' => 1,
				]);

			if ($miembro->num_rows() != 1) {
				if ($miembro->num_rows() == 0) {
					$respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
				} elseif ($miembro->num_rows() > 1) {
					$respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
				}
				return $respuesta;
			}

			// Define la carpeta para la carga de los archivos
			$carpeta_cargar_archivos = PATH_ARCHIVOS_MIEMBROS_COMITES_ADMINISTRACION . '/' . $idMiembro . '/';
			$se_cargaron_archivos = false;
			$imagen_archivo_existente = $miembro->row()->imagen_archivo;
			$identificacion_anverso_archivo_existente = $miembro->row()->identificacion_anverso_archivo;
			$identificacion_reverso_archivo_existente = $miembro->row()->identificacion_reverso_archivo;

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

			// Actualizar datos en miembros_comite_administracion
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if (!$this->db->update('miembros_comite_administracion', $data, ['id_miembro' => $idMiembro])) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				$this->db->trans_rollback();
				// Borrar archivos cargados
				if (!empty($data['imagen_archivo'])) {
					unlink($carpeta_cargar_archivos . $data['imagen_archivo']);
				}
				if (!empty($data['identificacion_anverso_archivo'])) {
					unlink($carpeta_cargar_archivos . $data['identificacion_anverso_archivo']);
				}
				if (!empty($data['identificacion_reverso_archivo'])) {
					unlink($carpeta_cargar_archivos . $data['identificacion_reverso_archivo']);
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
				if (!empty($contrato_archivo_existente) && $borrar_contrato) {
					unlink($carpeta_cargar_archivos . $contrato_archivo_existente);
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

			$respuesta['miembro'] = $this->listar($idMiembro);
			$respuesta['error'] = false;
		} catch (Exception $e) {
			throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
			die();
		}
		return $respuesta;
	}

	/*
    Alterna el estatus del registro
      $data => Información del registro a actualizar
	*/
	public function alternar_estatus($data)
	{
		$respuesta = [
			'error' => true,
			'msg' => null,
		];

		try {
			// Validar que sea proporcionada la información requerida para la actualizacion
			if (empty($data)) {
				$respuesta['msg'] = 'Debe especificar la información a actualizar.';
				return $respuesta;
			}

			$idMiembro = $data['id_miembro'];

			// Verificar cuantos registros serán actualizados
			$miembro = $this->db->get_where('miembros_comite_administracion', [
				'id_miembro' => $idMiembro,
			]);

			if ($miembro->num_rows() != 1) {
				if ($miembro->num_rows() == 0) {
					$respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
				} elseif ($miembro->num_rows() > 1) {
					$respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
				}
				return $respuesta;
			}

			$estatus = 0;
			// Si no se especifica valor para estatus, se alterna el valor
			if (!empty($data['estatus']) && intval($data['estatus'])) {
				$estatus = intval($data['estatus']) != 0 ? 1 : 0;
			} else {
				$estatus = !$miembro->row()->estatus;
			}

			$data = [
				'estatus' => $estatus,
				'fk_id_usuario_modifico' => $data['fk_id_usuario_modifico'],
				'fecha_modificacion' => date('Y-m-d H:i:s'),
			];

			// Almacenar información en BD
			$respuesta['error'] = !$this->db->update('miembros_comite_administracion', $data, [
				'id_miembro' => $idMiembro,
			]);
			$respuesta['msg'] = $respuesta['error']
				? 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message']
				: 'Estatus modificado con éxito.';
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
			die();
		}
		return $respuesta;
	}
}

/* End of file Miembro_Comite_Administracion_model.php */
/* Location: ./application/models/Miembro_Comite_Administracion_model.php */
