<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Tareas extends REST_Controller
{
  public function __construct()
  {
    parent::__construct();
    $this->load->model('Tareas_model');
  }

  public function index()
  {
    $this->response(APP_NAME . ' API / Tareas :: Controller');
  }

  public function listar_get()
  {
    $respuesta = ['err' => true, 'msg' => null];
    $codigo_respuesta = REST_Controller::HTTP_BAD_REQUEST;
    try {
      $token = getToken();
      if ($token->error) { $this->response($token, $codigo_respuesta); }
      $dataUsuario = [
        'idCondominio'    => $token->data->id_condominio_usuario,
        'idUsuario'       => $token->data->id,
        'idPerfilUsuario' => $token->data->id_perfil_usuario,
      ];
      $id = $this->uri->segment(2);
      $respuesta['data'] = $this->Tareas_model->listar($dataUsuario, $id);
      $respuesta['msg'] = 'Información obtenida con éxito.';
      $respuesta['err'] = false;
      $codigo_respuesta = REST_Controller::HTTP_OK;
    } catch (Exception $e) {
      $respuesta['msg'] = $e->getMessage();
    }
    $this->response($respuesta, $codigo_respuesta);
  }

  public function insertar_post()
  {
    $respuesta = ['err' => true, 'msg' => null];
    $codigo_respuesta = REST_Controller::HTTP_BAD_REQUEST;
    try {
      $token = getToken();
      if ($token->error) { $this->response($token, $codigo_respuesta); }
      $dataUsuario = [
        'idCondominio'    => $token->data->id_condominio_usuario,
        'idUsuario'       => $token->data->id,
        'idPerfilUsuario' => $token->data->id_perfil_usuario,
      ];
      $data = $this->post();
      if (empty($data)) {
        $respuesta['msg'] = 'Información no proporcionada.';
        $this->response($respuesta, $codigo_respuesta);
      }
      $respuesta['data'] = $this->Tareas_model->insertar($dataUsuario, $data);
      $respuesta['msg'] = 'Tarea creada con éxito.';
      $respuesta['err'] = false;
      $codigo_respuesta = REST_Controller::HTTP_OK;
    } catch (Exception $e) {
      $respuesta['msg'] = $e->getMessage();
    }
    $this->response($respuesta, $codigo_respuesta);
  }

  public function actualizar_post()
  {
    $respuesta = ['err' => true, 'msg' => null];
    $codigo_respuesta = REST_Controller::HTTP_BAD_REQUEST;
    try {
      $token = getToken();
      if ($token->error) { $this->response($token, $codigo_respuesta); }
      $dataUsuario = [
        'idCondominio'    => $token->data->id_condominio_usuario,
        'idUsuario'       => $token->data->id,
        'idPerfilUsuario' => $token->data->id_perfil_usuario,
      ];
      $id = $this->uri->segment(3);
      $data = $this->post();
      if (empty($id) || empty($data)) {
        $respuesta['msg'] = 'Información no proporcionada.';
        $this->response($respuesta, $codigo_respuesta);
      }
      $respuesta['data'] = $this->Tareas_model->actualizar($dataUsuario, $id, $data);
      $respuesta['msg'] = 'Tarea actualizada con éxito.';
      $respuesta['err'] = false;
      $codigo_respuesta = REST_Controller::HTTP_OK;
    } catch (Exception $e) {
      $respuesta['msg'] = $e->getMessage();
    }
    $this->response($respuesta, $codigo_respuesta);
  }

  public function cambiar_estatus_post()
  {
    $respuesta = ['err' => true, 'msg' => null];
    $codigo_respuesta = REST_Controller::HTTP_BAD_REQUEST;
    try {
      $token = getToken();
      if ($token->error) { $this->response($token, $codigo_respuesta); }
      $dataUsuario = [
        'idUsuario'       => $token->data->id,
        'idPerfilUsuario' => $token->data->id_perfil_usuario,
      ];
      $id = $this->uri->segment(3);
      $data = $this->post();
      if (empty($id) || empty($data['fk_id_estatus'])) {
        $respuesta['msg'] = 'Información no proporcionada.';
        $this->response($respuesta, $codigo_respuesta);
      }
      $respuesta['data'] = $this->Tareas_model->cambiar_estatus($dataUsuario, $id, $data['fk_id_estatus']);
      $respuesta['msg'] = 'Estatus actualizado con éxito.';
      $respuesta['err'] = false;
      $codigo_respuesta = REST_Controller::HTTP_OK;
    } catch (Exception $e) {
      $respuesta['msg'] = $e->getMessage();
    }
    $this->response($respuesta, $codigo_respuesta);
  }

  public function eliminar_post()
  {
    $respuesta = ['err' => true, 'msg' => null];
    $codigo_respuesta = REST_Controller::HTTP_BAD_REQUEST;
    try {
      $token = getToken();
      if ($token->error) { $this->response($token, $codigo_respuesta); }
      $dataUsuario = [
        'idUsuario'       => $token->data->id,
        'idPerfilUsuario' => $token->data->id_perfil_usuario,
      ];
      $id = $this->uri->segment(3);
      if (empty($id)) {
        $respuesta['msg'] = 'ID no proporcionado.';
        $this->response($respuesta, $codigo_respuesta);
      }
      $respuesta['data'] = $this->Tareas_model->eliminar($dataUsuario, $id);
      $respuesta['msg'] = 'Tarea eliminada con éxito.';
      $respuesta['err'] = false;
      $codigo_respuesta = REST_Controller::HTTP_OK;
    } catch (Exception $e) {
      $respuesta['msg'] = $e->getMessage();
    }
    $this->response($respuesta, $codigo_respuesta);
  }

  public function usuarios_asignables_get()
  {
    $respuesta = ['err' => true, 'msg' => null];
    $codigo_respuesta = REST_Controller::HTTP_BAD_REQUEST;
    try {
      $token = getToken();
      if ($token->error) { $this->response($token, $codigo_respuesta); }
      $respuesta['data'] = $this->Tareas_model->listar_usuarios_asignables($token->data->id_perfil_usuario);
      $respuesta['msg'] = 'Información obtenida con éxito.';
      $respuesta['err'] = false;
      $codigo_respuesta = REST_Controller::HTTP_OK;
    } catch (Exception $e) {
      $respuesta['msg'] = $e->getMessage();
    }
    $this->response($respuesta, $codigo_respuesta);
  }
}
