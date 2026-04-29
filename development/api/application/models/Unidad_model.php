<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Modelo: Unidad
 *
 * Este modelo realiza las operaciones requeridas sobre la información de los Unidades
 *
 * @package   CodeIgniter
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Unidad_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper('File_Upload');
	}

	/*
    Obtener información del identificador especificado o todos los registros.
      $id => ID especifico a obtener, si se requiere
      $soloActivos => Determinar si se requieren todos los registros o sólo los activos
	*/
	public function listar($idUnidad = 0, $idUsuarioPropietario = 0, $idCondominio = 0, $soloActivos = false)
	{
		try {
			// Si no se proporciona ID de registro ni ID condominio, aborta el proceso
			if (empty($idUnidad) && empty($idUsuarioPropietario) && empty($idCondominio)) {
				return false;
			}

			/* 
      IF(u.escrituras_archivo IS NOT NULL,
        CONCAT("' .
        PATH_ARCHIVOS_UNIDADES .
        '/", u.id_unidad, "/", u.escrituras_archivo),
        NULL) escrituras_archivo, */

			$this->db
				->select(
					'u.id_unidad,
            u.unidad,
            e.id_edificio,
            e.edificio,
            u.escrituras_archivo,
            u.cuota_mantenimiento_ordinaria,
            u.estatus'
				)
				->join('edificios e', 'e.id_edificio = u.fk_id_edificio');

			if ($idUnidad > 0) {
				$this->db->where(['u.id_unidad' => $idUnidad]);
			} elseif ($idUsuarioPropietario > 0) {
				$this->db->join(
					'unidades_propietarios up',
					'up.fk_id_unidad = u.id_unidad AND up.fk_id_usuario = ' . $idUsuarioPropietario . ' AND up.estatus = 1'
				);
			} else {
				$this->db->where(['e.fk_id_condominio' => $idCondominio]);
			}
			if ($soloActivos) {
				$this->db->where(['u.estatus' => 1]);
			}

			$respuesta = $this->db->order_by('e.edificio, u.unidad')->get('unidades u');

			return $idUnidad == 0 ? $respuesta->result_array() : $respuesta->row_array();
		} catch (Exception $e) {
			throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
			die();
		}
	}

	/*
    Obtener unidades disponibles para renta
	*/
	public function listar_sin_propietario($idCondominio)
	{
		try {
			// Si no se proporciona ID de registro ni ID condominio, aborta el proceso
			if (empty($idCondominio)) {
				return false;
			}
			/*
      IF(u.escrituras_archivo IS NOT NULL,
        CONCAT("' .
        PATH_ARCHIVOS_UNIDADES .
        '/", u.id_unidad, "/", u.escrituras_archivo),
        NULL) escrituras_archivo,
      */

			return $this->db
				->select(
					'u.id_unidad,
            CONCAT(u.unidad, " (", e.edificio, ")") unidad'
				)
				->join('edificios e', 'e.id_edificio = u.fk_id_edificio AND e.fk_id_condominio = ' . $idCondominio)
				->join('unidades_propietarios up', 'up.fk_id_unidad = u.id_unidad AND up.estatus = 1', 'left')
				->where('up.fk_id_unidad IS NULL')
				->order_by('e.edificio, u.unidad')
				->get_where('unidades u', ['u.estatus' => 1])
				->result_array();
		} catch (Exception $e) {
			throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
			die();
		}
	}

	/*
    Obtener unidades disponibles para renta
	*/
	public function listar_disponibles_renta($idCondominio = 0, $idUsuarioPropietario = 0)
	{
		try {
			// Si no se proporciona ID de registro ni ID condominio, aborta el proceso
			if (empty($idCondominio)) {
				return false;
			}

			$this->db
				->select(
					'u.id_unidad,
          CONCAT(u.unidad, " (", e.edificio, ")") unidad'
				)
				->join('edificios e', 'e.id_edificio = u.fk_id_edificio AND e.fk_id_condominio = ' . $idCondominio)
				->join('condominos_contratos cc', 'cc.fk_id_unidad = u.id_unidad AND cc.fecha_fin IS NULL', 'left')
				->where('cc.id_condomino_contrato IS NULL')
				->where(['u.estatus' => 1]);
			if (!empty($idUsuarioPropietario)) {
				$this->db->join(
					'unidades_propietarios up',
					'up.fk_id_unidad = u.id_unidad AND up.fk_id_usuario = ' . $idUsuarioPropietario
				);
			}
			return $this->db
				->order_by('e.edificio, u.unidad')
				->get('unidades u')
				->result_array();
		} catch (Exception $e) {
			throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
			die();
		}
	}

	/*
    Obtener información del identificador especificado o todos los registros.
      $id => ID especifico a obtener, si se requiere
      $soloActivos => Determinar si se requieren todos los registros o sólo los activos
	*/
	public function listar_para_recaudaciones($idCondominio = 0, $idUsuarioPropietario = 0)
	{
		try {
			// Si no se proporciona ID condominio, aborta el proceso
			if (empty($idCondominio)) {
				return false;
			}

			// IFNULL(cc.id_condomino_contrato, 0) id_condomino_contrato,
			$this->db
				->select(
					'u.id_unidad,
            u.unidad,
            e.id_edificio,
            e.edificio,
            IF(cc.id_condomino_contrato IS NULL, 0, 1) ocupada,
            IFNULL(puc.id_perfil_usuario, pup.id_perfil_usuario) id_perfil_usuario_paga,
            IFNULL(puc.perfil_usuario, pup.perfil_usuario) perfil_usuario_paga,
            IFNULL(usc.id_usuario, usp.id_usuario) id_usuario_paga,
            IFNULL(usc.nombre, usp.nombre) usuario_paga,
            IF(cc.renta IS NULL, 0, cc.renta) renta,
            u.cuota_mantenimiento_ordinaria'
				)
				->join('edificios e', 'e.id_edificio = u.fk_id_edificio')
				->join('unidades_propietarios up', 'up.fk_id_unidad = u.id_unidad')
				->join('usuarios usp', 'usp.id_usuario = up.fk_id_usuario')
				->join('cat_perfiles_usuarios pup', 'pup.id_perfil_usuario = usp.fk_id_perfil_usuario')
				->join('condominos_contratos cc', 'cc.fk_id_unidad = u.id_unidad AND cc.estatus = 1', 'left')
				->join('usuarios usc', 'usc.id_usuario = cc.fk_id_usuario', 'left')
				->join('cat_perfiles_usuarios puc', 'puc.id_perfil_usuario = usc.fk_id_perfil_usuario', 'left')
				->where(['e.fk_id_condominio' => $idCondominio, 'u.estatus' => 1]);
			if (!empty($idUsuarioPropietario)) {
				$this->db->where(['up.fk_id_usuario' => $idUsuarioPropietario]);
			}
			return $this->db
				->order_by('e.edificio, u.unidad')
				->get('unidades u')
				->result_array();
		} catch (Exception $e) {
			throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
			die();
		}
	}

	/*
    Obtener unidades disponibles para renta
	*/
	public function listar_para_visita($idCondominio)
	{
		try {
			// Si no se proporciona ID de registro ni ID condominio, aborta el proceso
			if (empty($idCondominio)) {
				return false;
			}

			return $this->db
				->select(
					'u.id_unidad,
          CONCAT(u.unidad, " (", e.edificio, ")") unidad'
				)
				->join('edificios e', 'e.id_edificio = u.fk_id_edificio AND e.fk_id_condominio = ' . $idCondominio)
				->order_by('e.edificio, u.unidad')
				->get('unidades u')
				->result_array();
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
			'err' => true,
			'msg' => null,
		];

		try {
			// Validar que la información a insertar sea proporcionada
			if (empty($data)) {
				$respuesta['msg'] = 'Debe especificar la información a insertar.';
				return $respuesta;
			}

			// Obtener identificador para la imagen de perfil
			$archivo_escrituras = !empty($data['archivo_escrituras']) ? $data['archivo_escrituras'] : null;
			unset($data['archivo_escrituras']);

			// Validar que los campos existan en la tabla
			if (!validar_campos('unidades', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con la base de datos.';
				return $respuesta;
			}

			// Define la carpeta temporal para la carga de los archivos
			$carpeta_temporal_cargar_archivos = PATH_ARCHIVOS_UNIDADES . '/' . uniqid() . '/';
			$se_cargaron_archivos = false;

			/*
			  Si se especificó documento del escrituras intenta la carga del mismo y agrega el nombre del archivo cargado a la data.
				Si no se pudo cargar el archivo, borra carpeta temporal y aborta el proceso
      */
			if (!empty($archivo_escrituras)) {
				$cargar_archivo = subir_documento($carpeta_temporal_cargar_archivos, $archivo_escrituras, 'escrituras');

				if ($cargar_archivo['error']) {
					borrar_directorio($carpeta_temporal_cargar_archivos);
					$respuesta['msg'] = 'Error al cargar el documento de las escrituras.' . PHP_EOL . $cargar_archivo['msg'];
					return $respuesta;
				}

				$se_cargaron_archivos = true;
				$data['escrituras_archivo'] = $cargar_archivo['archivo_servidor'];
			}

			// Almacenar información en BD
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if (!$this->db->insert('unidades', $data)) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				borrar_directorio($carpeta_temporal_cargar_archivos);
				return $respuesta;
			}
			// Obtener nuevo ID del registro
			$nuevoID = $this->db->insert_id();

			// Si no existió error al almacenar la información y se cargaron archivos, renombra la carpeta temporal
			if ($se_cargaron_archivos) {
				$carpeta_cargar_archivos = PATH_ARCHIVOS_UNIDADES . '/' . $nuevoID . '/';
				// Renombrar el directorio temporal de carga de archivos
				if (!rename($carpeta_temporal_cargar_archivos, $carpeta_cargar_archivos)) {
					$respuesta['msg'] = 'Error interno FOLDER_RENAME.';
					borrar_directorio($carpeta_temporal_cargar_archivos);
					borrar_directorio($carpeta_cargar_archivos);
					return $respuesta;
				}
			}

			$respuesta['err'] = false;
			$respuesta['msg'] = 'Información registrada con éxito.';
			$respuesta['unidad'] = $this->listar($nuevoID);
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
			die();
		}
		return $respuesta;
	}

	/*
    Actualizar registro
      $data => Información a actualizar
	*/
	public function actualizar($data)
	{
		$respuesta = [
			'err' => true,
			'msg' => null,
		];

		try {
			// Validar que la información a actualizar sea proporcionada
			if (empty($data)) {
				$respuesta['msg'] = 'Debe especificar la información a actualizar.';
				return $respuesta;
			}

			// Obtener ID del registro a actualizar y establecer la fecha de modificación
			$idUnidad = $data['id_unidad'];
			// Obtener identificador para las escrituras
			$archivo_escrituras = !empty($data['archivo_escrituras']) ? $data['archivo_escrituras'] : null;
			$borrar_escrituras = $data['borrar_escrituras'];
			// Eliminar elementos no necesarios de la data
			unset($data['borrar_escrituras']);
			unset($data['archivo_escrituras']);
			unset($data['id_unidad']);
			// Establecer la fecha de modificación
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');

			// Validar que los campos existan en la tabla
			if (!validar_campos('unidades', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con la base de datos.';
				return $respuesta;
			}

			// Verificar cuantos registros serán actualizados
			$unidad = $this->db->select('escrituras_archivo')->get_where('unidades', ['id_unidad' => $idUnidad]);

			if ($unidad->num_rows() != 1) {
				if ($unidad->num_rows() == 0) {
					$respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
				} elseif ($unidad->num_rows() > 1) {
					$respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
				}
				return $respuesta;
			}

			// Define la carpeta para la carga de los archivos
			$carpeta_cargar_archivos = PATH_ARCHIVOS_UNIDADES . '/' . $idUnidad . '/';
			$se_cargaron_archivos = false;
			// $escrituras_archivo_existente = null;
			$escrituras_archivo_existente = $unidad->row()->escrituras_archivo;

			/*
			  Si se especificó documento del escrituras intenta la carga del mismo y agrega el nombre del archivo cargado a la data.
				Si no se pudo cargar el archivo, borra los archivos cargados anteriormente y aborta el proceso
      */
			if (!empty($archivo_escrituras)) {
				$cargar_archivo = subir_documento($carpeta_cargar_archivos, $archivo_escrituras, 'escrituras');

				if ($cargar_archivo['error']) {
					$respuesta['msg'] = 'Error al cargar el documento de las escrituras.' . PHP_EOL . $cargar_archivo['msg'];
					return $respuesta;
				}

				$se_cargaron_archivos = true;
				$data['escrituras_archivo'] = $cargar_archivo['archivo_servidor'];
			} elseif ($escrituras_archivo_existente != '' && $borrar_escrituras) {
				$se_cargaron_archivos = true;
				$data['escrituras_archivo'] = null;
			}

			// Almacenar información en BD
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if (
				!$this->db->update('unidades', $data, [
					'id_unidad' => $idUnidad,
				])
			) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				if (!empty($data['escrituras_archivo'])) {
					unlink($carpeta_cargar_archivos . $data['escrituras_archivo']);
				}
				return $respuesta;
			}

			// Si no existió error al almacenar la información y se cargaron archivos, borra los archivos cargados anteriormente
			if ($se_cargaron_archivos) {
				if (!empty($escrituras_archivo_existente)) {
					unlink($carpeta_cargar_archivos . $escrituras_archivo_existente);
				}
			}

			$respuesta['err'] = false;
			$respuesta['msg'] = 'Información registrada con éxito.';
			$respuesta['unidad'] = $this->listar($idUnidad);
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
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
			'err' => true,
			'msg' => null,
		];

		try {
			// Validar que sea proporcionada la información requerida para la actualizacion
			if (empty($data)) {
				$respuesta['msg'] = 'Debe especificar la información a actualizar.';
				return $respuesta;
			}

			$idUnidad = $data['id_unidad'];

			// Verificar cuantos registros serán actualizados
			$unidad = $this->db->get_where('unidades', [
				'id_unidad' => $idUnidad,
			]);

			if ($unidad->num_rows() != 1) {
				if ($unidad->num_rows() == 0) {
					$respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
				} elseif ($unidad->num_rows() > 1) {
					$respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
				}
				return $respuesta;
			}

			$estatus = 0;
			// Si no se especifica valor para estatus, se alterna el valor
			if (isset($data['estatus']) && intval($data['estatus'])) {
				$estatus = intval($data['estatus']) != 0 ? 1 : 0;
			} else {
				$estatus = !$unidad->row()->estatus;
			}

			$data = [
				'estatus' => $estatus,
				'fk_id_usuario_modifico' => $data['fk_id_usuario_modifico'],
				'fecha_modificacion' => date('Y-m-d H:i:s'),
			];

			// Almacenar información en BD
			$respuesta['err'] = !$this->db->update('unidades', $data, [
				'id_unidad' => $idUnidad,
			]);
			/* $respuesta['msg'] = $respuesta['err']
				? 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message']
				: 'Estatus modificado con éxito.'; */
			$respuesta['msg'] = $respuesta['err']
				? 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message']
				: (isset($estatus) ? 'Unidad ' . ($estatus == 0 ? 'des' : '') . 'habilitada' : 'Estatus modificado') .
					' con éxito.';
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
			die();
		}
		return $respuesta;
	}
}

/* End of file Unidad_model.php */
/* Location: ./application/models/Unidad_model.php */
