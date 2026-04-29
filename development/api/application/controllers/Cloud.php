<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Controlador: Cloud
 *
 * Este controlador realiza las operaciones relacionadas con la nube de archivos
 *
 * @package   CodeIgniter
 * @category  Controller REST
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Cloud extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Cloud_model');
		$this->load->helper('File_Upload');
	}

	public function index()
	{
		$this->response(APP_NAME . ' API / Cloud :: Controller');
	}

	// Listar registro(s)
	public function listar_carpeta_get($soloActivos = false)
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

			// Verificar si se especifica el ID de un registro en particular
			$idCarpeta = $this->security->xss_clean($this->uri->segment(2));
			$idCarpeta = !empty($idCarpeta) && intval($idCarpeta) ? intval($idCarpeta) : 0;

			// Obtener registro(s)
			$response['data'] = $this->Cloud_model->listar_carpeta($idCondominio, $idCarpeta);

			$response['err'] = $response['data'] === false;
			$response['msg'] = !$response['err']
				? 'Información obtenida con éxito.'
				: 'Error al obtener información. Verifique información proporcionada.';
			$responseCode = !$response['err'] ? REST_Controller::HTTP_OK : $responseCode;
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}

	// Crear carpeta
	public function crear_carpeta_post()
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

			// Verificar si se especifica el ID de un registro en particular
			$idCarpetaPadre = $this->security->xss_clean($this->uri->segment(4));
			if (empty($idCarpetaPadre) || !intval($idCarpetaPadre) || intval($idCarpetaPadre) < 1) {
				$respuesta['msg'] = 'Debe especificar un identificador de carpeta padre válido.';
				$this->response($respuesta, $codigo_respuesta);
			}

			$data = $this->security->xss_clean($this->post());
			// Verificar que se haya enviado la información a insertar
			if (empty($data)) {
				$respuesta['msg'] = 'Información a procesar no ha sido proporcionada.';
				$this->response($respuesta, $codigo_respuesta);
			}

			$data = nulificar_elementos_arreglo(trim_elementos_arreglo($data));
			// $data = capitalizar_arreglo($data, ['carpeta_nombre']);

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('cloudCarpetaInsertar')) {
				// Error al validar la información
				$respuesta['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$respuesta['msg'] .= $value . PHP_EOL;
				}
				$this->response($respuesta, $codigo_respuesta);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'carpeta' => $data['nombre'],
				'fk_id_cloud_carpeta_padre' => $idCarpetaPadre,
				'fk_id_condominio' => $idCondominio,
				'fk_id_usuario_registro' => $idUsuarioRegistro,
			];

			$respuesta = $this->Cloud_model->crear_carpeta($data);
			if (!$respuesta['err']) {
				$codigo_respuesta = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$respuesta['msg'] = $e->getMessage();
		}
		$this->response($respuesta, $codigo_respuesta);
	}

	// Renombrar carpeta
	public function renombrar_carpeta_post()
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
			$idCondominio = $token->data->id_condominio_usuario;

			// Verificar si se especifica el ID de un registro en particular
			$idCarpeta = $this->security->xss_clean($this->uri->segment(4));
			if (empty($idCarpeta) || !intval($idCarpeta) || intval($idCarpeta) < 1) {
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
			// $data = capitalizar_arreglo($data, ['carpeta_nombre']);

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('cloudCarpetaInsertar')) {
				// Error al validar la información
				$respuesta['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$respuesta['msg'] .= $value . PHP_EOL;
				}
				$this->response($respuesta, $codigo_respuesta);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'id_condominio' => $idCondominio,
				'id_carpeta' => $idCarpeta,
				'carpeta' => $data['nombre'],
				'fk_id_usuario_modifico' => $idUsuarioModifico,
			];

			$respuesta = $this->Cloud_model->renombrar_carpeta($data);
			if (!$respuesta['err']) {
				$codigo_respuesta = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$respuesta['msg'] = $e->getMessage();
		}
		$this->response($respuesta, $codigo_respuesta);
	}

	// Alternar estatus carpeta
	public function alternar_estatus_carpeta_post($estatus = null)
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
			$idCondominio = $token->data->id_condominio_usuario;

			// Verificar si se especifica el ID de un registro en particular
			$idCarpeta = $this->security->xss_clean($this->uri->segment(4));
			if (empty($idCarpeta) || !intval($idCarpeta) || intval($idCarpeta) < 1) {
				$respuesta['msg'] = 'Debe especificar un identificador válido.';
				$this->response($respuesta, $codigo_respuesta);
			}

			// Información validada con éxito. Procede a la actualización del estatus
			$data = [
				'id_condominio' => $idCondominio,
				'id_carpeta' => $idCarpeta,
				'estatus' => $estatus,
				'fk_id_usuario_modifico' => $idUsuarioModifico,
			];

			$respuesta = $this->Cloud_model->alternar_estatus_carpeta($data);
			if (!$respuesta['err']) {
				$codigo_respuesta = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$respuesta['msg'] = $e->getMessage();
		}
		$this->response($respuesta, $codigo_respuesta);
	}

	// Subir archivo
	public function subir_archivo_post()
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

			// Verificar si se especifica el ID de la carpeta
			$idCarpeta = $this->security->xss_clean($this->uri->segment(4));
			if (empty($idCarpeta) || !intval($idCarpeta) || intval($idCarpeta) < 1) {
				$respuesta['msg'] = 'Debe especificar un identificador válido.';
				$this->response($respuesta, $codigo_respuesta);
			}

			if (empty($_FILES['archivo'])) {
				$respuesta['msg'] = 'No se especificó el archivo a subir.';
				$this->response($respuesta, $codigo_respuesta);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'id_condominio' => $idCondominio,
				'id_carpeta' => $idCarpeta,
				'archivo' => $_FILES['archivo'],
				'id_usuario' => $idUsuarioRegistro,
			];

			$respuesta = $this->Cloud_model->subir_archivo($data);
			if (!$respuesta['err']) {
				$codigo_respuesta = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$respuesta['msg'] = $e->getMessage();
		}
		$this->response($respuesta, $codigo_respuesta);
	}

	// Renombrar archivo
	public function renombrar_archivo_post()
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
			$idCondominio = $token->data->id_condominio_usuario;

			// Verificar si se especifica el ID de un registro en particular
			$idArchivo = $this->security->xss_clean($this->uri->segment(4));
			if (empty($idArchivo) || !intval($idArchivo) || intval($idArchivo) < 1) {
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
			// $data = capitalizar_arreglo($data, ['carpeta_nombre']);

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('cloudArchivoRenombrar')) {
				// Error al validar la información
				$respuesta['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$respuesta['msg'] .= $value . PHP_EOL;
				}
				$this->response($respuesta, $codigo_respuesta);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'id_condominio' => $idCondominio,
				'id_archivo' => $idArchivo,
				'archivo' => $data['nombre'],
				'fk_id_usuario_modifico' => $idUsuarioModifico,
			];

			$respuesta = $this->Cloud_model->renombrar_archivo($data);
			if (!$respuesta['err']) {
				$codigo_respuesta = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$respuesta['msg'] = $e->getMessage();
		}
		$this->response($respuesta, $codigo_respuesta);
	}

	// Alternar estatus archivo
	public function alternar_estatus_archivo_post($estatus = null)
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
			$idCondominio = $token->data->id_condominio_usuario;

			// Verificar si se especifica el ID de un registro en particular
			$idArchivo = $this->security->xss_clean($this->uri->segment(4));
			if (empty($idArchivo) || !intval($idArchivo) || intval($idArchivo) < 1) {
				$respuesta['msg'] = 'Debe especificar un identificador válido.';
				$this->response($respuesta, $codigo_respuesta);
			}

			// Información validada con éxito. Procede a la actualización del estatus
			$data = [
				'id_condominio' => $idCondominio,
				'id_archivo' => $idArchivo,
				'estatus' => $estatus,
				'fk_id_usuario_modifico' => $idUsuarioModifico,
			];

			$respuesta = $this->Cloud_model->alternar_estatus_archivo($data);
			if (!$respuesta['err']) {
				$codigo_respuesta = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$respuesta['msg'] = $e->getMessage();
		}
		$this->response($respuesta, $codigo_respuesta);
	}
}

/* End of file Cloud.php */
/* Location: ./application/controllers/Cloud.php */
