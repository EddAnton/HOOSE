<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Controlador: Tableros_Avisos
 *
 * Este controlador realiza las operaciones relacionadas con los Tablero de avisos
 *
 * @package   CodeIgniter
 * @category  Controller REST
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Tableros_Avisos extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Tablero_Avisos_model');
	}

	public function index()
	{
		$this->response(APP_NAME . ' API / Tablero avisos :: Controller');
	}

	// Listar registro(s)
	public function listar_get($soloActivos = false, $soloPublicados = false)
	{
		$response = [
			'error' => true,
			'msg' => null,
		];
		$codigo_respuesta = REST_Controller::HTTP_BAD_REQUEST;

		try {
			// Validar token
			$token = getToken();
			if ($token->error) {
				$this->response($token, $codigo_respuesta);
			}
			$idCondominio = $token->data->id_condominio_usuario;

			// Verificar si se especifica el ID de un registro en particular
			$idAviso = $this->security->xss_clean($this->uri->segment(2));
			$idAviso = !empty($idAviso) && intval($idAviso) ? intval($idAviso) : 0;
			// Verificar si se especifica el ID del perfil de usuario destino
			$idPerfilUsuarioDestino = $this->security->xss_clean($this->uri->segment(3));
			$idPerfilUsuarioDestino =
				!empty($idPerfilUsuarioDestino) && intval($idPerfilUsuarioDestino) ? intval($idPerfilUsuarioDestino) : 0;

			if (empty($idAviso) && empty($idPerfilUsuarioDestino)) {
				$respuesta['msg'] = 'Debe especificar un identificador válido.';
				$this->response($respuesta, $codigo_respuesta);
			}

			// Obtener registro(s)
			$result = $this->Tablero_Avisos_model->listar(
				$idAviso,
				$idCondominio,
				$idPerfilUsuarioDestino,
				$soloActivos,
				$soloPublicados
			);
			if (!empty($idAviso)) {
				$response['aviso'] = $result;
			} else {
				$response['avisos'] = $result;
			}

			$response['error'] = $result === false;
			$response['msg'] = !$response['error']
				? 'Información obtenida con éxito.'
				: 'Error al obtener información. Verifique información proporcionada.';
			$codigo_respuesta = !$response['error'] ? REST_Controller::HTTP_OK : $codigo_respuesta;
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $codigo_respuesta);
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
				$this->response($token, $codigo_respuesta);
			}
			$idCondominio = $token->data->id_condominio_usuario;
			$idUsuarioRegistro = $token->data->id;

			$data = $this->post();
			// Verificar que se haya enviado la información a insertar
			if (empty($data)) {
				$respuesta['msg'] = 'Información a procesar no ha sido proporcionada.';
				$this->response($respuesta, $codigo_respuesta);
			}

			// Si se incluye descripcion, extraerlo de la data y aplicar filtro de seguridad a la misma
			$descripcion = !empty($data['descripcion']) ? $data['descripcion'] : null;
			unset($data['descripcion']);

			$data = $this->security->xss_clean($data);
			// Verificar que se haya enviado la información a insertar
			if (empty($data)) {
				$respuesta['msg'] = 'Información a procesar no ha sido proporcionada.';
				$this->response($respuesta, $codigo_respuesta);
			}

			$data = nulificar_elementos_arreglo(trim_elementos_arreglo($data));
			$data = capitalizar_arreglo($data, ['titulo']);
			$data['descripcion'] = $descripcion;

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('avisoInsertar')) {
				// Error al validar la información
				$respuesta['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$respuesta['msg'] .= $value . PHP_EOL;
				}
				$this->response($respuesta, $codigo_respuesta);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'titulo' => $data['titulo'],
				'descripcion' => $data['descripcion'],
				'fk_id_condominio' => $idCondominio,
				'fk_id_perfil_usuario_destino' => $data['id_perfil_usuario_destino'],
				'publicado' => $data['publicado'],
				'fk_id_usuario_registro' => $idUsuarioRegistro,
			];

			$respuesta = $this->Tablero_Avisos_model->insertar($data);
			if (!$respuesta['err']) {
				unset($respuesta['data'][0]['descripcion']);
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
				$this->response($token, $codigo_respuesta);
			}
			$idUsuarioModifico = $token->data->id;

			// Verificar si se especifica el ID de un registro en particular
			$idAviso = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idAviso) || !intval($idAviso) || intval($idAviso) < 1) {
				$respuesta['msg'] = 'Debe especificar un identificador válido.';
				$this->response($respuesta, $codigo_respuesta);
			}

			$data = $this->post();
			// Verificar que se haya enviado la información a insertar
			if (empty($data)) {
				$respuesta['msg'] = 'Información a procesar no ha sido proporcionada.';
				$this->response($respuesta, $codigo_respuesta);
			}

			// Si se incluye descripcion, extraerlo de la data y aplicar filtro de seguridad a la misma
			$descripcion = !empty($data['descripcion']) ? $data['descripcion'] : null;
			unset($data['descripcion']);

			$data = $this->security->xss_clean($data);
			// Verificar que se haya enviado la información a insertar
			if (empty($data)) {
				$respuesta['msg'] = 'Información a procesar no ha sido proporcionada.';
				$this->response($respuesta, $codigo_respuesta);
			}

			$data = nulificar_elementos_arreglo(trim_elementos_arreglo($data));
			$data = capitalizar_arreglo($data, ['titulo']);
			$data['descripcion'] = $descripcion;

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('avisoInsertar')) {
				// Error al validar la información
				$respuesta['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$respuesta['msg'] .= $value . PHP_EOL;
				}
				$this->response($respuesta, $codigo_respuesta);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'id_aviso' => $idAviso,
				'titulo' => $data['titulo'],
				'descripcion' => $data['descripcion'],
				'fk_id_perfil_usuario_destino' => $data['id_perfil_usuario_destino'],
				'publicado' => $data['publicado'],
				'fk_id_usuario_modifico' => $idUsuarioModifico,
			];

			$respuesta = $this->Tablero_Avisos_model->actualizar($data);
			if (!$respuesta['err']) {
				unset($respuesta['data'][0]['descripcion']);
				$codigo_respuesta = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$respuesta['msg'] = $e->getMessage();
		}
		$this->response($respuesta, $codigo_respuesta);
	}

	// Alternar el estatus de la publicación del aviso
	public function alternar_estatus_publicado_post()
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
			$idAviso = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idAviso) || !intval($idAviso) || intval($idAviso) < 1) {
				$response['msg'] = 'Debe especificar un identificador válido.';
				$this->response($response, $responseCode);
			}

			$data = [
				'id_aviso' => $idAviso,
				'fk_id_usuario_modifico' => $idUsuarioModifico,
			];

			// Actualización del estatus
			$response = $this->Tablero_Avisos_model->alternar_estatus_publicado($data);
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
			'error' => true,
			'msg' => null,
		];
		$codigo_respuesta = REST_Controller::HTTP_BAD_REQUEST;

		try {
			// Validar token
			$token = getToken();
			if ($token->error) {
				$this->response($token, $codigo_respuesta);
			}
			$idUsuarioModifico = $token->data->id;

			// Verificar que se especique el ID del registro a actualizar
			$idAviso = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idAviso) || !intval($idAviso) || intval($idAviso) < 1) {
				$response['msg'] = 'Debe especificar un identificador válido.';
				$this->response($response, $codigo_respuesta);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'id_aviso' => $idAviso,
				'id_usuario_modifico' => $idUsuarioModifico,
			];

			// Información validada con éxito. Procede a la inserción
			$response = $this->Tablero_Avisos_model->eliminar($data);
			if (!$response['error']) {
				$codigo_respuesta = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $codigo_respuesta);
	}
}

/* End of file Tableros_Avisos.php */
/* Location: ./application/controllers/Tableros_Avisos.php */
