<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Controlador: Proyectos
 *
 * Este controlador realiza las operaciones relacionadas con los Proyectos
 *
 * @package   CodeIgniter
 * @category  Controller REST
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Proyectos extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Proyecto_model');
	}

	public function index()
	{
		$this->response(APP_NAME . ' API / Proyectos :: Controller');
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
			$idProyecto = $this->security->xss_clean($this->uri->segment(2));
			$idProyecto = !empty($idProyecto) && intval($idProyecto) ? intval($idProyecto) : 0;

			// Obtener registro(s)
			$result = $this->Proyecto_model->listar($idProyecto, $idCondominio, $soloActivos);
			if (!empty($idProyecto)) {
				$response['proyecto'] = $result;
			} else {
				$response['proyectos'] = $result;
			}

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
			$idCondominio = $token->data->id_condominio_usuario;
			$idUsuarioRegistro = $token->data->id;

			$data = $this->security->xss_clean($this->post());
			// Verificar que se haya enviado la información a insertar
			if (empty($data)) {
				$respuesta['msg'] = 'Información a procesar no ha sido proporcionada.';
				$this->response($respuesta, $codigo_respuesta);
			}

			$data = nulificar_elementos_arreglo(trim_elementos_arreglo($data));
			$data = capitalizar_arreglo($data, ['titulo', 'descripcion']);

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('proyectoInsertar')) {
				// Error al validar la información
				$respuesta['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$respuesta['msg'] .= $value . PHP_EOL;
				}
				$this->response($respuesta, $codigo_respuesta);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'fk_id_condominio' => $idCondominio,
				'titulo' => $data['titulo'],
				'descripcion' => !empty($data['descripcion']) ? $data['descripcion'] : null,
				'presupuesto' => $data['presupuesto'],
				'fecha_inicio' => $data['fecha_inicio'],
				'fecha_fin' => !empty($data['fecha_fin']) ? $data['fecha_fin'] : null,
				'porcentaje_avance' => !empty($data['porcentaje_avance']) ? $data['porcentaje_avance'] : 0,
				'archivos_imagenes' => !empty($_FILES) && !empty($_FILES['archivos_imagenes']) ? 'archivos_imagenes' : null,
				'fk_id_usuario_registro' => $idUsuarioRegistro,
			];

			$respuesta = $this->Proyecto_model->insertar($data);
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
			$idProyecto = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idProyecto) || !intval($idProyecto) || intval($idProyecto) < 1) {
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
			$data = capitalizar_arreglo($data, ['titulo', 'descripcion']);

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('proyectoInsertar')) {
				// Error al validar la información
				$respuesta['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$respuesta['msg'] .= $value . PHP_EOL;
				}
				$this->response($respuesta, $codigo_respuesta);
			}

			// print_r($data);
			// print_r($_FILES);
			// print_r($_FILES['archivos_imagenes']);
			// exit();

			// Información validada con éxito. Procede a la actualización
			$data = [
				'id_proyecto' => $idProyecto,
				'titulo' => $data['titulo'],
				'descripcion' => !empty($data['descripcion']) ? $data['descripcion'] : null,
				'presupuesto' => $data['presupuesto'],
				'fecha_inicio' => $data['fecha_inicio'],
				'fecha_fin' => !empty($data['fecha_fin']) ? $data['fecha_fin'] : null,
				'porcentaje_avance' => !empty($data['porcentaje_avance']) ? $data['porcentaje_avance'] : 0,
				'archivos_imagenes' => !empty($_FILES) && !empty($_FILES['archivos_imagenes']) ? 'archivos_imagenes' : null,
				'imagenes_borrar' => !empty($data['imagenes_borrar']) ? explode(',', $data['imagenes_borrar']) : null,
				'fk_id_usuario_modifico' => $idUsuarioModifico,
			];

			$respuesta = $this->Proyecto_model->actualizar($data);
			if (!$respuesta['err']) {
				$codigo_respuesta = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$respuesta['msg'] = $e->getMessage();
		}
		$this->response($respuesta, $codigo_respuesta);
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
			$idProyecto = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idProyecto) || !intval($idProyecto) || intval($idProyecto) < 1) {
				$response['msg'] = 'Debe especificar un identificador válido.';
				$this->response($response, $responseCode);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'id_proyecto' => $idProyecto,
				'id_usuario_modifico' => $idUsuarioModifico,
			];

			// Información validada con éxito. Procede a la inserción
			$response = $this->Proyecto_model->eliminar($data);
			if (!$response['err']) {
				$responseCode = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}
}

/* End of file Proyectos.php */
/* Location: ./application/controllers/Proyectos.php */
