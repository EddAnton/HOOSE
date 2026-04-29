<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Controlador: Recaudaciones
 *
 * Este controlador realiza las operaciones relacionadas con las Recaudaciones
 *
 * @package   CodeIgniter
 * @category  Controller REST
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Recaudaciones extends REST_Controller
{
  public function __construct()
  {
    parent::__construct();
    $this->load->model('Recaudacion_model');
  }

  public function index()
  {
    $this->response(APP_NAME . ' API / Recaudaciones :: Controller');
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
      $idUsuarioPropietario = in_array($token->data->id_perfil_usuario, [4, 5]) ? $token->data->id : 0;
      $idCondominio = $token->data->id_condominio_usuario;

      // Verificar si se especifica el ID de un registro en particular
      $idRecaudacion = $this->security->xss_clean($this->uri->segment(2));
      $idRecaudacion = !empty($idRecaudacion) && intval($idRecaudacion) ? intval($idRecaudacion) : 0;

      // Obtener registro(s)
      // $response['recaudaciones'] = $this->Recaudacion_model->listar($idRecaudacion, $idCondominio, $soloActivos);
      $result = $this->Recaudacion_model->listar($idRecaudacion, $idUsuarioPropietario, $idCondominio, $soloActivos);
      if (!empty($idRecaudacion)) {
        $response['recaudacion'] = $result;
      } else {
        $response['recaudaciones'] = $result;
      }

      $response['error'] = false;
      $response['msg'] = 'Información obtenida con éxito.';
      $responseCode = REST_Controller::HTTP_OK;
    } catch (Exception $e) {
      $response['msg'] = $e->getMessage();
    }
    $this->response($response, $responseCode);
  }

  // Obtener información para el recibo de pago.
  public function listar_recibo_pago_get()
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

      // Verificar si se especifica el ID de un registro en particular
      $idRecaudacion = $this->security->xss_clean($this->uri->segment(3));
      if (empty($idRecaudacion) || !intval($idRecaudacion) || intval($idRecaudacion) < 1) {
        $respuesta['msg'] = 'Debe especificar un identificador válido.';
        $this->response($respuesta, $responseCode);
      }

      // Obtener información
      $response['recibo_pago'] = $this->Recaudacion_model->listar_recibo_pago($idRecaudacion);
      $response['error'] = false;
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

      $data = $this->security->xss_clean($this->post());
      // Verificar que se haya enviado la información a insertar
      if (empty($data)) {
        $respuesta['msg'] = 'Información a procesar no ha sido proporcionada.';
        $this->response($respuesta, $responseCode);
      }

      $data = nulificar_elementos_arreglo(trim_elementos_arreglo($data));

      // Validar la información
      $this->form_validation->set_data($data);
      if (!$this->form_validation->run('recaudacionInsertar')) {
        // Error al validar la información
        $respuesta['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
        foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
          $respuesta['msg'] .= $value . PHP_EOL;
        }
        $this->response($respuesta, $responseCode);
      }

      // Información validada con éxito. Procede a la inserción
      $data = [
        'fk_id_unidad' => $data['id_unidad'],
        'fk_id_perfil_usuario_paga' => $data['id_perfil_usuario_paga'],
        'fk_id_usuario_paga' => $data['id_usuario_paga'],
        'anio' => $data['anio'],
        'mes' => $data['mes'],
        'renta' => $data['renta'],
        'agua' => $data['agua'],
        'energia_electrica' => $data['energia_electrica'],
        'gas' => $data['gas'],
        'seguridad' => $data['seguridad'],
        'servicios_publicos' => $data['servicios_publicos'],
        'otros_servicios' => $data['otros_servicios'],
        'fecha_limite_pago' => $data['fecha_limite_pago'],
        'fk_id_estatus_recaudacion' => $data['id_estatus_recaudacion'],
        'fecha_pago' =>
          $data['id_estatus_recaudacion'] == 3 && !empty($data['fecha_pago']) ? $data['fecha_pago'] : null,
        'fk_id_forma_pago' =>
          $data['id_estatus_recaudacion'] == 3 && !empty($data['id_forma_pago']) ? $data['id_forma_pago'] : null,
        'numero_referencia' =>
          $data['id_estatus_recaudacion'] == 3 && !empty($data['numero_referencia'])
          ? $data['numero_referencia']
          : null,
        'fk_id_forma_pago' =>
          $data['id_estatus_recaudacion'] == 3 && !empty($data['id_forma_pago']) ? $data['id_forma_pago'] : null,
        'notas' => !empty($data['notas']) ? $data['notas'] : null,
        'fk_id_usuario_registro' => $idUsuarioRegistro,
      ];

      $respuesta = $this->Recaudacion_model->insertar($data);
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

      // Verificar si se especifica el ID de un registro en particular
      $idRecaudacion = $this->security->xss_clean($this->uri->segment(3));
      if (empty($idRecaudacion) || !intval($idRecaudacion) || intval($idRecaudacion) < 1) {
        $respuesta['msg'] = 'Debe especificar un identificador válido.';
        $this->response($respuesta, $responseCode);
      }

      $data = $this->security->xss_clean($this->post());
      // Verificar que se haya enviado la información a insertar
      if (empty($data)) {
        $respuesta['msg'] = 'Información a procesar no ha sido proporcionada.';
        $this->response($respuesta, $responseCode);
      }

      $data = capitalizar_arreglo(nulificar_elementos_arreglo(trim_elementos_arreglo($data)));

      // Validar la información
      $this->form_validation->set_data($data);
      if (!$this->form_validation->run('recaudacionInsertar')) {
        // Error al validar la información
        $respuesta['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
        foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
          $respuesta['msg'] .= $value . PHP_EOL;
        }
        $this->response($respuesta, $responseCode);
      }

      if ($data['id_estatus_recaudacion'] == 3 && empty($data['fecha_pago'])) {
        $respuesta['msg'] = 'Faltó especificar la fecha de pago.';
        $this->response($respuesta, $responseCode);
      }
      if ($data['id_estatus_recaudacion'] == 3 && empty($data['id_forma_pago'])) {
        $respuesta['msg'] = 'Faltó especificar la forma de pago.';
        $this->response($respuesta, $responseCode);
      }

      // Información validada con éxito. Procede a la inserción
      $data = [
        'id_recaudacion' => $idRecaudacion,
        'fk_id_unidad' => $data['id_unidad'],
        'fk_id_perfil_usuario_paga' => $data['id_perfil_usuario_paga'],
        'fk_id_usuario_paga' => $data['id_usuario_paga'],
        'anio' => $data['anio'],
        'mes' => $data['mes'],
        'renta' => $data['renta'],
        'agua' => $data['agua'],
        'energia_electrica' => $data['energia_electrica'],
        'gas' => $data['gas'],
        'seguridad' => $data['seguridad'],
        'servicios_publicos' => $data['servicios_publicos'],
        'otros_servicios' => $data['otros_servicios'],
        'fecha_limite_pago' => $data['fecha_limite_pago'],
        'fk_id_estatus_recaudacion' => $data['id_estatus_recaudacion'],
        'fecha_pago' =>
          $data['id_estatus_recaudacion'] == 3 && !empty($data['fecha_pago']) ? $data['fecha_pago'] : null,
        'notas' => !empty($data['notas']) ? $data['notas'] : null,
        'numero_referencia' =>
          $data['id_estatus_recaudacion'] == 3 && !empty($data['numero_referencia'])
          ? $data['numero_referencia']
          : null,
        'fk_id_usuario_modifico' => $idUsuarioModifico,
      ];

      $respuesta = $this->Recaudacion_model->actualizar($data);
      if (!$respuesta['err']) {
        $responseCode = REST_Controller::HTTP_OK;
      }
    } catch (Exception $e) {
      $respuesta['msg'] = $e->getMessage();
    }
    $this->response($respuesta, $responseCode);
  }

  // Establecer como pagada la recaudación
  public function registrar_pago_post()
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
      $idUsuarioModifico = $token->data->id;

      // Verificar que se especique el ID del registro a actualizar
      $idRecaudacion = $this->security->xss_clean($this->uri->segment(3));
      if (empty($idRecaudacion) || !intval($idRecaudacion) || intval($idRecaudacion) < 1) {
        $response['msg'] = 'Debe especificar un identificador válido.';
        $this->response($response, $responseCode);
      }

      $data = $this->security->xss_clean($this->post());

      // Verificar que se haya enviado la información a actualizar
      if (empty($data)) {
        $response['msg'] = 'No se proporcionó información a procesar.';
        $this->response($response, $responseCode);
      }

      // Normalizar la información a almacenar
      $data = nulificar_elementos_arreglo(trim_elementos_arreglo($data));

      // Validación de la información proporcionada
      $this->form_validation->set_data($data);
      if (!$this->form_validation->run('recaudacionRegistrarPago')) {
        // Error al validar la información
        $response['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
        foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
          $response['msg'] .= $value . PHP_EOL;
        }
        $this->response($response, $responseCode);
      }

      // Información validada con éxito. Procede a la inserción
      $data = [
        'id_recaudacion' => $idRecaudacion,
        'fecha_pago' => $data['fecha_pago'],
        'id_forma_pago' => $data['id_forma_pago'],
        'numero_referencia' => !empty($data['numero_referencia']) ? $data['numero_referencia'] : null,
        'id_usuario_modifico' => $idUsuarioModifico,
      ];

      // Información validada con éxito. Procede a la inserción
      $response = $this->Recaudacion_model->registrar_pago($data);
      if (!$response['error']) {
        $responseCode = REST_Controller::HTTP_OK;
      }
    } catch (Exception $e) {
      $response['msg'] = $e->getMessage();
    }
    $this->response($response, $responseCode);
  }

  // Eliminar lógicamente el registro
  public function eliminar_post()
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
      $idUsuarioModifico = $token->data->id;

      // Verificar que se especique el ID del registro a actualizar
      $idRecaudacion = $this->security->xss_clean($this->uri->segment(3));
      if (empty($idRecaudacion) || !intval($idRecaudacion) || intval($idRecaudacion) < 1) {
        $response['msg'] = 'Debe especificar un identificador válido.';
        $this->response($response, $responseCode);
      }

      // Información validada con éxito. Procede a la inserción
      $data = [
        'id_recaudacion' => $idRecaudacion,
        'id_usuario_modifico' => $idUsuarioModifico,
      ];

      // Información validada con éxito. Procede a la inserción
      $response = $this->Recaudacion_model->eliminar($data);
      if (!$response['error']) {
        $responseCode = REST_Controller::HTTP_OK;
      }
    } catch (Exception $e) {
      $response['msg'] = $e->getMessage();
    }
    $this->response($response, $responseCode);
  }
}

/* End of file Recaudaciones.php */
/* Location: ./application/controllers/Recaudaciones.php */
