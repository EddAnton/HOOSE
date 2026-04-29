<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Controlador: Areas_Comunes
 *
 * Este controlador realiza las operaciones relacionadas con las Áreas comunes
 *
 * @package   CodeIgniter
 * @category  Controller REST
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Areas_Comunes extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Area_Comun_model');
	}

	public function index()
	{
		$this->response(APP_NAME . ' API / Áreas Comunes :: Controller');
	}

	// Listar registro(s)
	public function listar_get($soloActivos = false)
	{
		$response = [
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

			// Verificar si se especifica el ID de un registro en particular
			$idAreaComun = $this->security->xss_clean($this->uri->segment(2));
			$idAreaComun = !empty($idAreaComun) && intval($idAreaComun) ? intval($idAreaComun) : 0;

			// Obtener registro(s)
			$result = $this->Area_Comun_model->listar($idAreaComun, $soloActivos);
			if (!empty($idAreaComun)) {
				$response['area_comun'] = $result;
			} else {
				$response['areas_comunes'] = $result;
			}

			$response['err'] = false;
			$response['msg'] = 'Información obtenida con éxito.';
			$codigo_respuesta = REST_Controller::HTTP_OK;
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $codigo_respuesta);
	}

	// Listar registro(s)
	public function listar_para_reservaciones_get()
	{
		$response = [
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

			// Obtener registro(s)
			$response['areas_comunes'] = array_map(function ($e) {
				return [
					'id_area_comun' => $e['id_area_comun'],
					'nombre' => $e['nombre'],
					'importe_hora' => $e['importe_hora'],
				];
			}, $this->Area_Comun_model->listar(0, true));

			$response['err'] = false;
			$response['msg'] = 'Información obtenida con éxito.';
			$codigo_respuesta = REST_Controller::HTTP_OK;
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $codigo_respuesta);
	}

	// Listar reservaciones
	public function listar_reservaciones_post()
	{
		$response = [
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

			// Obtener data a procesar
			$data = $this->security->xss_clean($this->post());
			// Verificar que se haya enviado la información a insertar
			if (empty($data) || empty($data['anio']) || empty($data['mes'])) {
				$response['msg'] = 'Debe especificar la fecha a procesar.';
				$this->response($response, $codigo_respuesta);
			}

			$data = [
				'anio' => $data['anio'],
				'mes' => strlen($data['mes']) < 2 ? '0' . $data['mes'] : $data['mes'],
				'dia' => !empty($data['dia']) ? (strlen($data['dia']) < 2 ? '0' . $data['dia'] : $data['dia']) : null,
			];

			// Obtener registro(s)
			$response['reservaciones'] = $this->Area_Comun_model->listar_reservaciones($data);

			$response['err'] = false;
			$response['msg'] = 'Información obtenida con éxito.';
			$codigo_respuesta = REST_Controller::HTTP_OK;
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $codigo_respuesta);
	}

	// Listar reservaciones o reservación
	public function listar_reservacion_get()
	{
		$response = [
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

			// Obtener el ID del registro a procesar
			// Verificar que se especique el ID del registro a actualizar
			$idReservacion = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idReservacion) || !intval($idReservacion) || intval($idReservacion) < 1) {
				$response['msg'] = 'Debe especificar un identificador válido.';
				$this->response($response, $codigo_respuesta);
			}

			// Obtener registro(s)
			$response['reservacion'] = $this->Area_Comun_model->listar_reservacion($idReservacion);

			$response['err'] = false;
			$response['msg'] = 'Información obtenida con éxito.';
			$codigo_respuesta = REST_Controller::HTTP_OK;
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $codigo_respuesta);
	}

	// Nuevo registro
	public function insertar_post()
	{
		$response = [
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
			$idUsuarioRegistro = $token->data->id;
			$idCondominio = $token->data->id_condominio_usuario;

			$data = $this->security->xss_clean($this->post());
			// Verificar que se haya enviado la información a insertar
			if (empty($data)) {
				$response['msg'] = 'Información a procesar no ha sido proporcionada.';
				$this->response($response, $codigo_respuesta);
			}

			$data = nulificar_elementos_arreglo(trim_elementos_arreglo($data));
			$data = capitalizar_arreglo($data, ['nombre', 'descripcion']);

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('areaComunInsertar')) {
				// Error al validar la información
				$response['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$response['msg'] .= $value . PHP_EOL;
				}
				$this->response($response, $codigo_respuesta);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'fk_id_condominio' => $idCondominio,
				'nombre' => $data['nombre'],
				'descripcion' => !empty($data['descripcion']) ? $data['descripcion'] : null,
				'importe_hora' => $data['importe_hora'],
				'fk_id_usuario_registro' => $idUsuarioRegistro,
			];

			$response = $this->Area_Comun_model->insertar($data);
			if (!$response['err']) {
				$codigo_respuesta = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $codigo_respuesta);
	}

	// Actualizar registro
	public function actualizar_post()
	{
		$response = [
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
			$idAreaComun = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idAreaComun) || !intval($idAreaComun) || intval($idAreaComun) < 1) {
				$response['msg'] = 'Debe especificar un identificador válido.';
				$this->response($response, $codigo_respuesta);
			}

			$data = $this->security->xss_clean($this->post());
			// Verificar que se haya enviado la información a insertar
			if (empty($data)) {
				$response['msg'] = 'Información a procesar no ha sido proporcionada.';
				$this->response($response, $codigo_respuesta);
			}

			$data = nulificar_elementos_arreglo(trim_elementos_arreglo($data));
			$data = capitalizar_arreglo($data, ['nombre', 'descripcion']);

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('areaComunInsertar')) {
				// Error al validar la información
				$response['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$response['msg'] .= $value . PHP_EOL;
				}
				$this->response($response, $codigo_respuesta);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'id_area_comun' => $idAreaComun,
				'nombre' => $data['nombre'],
				'descripcion' => !empty($data['descripcion']) ? $data['descripcion'] : null,
				'importe_hora' => $data['importe_hora'],
				'fk_id_usuario_modifico' => $idUsuarioModifico,
			];

			$response = $this->Area_Comun_model->actualizar($data);
			if (!$response['err']) {
				$codigo_respuesta = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $codigo_respuesta);
	}

	// Alternar estatus
	public function alternar_estatus_post()
	{
		$response = [
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
			$idAreaComun = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idAreaComun) || !intval($idAreaComun) || intval($idAreaComun) < 1) {
				$response['msg'] = 'Debe especificar un identificador válido.';
				$this->response($response, $codigo_respuesta);
			}

			// Información validada con éxito. Procede a la actualización del estatus
			$data = [
				'id_area_comun' => $idAreaComun,
				'fk_id_usuario_modifico' => $idUsuarioModifico,
			];

			$response = $this->Area_Comun_model->alternar_estatus($data);
			if (!$response['err']) {
				$codigo_respuesta = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $codigo_respuesta);
	}

	// Nueva reservación
	public function insertar_reservacion_post()
	{
		$response = [
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
			$idUsuarioRegistro = $token->data->id;

			// Verificar si se especifica el ID de un registro en particular
			$idAreaComun = $this->security->xss_clean($this->uri->segment(4));
			if (empty($idAreaComun) || !intval($idAreaComun) || intval($idAreaComun) < 1) {
				$response['msg'] = 'Debe especificar un identificador válido.';
				$this->response($response, $codigo_respuesta);
			}

			$data = $this->security->xss_clean($this->post());
			// Verificar que se haya enviado la información a insertar
			if (empty($data)) {
				$response['msg'] = 'Información a procesar no ha sido proporcionada.';
				$this->response($response, $codigo_respuesta);
			}

			$data = nulificar_elementos_arreglo(trim_elementos_arreglo($data));

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('areaComunInsertarReservacion')) {
				// Error al validar la información
				$response['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$response['msg'] .= $value . PHP_EOL;
				}
				$this->response($response, $codigo_respuesta);
			}
			/* $fecha = $data['fecha_inicio'];
			$hora = $data['hora_inicio'];
			$fecha_inicio = date('Y-m-d H:i', strtotime("$fecha $hora"));
			$fecha = $data['fecha_fin'];
			$hora = $data['hora_fin'];
			$fecha_fin = date('Y-m-d H:i', strtotime("$fecha $hora"));
			$horas = fechasDiferencia('h', $fecha_inicio, $fecha_fin, true); */
			$horas = fechasDiferencia('h', $data['fecha_inicio'], $data['fecha_fin'], true);

			if ($horas <= 0) {
				$response['msg'] = 'Fechas y horas proporcionadas no son válidas.';
				$this->response($response, $codigo_respuesta);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'fk_id_area_comun' => $idAreaComun,
				'fk_id_usuario' => $data['id_usuario'],
				/* 'fecha_inicio' => $fecha_inicio,
				 'fecha_fin' => $fecha_fin, */
				'fecha_inicio' => $data['fecha_inicio'],
				'fecha_fin' => $data['fecha_fin'],
				'importe_total' => $data['importe_total'],
				'fecha_pago' => !empty($data['fecha_pago']) ? $data['fecha_pago'] : null,
				'fk_id_usuario_registro' => $idUsuarioRegistro,
			];

			/* print_r($data);
			 exit(); */
			$response = $this->Area_Comun_model->insertar_reservacion($data);
			if (!$response['err']) {
				$codigo_respuesta = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $codigo_respuesta);
	}

	// Actualizar reservación
	public function actualizar_reservacion_post()
	{
		$response = [
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

			// Verificar que se especique el ID del registro a actualizar
			$idReservacion = $this->security->xss_clean($this->uri->segment(4));
			if (empty($idReservacion) || !intval($idReservacion) || intval($idReservacion) < 1) {
				$response['msg'] = 'Debe especificar un identificador válido.';
				$this->response($response, $codigo_respuesta);
			}

			$data = $this->security->xss_clean($this->post());
			// Verificar que se haya enviado la información a insertar
			if (empty($data)) {
				$response['msg'] = 'Información a procesar no ha sido proporcionada.';
				$this->response($response, $codigo_respuesta);
			}

			$data = nulificar_elementos_arreglo(trim_elementos_arreglo($data));

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('areaComunInsertarReservacion')) {
				// Error al validar la información
				$response['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$response['msg'] .= $value . PHP_EOL;
				}
				$this->response($response, $codigo_respuesta);
			}
			/* $fecha = $data['fecha_inicio'];
			$hora = $data['hora_inicio'];
			$fecha_inicio = date('Y-m-d H:i', strtotime("$fecha $hora"));
			$fecha = $data['fecha_fin'];
			$hora = $data['hora_fin'];
			$fecha_fin = date('Y-m-d H:i', strtotime("$fecha $hora"));
			$horas = fechasDiferencia('h', $fecha_inicio, $fecha_fin, true); */
			$horas = fechasDiferencia('h', $data['fecha_inicio'], $data['fecha_fin'], true);

			if ($horas <= 0) {
				$response['msg'] = 'Fechas y horas proporcionadas no son válidas.';
				$this->response($response, $codigo_respuesta);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'id_area_comun_reservacion' => $idReservacion,
				'fk_id_usuario' => $data['id_usuario'],
				/* 'fecha_inicio' => $fecha_inicio,
				 'fecha_fin' => $fecha_fin, */
				'fecha_inicio' => $data['fecha_inicio'],
				'fecha_fin' => $data['fecha_fin'],
				'importe_total' => $data['importe_total'],
				'fecha_pago' => !empty($data['fecha_pago']) ? $data['fecha_pago'] : null,
				'fk_id_usuario_modifico' => $idUsuarioModifico,
			];

			/* print_r($data);
			 exit(); */
			$response = $this->Area_Comun_model->actualizar_reservacion($data);
			if (!$response['err']) {
				$codigo_respuesta = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $codigo_respuesta);
	}

	// Registrar pago Reservación
	public function registrar_pago_reservacion_post()
	{
		$response = [
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

			// Verificar que se especique el ID del registro a actualizar
			$idReservacion = $this->security->xss_clean($this->uri->segment(4));
			if (empty($idReservacion) || !intval($idReservacion) || intval($idReservacion) < 1) {
				$response['msg'] = 'Debe especificar un identificador válido.';
				$this->response($response, $codigo_respuesta);
			}

			$data = $this->security->xss_clean($this->post());
			// Verificar que se haya enviado la información a insertar
			if (empty($data)) {
				$response['msg'] = 'Información a procesar no ha sido proporcionada.';
				$this->response($response, $codigo_respuesta);
			}

			$data = nulificar_elementos_arreglo(trim_elementos_arreglo($data));

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('areaComunRegistrarPagoReservacion')) {
				// Error al validar la información
				$response['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$response['msg'] .= $value . PHP_EOL;
				}
				$this->response($response, $codigo_respuesta);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'id_area_comun_reservacion' => $idReservacion,
				'fecha_pago' => $data['fecha_pago'],
				'id_usuario_modifico' => $idUsuarioModifico,
			];

			// Información validada con éxito. Procede a la inserción
			$response = $this->Area_Comun_model->registrar_pago_reservacion($data);
			if (!$response['err']) {
				$codigo_respuesta = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $codigo_respuesta);
	}

	// Cancelar reservación
	public function cancelar_reservacion_post()
	{
		$response = [
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

			// Verificar que se especique el ID del registro a actualizar
			$idReservacion = $this->security->xss_clean($this->uri->segment(4));
			if (empty($idReservacion) || !intval($idReservacion) || intval($idReservacion) < 1) {
				$response['msg'] = 'Debe especificar un identificador válido.';
				$this->response($response, $codigo_respuesta);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'id_reservacion' => $idReservacion,
				'id_usuario_modifico' => $idUsuarioModifico,
			];

			// Información validada con éxito. Procede a la inserción
			$response = $this->Area_Comun_model->cancelar_reservacion($data);
			if (!$response['err']) {
				$codigo_respuesta = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $codigo_respuesta);
	}
}

/* End of file Areas_Comunes.php */
/* Location: ./application/controllers/Areas_Comunes.php */
