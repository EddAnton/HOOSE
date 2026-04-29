<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Modelo: Edificio
 *
 * Este modelo realiza las operaciones requeridas sobre la información de los Edificios
 *
 * @package   CodeIgniter
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Edificio_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	/*
    Obtener información del identificador especificado o todos los registros.
      $id => ID especifico a obtener, si se requiere
      $soloActivos => Determinar si se requieren todos los registros o sólo los activos
	*/
	public function listar($idEdificio = 0, $idCondominio = 0, $soloActivos = false)
	{
		try {
			// Si no se proporciona ID de registro ni ID condominio, aborta el proceso
			if (empty($idEdificio) && empty($idCondominio)) {
				return false;
			}

			$this->db->select(
				'e.id_edificio,
          e.edificio,
          e.estatus'
			);

			if ($idEdificio > 0) {
				$this->db->where(['e.id_edificio' => $idEdificio]);
			} else {
				$this->db->where(['e.fk_id_condominio' => $idCondominio]);
			}
			if ($soloActivos) {
				$this->db->where(['e.estatus' => 1]);
			}

			$respuesta = $this->db->order_by('e.edificio')->get('edificios e');

			return $idEdificio == 0 ? $respuesta->result_array() : $respuesta->row_array();
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

			// Validar que los campos existan en la tabla
			if (!validar_campos('edificios', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con la base de datos.';
				return $respuesta;
			}

			// Almacenar información en BD
			$respuesta['err'] = !$this->db->insert('edificios', $data);
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if ($respuesta['err']) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $respuesta;
			}

			$respuesta['msg'] = 'Información registrada con éxito.';
			$respuesta['edificio'] = $this->listar($this->db->insert_id());
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
			$idEdificio = $data['id_edificio'];
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			unset($data['id_edificio']);

			// Validar que los campos existan en la tabla
			if (!validar_campos('edificios', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con la base de datos.';
				return $respuesta;
			}

			// Verificar cuantos registros serán actualizados
			$registrosEncontrados = $this->db->get_where('edificios', ['id_edificio' => $idEdificio])->num_rows();

			if ($registrosEncontrados != 1) {
				if ($registrosEncontrados == 0) {
					$respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
				} elseif ($registrosEncontrados > 1) {
					$respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
				}
				return $respuesta;
			}

			// Almacenar información en BD
			$respuesta['err'] = !$this->db->update('edificios', $data, [
				'id_edificio' => $idEdificio,
			]);
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if ($respuesta['err']) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $respuesta;
			}

			$respuesta['msg'] = 'Información registrada con éxito.';
			$respuesta['edificio'] = $this->listar($idEdificio);
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

			$idEdificio = $data['id_edificio'];

			// Verificar cuantos registros serán actualizados
			$edificio = $this->db->get_where('edificios', [
				'id_edificio' => $idEdificio,
			]);

			if ($edificio->num_rows() != 1) {
				if ($edificio->num_rows() == 0) {
					$respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
				} elseif ($edificio->num_rows() > 1) {
					$respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
				}
				return $respuesta;
			}

			$estatus = 0;
			// Si no se especifica valor para estatus, se alterna el valor
			if (isset($data['estatus']) && intval($data['estatus'])) {
				$estatus = intval($data['estatus']) != 0 ? 1 : 0;
			} else {
				$estatus = !$edificio->row()->estatus;
			}

			$data = [
				'estatus' => $estatus,
				'fk_id_usuario_modifico' => $data['fk_id_usuario_modifico'],
				'fecha_modificacion' => date('Y-m-d H:i:s'),
			];

			// Almacenar información en BD
			$respuesta['err'] = !$this->db->update('edificios', $data, [
				'id_edificio' => $idEdificio,
			]);
			$respuesta['msg'] = $respuesta['err']
				? 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message']
				: (isset($estatus) ? 'Edificio ' . ($estatus == 0 ? 'des' : '') . 'habilitado' : 'Estatus modificado') .
					' con éxito.';
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
			die();
		}
		return $respuesta;
	}
}

/* End of file Edificio_model.php */
/* Location: ./application/models/Edificio_model.php */
