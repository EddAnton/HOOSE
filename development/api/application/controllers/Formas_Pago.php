<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Controlador: Formas_Pago
 *
 * Este controlador realiza las operaciones relacionadas con las Formas de pago
 *
 * @package   CodeIgniter
 * @category  Controller REST
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Formas_Pago extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Forma_Pago_model');
	}

	public function index()
	{
		$this->response(APP_NAME . ' API / Formas Pago :: Controller');
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

			$response['formas_pago'] = $this->Forma_Pago_model->listar($soloActivos);

			$response['error'] = false;
			$response['msg'] = 'Información obtenida con éxito.';
			$responseCode = REST_Controller::HTTP_OK;
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}
}

/* End of file Formas_Pago.php */
/* Location: ./application/controllers/Formas_Pago.php */
