<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Controlador: Usuarios_Propietarios
 *
 * Este controlador realiza las operaciones relacionadas con los Usuarios Propietarios
 *
 * @package   CodeIgniter
 * @category  Controller REST
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Usuarios_Propietarios extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Usuario_model');
		$this->load->model('Usuario_Propietario_model');
	}

	public function index()
	{
		$this->response(APP_NAME . ' API / Usuarios Propietarios :: Controller');
	}

	// Listar registro por ID
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

			// Obtener registro(s)
			$idUsuario = $this->security->xss_clean($this->uri->segment(2));
			if (empty($idUsuario) || !intval($idUsuario) || intval($idUsuario) < 1) {
				$response['propietarios'] = $this->Usuario_model->listar_perfil(
					PERFIL_USUARIO_PROPIETARIO,
					$idCondominio,
					$soloActivos
				);
			} else {
				$response['propietario'] = $this->Usuario_Propietario_model->listar($idUsuario, $idCondominio);
			}

			$response['error'] = false;
			$response['msg'] =
				!empty($response['propietario']) || !empty($response['propietarios'])
					? 'Información obtenida con éxito.'
					: 'No se encontraron registros.';
			$responseCode = REST_Controller::HTTP_OK;
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}

	// Insertar registro
	public function insertar_post()
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
			$idUsuarioRegistro = $token->data->id;
			$idCondominio = $token->data->id_condominio_usuario;

			// Verificar que se haya enviado la información a insertar
			$data = $this->security->xss_clean($this->post());
			if (empty($data)) {
				$response['msg'] = 'No se proporcionó información a procesar.';
				$this->response($response, $responseCode);
			}

			// Normalizar la información a almacenar
			$data = nulificar_elementos_arreglo(trim_elementos_arreglo($data));
			$data = capitalizar_arreglo($data, ['nombre', 'domicilio', 'identificacion_folio', 'identificacion_domicilio']);
			$data = minusculas_arreglo($data, ['usuario', 'email']);
			$dataUnidades = !empty($data['unidades']) ? json_decode($data['unidades'], true) : [];
			unset($data['unidades']);

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('usuarioInsertar')) {
				// Error al validar la información
				$response['msg'] = 'Existen errores en la información proporcionada (Usuario).' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$response['msg'] .= $value . PHP_EOL;
				}
				$this->response($response, $responseCode);
			}

			// Validar la información unidades del propietario
			if (!empty($dataUnidades)) {
				foreach ($dataUnidades as &$unidad) {
					$unidad = (array) $unidad;
					$this->form_validation->reset_validation();
					$this->form_validation->set_data($unidad);
					if (!$this->form_validation->run('propietarioInsertarUnidades')) {
						// Error al validar la información
						$response['msg'] = 'Existen errores en la información proporcionada (Unidades).' . PHP_EOL;
						foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
							$response['msg'] .= $value . PHP_EOL;
						}
						$this->response($response, $responseCode);
					}
				}
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'usuario' => $data['usuario'],
				'contrasenia' => !empty($data['contrasenia']) ? $data['contrasenia'] : $data['usuario'],
				'nombre' => $data['nombre'],
				'email' => $data['email'],
				'telefono' => $data['telefono'],
				'domicilio' => !empty($data['domicilio']) ? $data['domicilio'] : null,
				'identificacion_folio' => !empty($data['identificacion_folio']) ? $data['identificacion_folio'] : null,
				'identificacion_domicilio' => !empty($data['identificacion_domicilio'])
					? $data['identificacion_domicilio']
					: null,
				'archivo_identificacion_anverso' =>
					!empty($_FILES) && !empty($_FILES['archivo_identificacion_anverso'])
						? 'archivo_identificacion_anverso'
						: null,
				'archivo_identificacion_reverso' =>
					!empty($_FILES) && !empty($_FILES['archivo_identificacion_reverso'])
						? 'archivo_identificacion_reverso'
						: null,
				'archivo_imagen' => !empty($_FILES) && !empty($_FILES['archivo_imagen']) ? 'archivo_imagen' : null,
				'unidades' => cambiar_keys_arreglo($dataUnidades, ['id_unidad' => 'fk_id_unidad']),
				'fk_id_condominio' => $idCondominio,
				'fk_id_usuario_registro' => $idUsuarioRegistro,
			];

			$response = $this->Usuario_Propietario_model->insertar($data);

			if (!$response['error']) {
				$responseCode = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}

	// Actualizar usuario
	public function actualizar_post()
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
			$idCondominio = $token->data->id_condominio_usuario;

			// Verificar que se especique el ID del registro a actualizar
			$idUsuario = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idUsuario) || !intval($idUsuario) || intval($idUsuario) < 1) {
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
			$data = capitalizar_arreglo($data, ['nombre', 'domicilio', 'identificacion_folio', 'identificacion_domicilio']);
			$data = minusculas_arreglo($data, ['email']);
			$dataUnidades = !empty($data['unidades']) ? json_decode($data['unidades'], true) : [];
			unset($data['unidades']);

			/* $response['data'] = $dataUnidades;
			 $this->response($response, $responseCode); */

			/*
        Aplicar validación usuarioInsertar eliminando la regla relacionada con el usuario:
          - usuarioInsertar (Insertar usuario)
      */
			$validationRules = $this->form_validation->get_reglas()['usuarioInsertar'];
			$validationRules = array_values(
				array_filter($validationRules, function ($r) {
					return $r['field'] != 'usuario';
				})
			);
			$this->form_validation->set_data($data);
			$this->form_validation->set_rules($validationRules);
			if (!$this->form_validation->run()) {
				// Error al validar la información
				$response['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$response['msg'] .= $value . PHP_EOL;
				}
				$this->response($response, $responseCode);
			}

			// Validar la información unidades del propietario
			if (!empty($dataUnidades)) {
				foreach ($dataUnidades as &$unidad) {
					$unidad = (array) $unidad;
					$this->form_validation->reset_validation();
					$this->form_validation->set_data($unidad);
					if (!$this->form_validation->run('propietarioInsertarUnidades')) {
						// Error al validar la información
						$response['msg'] = 'Existen errores en la información proporcionada (Unidades).' . PHP_EOL;
						foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
							$response['msg'] .= $value . PHP_EOL;
						}
						$this->response($response, $responseCode);
					}
				}
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'id_condominio' => $idCondominio,
				'id_usuario' => $idUsuario,
				'nombre' => $data['nombre'],
				'email' => $data['email'],
				'telefono' => $data['telefono'],
				'domicilio' => !empty($data['domicilio']) ? $data['domicilio'] : null,
				'identificacion_folio' => !empty($data['identificacion_folio']) ? $data['identificacion_folio'] : null,
				'identificacion_domicilio' => !empty($data['identificacion_domicilio'])
					? $data['identificacion_domicilio']
					: null,
				'archivo_imagen' => !empty($_FILES) && !empty($_FILES['archivo_imagen']) ? 'archivo_imagen' : null,
				'borrar_imagen' => !empty($data['borrar_imagen']) ? $data['borrar_imagen'] == 1 : false,
				'archivo_identificacion_anverso' =>
					!empty($_FILES) && !empty($_FILES['archivo_identificacion_anverso'])
						? 'archivo_identificacion_anverso'
						: null,
				'borrar_identificacion_anverso' => !empty($data['borrar_identificacion_anverso'])
					? $data['borrar_identificacion_anverso'] == 1
					: false,
				'archivo_identificacion_reverso' =>
					!empty($_FILES) && !empty($_FILES['archivo_identificacion_reverso'])
						? 'archivo_identificacion_reverso'
						: null,
				'borrar_identificacion_reverso' => !empty($data['borrar_identificacion_reverso'])
					? $data['borrar_identificacion_reverso'] == 1
					: false,
				'unidades' => cambiar_keys_arreglo($dataUnidades, ['id_unidad' => 'fk_id_unidad']),
				'fk_id_usuario_modifico' => $idUsuarioModifico,
			];

			// Información validada con éxito. Procede a la inserción
			$response = $this->Usuario_Propietario_model->actualizar($data);
			if (!$response['error']) {
				$responseCode = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}

	// Eliminar lógicamente usuario
	/* public function deshabilitar_post()
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
			$idCondominio = $token->data->id_condominio_usuario;

			// Verificar que se especique el ID del registro a actualizar
			$idUsuario = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idUsuario) || !intval($idUsuario) || intval($idUsuario) < 1) {
				$response['msg'] = 'Debe especificar un identificador válido.';
				$this->response($response, $responseCode);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'id_condominio' => $idCondominio,
				'id_usuario' => $idUsuario,
				'id_usuario_modifico' => $idUsuarioModifico,
			];

			// Información validada con éxito. Procede a la inserción
			$response = $this->Usuario_Propietario_model->deshabilitar($data);
			if (!$response['error']) {
				$responseCode = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	} */
}

/* End of file Usuarios_Propietarios.php */
/* Location: ./application/controllers/Usuarios_Propietarios.php */
