<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Controlador: Estatus_Recaudaciones
 *
 * Este controlador realiza las operaciones relacionadas con los Estatus de las recaudaciones
 *
 * @package   CodeIgniter
 * @category  Controller REST
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Estatus_Recaudaciones extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Estatus_Recaudacion_model');
	}

	public function index()
	{
		$this->response(APP_NAME . ' API / Estatus Recaudaciones :: Controller');
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

			/* // Verificar si se especifica el ID de un registro en particular
			$idEstatusRecaudacion = $this->security->xss_clean($this->uri->segment(2));
			$idEstatusRecaudacion =
				!empty($idEstatusRecaudacion) && intval($idEstatusRecaudacion) ? intval($idEstatusRecaudacion) : 0; */

			// Obtener registro(s)
			/* $result = $this->Estatus_Recaudacion_model->listar($idEstatusRecaudacion, $soloActivos);
      if (!empty($idEstatusRecaudacion)) {
				$response['gasto_mantenimiento'] = $result;
			} else {
				$response['gastos_mantenimiento'] = $result;
			} */
			$response['estatus_recaudaciones'] = $this->Estatus_Recaudacion_model->listar($soloActivos);

			$response['error'] = false;
			$response['msg'] = 'Información obtenida con éxito.';
			$responseCode = REST_Controller::HTTP_OK;
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}
}

/* End of file Estatus_Recaudaciones.php */
/* Location: ./application/controllers/Estatus_Recaudaciones.php */
