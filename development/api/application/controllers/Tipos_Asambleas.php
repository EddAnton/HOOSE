<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Controlador: Tipos_Asambleas
 *
 * Este controlador realiza las operaciones relacionadas con los Tipos de asmableas
 *
 * @package   CodeIgniter
 * @category  Controller REST
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Tipos_Asambleas extends REST_Controller
{
  public function __construct()
  {
    parent::__construct();
    $this->load->model('Tipo_Asamblea_model');
  }

  public function index()
  {
    $this->response(APP_NAME . ' API / Tipos de Asambleas :: Controller');
  }

  // Listar registro(s)
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

      $response['tipos_asambleas'] = $this->Tipo_Asamblea_model->listar($soloActivos);

      $response['error'] = false;
      $response['msg'] = 'Información obtenida con éxito.';
      $responseCode = REST_Controller::HTTP_OK;
    } catch (Exception $e) {
      $response['msg'] = $e->getMessage();
    }
    $this->response($response, $responseCode);
  }
}

/* End of file Tipos_Asambleas.php */
/* Location: ./application/controllers/Tipos_Asambleas.php */
