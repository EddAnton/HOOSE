<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Metricas extends REST_Controller
{
  public function __construct()
  {
    parent::__construct();
    $this->load->model('Metricas_model');
  }

  public function tablero_get()
  {
    $respuesta = ['err' => true, 'msg' => null];
    $codigo = REST_Controller::HTTP_BAD_REQUEST;
    try {
      $token = getToken();
      if ($token->error) { $this->response($token, $codigo); return; }

      $idCondominio = $token->data->id_condominio_usuario;
      $comparativo = $this->get('comparativo') ?? 'mes_anterior';

      $respuesta['data'] = $this->Metricas_model->tablero($idCondominio, $comparativo);
      $respuesta['msg'] = 'Información obtenida con éxito.';
      $respuesta['err'] = false;
      $codigo = REST_Controller::HTTP_OK;
    } catch (Exception $e) {
      $respuesta['msg'] = $e->getMessage();
    }
    $this->response($respuesta, $codigo);
  }
}
