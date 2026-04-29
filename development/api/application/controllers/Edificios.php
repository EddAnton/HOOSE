<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Controlador: Edificios
 *
 * Este controlador realiza las operaciones relacionadas con los Edificios
 *
 * @package   CodeIgniter
 * @category  Controller REST
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Edificios extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Edificio_model');
	}

	public function index()
	{
		$this->response(APP_NAME . ' API / Edificios :: Controller');
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
			$idCondominio = $token->data->id_condominio_usuario;

			// Verificar si se especifica el ID de un registro en particular
			$idEdificio = $this->security->xss_clean($this->uri->segment(2));
			$idEdificio = !empty($idEdificio) && intval($idEdificio) ? intval($idEdificio) : 0;

			// Obtener registro(s)
			$response['edificios'] = $this->Edificio_model->listar($idEdificio, $idCondominio, $soloActivos);

			$response['error'] = $response['edificios'] === false;
			$response['msg'] = !$response['error']
				? 'Información obtenida con éxito.'
				: 'Error al obtener información. Verifique información proporcionada.';
			$responseCode = !$response['error'] ? REST_Controller::HTTP_OK : $responseCode;
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}

	// Nuevo registro
	public function insertar_post()
	{
		$respuesta = [
			'err' => true,
			'msg' => null,
		];
		$codigo_respuesta = REST_Controller::HTTP_BAD_REQUEST;

		try {
			// Validar token
			$token = getToken();
			if ($token->error) {
				$this->response($token, $responseCode);
			}
			$idUsuarioRegistro = $token->data->id;
			$idCondominio = $token->data->id_condominio_usuario;

			$data = $this->security->xss_clean($this->post());
			// Verificar que se haya enviado la información a insertar
			if (empty($data)) {
				$respuesta['msg'] = 'Información a procesar no ha sido proporcionada.';
				$this->response($respuesta, $codigo_respuesta);
			}

			$data = nulificar_elementos_arreglo(trim_elementos_arreglo($data));
			$data = capitalizar_arreglo($data, ['edificio']);
			// $data['id_condominio'] = $idCondominio;

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('edificioInsertar')) {
				// Error al validar la información
				$respuesta['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$respuesta['msg'] .= $value . PHP_EOL;
				}
				$this->response($respuesta, $codigo_respuesta);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'edificio' => $data['edificio'],
				// 'fk_id_condominio' => $data['id_condominio'],
				'fk_id_condominio' => $idCondominio,
				'fk_id_usuario_registro' => $idUsuarioRegistro,
			];

			$respuesta = $this->Edificio_model->insertar($data);
			if (!$respuesta['err']) {
				$codigo_respuesta = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$respuesta['msg'] = $e->getMessage();
		}
		$this->response($respuesta, $codigo_respuesta);
	}

	// Actualizar registro
	public function actualizar_post()
	{
		$respuesta = [
			'err' => true,
			'msg' => null,
		];
		$codigo_respuesta = REST_Controller::HTTP_BAD_REQUEST;

		try {
			// Validar token
			$token = getToken();
			if ($token->error) {
				$this->response($token, $responseCode);
			}
			$idUsuarioModifico = $token->data->id;
			// $idCondominio = $token->data->id_condominio_usuario;

			// Verificar si se especifica el ID de un registro en particular
			$idEdificio = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idEdificio) || !intval($idEdificio) || intval($idEdificio) < 1) {
				$respuesta['msg'] = 'Debe especificar un identificador válido.';
				$this->response($respuesta, $codigo_respuesta);
			}

			$data = $this->security->xss_clean($this->post());
			// Verificar que se haya enviado la información a insertar
			if (empty($data)) {
				$respuesta['msg'] = 'Información a procesar no ha sido proporcionada.';
				$this->response($respuesta, $codigo_respuesta);
			}

			$data = nulificar_elementos_arreglo(trim_elementos_arreglo($data));
			$data = capitalizar_arreglo($data, ['edificio']);
			// $data['id_condominio'] = $idCondominio;

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('edificioInsertar')) {
				// Error al validar la información
				$respuesta['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$respuesta['msg'] .= $value . PHP_EOL;
				}
				$this->response($respuesta, $codigo_respuesta);
			}

			// Información validada con éxito. Procede a la actualización
			$data = [
				'id_edificio' => $idEdificio,
				'edificio' => $data['edificio'],
				// 'fk_id_condominio' => $data['id_condominio'],
				'fk_id_usuario_modifico' => $idUsuarioModifico,
			];

			$respuesta = $this->Edificio_model->actualizar($data);
			if (!$respuesta['err']) {
				$codigo_respuesta = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$respuesta['msg'] = $e->getMessage();
		}
		$this->response($respuesta, $codigo_respuesta);
	}

	// Alternar estatus
	public function alternar_estatus_post($estatus = null)
	{
		$respuesta = [
			'err' => true,
			'msg' => null,
		];
		$codigo_respuesta = REST_Controller::HTTP_BAD_REQUEST;

		try {
			// Validar token
			$token = getToken();
			if ($token->error) {
				$this->response($token, $responseCode);
			}
			$idUsuarioModifico = $token->data->id;

			// Verificar si se especifica el ID de un registro en particular
			$idEdificio = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idEdificio) || !intval($idEdificio) || intval($idEdificio) < 1) {
				$respuesta['msg'] = 'Debe especificar un identificador válido.';
				$this->response($respuesta, $codigo_respuesta);
			}

			// Información validada con éxito. Procede a la actualización del estatus
			$data = [
				'id_edificio' => $idEdificio,
				'estatus' => $estatus,
				'fk_id_usuario_modifico' => $idUsuarioModifico,
			];

			$respuesta = $this->Edificio_model->alternar_estatus($data);
			if (!$respuesta['err']) {
				$codigo_respuesta = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$respuesta['msg'] = $e->getMessage();
		}
		$this->response($respuesta, $codigo_respuesta);
	}

	// Deshabilitar
	/* public function deshabilitar_post()
	{
		$respuesta = [
			'err' => true,
			'msg' => null,
		];
		$codigo_respuesta = REST_Controller::HTTP_BAD_REQUEST;

		try {
			// Validar token
			$token = getToken();
			if ($token->error) {
				$this->response($token, $responseCode);
			}
			$idUsuarioModifico = $token->data->id;

			// Verificar si se especifica el ID de un registro en particular
			$idEdificio = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idEdificio) || !intval($idEdificio) || intval($idEdificio) < 1) {
				$respuesta['msg'] = 'Debe especificar un identificador válido.';
				$this->response($respuesta, $codigo_respuesta);
			}

			// Información validada con éxito. Procede a la actualización del estatus
			$data = [
				'id_edificio' => $idEdificio,
        'estatus' => 0,
				'fk_id_usuario_modifico' => $idUsuarioModifico,
			];

			$respuesta = $this->Edificio_model->alternar_estatus($data);
			if (!$respuesta['err']) {
				$codigo_respuesta = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$respuesta['msg'] = $e->getMessage();
		}
		$this->response($respuesta, $codigo_respuesta);
	} */
}

/* End of file Edificios.php */
/* Location: ./application/controllers/Edificios.php */
