<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Layout extends REST_Controller
{
  public function __construct()
  {
    parent::__construct();
    $this->load->model('Layout_model');
  }

  public function tablero_get()
  {
    $respuesta = ['err' => true, 'msg' => null];
    try {
      $token = getToken();
      if ($token->error) { $this->response($respuesta, REST_Controller::HTTP_BAD_REQUEST); return; }
      $respuesta['data'] = $this->Layout_model->getLayout($token->data->id_usuario);
      $respuesta['err'] = false;
      $respuesta['msg'] = 'OK';
      $this->response($respuesta, REST_Controller::HTTP_OK);
    } catch (Exception $e) {
      $respuesta['msg'] = $e->getMessage();
      $this->response($respuesta, REST_Controller::HTTP_BAD_REQUEST);
    }
  }

  public function tablero_post()
  {
    $respuesta = ['err' => true, 'msg' => null];
    try {
      $token = getToken();
      if ($token->error) { $this->response($respuesta, REST_Controller::HTTP_BAD_REQUEST); return; }
      $secciones = $this->post('secciones');
      $this->Layout_model->saveLayout($token->data->id_usuario, $secciones);
      $respuesta['err'] = false;
      $respuesta['msg'] = 'Layout guardado';
      $this->response($respuesta, REST_Controller::HTTP_OK);
    } catch (Exception $e) {
      $respuesta['msg'] = $e->getMessage();
      $this->response($respuesta, REST_Controller::HTTP_BAD_REQUEST);
    }
  }
}
