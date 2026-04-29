<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('getToken')) {
	function getToken()
	{
		$CI = &get_instance();
		$CI->load->library('Authorization_Token');
		$response = (object) [
			'error' => true,
			'msg' => null,
			'data' => null,
		];

		// Validar token
		if (VALIDATE_TOKEN) {
			$token = $CI->authorization_token->validateToken();
			if (empty($token) or $token['status'] !== true) {
				// Token no válido
				$response->msg = $token['message'];
			} else {
				// Token válido
				$response->error = false;
				$response->data = $token['data'];
			}
		} else {
			$response->error = false;
			$response->data = (object) [
				'id' => PRUEBAS_ID_USUARIO,
				'id_perfil_usuario' => PRUEBAS_ID_PERFIL_USUARIO,
				'id_condominio_usuario' => PRUEBAS_ID_CONDOMINIO_USUARIO,
			];
		}

		return $response;
	}
}
?>
