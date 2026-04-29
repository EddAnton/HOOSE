<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Controlador: Tipos_Fondos_Monetarios
 *
 * Este controlador realiza las operaciones relacionadas con los Tipos de fondos
 * monetarios
 *
 * @package   CodeIgniter
 * @category  Controller REST
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Tipos_Fondos_Monetarios extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Tipo_Fondo_Monetario_model');
	}

	public function index()
	{
		$this->response(APP_NAME . ' API / Tipos de Fondos monetarios :: Controller');
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

			$response['tipos_fondos_monetarios'] = $this->Tipo_Fondo_Monetario_model->listar($soloActivos);

			$response['error'] = false;
			$response['msg'] = 'Información obtenida con éxito.';
			$responseCode = REST_Controller::HTTP_OK;
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}
}

/* End of file Tipos_Fondos_Monetarios.php */
/* Location: ./application/controllers/Tipos_Fondos_Monetarios.php */
