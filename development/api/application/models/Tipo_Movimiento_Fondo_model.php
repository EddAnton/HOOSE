<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Modelo: Tipo_Movimiento_Fondo_model
 *
 * Este modelo realiza las operaciones requeridas sobre los Tipos de movimientos
 * de los fondos monetarios
 *
 * @package   CodeIgniter
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Tipo_Movimiento_Fondo_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	/*
    Obtener información del identificador especificado.
	*/
	public function listar($soloActivos = false)
	{
		try {
			if ($soloActivos) {
				$this->db->where(['estatus' => 1]);
			}
			return $this->db
				->order_by('tipo_movimiento')
				->get('cat_tipos_movimientos_fondos')
				->result_array();
		} catch (Exception $e) {
			throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
			die();
		}
	}
}

/* End of file Tipo_Movimiento_Fondo_model.php */
/* Location: ./application/models/Tipo_Movimiento_Fondo_model.php */
