<?php
defined('BASEPATH') or exit('No direct script access allowed');

/* use PHPMailer\PHPMailer\PHPMailer;
 use PHPMailer\PHPMailer\SMTP; */

/**
 *
 * Controlador: Notificaciones
 *
 * Este controlador realiza las operaciones relacionadas con las Notificaciones
 *
 * @package   CodeIgniter
 * @category  Controller REST
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Notificaciones extends REST_Controller
{
	// private $mail = null;

	public function __construct()
	{
		parent::__construct();
		$this->load->model('Notificacion_model');
	}

	public function index()
	{
		$this->response(APP_NAME . ' API / Notificaciones :: Controller');
	}

	/* public function correo_prueba_get()
	{
		$response = [
			'err' => true,
			'msg' => null,
		];
		$codigo_respuesta = REST_Controller::HTTP_BAD_REQUEST;

		try {
			// $response['err'] = !$this->test_sendmail();
			$response['err'] = !$this->test_phpmailer();
			if (!$response['err']) {
				$response['msg'] = 'Mensaje enviado con éxito.';
				$codigo_respuesta = REST_Controller::HTTP_OK;
			} else {
				$response['msg'] =
					'Error al enviar el mensaje.' . ($this->mail != null ? PHP_EOL . $this->mail->ErrorInfo : '');
			}
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $codigo_respuesta);
	} */

	/* public function test_phpmailer()
	{
		try {
			$this->mail = null;
			$this->load->library('PHPMailer_Lib');
			$this->mail = $this->phpmailer_lib->load();

			// SMTP configuration
			// $this->mail->isSMTP();
			$this->mail->SMTPDebug = 0;
			$this->mail->SMTPAuth = true;
			$this->mail->SMTPSecure = 'ssl';
			$this->mail->Username = 'administracion@arboleda.pontevedra.mx';
			$this->mail->Password = '34Y3iB9.meZfttj4';
			$this->mail->Host = 'pontevedra.mx';
			$this->mail->Port = 465;
			$this->mail->isHTML(true);
			$this->mail->CharSet = 'UTF-8';
			$this->mail->Encoding = 'quoted-printable';

			$this->mail->SetFrom('administracion@arboleda.pontevedra.mx', 'Administración Arboleda');
			$this->mail->AddReplyTo('administracion@arboleda.pontevedra.mx', 'Administración Arboleda');
			$this->mail->AddAddress('cmalpicag@gmail.com', 'Carlos Malpica');
			$this->mail->Subject = 'Envío de email con PHPMailer en PHP';

			$body = "<h1>Send HTML Email using SMTP in CodeIgniter</h1>
            <p>This is a test email sending using SMTP mail server with PHPMailer.</p>";
			$this->mail->MsgHTML($body);

			return $this->mail->Send();
		} catch (Exception $e) {
			throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
		}
	} */

	/* public function test_sendmail()
	{
		try {
			// The message
			$message = "Line 1\r\nLine 2\r\nLine 3";
			// In case any of our lines are larger than 70 characters, we should use wordwrap()
			$message = wordwrap($message, 70, "\r\n");
			$headers =
				'From: webmaster@example.com' .
				"\r\n" .
				'Reply-To: webmaster@example.com' .
				"\r\n" .
				'X-Mailer: PHP/' .
				phpversion();
			// Send
			return mail('cmalpicag@gmail.com', 'My Subject', $message, $headers);
		} catch (Exception $e) {
			throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
		}
	} */

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

			// Obtener el ID del registro
			$idNotificacion = $this->security->xss_clean($this->uri->segment(2));
			$idNotificacion = !empty($idNotificacion) && intval($idNotificacion) ? intval($idNotificacion) : 0;

			// Obtener registro(s)
			$result = $this->Notificacion_model->listar($idNotificacion, $idCondominio, $soloActivos);
			if (!empty($idNotificacion)) {
				$response['notificacion'] = $result;
			} else {
				$response['notificaciones'] = $result;
			}

			$response['err'] = false;
			$response['msg'] = 'Información obtenida con éxito.';
			$responseCode = REST_Controller::HTTP_OK;
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}

	// Listar registro a detalle
	public function listar_detalle_get()
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

			// Verificar si se especifica el ID del registro
			$idNotificacion = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idNotificacion) || !intval($idNotificacion) || intval($idNotificacion) < 1) {
				$respuesta['msg'] = 'Debe especificar un identificador válido.';
				$this->response($respuesta, $codigo_respuesta);
			}

			// Obtener registro(s)
			$response['notificacion'] = $this->Notificacion_model->listar_detalle($idNotificacion);
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
			'err' => 1,
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

			$data = $this->post();
			// Verificar que se haya enviado la información a insertar
			if (empty($data)) {
				$respuesta['msg'] = 'Información a procesar no ha sido proporcionada.';
				$this->response($respuesta, $codigo_respuesta);
			}

			// Si se incluye mensaje, extraerlo de la data y aplicar filtro de seguridad a la misma
			$mensaje = !empty($data['mensaje']) ? $data['mensaje'] : null;
			$enviar = !empty($data['enviar']) && intval($data['enviar']) ? intval($data['enviar']) == 1 : false;
			unset($data['enviar']);
			unset($data['mensaje']);

			$data = $this->security->xss_clean($data);
			$data = nulificar_elementos_arreglo(trim_elementos_arreglo($data));
			// $data = capitalizar_arreglo($data, ['asunto']);
			$data['mensaje'] = $mensaje;

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('notificacionInsertar')) {
				// Error al validar la información
				$respuesta['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$respuesta['msg'] .= $value . PHP_EOL;
				}
				$this->response($respuesta, $codigo_respuesta);
			}

			/* $destinatarios = [];
			foreach ($data['destinatarios'] as $destinatario) {
				$destinatarios[] = ['fk_id_usuario' => $destinatario];
			} */

			// Información validada con éxito. Procede a la inserción
			$data = [
				'fk_id_condominio' => $idCondominio,
				'asunto' => $data['asunto'],
				'mensaje' => $data['mensaje'],
				// 'destinatarios' => $destinatarios,
				'destinatarios' => $data['destinatarios'],
				'fk_id_usuario_registro' => $idUsuarioRegistro,
				'enviar' => $enviar,
			];

			$respuesta = $this->Notificacion_model->insertar($data);
			if ($respuesta['err'] != 1) {
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
			'err' => 1,
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
			$idNotificacion = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idNotificacion) || !intval($idNotificacion) || intval($idNotificacion) < 1) {
				$respuesta['msg'] = 'Debe especificar un identificador válido.';
				$this->response($respuesta, $codigo_respuesta);
			}

			$data = $this->post();
			// Verificar que se haya enviado la información a actualizar
			if (empty($data)) {
				$respuesta['msg'] = 'Información a procesar no ha sido proporcionada.';
				$this->response($respuesta, $codigo_respuesta);
			}

			// Si se incluye mensaje, extraerlo de la data y aplicar filtro de seguridad a la misma
			$mensaje = !empty($data['mensaje']) ? $data['mensaje'] : null;
			$enviar = !empty($data['enviar']) && intval($data['enviar']) ? intval($data['enviar']) == 1 : false;
			unset($data['enviar']);
			unset($data['mensaje']);

			$data = $this->security->xss_clean($data);
			$data = nulificar_elementos_arreglo(trim_elementos_arreglo($data));
			// $data = capitalizar_arreglo($data, ['titulo']);
			$data['mensaje'] = $mensaje;

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('notificacionInsertar')) {
				// Error al validar la información
				$respuesta['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$respuesta['msg'] .= $value . PHP_EOL;
				}
				$this->response($respuesta, $codigo_respuesta);
			}
			/* $destinatarios = [];
			foreach ($destinatario as $data['destinatarios']) {
				$destinatarios[] = ['fk_id_usuario' => $destinatario];
			} */

			// Información validada con éxito. Procede a la inserción
			$data = [
				'id_notificacion' => $idNotificacion,
				'asunto' => $data['asunto'],
				'mensaje' => $data['mensaje'],
				// 'destinatarios' => $destinatarios,
				'destinatarios' => $data['destinatarios'],
				'fk_id_usuario_modifico' => $idUsuarioModifico,
				'enviar' => $enviar,
			];

			$respuesta = $this->Notificacion_model->actualizar($data);
			if ($respuesta['err'] != 1) {
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
			$idNotificacion = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idNotificacion) || !intval($idNotificacion) || intval($idNotificacion) < 1) {
				$response['msg'] = 'Debe especificar un identificador válido.';
				$this->response($response, $responseCode);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'id_notificacion' => $idNotificacion,
				'id_usuario_modifico' => $idUsuarioModifico,
			];

			// Información validada con éxito. Procede a la inserción
			$response = $this->Notificacion_model->eliminar($data);
			if (!$response['err']) {
				$responseCode = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}

	// Enviar notificacion
	public function enviar_post()
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
			$idUsuarioEnvia = $token->data->id;

			// Verificar que se especique el ID del registro a actualizar
			$idNotificacion = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idNotificacion) || !intval($idNotificacion) || intval($idNotificacion) < 1) {
				$response['msg'] = 'Debe especificar un identificador válido.';
				$this->response($response, $responseCode);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'id_notificacion' => $idNotificacion,
				'id_usuario_envia' => $idUsuarioEnvia,
			];

			// Información validada con éxito. Procede a la inserción
			$response = $this->Notificacion_model->enviar($data);
			if (!$response['err']) {
				$responseCode = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}
}

/* End of file Notificaciones.php */
/* Location: ./application/controllers/Notificaciones.php */
