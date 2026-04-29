<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Controlador: Tipos_Movimientos_Fondos
 *
 * Este controlador realiza las operaciones relacionadas con los Tipos de movimientos
 * de los fondos monetarios
 *
 * @package   CodeIgniter
 * @category  Controller REST
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Tipos_Movimientos_Fondos extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Tipo_Movimiento_Fondo_model');
	}

	public function index()
	{
		$this->response(APP_NAME . ' API / Tipos de movimientos de Fondos :: Controller');
	}

	// Listar registro(s)
	public function listar_get($soloActivos = false)
	{
		$response = [
			'error' => true,
			'msg' => null,
		];
		$responseCode = REST_Controller::HTTP_BAD_REQUEST;

		try {
			// Validar token
			$token = getToken();
			if ($token->error) {
				$this->response($token, $responseCode);
			}

			$response['tipos_movimientos_fondos'] = $this->Tipo_Movimiento_Fondo_model->listar($soloActivos);

			$response['error'] = false;
			$response['msg'] = 'Información obtenida con éxito.';
			$responseCode = REST_Controller::HTTP_OK;
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}
}

/* End of file Tipos_Movimientos_Fondos.php */
/* Location: ./application/controllers/Tipos_Movimientos_Fondos.php */
