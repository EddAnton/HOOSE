<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Controlador: Condominios
 *
 * Este controlador realiza las operaciones relacionadas con los Condominios
 *
 * @package   CodeIgniter
 * @category  Controller REST
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Condominios extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Condominio_model');
		$this->load->helper('File_Upload');
	}

	public function index()
	{
		$this->response(APP_NAME . ' API / Condominios :: Controller');
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

			// Verificar si se especifica el ID de un registro en particular
			$idCondominio = $this->security->xss_clean($this->uri->segment(2));
			$idCondominio = !empty($idCondominio) && intval($idCondominio) ? intval($idCondominio) : 0;

			// Obtener registro(s)
			$response['condominios'] = $this->Condominio_model->listar($idCondominio, $soloActivos);

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

			$data = $this->post();
			// Verificar que se haya enviado la información a insertar
			if (empty($data)) {
				$respuesta['msg'] = 'Información a procesar no ha sido proporcionada.';
				$this->response($respuesta, $codigo_respuesta);
			}
			// Si se incluye reglamento, extraerlo de la data y aplicar filtro de seguridad a la misma
			$reglamento = null;
			if (!empty($data['reglamento'])) {
				$reglamento = $data['reglamento'];
				unset($data['reglamento']);
			}
			$data = $this->security->xss_clean($data);

			$data = nulificar_elementos_arreglo(trim_elementos_arreglo($data));
			$data = capitalizar_arreglo($data, ['condominio', 'domicilio', 'constructora', 'constructora_domicilio']);
			$data = minusculas_arreglo($data, ['email']);

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('condominioInsertar')) {
				// Error al validar la información
				$respuesta['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$respuesta['msg'] .= $value . PHP_EOL;
				}
				$this->response($respuesta, $codigo_respuesta);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'condominio' => $data['condominio'],
				'email' => !empty($data['email']) ? $data['email'] : null,
				'telefono' => !empty($data['telefono']) ? $data['telefono'] : null,
				'domicilio' => $data['domicilio'],
				'telefono_guardia' => !empty($data['telefono_guardia']) ? $data['telefono_guardia'] : null,
				'telefono_secretaria' => !empty($data['telefono_secretaria']) ? $data['telefono_secretaria'] : null,
				'telefono_moderador' => !empty($data['telefono_moderador']) ? $data['telefono_moderador'] : null,
				'anio_construccion' => !empty($data['anio_construccion']) ? $data['anio_construccion'] : null,
				'archivo_imagen' => !empty($_FILES) && !empty($_FILES['archivo_imagen']) ? 'archivo_imagen' : null,
				'constructora' => !empty($data['constructora']) ? $data['constructora'] : null,
				'constructora_telefono' => !empty($data['constructora_telefono']) ? $data['constructora_telefono'] : null,
				'constructora_domicilio' => !empty($data['constructora_domicilio']) ? $data['constructora_domicilio'] : null,
				'reglamento' => !empty($reglamento) ? $reglamento : null,
				'fk_id_usuario_registro' => $idUsuarioRegistro,
			];

			$respuesta = $this->Condominio_model->insertar($data);
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
			$idCondominio = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idCondominio) || !intval($idCondominio) || intval($idCondominio) < 1) {
				$respuesta['msg'] = 'Debe especificar un identificador válido.';
				$this->response($respuesta, $codigo_respuesta);
			}

			// $data = $this->security->xss_clean($this->post());
			$data = $this->post();
			// Verificar que se haya enviado la información a insertar
			if (empty($data)) {
				$respuesta['msg'] = 'Información a procesar no ha sido proporcionada.';
				$this->response($respuesta, $codigo_respuesta);
			}

			// Si se incluye reglamento, extraerlo de la data y aplicar filtro de seguridad a la misma
			$reglamento = null;
			if (!empty($data['reglamento'])) {
				$reglamento = $data['reglamento'];
				unset($data['reglamento']);
			}
			$data = $this->security->xss_clean($data);

			$data = nulificar_elementos_arreglo(trim_elementos_arreglo($data));
			$data = capitalizar_arreglo($data, ['condominio', 'domicilio', 'constructora', 'constructora_domicilio']);
			$data = minusculas_arreglo($data, ['email']);

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('condominioInsertar')) {
				// Error al validar la información
				$respuesta['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$respuesta['msg'] .= $value . PHP_EOL;
				}
				$this->response($respuesta, $codigo_respuesta);
			}

			// Información validada con éxito. Procede a la actualización
			$data = [
				'id_condominio' => $idCondominio,
				'condominio' => $data['condominio'],
				'email' => !empty($data['email']) ? $data['email'] : null,
				'telefono' => !empty($data['telefono']) ? $data['telefono'] : null,
				'domicilio' => $data['domicilio'],
				'telefono_guardia' => !empty($data['telefono_guardia']) ? $data['telefono_guardia'] : null,
				'telefono_secretaria' => !empty($data['telefono_secretaria']) ? $data['telefono_secretaria'] : null,
				'telefono_moderador' => !empty($data['telefono_moderador']) ? $data['telefono_moderador'] : null,
				'anio_construccion' => !empty($data['anio_construccion']) ? $data['anio_construccion'] : null,
				'archivo_imagen' => !empty($_FILES) && !empty($_FILES['archivo_imagen']) ? 'archivo_imagen' : null,
				'constructora' => !empty($data['constructora']) ? $data['constructora'] : null,
				'constructora_telefono' => !empty($data['constructora_telefono']) ? $data['constructora_telefono'] : null,
				'constructora_domicilio' => !empty($data['constructora_domicilio']) ? $data['constructora_domicilio'] : null,
				'reglamento' => !empty($reglamento) ? $reglamento : null,
				'fk_id_usuario_modifico' => $idUsuarioModifico,
			];

			$respuesta = $this->Condominio_model->actualizar($data);
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
			$idCondominio = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idCondominio) || !intval($idCondominio) || intval($idCondominio) < 1) {
				$respuesta['msg'] = 'Debe especificar un identificador válido.';
				$this->response($respuesta, $codigo_respuesta);
			}

			// Información validada con éxito. Procede a la actualización del estatus
			$data = [
				'id_condominio' => $idCondominio,
				'fk_id_usuario_modifico' => $idUsuarioModifico,
			];

			$respuesta = $this->Condominio_model->alternar_estatus($data);
			if (!$respuesta['err']) {
				$codigo_respuesta = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$respuesta['msg'] = $e->getMessage();
		}
		$this->response($respuesta, $codigo_respuesta);
	}
}

/* End of file Condominios.php */
/* Location: ./application/controllers/Condominios.php */
