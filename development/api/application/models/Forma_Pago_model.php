<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Modelo: Forma_Pago_model
 *
 * Este modelo realiza las operaciones requeridas sobre las Formas de pago
 *
 * @package   CodeIgniter
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Forma_Pago_model extends CI_Model
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
			return $this->db->get('cat_formas_pago')->result_array();
		} catch (Exception $e) {
			throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
			die();
		}
	}
}

/* End of file Forma_Pago_model.php */
/* Location: ./application/models/Forma_Pago_model.php */
