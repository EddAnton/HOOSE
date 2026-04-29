<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Modelo: Notificacion
 *
 * Este modelo realiza las operaciones requeridas sobre la información de las Notificaciones
 *
 * @package   CodeIgniter
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Notificacion_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	/*
    Obtener información del identificador especificado o todos los registros.
      $id => ID especifico a obtener, si se requiere
      $soloActivos => Determinar si se requieren todos los registros o sólo los activos
	*/
	public function listar($id = 0, $idCondominio = 0, $soloActivos = false)
	{
		try {
			$this->db
				->select(
					'n.id_notificacion,
            n.fecha_registro fecha,
            n.asunto,
            n.enviado,
            n.fecha_enviado,
            ue.nombre usuario_envio,
            n.estatus'
				)
				->join('usuarios ue', 'ue.id_usuario = n.fk_id_usuario_envio', 'left');

			if ($id > 0) {
				$this->db->where(['n.id_notificacion' => $id]);
			} else {
				$this->db->where(['n.fk_id_condominio' => $idCondominio]);
			}
			if ($soloActivos) {
				$this->db->where(['n.estatus' => 1]);
			}

			$respuesta = $this->db->order_by('n.enviado DESC, n.fecha_enviado DESC')->get('notificaciones n');
			$respuesta = $id > 0 ? $respuesta->row_array() : $respuesta->result_array();

			return $respuesta;
		} catch (Exception $e) {
			throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
			die();
		}
	}

	/*
    Obtener información a detalle de la notificación
      $id => ID de la notificacion
	*/
	public function listar_detalle($id = 0)
	{
		try {
			$respuesta = $this->db
				->select(
					'n.id_notificacion,
            n.fecha_registro fecha,
            n.asunto,
            n.mensaje,
            n.enviado,
            n.fecha_enviado,
            ue.nombre usuario_envio,
            n.estatus'
				)
				->join('usuarios ue', 'ue.id_usuario = n.fk_id_usuario_envio', 'left')
				->get_where('notificaciones n', ['n.id_notificacion' => $id, 'n.estatus' => 1])
				->row_array();

			if (!empty($respuesta)) {
				$respuesta['destinatarios'] = $this->db
					->select(
						'u.id_usuario,
            u.nombre,
            u.email,
            pu.id_perfil_usuario,
            pu.perfil_usuario'
					)
					->join('usuarios u', 'u.id_usuario = d.fk_id_usuario')
					->join('cat_perfiles_usuarios pu', 'pu.id_perfil_usuario = u.fk_id_perfil_usuario')
					->order_by('u.nombre')
					->get_where('notificaciones_destinatarios d', ['d.fk_id_notificacion' => $id])
					->result_array();
			}
			return $respuesta;
		} catch (Exception $e) {
			throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
			die();
		}
	}

	/*
    Insertar registro
      $data => Información a insertar
	*/
	public function insertar($data)
	{
		$respuesta = [
			'err' => 1,
			'msg' => null,
		];

		try {
			// Validar que la información a insertar sea proporcionada
			if (empty($data)) {
				$respuesta['msg'] = 'Debe especificar la información a insertar.';
				return $respuesta;
			}

			$destinatarios = $data['destinatarios'];
			$enviar = $data['enviar'];
			unset($data['enviar']);
			unset($data['destinatarios']);

			/* $dataNotificacionesDestinatarios = [];
			foreach ($destinatarios as $destinatario) {
				$dataNotificacionesDestinatarios[] = [
					'fk_id_usuario' => $destinatario,
				];
			} */

			/* print_r($data);
			 echo PHP_EOL; */

			// Obtener destinatarios activos
			$dataNotificacionesDestinatarios = $this->db
				->select(
					'id_usuario fk_id_usuario,
            email'
				)
				->where_in('id_usuario', $destinatarios)
				->get_where('usuarios', ['estatus' => 1])
				->result_array();

			// Filtrar sólo destinatarios con email válido
			$dataNotificacionesDestinatarios = borrar_columna_arreglo(
				array_filter($dataNotificacionesDestinatarios, function ($r) {
					return emailValido($r['email']);
				}),
				'email'
			);
			/* 	print_r($dataNotificacionesDestinatarios);
			 exit(); */

			// Validar que los campos existan en la tabla notificaciones
			if (!validar_campos('notificaciones', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con la base de datos.';
				return $respuesta;
			}

			// Validar que los campos existan en la tabla notificaciones_destinatarios
			/* foreach ($dataNotificacionesDestinatarios as $destinatario) {
				if (!validar_campos('notificaciones_destinatarios', $destinatario)) {
					$respuesta['msg'] = 'Error de integridad de la información con la base de datos (Destinatarios).';
					return $respuesta;
				}
			} */

			// Almacenar información en BD
			// Inicializar la transaccion
			$this->db->trans_start();
			$this->db->trans_strict(false);

			// Insertar registro en notificaciones
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if (!$this->db->insert('notificaciones', $data)) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				$this->db->trans_rollback();
				return $respuesta;
			}

			// Obtener nuevo ID del registro
			$nuevoID = $this->db->insert_id();

			// Agregar ID de la notificación al arreglo con los destinatarios
			$dataNotificacionesDestinatarios = agregar_columnas_arreglo($dataNotificacionesDestinatarios, [
				'fk_id_notificacion' => $nuevoID,
			]);

			// Insertar registros en notificaciones_destinatarios
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if (!$this->db->insert_batch('notificaciones_destinatarios', $dataNotificacionesDestinatarios)) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				$this->db->trans_rollback();
				return $respuesta;
			}

			// Confirmar la transacción
			$this->db->trans_complete();
			// Confirmar la transacción y verificar el estatus de la misma
			if ($this->db->trans_status() === false) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $respuesta;
			} else {
				$respuesta['msg'] = 'Información almacenada ';
			}
			$respuesta['err'] = 2;

			// Si se especificó enviar también la notificación
			if ($enviar) {
				// Enviar la notificación
				$dataEnvio = [
					'id_notificacion' => $nuevoID,
				];
				$respuestaEnvioCorreo = $this->enviar($dataEnvio);

				// El envío fue satisfactorio
				if (!$respuestaEnvioCorreo['err']) {
					$respuesta['msg'] .= 'y correo enviado con éxito.';

					// Actualizar estatus del envío de la notificación
					$dataActualizarEnvio = [
						'enviado' => 1,
						'fk_id_usuario_envio' => $data['fk_id_usuario_registro'],
						'fecha_enviado' => date('Y-m-d H:i:s'),
					];

					// Actualizar registro de la notificación
					$errorActualizarEnvio = !$this->db->update('notificaciones', $dataActualizarEnvio, [
						'id_notificacion' => $nuevoID,
					]);
					// Si existe error al actualizar el estatus, obtiene descripción del mismo y aborta el proceso
					if ($errorActualizarEnvio) {
						$respuesta['msg'] .=
							PHP_EOL .
							'Pero ocurrió un error al actualizar el estatus del envío:' .
							PHP_EOL .
							'  ' .
							$this->db->error()['code'] .
							' - ' .
							$this->db->error()['message'];
					} else {
						$respuesta['err'] = 0;
					}
					// Falló el envío de la notificación
				} else {
					$respuesta['msg'] .=
						'con éxito pero no se pudo enviar el correo de la notificación.' . PHP_EOL . $respuestaEnvioCorreo['MSG'];
				}
				// No se solicitó enviar la notificación
			} else {
				$respuesta['err'] = 0;
			}

			$respuesta['notificacion'] = $this->listar($nuevoID);
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
			die();
		}
		return $respuesta;
	}

	/*
    Actualizar registro
      $data => Información a insertar
	*/
	public function actualizar($data)
	{
		$respuesta = [
			'err' => 1,
			'msg' => null,
		];

		try {
			// Validar que la información a insertar sea proporcionada
			if (empty($data)) {
				$respuesta['msg'] = 'Debe especificar la información a insertar.';
				return $respuesta;
			}

			$idNotificacion = $data['id_notificacion'];
			$destinatarios = $data['destinatarios'];
			$enviar = $data['enviar'];
			unset($data['enviar']);
			unset($data['destinatarios']);

			// Verificar cuantos registros serán actualizados
			$notificacion = $this->db->get_where('notificaciones', [
				'id_notificacion' => $idNotificacion,
				'estatus' => 1,
			]);

			if ($notificacion->num_rows() != 1) {
				if ($notificacion->num_rows() == 0) {
					$respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
				} elseif ($notificacion->num_rows() > 1) {
					$respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
				}
				return $respuesta;
			}
			$notificacion = $notificacion->row_array();

			if ($notificacion['enviado'] == 1) {
				$respuesta['msg'] = 'La notificación ya fue enviada con anterioridad, imposible modificar.';
				return $respuesta;
			}

			// Obtener destinatarios activos
			$dataNotificacionesDestinatarios = $this->db
				->select(
					'id_usuario fk_id_usuario,
            email'
				)
				->where_in('id_usuario', $destinatarios)
				->get_where('usuarios', ['estatus' => 1])
				->result_array();

			// Filtrar sólo destinatarios con email válido y agregar el ID de la notificación
			$dataNotificacionesDestinatarios = agregar_columnas_arreglo(
				borrar_columna_arreglo(
					array_filter($dataNotificacionesDestinatarios, function ($r) {
						return emailValido($r['email']);
					}),
					'email'
				),
				[
					'fk_id_notificacion' => $idNotificacion,
				]
			);

			// Validar que los campos existan en la tabla notificaciones
			if (!validar_campos('notificaciones', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con la base de datos.';
				return $respuesta;
			}

			// Almacenar información en BD
			// Inicializar la transaccion
			$this->db->trans_start();
			$this->db->trans_strict(false);

			// Borrar los destinatarios existentes
			// Si existe error al borrar los registros, obtiene descripción del mismo y aborta el proceso
			if (!$this->db->delete('notificaciones_destinatarios', ['fk_id_notificacion' => $idNotificacion])) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				$this->db->trans_rollback();
				return $respuesta;
			}

			// Actualizar registro en notificaciones
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if (!$this->db->update('notificaciones', $data, ['id_notificacion' => $idNotificacion])) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				$this->db->trans_rollback();
				return $respuesta;
			}

			// Insertar registros en notificaciones_destinatarios
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if (!$this->db->insert_batch('notificaciones_destinatarios', $dataNotificacionesDestinatarios)) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				$this->db->trans_rollback();
				return $respuesta;
			}

			// Confirmar la transacción
			$this->db->trans_complete();
			// Confirmar la transacción y verificar el estatus de la misma
			if ($this->db->trans_status() === false) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $respuesta;
			} else {
				$respuesta['msg'] = 'Información almacenada ';
			}
			$respuesta['err'] = 2;

			// Si se especificó enviar también la notificación
			if ($enviar) {
				// Enviar la notificación
				$dataEnvio = [
					'id_notificacion' => $idNotificacion,
				];
				$respuestaEnvioCorreo = $this->enviar($dataEnvio);

				// El envío fue satisfactorio
				if (!$respuestaEnvioCorreo['err']) {
					$respuesta['msg'] .= 'y correo enviado con éxito.';

					// Actualizar estatus del envío de la notificación
					$dataActualizarEnvio = [
						'enviado' => 1,
						'fk_id_usuario_envio' => $data['fk_id_usuario_modifico'],
						'fecha_enviado' => date('Y-m-d H:i:s'),
					];

					// Actualizar registro de la notificación
					$errorActualizarEnvio = !$this->db->update('notificaciones', $dataActualizarEnvio, [
						'id_notificacion' => $idNotificacion,
					]);
					// Si existe error al actualizar el estatus, obtiene descripción del mismo y aborta el proceso
					if ($errorActualizarEnvio) {
						$respuesta['msg'] .=
							PHP_EOL .
							'Pero ocurrió un error al actualizar el estatus del envío:' .
							PHP_EOL .
							'  ' .
							$this->db->error()['code'] .
							' - ' .
							$this->db->error()['message'];
					} else {
						$respuesta['err'] = 0;
					}
					// Falló el envío de la notificación
				} else {
					$respuesta['msg'] .=
						'con éxito pero no se pudo enviar el correo de la notificación.' . PHP_EOL . $respuestaEnvioCorreo['msg'];
				}
				// NO se solicitó enviar la notificación
			} else {
				$respuesta['err'] = 0;
			}

			$respuesta['notificacion'] = $this->listar($idNotificacion);
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
			die();
		}
		return $respuesta;
	}

	/*
    Eliminar logicamente un registro
      $data => Información a procesar
	*/
	public function eliminar($data)
	{
		$respuesta = [
			'err' => true,
			'msg' => null,
		];

		try {
			// Validar que la información a procesar sea proporcionada
			if (empty($data)) {
				$respuesta['msg'] = 'Debe especificar la información a procesar.';
				return $respuesta;
			}

			// Obtener ID del registro a actualizar
			$idNotificacion = $data['id_notificacion'];

			// Verificar cuantos registros serán actualizados
			$notificacion = $this->db->get_where('notificaciones', [
				'id_notificacion' => $idNotificacion,
				'estatus' => 1,
			]);

			if ($notificacion->num_rows() != 1) {
				if ($notificacion->num_rows() == 0) {
					$respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
				} elseif ($notificacion->num_rows() > 1) {
					$respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
				}
				return $respuesta;
			}
			$notificacion = $notificacion->row_array();

			if ($notificacion['enviado'] == 1) {
				$respuesta['msg'] = 'La notificación ya fue enviada con anterioridad, imposible eliminar.';
				return $respuesta;
			}

			// Establecer la información que se actualizará
			$data = [
				'estatus' => 0,
				'fk_id_usuario_modifico' => $data['id_usuario_modifico'],
				'fecha_modificacion' => date('Y-m-d H:i:s'),
			];

			// Validar que los campos existan en la tabla usuarios
			if (!validar_campos('notificaciones', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con respecto a la base de datos.';
				return $respuesta;
			}

			// Almacenar información en BD
			// Actualizar registro
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if (!$this->db->update('notificaciones', $data, ['id_notificacion' => $idNotificacion])) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $respuesta;
			}

			$respuesta['msg'] = 'Notificación eliminada con éxito.';
			$respuesta['err'] = false;
		} catch (Exception $e) {
			throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
			die();
		}
		return $respuesta;
	}

	/*
    Enviar notificación por correo
      $data => Información a procesar
  */
	public function enviar($data = null)
	{
		$respuesta = [
			'err' => true,
			'msg' => null,
		];

		try {
			$mail = null;

			if (empty($data)) {
				$respuesta['msg'] = 'No se especificó la información a procesar.';
				return $respuesta;
			}

			$idNotificacion = $data['id_notificacion'];
			$idUsuarioEnvia = !empty($data['id_usuario_envia']) ? $data['id_usuario_envia'] : null;

			// Obtener el mensaje a enviar
			$notificacion = $this->db
				->select('asunto, mensaje, enviado, estatus')
				->get_where('notificaciones', ['id_notificacion' => $idNotificacion])
				->row_array();
			if (empty($notificacion)) {
				$respuesta['msg'] = 'La notificación no existe.';
				return $respuesta;
			}
			if ($notificacion['estatus'] == 0) {
				$respuesta['msg'] = 'La notificación se encuentra eliminada.';
				return $respuesta;
			}
			/* if ($notificacion['enviado'] == 1) {
				$respuesta['msg'] = 'La notificación ya fue enviada con anterioridad.';
				return $respuesta;
			} */

			// Obtener los parámetros para el envío del correo
			$dataMail = $this->db
				->where('opcion LIKE "mail_%"')
				->get('opciones_generales')
				->result_array();

			// Determinar el servidor de correo
			$mailHost = array_values(
				array_filter($dataMail, function ($var) {
					return $var['opcion'] == 'mail_host';
				})
			);
			$mailHost = count($mailHost) > 0 ? $mailHost[0]['valor'] : null;

			// Determinar el puerto del servidor de correo
			$mailPort = array_values(
				array_filter($dataMail, function ($var) {
					return $var['opcion'] == 'mail_port';
				})
			);
			$mailPort = count($mailPort) > 0 ? $mailPort[0]['valor'] : null;

			// Determinar la cuenta de correo del remitente
			$mailUser = array_values(
				array_filter($dataMail, function ($var) {
					return $var['opcion'] == 'mail_user';
				})
			);
			$mailUser = count($mailUser) > 0 ? $mailUser[0]['valor'] : null;

			// Determinar el nombre de la cuenta de correo del remitente
			$mailFromName = array_values(
				array_filter($dataMail, function ($var) {
					return $var['opcion'] == 'mail_from_name';
				})
			);
			$mailFromName = count($mailFromName) > 0 ? $mailFromName[0]['valor'] : APP_FULLNAME;

			// Determinar la contraseña de la cuenta de correo del remitente
			$mailPassword = array_values(
				array_filter($dataMail, function ($var) {
					return $var['opcion'] == 'mail_password';
				})
			);
			$mailPassword = count($mailPassword) > 0 ? $mailPassword[0]['valor'] : null;

			if (empty($mailHost) || empty($mailPort) || empty($mailUser) || empty($mailPassword)) {
				$respuesta['msg'] = 'Falta algún parámetro para el envío del correo.';
				return $respuesta;
			}

			// Obtener los destinatarios
			$destinatarios = $this->db
				->select(
					'u.email,
            u.nombre'
				)
				->join('usuarios u', 'u.id_usuario = d.fk_id_usuario')
				->order_by('u.nombre')
				->get_where('notificaciones_destinatarios d', ['d.fk_id_notificacion' => $idNotificacion])
				->result_array();

			$this->load->library('PHPMailer_Lib');
			$mail = $this->phpmailer_lib->load();

			if (empty($mail)) {
				$respuesta['msg'] = 'No se pudo inicializar el manejador de correo.';
				return $respuesta;
			}

			// Configuración inicial para el envio del correo
			// $mail->isSMTP();
			$mail->SMTPDebug = 0;
			$mail->SMTPAuth = true;
			$mail->SMTPSecure = 'ssl';
			$mail->isHTML(true);
			$mail->CharSet = 'UTF-8';
			$mail->Encoding = 'quoted-printable';

			// Establecer parámetros de conexión al servidor de correo
			$mail->Username = $mailUser;
			$mail->Password = $mailPassword;
			$mail->Host = $mailHost;
			$mail->Port = $mailPort;

			// Establecer quien envía el correo
			$mail->SetFrom($mailUser, $mailFromName);
			$mail->AddReplyTo($mailUser, $mailFromName);

			// Limpiar todos los destinatarios
			$mail->clearAllRecipients();
			// Establecer destinatarios
			foreach ($destinatarios as $destinatario) {
				$mail->AddAddress($destinatario['email'], $destinatario['nombre']);
			}

			// Establecer el asunto
			$mail->Subject = !empty($notificacion['asunto']) ? $notificacion['asunto'] : 'Notificación de ' . $mailFromName;
			$mail->MsgHTML($notificacion['mensaje']);

			$respuesta['err'] = !$mail->Send();
			if ($respuesta['err']) {
				$respuesta['msg'] = $mail->ErrorInfo;
			} else {
				$respuesta['msg'] = 'Notificación enviada con éxito';
				// Si se especificó usuario que envia la notificación
				if (!empty($idUsuarioEnvia)) {
					// Actualizar estatus del envío de la notificación
					$dataActualizarEnvio = [
						'enviado' => 1,
						'fk_id_usuario_envio' => $idUsuarioEnvia,
						'fecha_enviado' => date('Y-m-d H:i:s'),
					];

					// Actualizar registro de la notificación
					$errorActualizarEnvio = !$this->db->update('notificaciones', $dataActualizarEnvio, [
						'id_notificacion' => $idNotificacion,
					]);
					if ($errorActualizarEnvio) {
						$respuesta['msg'] .=
							PHP_EOL .
							', pero ocurrió un error al actualizar el estatus del envío:' .
							PHP_EOL .
							'  ' .
							$this->db->error()['code'] .
							' - ' .
							$this->db->error()['message'];
					} else {
						$respuesta['msg'] .= '.';
					}
					$respuesta['notificacion'] = $this->listar($idNotificacion);
				}
			}

			return $respuesta;
		} catch (Exception $e) {
			throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
		}
	}
}

/* End of file Notificacion_model.php */
/* Location: ./application/models/Notificacion_model.php */
