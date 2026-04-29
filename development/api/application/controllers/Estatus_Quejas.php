<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Controlador: Estatus_Quejas
 *
 * Este controlador realiza las operaciones relacionadas con los Estatus de las quejas
 *
 * @package   CodeIgniter
 * @category  Controller REST
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Estatus_Quejas extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Estatus_Queja_model');
	}

	public function index()
	{
		$this->response(APP_NAME . ' API / Estatus Quejas :: Controller');
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

			$response['estatus_quejas'] = $this->Estatus_Queja_model->listar($soloActivos);

			$response['error'] = false;
			$response['msg'] = 'Información obtenida con éxito.';
			$responseCode = REST_Controller::HTTP_OK;
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}
}

/* End of file Estatus_Quejas.php */
/* Location: ./application/controllers/Estatus_Quejas.php */
