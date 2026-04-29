<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Controlador: Cuotas_Mantenimiento
 *
 * Este controlador realiza las operaciones relacionadas con las Cuotas de mantenimiento
 *
 * @package   CodeIgniter
 * @category  Controller REST
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Cuotas_Mantenimiento extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Cuota_Mantenimiento_model');
		$this->load->model('Fondo_Monetario_model');
		// $this->load->model('Unidad_model');
	}

	public function index()
	{
		$this->response(APP_NAME . ' API / Cuotas de mantenimiento :: Controller');
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
			$idUsuario = in_array($token->data->id_perfil_usuario, [4, 5]) ? $token->data->id : 0;
			$esUsuarioPropietario = $token->data->id_perfil_usuario == 4;
			$idCondominio = $token->data->id_condominio_usuario;

			// Verificar si se especifica el ID de un registro en particular
			$idCuotaMantenimiento = $this->security->xss_clean($this->uri->segment(2));
			$idCuotaMantenimiento =
				!empty($idCuotaMantenimiento) && intval($idCuotaMantenimiento) ? intval($idCuotaMantenimiento) : 0;

			// Obtener registro(s)
			// $response['recaudaciones'] = $this->Recaudacion_model->listar($idCuotaMantenimiento, $idCondominio, $soloActivos);
			$result = $this->Cuota_Mantenimiento_model->listar(
				$idCuotaMantenimiento,
				$idUsuario,
				$idCondominio,
				$esUsuarioPropietario,
				$soloActivos
			);
			if (!empty($idCuotaMantenimiento)) {
				$response['cuota_mantenimiento'] = $result;
			} else {
				$response['cuotas_mantenimiento'] = $result;
			}

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
			$idCuotaMantenimiento = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idCuotaMantenimiento) || !intval($idCuotaMantenimiento) || intval($idCuotaMantenimiento) < 1) {
				$respuesta['msg'] = 'Debe especificar un identificador válido.';
				$this->response($respuesta, $responseCode);
			}

			// Obtener información
			$response['recibo_pago'] = $this->Cuota_Mantenimiento_model->listar_recibo_pago($idCuotaMantenimiento);
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

			$data = capitalizar_arreglo(nulificar_elementos_arreglo(trim_elementos_arreglo($data)));

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('cuotaMantenimientoInsertar')) {
				// Error al validar la información
				$respuesta['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$respuesta['msg'] .= $value . PHP_EOL;
				}
				$this->response($respuesta, $responseCode);
			}

			$dataPago = [];
			$dataMovimiento = [];
			$importe = floatval($data['importe']);
			// Validar data del abono y movimiento al fondo monetario
			if ($importe > 0) {
				// Validar la información del abono
				$dataPago = [
					'importe' => $importe,
					'fecha_pago' => !empty($data['fecha_pago']) ? $data['fecha_pago'] : null,
					'id_forma_pago' => !empty($data['id_forma_pago']) ? $data['id_forma_pago'] : null,
					'numero_referencia' => !empty($data['numero_referencia']) ? $data['numero_referencia'] : null,
					'fk_id_usuario_registro' => $idUsuarioRegistro,
				];

				$this->form_validation->reset_validation();
				$this->form_validation->set_data($dataPago);
				if (!$this->form_validation->run('cuotaMantenimientoRegistrarPago')) {
					// Error al validar la información
					$respuesta['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
					foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
						$respuesta['msg'] .= $value . PHP_EOL;
					}
					$this->response($respuesta, $responseCode);
				}
				$dataPago['fk_id_forma_pago'] = $dataPago['id_forma_pago'];
				unset($dataPago['id_forma_pago']);

				// Validar la información del movimiento al fondo monetario
				$dataMovimiento = [
					'fk_id_fondo_monetario' => !empty($data['id_fondo_monetario']) ? $data['id_fondo_monetario'] : null,
					'fk_id_tipo_movimiento' => 1,
					'fecha' => !empty($data['fecha_pago']) ? $data['fecha_pago'] : null,
					'importe' => $importe,
					'es_externo' => 1,
					'fk_id_usuario_registro' => $idUsuarioRegistro,
				];

				$this->form_validation->reset_validation();
				$this->form_validation->set_data($dataMovimiento);
				if (!$this->form_validation->run('fondoMonetarioRegistrarMovtoExterno')) {
					// Error al validar la información
					$respuesta['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
					foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
						$respuesta['msg'] .= $value . PHP_EOL;
					}
					$this->response($respuesta, $responseCode);
				}
			}

			$total =
				floatval($data['ordinaria']) +
				floatval($data['extraordinaria']) +
				floatval($data['otros_servicios']) -
				floatval($data['descuento_pronto_pago']);
			// Verificar que se haya enviado la información a insertar
			if ($importe > $total) {
				$respuesta['msg'] = 'El abono no puede ser mayor al total.';
				$this->response($respuesta, $responseCode);
			}
			$saldo = $total - $importe;
			$id_estatus_recaudacion = 0;
			if ($saldo == 0) {
				$id_estatus_recaudacion = 3;
			} elseif ($saldo == $total) {
				$id_estatus_recaudacion = 1;
			} elseif ($saldo > 0 && $saldo < $total) {
				$id_estatus_recaudacion = 2;
			}
			if ($id_estatus_recaudacion == 0) {
				$respuesta['msg'] = 'No fue posible determinar el estatus de la Cuota de mantenimiento.';
				$this->response($respuesta, $responseCode);
			}
			/*
			if (($importe > 0 || $id_estatus_recaudacion == 2) && empty($data['fecha_pago'])) {
				$respuesta['msg'] = 'Faltó especificar la fecha de pago.';
				$this->response($respuesta, $responseCode);
			}
			if (($importe > 0 || $id_estatus_recaudacion == 2) && empty($data['id_forma_pago'])) {
				$respuesta['msg'] = 'Faltó especificar la forma de pago.';
				$this->response($respuesta, $responseCode);
			}

			$dataPago = [];
			if ($importe > 0) {
				$dataPago = [
					'fecha_pago' => $data['fecha_pago'],
					'importe' => $importe,
					'id_forma_pago' => $data['id_forma_pago'],
					'numero_referencia' => !empty($data['numero_referencia']) ? $data['numero_referencia'] : null,
					'fk_id_usuario_registro' => $idUsuarioRegistro,
				];
			}
      */
			// Información validada con éxito. Procede a la inserción
			$data = [
				'fk_id_unidad' => $data['id_unidad'],
				'fk_id_perfil_usuario_paga' => $data['id_perfil_usuario_paga'],
				'fk_id_usuario_paga' => $data['id_usuario_paga'],
				'anio' => $data['anio'],
				'mes' => $data['mes'],
				'ordinaria' => $data['ordinaria'],
				'extraordinaria' => $data['extraordinaria'],
				'otros_servicios' => $data['otros_servicios'],
				'descuento_pronto_pago' => $data['descuento_pronto_pago'],
				'total' => $total,
				'saldo' => $saldo,
				'fecha_limite_pago' => $data['fecha_limite_pago'],
				'notas' => !empty($data['notas']) ? $data['notas'] : null,
				'fk_id_estatus_recaudacion' => $id_estatus_recaudacion,
				'fk_id_usuario_registro' => $idUsuarioRegistro,
				'pago' => $dataPago,
				'movimiento' => $dataMovimiento,
			];

			$respuesta = $this->Cuota_Mantenimiento_model->insertar($data);
			if (!$respuesta['err']) {
				$responseCode = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$respuesta['msg'] = $e->getMessage();
		}
		$this->response($respuesta, $responseCode);
	}

	// Generar masivamente cuotas mantenimiento
	public function generacion_masiva_post($soloTotal = false)
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
			$idUsuarioRegistro = $token->data->id;

			$data = $this->security->xss_clean($this->post());

			// Verificar que se haya enviado la información a insertar
			if (empty($data)) {
				$response['msg'] = 'Información a procesar no ha sido proporcionada.';
				$this->response($response, $responseCode);
			}

			$data = nulificar_elementos_arreglo(trim_elementos_arreglo($data));

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('generarCuotasMantenimiento')) {
				// Error al validar la información
				$response['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$response['msg'] .= $value . PHP_EOL;
				}
				$this->response($response, $responseCode);
			}

			$fechaActual = new DateTime();
			$anioActual = $fechaActual->format('Y');
			$mesActual = $fechaActual->format('n');

			if ($data['anio'] < $anioActual) {
				$response['msg'] = 'El año no puede ser inferior al actual.';
				$this->response($response, $responseCode);
			} elseif ($data['anio'] == $anioActual && $data['mes'] < $mesActual) {
				$response['msg'] = 'El mes no puede ser inferior al actual.';
				$this->response($response, $responseCode);
			}

			$data['idCondominio'] = $idCondominio;
			$data['idUsuarioRegistro'] = $idUsuarioRegistro;

			// Obtener registro(s)
			$result = $soloTotal
				? $this->Cuota_Mantenimiento_model->listar_para_generacion_masiva($data)
				: $this->Cuota_Mantenimiento_model->generar_masivamente($data);

			if ($result === false) {
				$response['msg'] = 'Error al obtener información. Verifique información proporcionada.';
			} elseif (!isset($result['err'])) {
				$response['total_generar'] = count($result);
				$response['msg'] = 'Información obtenida con éxito.';
				$response['err'] = false;
			} else {
				$response = $result;
			}

			$responseCode = !$response['err'] ? REST_Controller::HTTP_OK : $responseCode;
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
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
			$idCuotaMantenimiento = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idCuotaMantenimiento) || !intval($idCuotaMantenimiento) || intval($idCuotaMantenimiento) < 1) {
				$respuesta['msg'] = 'Debe especificar un identificador válido.';
				$this->response($respuesta, $responseCode);
			}

			$data = $this->security->xss_clean($this->post());
			// Verificar que se haya enviado la información a insertar
			if (empty($data)) {
				$respuesta['msg'] = 'Información a procesar no ha sido proporcionada.';
				$this->response($respuesta, $responseCode);
			}

			$data = capitalizar_arreglo(nulificar_elementos_arreglo(trim_elementos_arreglo($data)));

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('cuotaMantenimientoInsertar')) {
				// Error al validar la información
				$respuesta['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$respuesta['msg'] .= $value . PHP_EOL;
				}
				$this->response($respuesta, $responseCode);
			}

			$dataPago = [];
			$importe = floatval($data['importe']);
			// Validar la información del abono
			if ($importe > 0) {
				$dataPago = [
					'fk_id_cuota_mantenimiento' => $idCuotaMantenimiento,
					'importe' => $importe,
					'fecha_pago' => !empty($data['fecha_pago']) ? $data['fecha_pago'] : null,
					'id_forma_pago' => !empty($data['id_forma_pago']) ? $data['id_forma_pago'] : null,
					'numero_referencia' => !empty($data['numero_referencia']) ? $data['numero_referencia'] : null,
					'fk_id_usuario_registro' => $idUsuarioModifico,
				];

				$this->form_validation->reset_validation();
				$this->form_validation->set_data($dataPago);
				if (!$this->form_validation->run('cuotaMantenimientoRegistrarPago')) {
					// Error al validar la información
					$respuesta['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
					foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
						$respuesta['msg'] .= $value . PHP_EOL;
					}
					$this->response($respuesta, $responseCode);
				}
				$dataPago['fk_id_forma_pago'] = $dataPago['id_forma_pago'];
				unset($dataPago['id_forma_pago']);
			}

			$total = floatval($data['ordinaria']) + floatval($data['extraordinaria']) + floatval($data['otros_servicios']);
			// Verificar que se haya enviado la información a insertar
			if ($importe > $total) {
				$respuesta['msg'] = 'El abono no puede ser mayor al total.';
				$this->response($respuesta, $responseCode);
			}
			$saldo = $total - $importe;
			$id_estatus_recaudacion = 0;
			if ($saldo == 0) {
				$id_estatus_recaudacion = 3;
			} elseif ($saldo == $total) {
				$id_estatus_recaudacion = 1;
			} elseif ($saldo > 0 && $saldo < $total) {
				$id_estatus_recaudacion = 2;
			}
			if ($id_estatus_recaudacion == 0) {
				$respuesta['msg'] = 'No fue posible determinar el estatus de la Cuota de mantenimiento.';
				$this->response($respuesta, $responseCode);
			}
			/*
			if (($importe > 0 || $id_estatus_recaudacion == 2) && empty($data['fecha_pago'])) {
				$respuesta['msg'] = 'Faltó especificar la fecha de pago.';
				$this->response($respuesta, $responseCode);
			}
			if (($importe > 0 || $id_estatus_recaudacion == 2) && empty($data['id_forma_pago'])) {
				$respuesta['msg'] = 'Faltó especificar la forma de pago.';
				$this->response($respuesta, $responseCode);
			}

			$dataPago = [];
			if ($importe > 0) {
				$dataPago = [
					'fecha_pago' => $data['fecha_pago'],
					'importe' => $importe,
					'id_forma_pago' => $data['id_forma_pago'],
					'numero_referencia' => !empty($data['numero_referencia']) ? $data['numero_referencia'] : null,
					'fk_id_usuario_registro' => $idUsuarioRegistro,
				];
			}
      */

			// Información validada con éxito. Procede a la inserción
			$data = [
				'id_cuota_mantenimiento' => $idCuotaMantenimiento,
				'fk_id_unidad' => $data['id_unidad'],
				'fk_id_perfil_usuario_paga' => $data['id_perfil_usuario_paga'],
				'fk_id_usuario_paga' => $data['id_usuario_paga'],
				'anio' => $data['anio'],
				'mes' => $data['mes'],
				'ordinaria' => $data['ordinaria'],
				'extraordinaria' => $data['extraordinaria'],
				'otros_servicios' => $data['otros_servicios'],
				'total' => $total,
				'saldo' => $saldo,
				'fecha_limite_pago' => $data['fecha_limite_pago'],
				'notas' => !empty($data['notas']) ? $data['notas'] : null,
				'fk_id_estatus_recaudacion' => $id_estatus_recaudacion,
				'fk_id_usuario_modifico' => $idUsuarioModifico,
				'pago' => $dataPago,
			];

			$respuesta = $this->Cuota_Mantenimiento_model->actualizar($data);
			if (!$respuesta['err']) {
				$responseCode = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$respuesta['msg'] = $e->getMessage();
		}
		$this->response($respuesta, $responseCode);
	}

	// Establecer como pagada la recaudación
	public function registrar_pago_post()
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
			$idCuotaMantenimiento = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idCuotaMantenimiento) || !intval($idCuotaMantenimiento) || intval($idCuotaMantenimiento) < 1) {
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
			$data = capitalizar_arreglo(nulificar_elementos_arreglo(trim_elementos_arreglo($data)));

			// Validación de la información proporcionada
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('cuotaMantenimientoRegistrarPago')) {
				// Error al validar la información
				$response['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$response['msg'] .= $value . PHP_EOL;
				}
				$this->response($response, $responseCode);
			}

			// Validar la información del movimiento al fondo monetario
			$dataMovimiento = [
				'fk_id_fondo_monetario' => !empty($data['id_fondo_monetario']) ? $data['id_fondo_monetario'] : null,
				'fk_id_tipo_movimiento' => 1,
				'fecha' => $data['fecha_pago'],
				'importe' => floatval($data['importe']),
				'es_externo' => 1,
				'fk_id_usuario_registro' => $idUsuarioRegistro,
			];

			$this->form_validation->reset_validation();
			$this->form_validation->set_data($dataMovimiento);
			if (!$this->form_validation->run('fondoMonetarioRegistrarMovtoExterno')) {
				// Error al validar la información
				$respuesta['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$respuesta['msg'] .= $value . PHP_EOL;
				}
				$this->response($respuesta, $responseCode);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'fk_id_cuota_mantenimiento' => $idCuotaMantenimiento,
				'importe' => floatval($data['importe']),
				'fecha_pago' => $data['fecha_pago'],
				'fk_id_forma_pago' => $data['id_forma_pago'],
				'numero_referencia' => !empty($data['numero_referencia']) ? $data['numero_referencia'] : null,
				'notas' => !empty($data['notas']) ? $data['notas'] : null,
				'fk_id_usuario_registro' => $idUsuarioRegistro,
				'movimiento' => $dataMovimiento,
			];

			// Información validada con éxito. Procede a la inserción
			$response = $this->Cuota_Mantenimiento_model->registrar_pago($data);
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
			$idCuotaMantenimiento = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idCuotaMantenimiento) || !intval($idCuotaMantenimiento) || intval($idCuotaMantenimiento) < 1) {
				$response['msg'] = 'Debe especificar un identificador válido.';
				$this->response($response, $responseCode);
			}

			// Información validada con éxito. Procede a la eliminación de registro
			$data = [
				'id_cuota_mantenimiento' => $idCuotaMantenimiento,
				'id_usuario_modifico' => $idUsuarioModifico,
			];

			$response = $this->Cuota_Mantenimiento_model->eliminar($data);
			if (!$response['err']) {
				$responseCode = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}

	// Eliminar lógicamente el registro
	public function eliminar_pago_post()
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
			$idPagoCuotaMantenimiento = $this->security->xss_clean($this->uri->segment(3));
			if (
				empty($idPagoCuotaMantenimiento) ||
				!intval($idPagoCuotaMantenimiento) ||
				intval($idPagoCuotaMantenimiento) < 1
			) {
				$response['msg'] = 'Debe especificar un identificador válido.';
				$this->response($response, $responseCode);
			}

			// Información validada con éxito. Procede a la eliminación de registro
			$data = [
				'id_cuota_mantenimiento_pago' => $idPagoCuotaMantenimiento,
				'id_usuario_modifico' => $idUsuarioModifico,
			];

			$response = $this->Cuota_Mantenimiento_model->eliminar_pago($data);
			if (!$response['err']) {
				$responseCode = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}
}

/* End of file Cuotas_Mantenimiento.php */
/* Location: ./application/controllers/Cuotas_Mantenimiento.php */
