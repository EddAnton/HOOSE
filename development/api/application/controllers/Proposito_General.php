<?php
defined('BASEPATH') or exit('No direct script access allowed');
// Don't forget include/define REST_Controller path

/**
 *
 * Controller Proposito_General
 *
 * This controller for ...
 *
 * @package   CodeIgniter
 * @category  Controller REST
 * @author    Setiawan Jodi <jodisetiawan@fisip-untirta.ac.id>
 * @author    Raul Guerrero <r.g.c@me.com>
 * @link      https://github.com/setdjod/myci-extension/
 * @param     ...
 * @return    ...
 *
 */

class Proposito_General extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Proposito_General_model');
	}

	public function index()
	{
		$this->response(APP_NAME . ' API / Propósito General :: Controller');
	}

	public function login_imagenes_get()
	{
		$response = [
			'err' => true,
			'msg' => null,
		];
		$responseCode = REST_Controller::HTTP_BAD_REQUEST;

		try {
			// Obtener registro(s)
			$response['data'] = $this->Proposito_General_model->login_imagenes();

			$response['err'] = false;
			$response['msg'] = 'Información obtenida con éxito.';
			$responseCode = REST_Controller::HTTP_OK;
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}

	public function condominio_default_get()
	{
		$response = [
			'err' => true,
			'msg' => null,
		];
		$responseCode = REST_Controller::HTTP_BAD_REQUEST;

		try {
			// Validar token
			$token = getToken();
			if ($token->error) {
				$this->response($token, $responseCode);
			}
			$idPerfilUsuario = $token->data->id_perfil_usuario;

			// Verificar que el perfil del usuario pueda cambiar de condominio
      if (empty($idPerfilUsuario) || !intval($idPerfilUsuario) || intval($idPerfilUsuario) != 1) {
				$response['msg'] = 'Perfil sin privilegios.';
				$this->response($response, $responseCode);
			}

			// Obtener id del condominio por defecto
			$response['id_condominio'] = $this->Proposito_General_model->condominio_default();

			$response['err'] = false;
			$response['msg'] = 'Información obtenida con éxito.';
			$responseCode = REST_Controller::HTTP_OK;
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}

	public function respaldar_db_post()
	{
		$this->Proposito_General_model->respaldar_db();
	}
}

/* End of file Proposito_General.php */
/* Location: ./application/controllers/Proposito_General.php */
