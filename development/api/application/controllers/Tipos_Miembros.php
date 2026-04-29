<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Controlador: Tipos_Miembros
 *
 * Este controlador realiza las operaciones relacionadas con los Tipos de miembros
 *
 * @package   CodeIgniter
 * @category  Controller REST
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Tipos_Miembros extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Tipo_Miembro_model');
	}

	public function index()
	{
		$this->response(APP_NAME . ' API / Tipos Miembros :: Controller');
	}

	// Listar registro(s)
	public function listar_get($soloColaboradores = 0, $soloActivos = false)
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

			// Verificar si se especifica el ID de un registro en particular
			$idTipoMiembro = $this->security->xss_clean($this->uri->segment(2));
			$idTipoMiembro = !empty($idTipoMiembro) && intval($idTipoMiembro) ? intval($idTipoMiembro) : 0;

			// Obtener registro(s)
			// $response['tipos_miembros'] = $this->Tipo_Miembro_model->listar($idTipoMiembro, $soloActivos);
			$response['tipos_miembros'] = $this->Tipo_Miembro_model->listar($idTipoMiembro, $soloColaboradores, $soloActivos);

			$response['error'] = false;
			$response['msg'] = 'Información obtenida con éxito.';
			$responseCode = REST_Controller::HTTP_OK;
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

			$data = $this->security->xss_clean($this->post());
			// Verificar que se haya enviado la información a insertar
			if (empty($data)) {
				$respuesta['msg'] = 'Información a procesar no ha sido proporcionada.';
				$this->response($respuesta, $codigo_respuesta);
			}

			$data = nulificar_elementos_arreglo(trim_elementos_arreglo($data));
			$data = capitalizar_arreglo($data, ['tipo_miembro']);

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('tipoMiembroInsertar')) {
				// Error al validar la información
				$respuesta['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$respuesta['msg'] .= $value . PHP_EOL;
				}
				$this->response($respuesta, $codigo_respuesta);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'tipo_miembro' => $data['tipo_miembro'],
				'es_colaborador' => $data['es_colaborador'],
				'fk_id_usuario_registro' => $idUsuarioRegistro,
			];

			$respuesta = $this->Tipo_Miembro_model->insertar($data);
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

			// Verificar si se especifica el ID de un registro en particular
			$idTipoMiembro = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idTipoMiembro) || !intval($idTipoMiembro) || intval($idTipoMiembro) < 1) {
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
			$data = capitalizar_arreglo($data, ['tipo_miembro']);

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('tipoMiembroInsertar')) {
				// Error al validar la información
				$respuesta['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$respuesta['msg'] .= $value . PHP_EOL;
				}
				$this->response($respuesta, $codigo_respuesta);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'id_tipo_miembro' => $idTipoMiembro,
				'tipo_miembro' => $data['tipo_miembro'],
				'es_colaborador' => $data['es_colaborador'],
				'fk_id_usuario_modifico' => $idUsuarioModifico,
			];

			$respuesta = $this->Tipo_Miembro_model->actualizar($data);
			if (!$respuesta['err']) {
				$codigo_respuesta = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$respuesta['msg'] = $e->getMessage();
		}
		$this->response($respuesta, $codigo_respuesta);
	}

	// Alternar estatus
	public function alternar_estatus_post()
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
			$idTipoMiembro = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idTipoMiembro) || !intval($idTipoMiembro) || intval($idTipoMiembro) < 1) {
				$respuesta['msg'] = 'Debe especificar un identificador válido.';
				$this->response($respuesta, $codigo_respuesta);
			}

			// Información validada con éxito. Procede a la actualización del estatus
			$data = [
				'id_tipo_miembro' => $idTipoMiembro,
				'fk_id_usuario_modifico' => $idUsuarioModifico,
			];

			$respuesta = $this->Tipo_Miembro_model->alternar_estatus($data);
			if (!$respuesta['err']) {
				$codigo_respuesta = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$respuesta['msg'] = $e->getMessage();
		}
		$this->response($respuesta, $codigo_respuesta);
	}
}

/* End of file Tipos_Miembros.php */
/* Location: ./application/controllers/Tipos_Miembros.php */
