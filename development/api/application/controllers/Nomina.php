<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Controlador: Nomina
 *
 * Este controlador realiza las operaciones relacionadas con los pagos de Nómina
 *
 * @package   CodeIgniter
 * @category  Controller REST
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Nomina extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Nomina_model');
	}

	public function index()
	{
		$this->response(APP_NAME . ' API / Nómina :: Controller');
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
			$idColaborador = $token->data->id_perfil_usuario == 3 ? $token->data->id : 0;
			$idCondominio = $token->data->id_condominio_usuario;

			// Verificar si se especifica el ID de un registro en particular
			$idNomina = $this->security->xss_clean($this->uri->segment(2));
			$idNomina = !empty($idNomina) && intval($idNomina) ? intval($idNomina) : 0;

			// Obtener registro(s)
			$result = $this->Nomina_model->listar($idNomina, $idCondominio, $idColaborador, $soloActivos);
			if ($idNomina > 0) {
				$response['pago'] = $result;
				$response['err'] = empty($result);
			} else {
				$response['pagos'] = $result;
				$response['err'] = false;
			}

			$response['msg'] = !$response['err']
				? 'Información obtenida con éxito.'
				: 'Error al obtener la información solicitada.';
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
			$idUsuarioRegistro = $token->data->id;
			$idCondominio = $token->data->id_condominio_usuario;

			$data = $this->security->xss_clean($this->post());
			// Verificar que se haya enviado la información a insertar
			if (empty($data)) {
				$respuesta['msg'] = 'Información a procesar no ha sido proporcionada.';
				$this->response($respuesta, $responseCode);
			}

			$data = nulificar_elementos_arreglo(trim_elementos_arreglo($data));

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('nominaRegistrar')) {
				// Error al validar la información
				$respuesta['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$respuesta['msg'] .= $value . PHP_EOL;
				}
				$this->response($respuesta, $responseCode);
			}

			// Validar la información del movimiento al fondo monetario
			$dataMovimiento = [
				'fk_id_fondo_monetario' => !empty($data['id_fondo_monetario']) ? $data['id_fondo_monetario'] : null,
				'fk_id_tipo_movimiento' => 2,
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
				'id_condominio' => $idCondominio,
				'fk_id_usuario' => $data['id_colaborador'],
				'anio' => $data['anio'],
				'mes' => $data['mes'],
				'importe' => floatval($data['importe']),
				'fecha_pago' => $data['fecha_pago'],
				'fk_id_usuario_registro' => $idUsuarioRegistro,
				'movimiento' => $dataMovimiento,
			];

			$respuesta = $this->Nomina_model->insertar($data);
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
			$idCondominio = $token->data->id_condominio_usuario;

			// Verificar si se especifica el ID de un registro en particular
			$idNomina = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idNomina) || !intval($idNomina) || intval($idNomina) < 1) {
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

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('nominaRegistrar')) {
				// Error al validar la información
				$respuesta['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$respuesta['msg'] .= $value . PHP_EOL;
				}
				$this->response($respuesta, $responseCode);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'id_condominio' => $idCondominio,
				'id_colaborador_nomina' => $idNomina,
				'fk_id_usuario' => $data['id_colaborador'],
				'anio' => $data['anio'],
				'mes' => $data['mes'],
				'importe' => $data['importe'],
				'fecha_pago' => $data['fecha_pago'],
				'fk_id_usuario_modifico' => $idUsuarioModifico,
			];

			$respuesta = $this->Nomina_model->actualizar($data);
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

			// Verificar si se especifica el ID de un registro en particular
			$idNomina = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idNomina) || !intval($idNomina) || intval($idNomina) < 1) {
				$respuesta['msg'] = 'Debe especificar un identificador válido.';
				$this->response($respuesta, $responseCode);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'id_colaborador_nomina' => $idNomina,
				'id_usuario_modifico' => $idUsuarioModifico,
			];

			// Información validada con éxito. Procede a la inserción
			$response = $this->Nomina_model->eliminar($data);
			if (!$response['err']) {
				$responseCode = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}
}

/* End of file Nomina.php */
/* Location: ./application/controllers/Nomina.php */
