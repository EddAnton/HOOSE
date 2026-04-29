<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Controlador: Usuarios
 *
 * Este controlador realiza las operaciones relacionadas con los Usuarios
 *
 * @package   CodeIgniter
 * @category  Controller REST
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Usuarios extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Usuario_model');
		$this->load->model('Condominio_model');
		// $this->load->helper('File_Upload');
	}

	public function index()
	{
		$this->response(APP_NAME . ' API / Usuarios :: Controller');
	}

	// Validar inicio de sesión
	public function iniciar_sesion_post()
	{
		$response = [
			'err' => true,
			'msg' => null,
		];
		$responseCode = REST_Controller::HTTP_BAD_REQUEST;

		try {
			$data = $this->security->xss_clean($this->post());

			// Verificar que se haya enviado la información a insertar
			if (empty($data)) {
				$response['msg'] = 'Información a procesar no ha sido proporcionada.';
				$this->response($response, $responseCode);
			}

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('usuarioIniciarSesion')) {
				// Error al validar la información
				$response['msg'] = 'Existen errores en el envío de información.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$response['msg'] .= $value . PHP_EOL;
				}
				$this->response($response, $responseCode);
			}

			// Información validada con éxito. Procede a solicitar el inicio de sesión
			$data = [
				'usuario' => $data['usuario'],
				'contrasenia' => md5Valido($data['contrasenia']) ? $data['contrasenia'] : md5($data['contrasenia']),
				// 'contrasenia_flat' => $data['contrasenia'],
			];

			$response = $this->Usuario_model->iniciar_sesion($data);

			if (!$response['err']) {
				// Generar token
				// Cargar librería
				$this->load->library('Authorization_Token');

				// Constuir arreglo con la información para generar el token
				$dataTokenizer = [
					'id' => $response['usuario']->id_usuario,
					'id_perfil_usuario' => $response['usuario']->id_perfil_usuario,
					'id_condominio_usuario' => $response['usuario']->id_condominio_usuario,
				];
				$token = $this->authorization_token->generateToken($dataTokenizer);
				$decodedToken = $this->authorization_token->decodeToken($token);

				// Desmontar librería
				unset($this->authorization_token);

				$response['usuario']->token = $token;
				$response['usuario']->tokenExpire = $decodedToken->expire;

				$response['err'] = false;
				$response['msg'] = 'Inicio de sesión con éxito.';
				$responseCode = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}

		$this->response($response, $responseCode);
	}

	// Listar registros por perfil
	public function listar_perfil_get($soloActivos = false)
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
			// $idPerfilUsuario = $token->data->id_perfil_usuario;
			$idCondominio = $token->data->id_condominio_usuario;

			// Verificar si se especifica el ID de un registro en particular
			$idPerfilUsuario = $this->security->xss_clean($this->uri->segment(2));
			if (empty($idPerfilUsuario) || !intval($idPerfilUsuario) || intval($idPerfilUsuario) < 1) {
				$response['msg'] = 'Debe especificar un identificador válido.';
				$this->response($response, $responseCode);
			}

			// Obtener registros
			$response['usuarios'] = $this->Usuario_model->listar_perfil($idPerfilUsuario, $idCondominio, $soloActivos);

			$response['err'] = false;
			$response['msg'] = 'Información obtenida con éxito.';
			$responseCode = REST_Controller::HTTP_OK;
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}

	// Listar perfiles para tablero de avisos
	public function listar_perfiles_usuarios_tablero_avisos_get()
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

			// Obtener registros
			$response['perfiles_usuarios'] = $this->Usuario_model->listar_perfiles_usuarios_tablero_avisos();

			$response['err'] = false;
			$response['msg'] = 'Información obtenida con éxito.';
			$responseCode = REST_Controller::HTTP_OK;
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}

	// Listar propietarios y condominos
	public function listar_propietarios_condominos_get()
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

			// Obtener registros
			$propietarios = $this->Usuario_model->listar_perfil(PERFIL_USUARIO_PROPIETARIO, $idCondominio, true);
			$condominos = $this->Usuario_model->listar_perfil(PERFIL_USUARIO_CONDOMINO, $idCondominio, true);
			$response['usuarios'] = array_map(function ($e) {
				return [
					'id_usuario' => $e['id_usuario'],
					'nombre' => $e['nombre'],
				];
			}, array_merge($propietarios, $condominos));
			usort($response['usuarios'], fn($a, $b) => strcmp($a['nombre'], $b['nombre']));

			$response['err'] = false;
			$response['msg'] = 'Información obtenida con éxito.';
			$responseCode = REST_Controller::HTTP_OK;
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}

	// Listar propietarios y condominos
	public function listar_usuarios_notificaciones_get()
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

			// Obtener registros
			$propietarios = $this->Usuario_model->listar_perfil(PERFIL_USUARIO_PROPIETARIO, $idCondominio, true);
			$condominos = $this->Usuario_model->listar_perfil(PERFIL_USUARIO_CONDOMINO, $idCondominio, true);
			$colaboradores = $this->Usuario_model->listar_perfil(PERFIL_USUARIO_COLABORADOR, $idCondominio, true);
			$response['usuarios'] = array_map(function ($e) {
				return [
					'id_usuario' => $e['id_usuario'],
					'nombre' => $e['nombre'],
					'email' => $e['email'],
					'id_perfil_usuario' => $e['id_perfil_usuario'],
					'perfil_usuario' => $e['perfil_usuario'],
				];
			}, array_merge($propietarios, $condominos, $colaboradores));
			usort(
				$response['usuarios'],
				fn($a, $b) => strcmp($a['nombre'] . $a['id_perfil_usuario'], $b['nombre'] . $b['id_perfil_usuario'])
			);

			$response['err'] = false;
			$response['msg'] = 'Información obtenida con éxito.';
			$responseCode = REST_Controller::HTTP_OK;
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}

    // Listar usuarios para pase de lista y votaciones
  public function listar_usuarios_acta_asamblea_get()
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

      // Obtener registro(s)
      $response['usuarios'] = $this->Usuario_model->listar_usuarios_acta_asamblea($idCondominio);

      $response['err'] = $response['usuarios'] === false;
      $response['msg'] = !$response['err']
        ? 'Información obtenida con éxito.'
        : 'Error al obtener información. Verifique información proporcionada.';
      $responseCode = !$response['err'] ? REST_Controller::HTTP_OK : $responseCode;
    } catch (Exception $e) {
      $response['msg'] = $e->getMessage();
    }
    $this->response($response, $responseCode);
  }

	// Alternar estatus de activo del usuario
	public function alternar_estatus_post()
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
			$idUsuario = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idUsuario) || !intval($idUsuario) || intval($idUsuario) < 1) {
				$response['msg'] = 'Debe especificar un identificador válido.';
				$this->response($response, $responseCode);
			}

			$data = [
				'id_usuario' => $idUsuario,
				'fk_id_usuario_modifico' => $idUsuarioModifico,
			];

			// Actualización del estatus
			$response = $this->Usuario_model->alternar_estatus($data);
			if (!$response['err']) {
				$responseCode = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}

	// Cambiar contraseña del usuario
	public function cambiar_contrasenia_post()
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

			// Verificar que se especique el id de usuario
			$idUsuario = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idUsuario) || !intval($idUsuario) || intval($idUsuario) < 1) {
				$response['msg'] = 'Debe especificar un identificador válido.';
				$this->response($response, $responseCode);
			}

			$data = $this->security->xss_clean($this->post());

			// Verificar que se haya enviado la información a actualizar
			if (empty($data)) {
				$response['msg'] = 'Información a procesar no ha sido proporcionada.';
				$this->response($response, $responseCode);
			}

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('usuarioCambiarContrasenia')) {
				// Error al validar la información
				$response['msg'] = 'Existen errores en el envío de información.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$response['msg'] .= $value . PHP_EOL;
				}
				$this->response($response, $responseCode);
			}

			// Información validada con éxito. Procede a la inserción
			$data = [
				'id_usuario' => $idUsuario,
				'contrasenia_actual' => md5Valido($data['contrasenia_actual'])
					? $data['contrasenia_actual']
					: md5($data['contrasenia_actual']),
				'contrasenia_nueva' => md5Valido($data['contrasenia_nueva'])
					? $data['contrasenia_nueva']
					: md5($data['contrasenia_nueva']),
			];

			$response = $this->Usuario_model->cambiar_contrasenia($data);

			if (!$response['err']) {
				$responseCode = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}

	// Reiniciar contraseña del usuario
	public function reiniciar_contrasenia_post()
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

			// Verificar que se especique el id de usuario
			$idUsuario = $this->security->xss_clean($this->uri->segment(3));
			if (empty($idUsuario) || !intval($idUsuario) || intval($idUsuario) < 1) {
				$response['msg'] = 'Debe especificar un identificador válido.';
				$this->response($response, $responseCode);
			}

			// Información validada con éxito. Procede a la inserción
			$data = ['id_usuario' => $idUsuario];

			$response = $this->Usuario_model->cambiar_contrasenia($data);

			if (!$response['err']) {
				$responseCode = REST_Controller::HTTP_OK;
			}
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}

	// Actualizar Condominio del usuario
	public function seleccionar_condominio_post()
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
			/* $response['msg'] = $token->data;
			 $this->response($response, $responseCode); */
			$idUsuario = $token->data->id;
			$idPerfilUsuario = $token->data->id_perfil_usuario;
			$usuario = $token->data;

			// Verificar que el perfil del usuario pueda cambiar de condominio
			if (empty($idPerfilUsuario) || !intval($idPerfilUsuario) || intval($idPerfilUsuario) != 1) {
				$response['msg'] = 'Perfil sin privilegios.';
				$this->response($response, $responseCode);
			}

			$data = $this->security->xss_clean($this->post());
			// Verificar que se haya enviado requerida
			if (empty($data)) {
				$response['msg'] = 'Información a procesar no ha sido proporcionada.';
				$this->response($response, $responseCode);
			}

			// Validar la información
			$this->form_validation->set_data($data);
			if (!$this->form_validation->run('usuarioCambiarCondominio')) {
				// Error al validar la información
				$response['msg'] = 'Existen errores en el envío de información.' . PHP_EOL;
				foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
					$response['msg'] .= $value . PHP_EOL;
				}
				$this->response($response, $responseCode);
			}
			$idCondominio = $data['id_condominio'];

			// Validar que el condominio exista y se encuentre activo
			$condominio = $this->Condominio_model->listar($idCondominio, true);
			if (empty($condominio)) {
				$response['msg'] = 'Condominio no válido.';
				$response['idCondominio'] = $idCondominio;
				$response['condominio'] = $condominio;
				$this->response($response, $responseCode);
			}

			// Generar token
			// Cargar librería
			$this->load->library('Authorization_Token');
			// Constuir arreglo con la información para generar el token
			$dataTokenizer = [
				'id' => $idUsuario,
				'id_perfil_usuario' => $idPerfilUsuario,
				'id_condominio_usuario' => $idCondominio,
			];
			$token = $this->authorization_token->generateToken($dataTokenizer);
			$decodedToken = $this->authorization_token->decodeToken($token);
			// Desmontar librería
			unset($this->authorization_token);

			// Preparar los datos de respuesta
			$response['usuario'] = (object) [
				'id_condominio_usuario' => $idCondominio,
				'condominio_usuario' => $condominio['condominio'],
				'token' => $token,
				'tokenExpire' => $decodedToken->expire,
			];

			$response['err'] = false;
			$response['msg'] = 'Condominio actualizado con éxito.';
			$responseCode = REST_Controller::HTTP_OK;
		} catch (Exception $e) {
			$response['msg'] = $e->getMessage();
		}
		$this->response($response, $responseCode);
	}
}

/* End of file Usuario.php */
/* Location: ./application/controllers/Usuario.php */
