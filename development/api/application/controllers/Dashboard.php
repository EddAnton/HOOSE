<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Controlador: Dashboard
 *
 * Este controlador realiza las operaciones relacionadas con el Dashboard
 *
 * @package   CodeIgniter
 * @category  Controller REST
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Dashboard extends REST_Controller
{
  public function __construct()
  {
    parent::__construct();
    $this->load->model('Dashboard_model');
  }

  public function index()
  {
    $this->response(APP_NAME . ' API / Dashboard :: Controller');
  }

  // Listar registro(s)
  public function listar_post()
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
        $this->response($token, $codigo_respuesta);
      }
      $dataUsuario = [
        'idCondominio' => $token->data->id_condominio_usuario,
        'idUsuario' => $token->data->id,
        'idPerfilUsuario' => $token->data->id_perfil_usuario,
      ];


      // Verificar que se haya enviado la información a insertar
      $data = $this->post();
      if (empty($data) || empty($data['anios']) || empty($data['meses'])) {
        $respuesta['msg'] = 'Información a procesar no ha sido proporcionada.';
        $this->response($respuesta, $codigo_respuesta);
      }

      // Obtener información
      /* $anios = $data['anios'];
      $meses = $data['meses']; */
      // $meses = 2;
      /* $anios = 2024;
      $meses = [2, 7];
      $meses = 2; */

      // Obtener información
      $respuesta['data'] = $this->Dashboard_model->listar($dataUsuario, $data['anios'], $data['meses']);
      $respuesta['msg'] = 'Información obtenida con éxito.';
      $respuesta['err'] = false;
      $codigo_respuesta = REST_Controller::HTTP_OK;
    } catch (Exception $e) {
      $respuesta['msg'] = $e->getMessage();
    }
    $this->response($respuesta, $codigo_respuesta);
  }

  // Data gráfico Por Tipo de Ingreso
  public function grpTmp_get()
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
        $this->response($token, $codigo_respuesta);
      }
      $idCondominio = $token->data->id_condominio_usuario;

      // Obtener información
      $anios = 2023;
      $meses = [4, 7];
      // $meses = 2;
      // $respuesta['data'] = $this->Dashboard_model->grpIngresosPorTipo($idCondominio, $anios, $meses);
      // $respuesta['data'] = $this->Dashboard_model->grpEgresosPorTipo($idCondominio, $anios, $meses);
      // $respuesta['data'] = $this->Dashboard_model->grpFondosMonetarios($idCondominio);
      $respuesta['data'] = $this->Dashboard_model->grpVisitas($idCondominio, $anios, $meses);
      $respuesta['msg'] = 'Información obtenida con éxito.';
      $respuesta['err'] = false;
      $codigo_respuesta = REST_Controller::HTTP_OK;
    } catch (Exception $e) {
      $respuesta['msg'] = $e->getMessage();
    }
    $this->response($respuesta, $codigo_respuesta);
  }
}

/* End of file Dashboard.php */
/* Location: ./application/controllers/Dashboard.php */
