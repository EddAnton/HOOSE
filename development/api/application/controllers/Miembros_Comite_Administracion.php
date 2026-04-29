<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Controlador: Miembros_Comite_Administracion
 *
 * Este controlador realiza las operaciones relacionadas con los Miembros del Comité de Administración
 *
 * @package   CodeIgniter
 * @category  Controller REST
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Miembros_Comite_Administracion extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Miembro_Comite_Administracion_model');
	}

	public function index()
	{
		$this->response(APP_NAME . ' API / Miembros Comité Administración :: Controller');
	}

	// Listar registros o registro
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
			$idMiembro = $this->security->xss_clean($this->uri->segment(2));
			$idMiembro = !empty($idMiembro) && intval($idMiembro) ? intval($idMiembro) : 0;

			// Obtener registro(s)
			$result = $this->Miembro_Comite_Administracion_model->listar($idMiembro, $idCondominio, $soloActivos);
			if (!empty($idMiembro)) {
				$response['miembro'] = $result;
			} else {
				$response['miembros'] = $result;
			}

			$response['error'] = false;
			$response['msg'] = !empty($result) ? 'Información obtenida con éxito.' : 'No se encontraron registros.';
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

			/*
        Aplicar validación de la data combinando las reglas:
          - usuarioInsertar (Insertar usuario)
          - colaboradorInsertar (Insertar colaborador)
      */
			/* $validationRules = array_merge(
				$this->form_validation->get_reglas()['usuarioInsertar'],
				$this->form_validation->get_reglas()['colaboradorInsertar']
			);
			// Eliminar la reglas que no aplican
			$validationRules = array_values(
				array_filter($validationRules, function ($r) {
					return $r['field'] != 'usuario' && $r['field'] != 'contrasenia' && $r['field'] != 'salario';
				})
			); */

			$this->form_validation->set_data($data);
			// $this->form_validation->set_rules($validationRules);
			if (!$this->form_validation->run('miembroComiteAdmonInsertar')) {
				// Error al validar la información
				$response['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$response['msg'] .= $value . PHP_EOL;
				}
				$this->response($response, $responseCode);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'nombre' => $data['nombre'],
				'email' => $data['email'],
				'telefono' => $data['telefono'],
				'domicilio' => !empty($data['domicilio']) ? $data['domicilio'] : null,
				'identificacion_folio' => !empty($data['identificacion_folio']) ? $data['identificacion_folio'] : null,
				'identificacion_domicilio' => !empty($data['identificacion_domicilio'])
					? $data['identificacion_domicilio']
					: null,
				'archivo_imagen' => !empty($_FILES) && !empty($_FILES['archivo_imagen']) ? 'archivo_imagen' : null,
				'archivo_identificacion_anverso' =>
					!empty($_FILES) && !empty($_FILES['archivo_identificacion_anverso'])
						? 'archivo_identificacion_anverso'
						: null,
				'archivo_identificacion_reverso' =>
					!empty($_FILES) && !empty($_FILES['archivo_identificacion_reverso'])
						? 'archivo_identificacion_reverso'
						: null,
				'fk_id_condominio' => $idCondominio,
				'fk_id_tipo_miembro' => $data['id_tipo_miembro'],
				'fecha_inicio' => $data['fecha_inicio'],
				'fk_id_usuario_registro' => $idUsuarioRegistro,
			];

			$response = $this->Miembro_Comite_Administracion_model->insertar($data);

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
			$idMiembro = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idMiembro) || !intval($idMiembro) || intval($idMiembro) < 1) {
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

			/*
        Aplicar validación de la data combinando las reglas:
          - usuarioInsertar (Insertar usuario)
          - colaboradorInsertar (Insertar colaborador)
      */
			/* $validationRules = array_merge(
				$this->form_validation->get_reglas()['usuarioInsertar'],
				$this->form_validation->get_reglas()['colaboradorInsertar']
			);
			// Eliminar la regla que aplica al campo usuario
			$validationRules = array_values(
				array_filter($validationRules, function ($r) {
					return $r['field'] != 'usuario';
				})
			); */
			$this->form_validation->set_data($data);
			// $this->form_validation->set_rules($validationRules);
			if (!$this->form_validation->run('miembroComiteAdmonInsertar')) {
				// Error al validar la información
				$response['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$response['msg'] .= $value . PHP_EOL;
				}
				$this->response($response, $responseCode);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				// 'id_condominio' => $idCondominio,
				'id_miembro' => $idMiembro,
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
				'fk_id_tipo_miembro' => $data['id_tipo_miembro'],
				'fecha_inicio' => $data['fecha_inicio'],
				'fk_id_usuario_modifico' => $idUsuarioModifico,
			];

			// Información validada con éxito. Procede a la inserción
			$response = $this->Miembro_Comite_Administracion_model->actualizar($data);
			if (!$response['error']) {
				$responseCode = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}

	// Alternar estatus de activo del usuario
	public function alternar_estatus_post()
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
			$idMiembro = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idMiembro) || !intval($idMiembro) || intval($idMiembro) < 1) {
				$response['msg'] = 'Debe especificar un identificador válido.';
				$this->response($response, $responseCode);
			}

			$data = [
				'id_miembro' => $idMiembro,
				'fk_id_usuario_modifico' => $idUsuarioModifico,
			];

			// Actualización del estatus
			$response = $this->Miembro_Comite_Administracion_model->alternar_estatus($data);
			if (!$response['error']) {
				$responseCode = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}
}

/* End of file Miembros_Comite_Administracion.php */
/* Location: ./application/controllers/Miembros_Comite_Administracion.php */
