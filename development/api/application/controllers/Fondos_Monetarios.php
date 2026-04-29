<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Controlador: Fondos_Monetarios
 *
 * Este controlador realiza las operaciones relacionadas con los Fondos monetarios
 *
 * @package   CodeIgniter
 * @category  Controller REST
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Fondos_Monetarios extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Fondo_Monetario_model');
	}

	public function index()
	{
		$this->response(APP_NAME . ' API / Fondos Monetarios :: Controller');
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
			$idFondoMonetario = $this->security->xss_clean($this->uri->segment(2));
			$idFondoMonetario = !empty($idFondoMonetario) && intval($idFondoMonetario) ? intval($idFondoMonetario) : 0;

			// Obtener registro(s)
			$result = $this->Fondo_Monetario_model->listar($idFondoMonetario, $idCondominio, $soloActivos);
			if (!empty($idFondoMonetario)) {
				$response['fondo_monetario'] = $result;
			} else {
				$response['fondos_monetarios'] = $result;
			}

			$response['err'] = false;
			$response['msg'] = 'Información obtenida con éxito.';
			$responseCode = REST_Controller::HTTP_OK;
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}

	// Listar registro(s)
	public function listar_movimientos_get()
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

			// Verificar si se especifica el ID de un registro en particular
			$idFondoMonetario = $this->security->xss_clean($this->uri->segment(3));
			// Determinar si se especifica el ID del movimiento
			$idMovimiento = $this->security->xss_clean($this->uri->segment(4));

			if (
				(empty($idFondoMonetario) || !intval($idFondoMonetario) || intval($idFondoMonetario) < 1) &&
				(empty($idMovimiento) || !intval($idMovimiento) || intval($idMovimiento) < 1)
			) {
				$respuesta['msg'] = 'Debe especificar un identificador válido.';
				$this->response($respuesta, $codigo_respuesta);
			}

			$idFondoMonetario =
				empty($idFondoMonetario) || !intval($idFondoMonetario) || intval($idFondoMonetario) < 1 ? 0 : $idFondoMonetario;
			$idMovimiento = empty($idMovimiento) || !intval($idMovimiento) || intval($idMovimiento) < 1 ? 0 : $idMovimiento;

			// Obtener registro(s)
			$response['movimientos'] = $this->Fondo_Monetario_model->listar_movimientos($idFondoMonetario, $idMovimiento);
			$response['err'] = false;
			$response['msg'] = 'Información obtenida con éxito.';
			$responseCode = REST_Controller::HTTP_OK;
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}

	// Obtener información para el recibo de pago.
	public function listar_recibo_pago_get()
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

			// Verificar si se especifica el ID de un registro en particular
			$idFondoMonetario = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idFondoMonetario) || !intval($idFondoMonetario) || intval($idFondoMonetario) < 1) {
				$respuesta['msg'] = 'Debe especificar un identificador válido.';
				$this->response($respuesta, $codigo_respuesta);
			}

			// Obtener información
			$response['recibo_pago'] = $this->Fondo_Monetario_model->listar_recibo_pago($idFondoMonetario);
			$response['err'] = empty($response['recibo_pago']);
			if (!$response['err']) {
				$response['msg'] = 'Información obtenida con éxito.';
				$responseCode = REST_Controller::HTTP_OK;
			} else {
				$response['msg'] = 'No se puedo generar el recibo de pago.';
			}
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
			$data = capitalizar_arreglo($data, ['fondo_monetario', 'banco', 'numero_cuenta', 'clabe']);

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('fondoMonetarioInsertar')) {
				// Error al validar la información
				$respuesta['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$respuesta['msg'] .= $value . PHP_EOL;
				}
				$this->response($respuesta, $codigo_respuesta);
			}

			if (!empty($data['requiere_datos_bancarios'])) {
				$this->form_validation->reset_validation();
				if (!$this->form_validation->run('fondoMonetarioInsertarDatosBancarios')) {
					// Error al validar la información
					$respuesta['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
					foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
						$respuesta['msg'] .= $value . PHP_EOL;
					}
					$this->response($respuesta, $codigo_respuesta);
				}
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'fk_id_condominio' => $idCondominio,
				'fondo_monetario' => $data['fondo_monetario'],
				'fk_id_tipo_fondo_monetario' => $data['id_tipo_fondo_monetario'],
				'banco' => !empty($data['banco']) ? $data['banco'] : null,
				'numero_cuenta' => !empty($data['numero_cuenta']) ? $data['numero_cuenta'] : null,
				'clabe' => !empty($data['clabe']) ? $data['clabe'] : null,
				'saldo' => $data['saldo'],
				'fk_id_usuario_registro' => $idUsuarioRegistro,
			];

			$respuesta = $this->Fondo_Monetario_model->insertar($data);
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
			$idFondoMonetario = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idFondoMonetario) || !intval($idFondoMonetario) || intval($idFondoMonetario) < 1) {
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
			$data = capitalizar_arreglo($data, ['fondo_monetario', 'banco', 'numero_cuenta', 'clabe']);
			// Quitar validacion de campos del destinatario a la regla documentosInsertarBasico
			$validationRules = array_filter($this->form_validation->get_reglas()['fondoMonetarioInsertar'], function ($r) {
				return $r['field'] != 'saldo';
			});

			// Validar la información
			$this->form_validation->set_data($data);
			$this->form_validation->set_rules($validationRules);
			if (!$this->form_validation->run()) {
				// if (!$this->form_validation->run('fondoMonetarioInsertar')) {
				// Error al validar la información
				$respuesta['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$respuesta['msg'] .= $value . PHP_EOL;
				}
				$this->response($respuesta, $codigo_respuesta);
			}

			if (!empty($data['requiere_datos_bancarios'])) {
				$this->form_validation->reset_validation();
				if (!$this->form_validation->run('fondoMonetarioInsertarDatosBancarios')) {
					// Error al validar la información
					$respuesta['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
					foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
						$respuesta['msg'] .= $value . PHP_EOL;
					}
					$this->response($respuesta, $codigo_respuesta);
				}
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'id_fondo_monetario' => $idFondoMonetario,
				'fondo_monetario' => $data['fondo_monetario'],
				'fk_id_tipo_fondo_monetario' => $data['id_tipo_fondo_monetario'],
				'banco' => !empty($data['banco']) ? $data['banco'] : null,
				'numero_cuenta' => !empty($data['numero_cuenta']) ? $data['numero_cuenta'] : null,
				'clabe' => !empty($data['clabe']) ? $data['clabe'] : null,
				'fk_id_usuario_modifico' => $idUsuarioModifico,
			];

			$respuesta = $this->Fondo_Monetario_model->actualizar($data);
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
			$idFondoMonetario = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idFondoMonetario) || !intval($idFondoMonetario) || intval($idFondoMonetario) < 1) {
				$response['msg'] = 'Debe especificar un identificador válido.';
				$this->response($response, $responseCode);
			}

			// Información validada con éxito. Procede a la eliminación de registro
			$data = [
				'id_fondo_monetario' => $idFondoMonetario,
				'id_usuario_modifico' => $idUsuarioModifico,
			];

			$response = $this->Fondo_Monetario_model->eliminar($data);
			if (!$response['err']) {
				$responseCode = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}

	// Registrar movimiento
	public function registrar_movimiento_post()
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

			// Verificar que se especique el ID del registro a actualizar
			$idFondoMonetario = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idFondoMonetario) || !intval($idFondoMonetario) || intval($idFondoMonetario) < 1) {
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
			$data = capitalizar_arreglo($data, ['concepto']);

			// Validación de la información proporcionada
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('fondoMonetarioRegistrarMovto')) {
				// Error al validar la información
				$response['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$response['msg'] .= $value . PHP_EOL;
				}
				$this->response($response, $responseCode);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'fk_id_fondo_monetario' => $idFondoMonetario,
				'fk_id_tipo_movimiento' => $data['id_tipo_movimiento'],
				'fecha' => $data['fecha'],
				'concepto' => $data['concepto'],
				'importe' => $data['importe'],
				'archivo_comprobante' =>
					!empty($_FILES) && !empty($_FILES['archivo_comprobante']) ? 'archivo_comprobante' : null,
				'fk_id_usuario_registro' => $idUsuarioRegistro,
			];

			// Información validada con éxito. Procede a la inserción
			$response = $this->Fondo_Monetario_model->registrar_movimiento($data);
			if (!$response['err']) {
				$responseCode = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}

	// Eliminar lógicamente el registro
	public function eliminar_movimiento_post()
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
			$idMovimientoFondoMonetario = $this->security->xss_clean($this->uri->segment(3));
			if (
				empty($idMovimientoFondoMonetario) ||
				!intval($idMovimientoFondoMonetario) ||
				intval($idMovimientoFondoMonetario) < 1
			) {
				$response['msg'] = 'Debe especificar un identificador válido.';
				$this->response($response, $responseCode);
			}

			// Información validada con éxito. Procede a la eliminación de registro
			$data = [
				'id_fondo_monetario_movimiento' => $idMovimientoFondoMonetario,
				'id_usuario_modifico' => $idUsuarioModifico,
			];

			$response = $this->Fondo_Monetario_model->eliminar_movimiento($data);
			if (!$response['err']) {
				$responseCode = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}

	// Traspaso entre fondos
	public function traspaso_post()
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

			// Verificar que se especique el ID del registro a actualizar
			$idFondoMonetario = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idFondoMonetario) || !intval($idFondoMonetario) || intval($idFondoMonetario) < 1) {
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
			$data = capitalizar_arreglo($data, ['concepto']);

			// Validación de la información proporcionada
			$this->form_validation->set_data($data);
			/* $validationRules = array_merge(
				$this->form_validation->get_reglas()['fondoMonetarioTraspaso'],
				$this->form_validation->get_reglas()['fondoMonetarioRegistrarMovto']
			);
			$validationRules = array_values(
				array_filter($validationRules, function ($r) {
					return $r['field'] != 'id_tipo_movimiento' && $r['field'] != 'concepto';
				})
			); */

			/* $this->form_validation->set_rules($validationRules);
			 if (!$this->form_validation->run()) { */
			if (!$this->form_validation->run('fondoMonetarioTraspaso')) {
				// Error al validar la información
				$response['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$response['msg'] .= $value . PHP_EOL;
				}
				$this->response($response, $responseCode);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'id_fondo_monetario_origen' => $idFondoMonetario,
				'id_fondo_monetario_destino' => $data['id_fondo_monetario_destino'],
				'id_tipo_movimiento' => 3,
				'fecha' => $data['fecha'],
				'importe' => $data['importe'],
				'archivo_comprobante' =>
					!empty($_FILES) && !empty($_FILES['archivo_comprobante']) ? 'archivo_comprobante' : null,
				'id_usuario_registro' => $idUsuarioRegistro,
			];

			// Información validada con éxito. Procede a la inserción
			$response = $this->Fondo_Monetario_model->traspaso($data);
			if (!$response['err']) {
				$responseCode = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}
}

/* End of file Fondos_Monetarios.php */
/* Location: ./application/controllers/Fondos_Monetarios.php */
