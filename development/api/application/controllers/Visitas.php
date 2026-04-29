<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Controlador: Visitas
 *
 * Este controlador realiza las operaciones relacionadas con los Visitas
 *
 * @package   CodeIgniter
 * @category  Controller REST
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Visitas extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Visita_model');
	}

	public function index()
	{
		$this->response(APP_NAME . ' API / Visitas :: Controller');
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
			$idVisita = $this->security->xss_clean($this->uri->segment(2));
			$idVisita = !empty($idVisita) && intval($idVisita) ? intval($idVisita) : 0;

			// Obtener registro(s)
			$result = $this->Visita_model->listar($idVisita, $idCondominio, $soloActivos);
			if (!empty($idVisita)) {
				$response['visita'] = $result;
			} else {
				$response['visitas'] = $result;
			}

			$response['error'] = $result === false;
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

			$data = $this->security->xss_clean($this->post());
			// Verificar que se haya enviado la información a insertar
			if (empty($data)) {
				$respuesta['msg'] = 'Información a procesar no ha sido proporcionada.';
				$this->response($respuesta, $codigo_respuesta);
			}

			$data = nulificar_elementos_arreglo(trim_elementos_arreglo($data));
			$data = capitalizar_arreglo($data, ['visitante', 'domicilio']);

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('visitaInsertar')) {
				// Error al validar la información
				$respuesta['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$respuesta['msg'] .= $value . PHP_EOL;
				}
				$this->response($respuesta, $codigo_respuesta);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'visitante' => $data['visitante'],
				'telefono' => !empty($data['telefono']) ? $data['telefono'] : null,
				'domicilio' => !empty($data['domicilio']) ? $data['domicilio'] : null,
				'identificacion_folio' => !empty($data['identificacion_folio']) ? $data['identificacion_folio'] : null,
				'fk_id_unidad' => $data['id_unidad'],
				'fecha_hora_entrada' => $data['fecha_hora_entrada'],
				'fk_id_usuario_registro' => $idUsuarioRegistro,
			];

			$respuesta = $this->Visita_model->insertar($data);
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
			$idVisita = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idVisita) || !intval($idVisita) || intval($idVisita) < 1) {
				$respuesta['msg'] = 'Debe especificar un identificador válido.';
				$this->response($respuesta, $codigo_respuesta);
			}

			// $data = $this->security->xss_clean($this->post());
			$data = $this->security->xss_clean($this->post());
			// Verificar que se haya enviado la información a insertar
			if (empty($data)) {
				$respuesta['msg'] = 'Información a procesar no ha sido proporcionada.';
				$this->response($respuesta, $codigo_respuesta);
			}

			$data = nulificar_elementos_arreglo(trim_elementos_arreglo($data));
			$data = capitalizar_arreglo($data, ['visitante', 'domicilio']);

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('visitaInsertar')) {
				// Error al validar la información
				$respuesta['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$respuesta['msg'] .= $value . PHP_EOL;
				}
				$this->response($respuesta, $codigo_respuesta);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'id_visita' => $idVisita,
				'visitante' => $data['visitante'],
				'telefono' => !empty($data['telefono']) ? $data['telefono'] : null,
				'domicilio' => !empty($data['domicilio']) ? $data['domicilio'] : null,
				'identificacion_folio' => !empty($data['identificacion_folio']) ? $data['identificacion_folio'] : null,
				'fk_id_unidad' => $data['id_unidad'],
				'fecha_hora_entrada' => $data['fecha_hora_entrada'],
				'fecha_hora_salida' => !empty($data['fecha_hora_salida']) ? $data['fecha_hora_salida'] : null,
				'fk_id_usuario_modifico' => $idUsuarioModifico,
			];

			$respuesta = $this->Visita_model->actualizar($data);
			if (!$respuesta['err']) {
				$codigo_respuesta = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$respuesta['msg'] = $e->getMessage();
		}
		$this->response($respuesta, $codigo_respuesta);
	}

	// Establecer la fecha y hora de salida
	public function registrar_salida_post()
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
			$idUsuarioModifico = $token->data->id;

			// Verificar que se especique el ID del registro a actualizar
			$idVisita = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idVisita) || !intval($idVisita) || intval($idVisita) < 1) {
				$response['msg'] = 'Debe especificar un identificador válido.';
				$this->response($response, $responseCode);
			}

			$data = $this->security->xss_clean($this->post());

			// Verificar que se haya enviado la información a actualizar
			if (empty($data)) {
				$response['msg'] = 'No se proporcionó información a procesar.';
				$this->response($response, $responseCode);
			}

			// Normalizar la información a almacenar
			$data = nulificar_elementos_arreglo(trim_elementos_arreglo($data));

			// Validación de la información proporcionada
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('visitaRegistrarSalida')) {
				// Error al validar la información
				$response['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$response['msg'] .= $value . PHP_EOL;
				}
				$this->response($response, $responseCode);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'id_visita' => $idVisita,
				'fecha_hora_salida' => $data['fecha_hora_salida'],
				'id_usuario_modifico' => $idUsuarioModifico,
			];

			// Información validada con éxito. Procede a la inserción
			$response = $this->Visita_model->registrar_salida($data);
			if (!$response['error']) {
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
			$idUsuarioModifico = $token->data->id;

			// Verificar que se especique el ID del registro a actualizar
			$idVisita = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idVisita) || !intval($idVisita) || intval($idVisita) < 1) {
				$response['msg'] = 'Debe especificar un identificador válido.';
				$this->response($response, $responseCode);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'id_visita' => $idVisita,
				'id_usuario_modifico' => $idUsuarioModifico,
			];

			// Información validada con éxito. Procede a la inserción
			$response = $this->Visita_model->eliminar($data);
			if (!$response['error']) {
				$responseCode = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}
}

/* End of file Visitas.php */
/* Location: ./application/controllers/Visitas.php */
