<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Modelo: Estatus_Recaudacion_model
 *
 * Este modelo realiza las operaciones requeridas sobre los Estatus de las recaudaciones
 *
 * @package   CodeIgniter
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Estatus_Recaudacion_model extends CI_Model
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
				->order_by('estatus_recaudacion')
				->get('cat_estatus_recaudacion')
				->result_array();
		} catch (Exception $e) {
			throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
			die();
		}
	}
}

/* End of file Estatus_Recaudacion_model.php */
/* Location: ./application/models/Estatus_Recaudacion_model.php */
