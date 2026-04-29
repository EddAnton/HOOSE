<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Controlador: Quejas
 *
 * Este controlador realiza las operaciones relacionadas con las Quejas
 *
 * @package   CodeIgniter
 * @category  Controller REST
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Quejas extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Queja_model');
	}

	public function index()
	{
		$this->response(APP_NAME . ' API / Quejas :: Controller');
	}

	// Listar registro(s)
	public function listar_get($soloActivos = false)
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
			$idCondominio = $token->data->id_condominio_usuario;
			// $idPerfilUsuario = $token->data->id_perfil_usuario;
			// $idUsuario = $token->data->id;
			// $esAdministrador = in_array($token->data->id_perfil_usuario, [1, 2]);

			// Verificar si se especifica el ID de un registro en particular
			$idQueja = $this->security->xss_clean($this->uri->segment(2));
			$idQueja = !empty($idQueja) && intval($idQueja) ? intval($idQueja) : 0;

			// Obtener registro(s)
			// $result = $this->Queja_model->listar($idQueja, $idCondominio, $idUsuario, $esAdministrador, $soloActivos);
			$result = $this->Queja_model->listar($idQueja, $idCondominio, $soloActivos);
			if (!empty($idQueja)) {
				$response['queja'] = $result;
			} else {
				$response['quejas'] = $result;
			}

			$response['err'] = false;
			$response['msg'] = 'Información obtenida con éxito.';
			$responseCode = REST_Controller::HTTP_OK;
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}

	// Listar seguimientos de la queja
	public function listar_seguimiento_get()
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

			// Verificar si se especifica el ID de la queja
			$idQueja = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idQueja) || !intval($idQueja) || intval($idQueja) < 1) {
				$response['msg'] = 'Debe especificar un identificador válido.';
				$this->response($response, $responseCode);
			}

			$response['seguimiento'] = $this->Queja_model->listar_seguimiento($idQueja);

			$response['err'] = false;
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
			$idUsuarioRegistro = $token->data->id;
			$idCondominio = $token->data->id_condominio_usuario;

			$data = $this->security->xss_clean($this->post());
			// Verificar que se haya enviado la información a insertar
			if (empty($data)) {
				$response['msg'] = 'Información a procesar no ha sido proporcionada.';
				$this->response($response, $responseCode);
			}

			$data = nulificar_elementos_arreglo(trim_elementos_arreglo($data));
			$data = capitalizar_arreglo($data, ['titulo', 'descripcion']);

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('quejaInsertar')) {
				// Error al validar la información
				$response['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$response['msg'] .= $value . PHP_EOL;
				}
				$this->response($response, $responseCode);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'fk_id_condominio' => $idCondominio,
				'titulo' => $data['titulo'],
				'descripcion' => $data['descripcion'],
				'fk_id_estatus_queja' => 1,
				'archivos' => !empty($_FILES) && !empty($_FILES['archivos']) ? 'archivos' : null,
				'fk_id_usuario_registro' => $idUsuarioRegistro,
			];

			$response = $this->Queja_model->insertar($data);
			if (!$response['err']) {
				$responseCode = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}

	// Actualizar registro
	public function actualizar_post()
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
			$idUsuarioModifico = $token->data->id;

			// Verificar si se especifica el ID de un registro en particular
			$idQueja = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idQueja) || !intval($idQueja) || intval($idQueja) < 1) {
				$response['msg'] = 'Debe especificar un identificador válido.';
				$this->response($response, $responseCode);
			}

			$data = $this->security->xss_clean($this->post());
			// Verificar que se haya enviado la información a insertar
			if (empty($data)) {
				$response['msg'] = 'Información a procesar no ha sido proporcionada.';
				$this->response($response, $responseCode);
			}

			$data = nulificar_elementos_arreglo(trim_elementos_arreglo($data));
			$data = capitalizar_arreglo($data, ['titulo', 'descripcion']);

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('quejaInsertar')) {
				// Error al validar la información
				$response['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$response['msg'] .= $value . PHP_EOL;
				}
				$this->response($response, $responseCode);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'id_queja' => $idQueja,
				'titulo' => $data['titulo'],
				'descripcion' => $data['descripcion'],
				'archivos' => !empty($_FILES) && !empty($_FILES['archivos']) ? 'archivos' : null,
				'archivos_borrar' => !empty($data['archivos_borrar']) ? explode(',', $data['archivos_borrar']) : null,
				'fk_id_usuario_modifico' => $idUsuarioModifico,
			];

			$response = $this->Queja_model->actualizar($data);
			if (!$response['err']) {
				$responseCode = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}

	// Eliminar lógicamente el registro
	public function eliminar_post()
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
			$idUsuarioModifico = $token->data->id;

			// Verificar que se especique el ID del registro a actualizar
			$idQueja = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idQueja) || !intval($idQueja) || intval($idQueja) < 1) {
				$response['msg'] = 'Debe especificar un identificador válido.';
				$this->response($response, $responseCode);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'id_queja' => $idQueja,
				'id_usuario_modifico' => $idUsuarioModifico,
			];

			// Información validada con éxito. Procede a la inserción
			$response = $this->Queja_model->eliminar($data);
			if (!$response['err']) {
				$responseCode = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}

	// Asignar colaborador registro
	public function asignar_colaborador_post()
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
			$idUsuarioModifico = $token->data->id;

			// Verificar si se especifica el ID de un registro en particular
			$idQueja = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idQueja) || !intval($idQueja) || intval($idQueja) < 1) {
				$response['msg'] = 'Debe especificar un identificador válido.';
				$this->response($response, $responseCode);
			}

			$data = $this->security->xss_clean($this->post());
			// Verificar que se haya enviado la información a actualizar
			if (empty($data)) {
				$response['msg'] = 'Información a procesar no ha sido proporcionada.';
				$this->response($response, $responseCode);
			}

			$data = nulificar_elementos_arreglo(trim_elementos_arreglo($data));

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('quejaAsignarColaborador')) {
				// Error al validar la información
				$response['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$response['msg'] .= $value . PHP_EOL;
				}
				$this->response($response, $responseCode);
			}

			// Información validada con éxito. Procede a la actualización
			$data = [
				'id_queja' => $idQueja,
				'fk_id_usuario_asignado' => $data['id_usuario_asignado'],
				'fk_id_usuario_modifico' => $idUsuarioModifico,
			];

			$response = $this->Queja_model->asignar_colaborador($data);
			if (!$response['err']) {
				$responseCode = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}

	// Actualizar registro
	public function actualizar_estatus_post()
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
			$idUsuarioModifico = $token->data->id;

			// Verificar si se especifica el ID de un registro en particular
			$idQueja = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idQueja) || !intval($idQueja) || intval($idQueja) < 1) {
				$response['msg'] = 'Debe especificar un identificador válido.';
				$this->response($response, $responseCode);
			}

			$data = $this->security->xss_clean($this->post());
			// Verificar que se haya enviado la información a actualizar
			if (empty($data)) {
				$response['msg'] = 'Información a procesar no ha sido proporcionada.';
				$this->response($response, $responseCode);
			}

			$data = nulificar_elementos_arreglo(trim_elementos_arreglo($data));
			$data = capitalizar_arreglo($data, ['solucion']);

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('quejaActualizarEstatus')) {
				// Error al validar la información
				$response['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$response['msg'] .= $value . PHP_EOL;
				}
				$this->response($response, $responseCode);
			}

			// Información validada con éxito. Procede a la actualización
			$data = [
				'id_queja' => $idQueja,
				'fk_id_estatus_queja' => $data['id_estatus_queja'],
				'solucion' => !empty($data['solucion']) ? $data['solucion'] : null,
				'fk_id_usuario_modifico' => $idUsuarioModifico,
			];

			$response = $this->Queja_model->actualizar_estatus($data);
			if (!$response['err']) {
				$responseCode = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}

	public function insertar_seguimiento_post()
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
			$idUsuarioRegistro = $token->data->id;

			// Verificar si se especifica el ID de un registro en particular
			$idQueja = $this->security->xss_clean($this->uri->segment(4));
			if (empty($idQueja) || !intval($idQueja) || intval($idQueja) < 1) {
				$response['msg'] = 'Debe especificar un identificador válido.';
				$this->response($response, $responseCode);
			}

			$data = $this->security->xss_clean($this->post());
			// Verificar que se haya enviado la información a insertar
			if (empty($data)) {
				$response['msg'] = 'Información a procesar no ha sido proporcionada.';
				$this->response($response, $responseCode);
			}

			$data = nulificar_elementos_arreglo(trim_elementos_arreglo($data));
			$data = capitalizar_arreglo($data, ['seguimiento']);

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('quejaSeguimientoInsertar')) {
				// Error al validar la información
				$response['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$response['msg'] .= $value . PHP_EOL;
				}
				$this->response($response, $responseCode);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'fk_id_queja' => $idQueja,
				'fecha' => $data['fecha'],
				'seguimiento' => $data['seguimiento'],
				'fk_id_usuario_registro' => $idUsuarioRegistro,
			];

			$response = $this->Queja_model->insertar_seguimiento($data);
			if (!$response['err']) {
				$responseCode = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}

	public function actualizar_seguimiento_post()
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
			$idUsuarioModifico = $token->data->id;

			// Verificar si se especifica el ID de un registro en particular
			$idSeguimiento = $this->security->xss_clean($this->uri->segment(4));
			if (empty($idSeguimiento) || !intval($idSeguimiento) || intval($idSeguimiento) < 1) {
				$response['msg'] = 'Debe especificar un identificador válido.';
				$this->response($response, $responseCode);
			}

			$data = $this->security->xss_clean($this->post());
			// Verificar que se haya enviado la información a actualizar
			if (empty($data)) {
				$response['msg'] = 'Información a procesar no ha sido proporcionada.';
				$this->response($response, $responseCode);
			}

			$data = nulificar_elementos_arreglo(trim_elementos_arreglo($data));
			$data = capitalizar_arreglo($data, ['seguimiento']);

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('quejaSeguimientoInsertar')) {
				// Error al validar la información
				$response['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$response['msg'] .= $value . PHP_EOL;
				}
				$this->response($response, $responseCode);
			}

			// Información validada con éxito. Procede a la actualización
			$data = [
				'id_queja_seguimiento' => $idSeguimiento,
				'fecha' => $data['fecha'],
				'seguimiento' => $data['seguimiento'],
				'fk_id_usuario_modifico' => $idUsuarioModifico,
			];

			$response = $this->Queja_model->actualizar_seguimiento($data);
			if (!$response['err']) {
				$responseCode = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}

	public function eliminar_seguimiento_post()
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
			$idUsuarioModifico = $token->data->id;

			// Verificar si se especifica el ID de un registro en particular
			$idSeguimiento = $this->security->xss_clean($this->uri->segment(4));
			if (empty($idSeguimiento) || !intval($idSeguimiento) || intval($idSeguimiento) < 1) {
				$response['msg'] = 'Debe especificar un identificador válido.';
				$this->response($response, $responseCode);
			}

			$data = [
				'id_queja_seguimiento' => $idSeguimiento,
				'id_usuario_modifico' => $idUsuarioModifico,
			];

			$response = $this->Queja_model->eliminar_seguimiento($data);
			if (!$response['err']) {
				$responseCode = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}
}

/* End of file Quejas.php */
/* Location: ./application/controllers/Quejas.php */
