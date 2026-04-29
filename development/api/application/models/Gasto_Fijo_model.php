<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Modelo: Gasto_Fijo
 *
 * Este modelo realiza las operaciones requeridas sobre la información de los Gasto Fijos
 *
 * @package   CodeIgniter
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Gasto_Fijo_model extends CI_Model
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
	public function listar($id = 0, $soloActivos = false)
	{
		try {
			$this->db->select(
				'id_gasto_fijo,
          gasto_fijo,
          estatus'
			);

			if ($id > 0) {
				$this->db->where(['id_gasto_fijo' => $id]);
			} elseif ($soloActivos) {
				$this->db->where(['estatus' => 1]);
			}

			$respuesta = $this->db->order_by('gasto_fijo')->get('cat_gastos_fijos');
			return $id > 0 ? $respuesta->row_array() : $respuesta->result_array();

			return $respuesta;
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
			if (!validar_campos('cat_gastos_fijos', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con la base de datos.';
				return $respuesta;
			}

			// Almacenar información en BD
			$respuesta['err'] = !$this->db->insert('cat_gastos_fijos', $data);
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if ($respuesta['err']) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $respuesta;
			}

			$respuesta['msg'] = 'Información registrada con éxito.';
			$respuesta['gasto_fijo'] = $this->listar($this->db->insert_id());
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
			$idGastoFijo = $data['id_gasto_fijo'];
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			unset($data['id_gasto_fijo']);

			// Validar que los campos existan en la tabla
			if (!validar_campos('cat_gastos_fijos', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con la base de datos.';
				return $respuesta;
			}

			// Verificar cuantos registros serán actualizados
			$registrosEncontrados = $this->db->get_where('cat_gastos_fijos', ['id_gasto_fijo' => $idGastoFijo])->num_rows();

			if ($registrosEncontrados != 1) {
				if ($registrosEncontrados == 0) {
					$respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
				} elseif ($registrosEncontrados > 1) {
					$respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
				}
				return $respuesta;
			}

			// Almacenar información en BD
			$respuesta['err'] = !$this->db->update('cat_gastos_fijos', $data, [
				'id_gasto_fijo' => $idGastoFijo,
			]);
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if ($respuesta['err']) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $respuesta;
			}

			$respuesta['msg'] = 'Información registrada con éxito.';
			$respuesta['tipo_miembro'] = $this->listar($idGastoFijo);
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

			$idGastoFijo = $data['id_gasto_fijo'];

			// Verificar cuantos registros serán actualizados
			$gastoFijo = $this->db->get_where('cat_gastos_fijos', [
				'id_gasto_fijo' => $idGastoFijo,
			]);

			if ($gastoFijo->num_rows() != 1) {
				if ($gastoFijo->num_rows() == 0) {
					$respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
				} elseif ($gastoFijo->num_rows() > 1) {
					$respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
				}
				return $respuesta;
			}

			$estatus = 0;
			// Si no se especifica valor para estatus, se alterna el valor
			if (!empty($data['estatus']) && intval($data['estatus'])) {
				$estatus = intval($data['estatus']) != 0 ? 1 : 0;
			} else {
				$estatus = !$gastoFijo->row()->estatus;
			}

			$data = [
				'estatus' => $estatus,
				'fk_id_usuario_modifico' => $data['fk_id_usuario_modifico'],
				'fecha_modificacion' => date('Y-m-d H:i:s'),
			];

			// Almacenar información en BD
			$respuesta['err'] = !$this->db->update('cat_gastos_fijos', $data, [
				'id_gasto_fijo' => $idGastoFijo,
			]);
			$respuesta['msg'] = $respuesta['err']
				? 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message']
				: 'Estatus modificado con éxito.';
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
			die();
		}
		return $respuesta;
	}
}

/* End of file Gasto_Fijo_model.php */
/* Location: ./application/models/Gasto_Fijo_model.php */
