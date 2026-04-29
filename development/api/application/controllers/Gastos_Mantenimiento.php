<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Controlador: Gastos_Mantenimiento
 *
 * Este controlador realiza las operaciones relacionadas con los Gastos de Mantenimiento
 *
 * @package   CodeIgniter
 * @category  Controller REST
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Gastos_Mantenimiento extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Gasto_Mantenimiento_model');
	}

	public function index()
	{
		$this->response(APP_NAME . ' API / Gastos de Mantenimiento :: Controller');
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

			// Verificar si se especifica el ID de un registro en particular
			$idGastoMantenimiento = $this->security->xss_clean($this->uri->segment(2));
			$idGastoMantenimiento =
				!empty($idGastoMantenimiento) && intval($idGastoMantenimiento) ? intval($idGastoMantenimiento) : 0;

			// Obtener registro(s)
			$result = $this->Gasto_Mantenimiento_model->listar($idGastoMantenimiento, $idCondominio, $soloActivos);
			if (!empty($idGastoMantenimiento)) {
				$response['gasto_mantenimiento'] = $result;
			} else {
				$response['gastos_mantenimiento'] = $result;
			}

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
			$idCondominio = $token->data->id_condominio_usuario;
			$idUsuarioRegistro = $token->data->id;

			$data = $this->security->xss_clean($this->post());
			// Verificar que se haya enviado la información a insertar
			if (empty($data)) {
				$respuesta['msg'] = 'Información a procesar no ha sido proporcionada.';
				$this->response($respuesta, $responseCode);
			}

			$data = nulificar_elementos_arreglo(trim_elementos_arreglo($data));
			$data = capitalizar_arreglo($data, ['concepto', 'descripcion']);

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('gastoMantenimientoInsertar')) {
				// Error al validar la información
				$respuesta['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$respuesta['msg'] .= $value . PHP_EOL;
				}
				$this->response($respuesta, $responseCode);
			}

			if (intval($data['id_gasto_fijo']) < 1 && empty($data['concepto'])) {
				$respuesta['msg'] = 'Se debe especificar concepto si no se indica Gasto fijo.';
				$this->response($respuesta, $responseCode);
			}

			// Validar la información del movimiento al fondo monetario
			$dataMovimiento = [
				'fk_id_fondo_monetario' => !empty($data['id_fondo_monetario']) ? $data['id_fondo_monetario'] : null,
				'fk_id_tipo_movimiento' => 2,
				'fecha' => $data['fecha'],
				'importe' => floatval($data['importe']),
				'es_externo' => 1,
				'fk_id_usuario_registro' => $idUsuarioRegistro,
			];

			$this->form_validation->reset_validation();
			$this->form_validation->set_data($dataMovimiento);
			if (!$this->form_validation->run('fondoMonetarioRegistrarMovtoExterno')) {
				// Error al validar la información
				$respuesta['msg'] = 'Existen errores en la información proporcionada. (Fondo)' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$respuesta['msg'] .= $value . PHP_EOL;
				}
				$this->response($respuesta, $responseCode);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'fk_id_condominio' => $idCondominio,
				'fk_id_gasto_fijo' => intval($data['id_gasto_fijo']) > 0 ? $data['id_gasto_fijo'] : null,
				'concepto' => intval($data['id_gasto_fijo']) < 1 ? $data['concepto'] : null,
				'descripcion' => !empty($data['descripcion']) ? $data['descripcion'] : null,
				'importe' => $data['importe'],
				'fecha' => $data['fecha'],
				'es_deducible' => $data['es_deducible'],
				'archivo_comprobante' =>
					!empty($_FILES) && !empty($_FILES['archivo_comprobante']) ? 'archivo_comprobante' : null,
				'fk_id_usuario_registro' => $idUsuarioRegistro,
				'movimiento' => $dataMovimiento,
			];

			$respuesta = $this->Gasto_Mantenimiento_model->insertar($data);
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
			$idGastoMantenimiento = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idGastoMantenimiento) || !intval($idGastoMantenimiento) || intval($idGastoMantenimiento) < 1) {
				$respuesta['msg'] = 'Debe especificar un identificador válido.';
				$this->response($respuesta, $responseCode);
			}

			$data = $this->security->xss_clean($this->post());
			// Verificar que se haya enviado la información a insertar
			if (empty($data)) {
				$respuesta['msg'] = 'Información a procesar no ha sido proporcionada.';
				$this->response($respuesta, $responseCode);
			}

			$data = nulificar_elementos_arreglo(trim_elementos_arreglo($data));
			$data = capitalizar_arreglo($data, ['concepto', 'descripcion']);

			// Quitar validacion de campos del destinatario a la regla documentosInsertarBasico
			$validationRules = array_filter($this->form_validation->get_reglas()['gastoMantenimientoInsertar'], function (
				$r
			) {
				return $r['field'] != 'importe' && $r['field'] != 'fecha';
			});

			// Validar la información
			$this->form_validation->set_data($data);
			$this->form_validation->set_rules($validationRules);
			if (!$this->form_validation->run()) {
				// Error al validar la información
				$respuesta['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$respuesta['msg'] .= $value . PHP_EOL;
				}
				$this->response($respuesta, $responseCode);
			}

			if (intval($data['id_gasto_fijo']) < 1 && empty($data['concepto'])) {
				$respuesta['msg'] = 'Se debe especificar concepto si no se indica Gasto fijo.';
				$this->response($respuesta, $responseCode);
			}

			// Información validada con éxito. Procede a la actualización
			$data = [
				'id_gasto_mantenimiento' => $idGastoMantenimiento,
				'fk_id_gasto_fijo' => intval($data['id_gasto_fijo']) > 0 ? $data['id_gasto_fijo'] : null,
				'concepto' => intval($data['id_gasto_fijo']) < 1 ? $data['concepto'] : null,
				'descripcion' => !empty($data['descripcion']) ? $data['descripcion'] : null,
				/* 'importe' => $data['importe'],
				 'fecha' => $data['fecha'], */
				'es_deducible' => $data['es_deducible'],
				'archivo_comprobante' =>
					!empty($_FILES) && !empty($_FILES['archivo_comprobante']) ? 'archivo_comprobante' : null,
				'borrar_comprobante' => !empty($data['borrar_comprobante']) ? $data['borrar_comprobante'] == 1 : false,
				'fk_id_usuario_modifico' => $idUsuarioModifico,
			];

			$respuesta = $this->Gasto_Mantenimiento_model->actualizar($data);
			if (!$respuesta['err']) {
				$responseCode = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$respuesta['msg'] = $e->getMessage();
		}
		$this->response($respuesta, $responseCode);
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
			$idGastoMantenimiento = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idGastoMantenimiento) || !intval($idGastoMantenimiento) || intval($idGastoMantenimiento) < 1) {
				$response['msg'] = 'Debe especificar un identificador válido.';
				$this->response($response, $responseCode);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'id_gasto_mantenimiento' => $idGastoMantenimiento,
				'id_usuario_modifico' => $idUsuarioModifico,
			];

			// Información validada con éxito. Procede a la inserción
			$response = $this->Gasto_Mantenimiento_model->eliminar($data);
			if (!$response['err']) {
				$responseCode = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}
}

/* End of file Gastos_Mantenimiento.php */
/* Location: ./application/controllers/Gastos_Mantenimiento.php */
