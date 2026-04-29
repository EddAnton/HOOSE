<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Controlador: Unidades
 *
 * Este controlador realiza las operaciones relacionadas con los Unidades
 *
 * @package   CodeIgniter
 * @category  Controller REST
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Unidades extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Unidad_model');
	}

	public function index()
	{
		$this->response(APP_NAME . ' API / Edificios :: Controller');
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
			$idUsuarioPropietario = $token->data->id_perfil_usuario == 4 ? $token->data->id : 0;

			// Verificar si se especifica el ID de un registro en particular
			$idUnidad = $this->security->xss_clean($this->uri->segment(2));
			$idUnidad = !empty($idUnidad) && intval($idUnidad) ? intval($idUnidad) : 0;

			// Obtener registro(s)
			$response['unidades'] = $this->Unidad_model->listar(
				$idUnidad,
				$idUsuarioPropietario,
				$idCondominio,
				$soloActivos
			);

			/*
      $response['err'] = false;
			$response['msg'] = 'Información obtenida con éxito.';
			$responseCode = REST_Controller::HTTP_OK;
      */
			$response['err'] = $response['unidades'] === false;
			$response['msg'] = !$response['err']
				? 'Información obtenida con éxito.'
				: 'Error al obtener información. Verifique información proporcionada.';
			$responseCode = !$response['err'] ? REST_Controller::HTTP_OK : $responseCode;
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}

	// Listar unidades disponibles para renta
	public function listar_disponibles_renta_get()
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
			$idUsuarioPropietario = $token->data->id_perfil_usuario == 4 ? $token->data->id : 0;

			// Obtener registro(s)
			$response['unidades'] = $this->Unidad_model->listar_disponibles_renta($idCondominio, $idUsuarioPropietario);

			$response['err'] = $response['unidades'] === false;
			$response['msg'] = !$response['err']
				? 'Información obtenida con éxito.'
				: 'Error al obtener información. Verifique información proporcionada.';
			$responseCode = !$response['err'] ? REST_Controller::HTTP_OK : $responseCode;
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}

	// Listar registro para recaudaciones
	public function listar_para_recaudaciones_get()
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
			$idUsuarioPropietario = $token->data->id_perfil_usuario == 4 ? $token->data->id : 0;

			// Obtener registro(s)
			$response['unidades'] = $this->Unidad_model->listar_para_recaudaciones($idCondominio, $idUsuarioPropietario);

			$response['err'] = $response['unidades'] === false;
			$response['msg'] = !$response['err']
				? 'Información obtenida con éxito.'
				: 'Error al obtener información. Verifique información proporcionada.';
			$responseCode = !$response['err'] ? REST_Controller::HTTP_OK : $responseCode;
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}

	// Listar registros para visitas
	public function listar_para_visita_get()
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

			// Obtener registro(s)
			$response['unidades'] = $this->Unidad_model->listar_para_visita($idCondominio);

			$response['err'] = $response['unidades'] === false;
			$response['msg'] = !$response['err']
				? 'Información obtenida con éxito.'
				: 'Error al obtener información. Verifique información proporcionada.';
			$responseCode = !$response['err'] ? REST_Controller::HTTP_OK : $responseCode;
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}

	// Listar unidades disponibles para renta
	public function listar_sin_propietario_get()
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

			// Obtener registro(s)
			$response['unidades'] = $this->Unidad_model->listar_sin_propietario($idCondominio);

			$response['err'] = $response['unidades'] === false;
			$response['msg'] = !$response['err']
				? 'Información obtenida con éxito.'
				: 'Error al obtener información. Verifique información proporcionada.';
			$responseCode = !$response['err'] ? REST_Controller::HTTP_OK : $responseCode;
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
		$responseCode = REST_Controller::HTTP_BAD_REQUEST;

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
				$this->response($respuesta, $responseCode);
			}

			$data = nulificar_elementos_arreglo(trim_elementos_arreglo($data));
			$data = capitalizar_arreglo($data, ['unidad']);

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('unidadInsertar')) {
				// Error al validar la información
				$respuesta['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$respuesta['msg'] .= $value . PHP_EOL;
				}
				$this->response($respuesta, $responseCode);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'fk_id_edificio' => $data['id_edificio'],
				'unidad' => $data['unidad'],
				'archivo_escrituras' => !empty($_FILES) && !empty($_FILES['archivo_escrituras']) ? 'archivo_escrituras' : null,
				'cuota_mantenimiento_ordinaria' => $data['cuota_mantenimiento_ordinaria'],
				'fk_id_usuario_registro' => $idUsuarioRegistro,
			];

			$respuesta = $this->Unidad_model->insertar($data);
			if (!$respuesta['err']) {
				$responseCode = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$respuesta['msg'] = $e->getMessage();
		}
		$this->response($respuesta, $responseCode);
	}

	// Actualizar registro
	public function actualizar_post()
	{
		$respuesta = [
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
			$idUnidad = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idUnidad) || !intval($idUnidad) || intval($idUnidad) < 1) {
				$respuesta['msg'] = 'Debe especificar un identificador válido.';
				$this->response($respuesta, $responseCode);
			}

			// $data = $this->security->xss_clean($this->post());
			$data = $this->security->xss_clean($this->post());
			// Verificar que se haya enviado la información a insertar
			if (empty($data)) {
				$respuesta['msg'] = 'Información a procesar no ha sido proporcionada.';
				$this->response($respuesta, $responseCode);
			}

			$data = nulificar_elementos_arreglo(trim_elementos_arreglo($data));
			$data = capitalizar_arreglo($data, ['unidad']);

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('unidadInsertar')) {
				// Error al validar la información
				$respuesta['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$respuesta['msg'] .= $value . PHP_EOL;
				}
				$this->response($respuesta, $responseCode);
			}

			// Información validada con éxito. Procede a la actualización
			$data = [
				'fk_id_edificio' => $data['id_edificio'],
				'id_unidad' => $idUnidad,
				'unidad' => $data['unidad'],
				'archivo_escrituras' => !empty($_FILES) && !empty($_FILES['archivo_escrituras']) ? 'archivo_escrituras' : null,
				'borrar_escrituras' => !empty($data['borrar_escrituras']) ? $data['borrar_escrituras'] == 1 : false,
				'cuota_mantenimiento_ordinaria' => $data['cuota_mantenimiento_ordinaria'],
				'fk_id_usuario_modifico' => $idUsuarioModifico,
			];

			$respuesta = $this->Unidad_model->actualizar($data);
			if (!$respuesta['err']) {
				$responseCode = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$respuesta['msg'] = $e->getMessage();
		}
		$this->response($respuesta, $responseCode);
	}

	// Alternar estatus
	public function alternar_estatus_post($estatus = null)
	{
		$respuesta = [
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
			$idUnidad = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idUnidad) || !intval($idUnidad) || intval($idUnidad) < 1) {
				$respuesta['msg'] = 'Debe especificar un identificador válido.';
				$this->response($respuesta, $responseCode);
			}

			// Información validada con éxito. Procede a la actualización del estatus
			$data = [
				'id_unidad' => $idUnidad,
				'estatus' => $estatus,
				'fk_id_usuario_modifico' => $idUsuarioModifico,
			];

			$respuesta = $this->Unidad_model->alternar_estatus($data);
			if (!$respuesta['err']) {
				$responseCode = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$respuesta['msg'] = $e->getMessage();
		}
		$this->response($respuesta, $responseCode);
	}
}

/* End of file Unidades.php */
/* Location: ./application/controllers/Unidades.php */
