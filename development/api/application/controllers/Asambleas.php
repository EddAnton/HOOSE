<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Controlador: Asambleas
 *
 * Este controlador realiza las operaciones relacionadas con las Asambleas
 *
 * @package   CodeIgniter
 * @category  Controller REST
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Asambleas extends REST_Controller
{
  public function __construct()
  {
    parent::__construct();
    $this->load->model('Asamblea_model');
  }

  public function index()
  {
    $this->response(APP_NAME . ' API / Asambleas :: Controller');
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
      $idCondominio = $token->data->id_condominio_usuario;

      // Verificar si se especifica el ID de un registro en particular
      $idAsamblea = $this->security->xss_clean($this->uri->segment(2));
      $idAsamblea = !empty($idAsamblea) && intval($idAsamblea) ? intval($idAsamblea) : 0;

      // Obtener registro(s)
      $resultado = $this->Asamblea_model->listar($idAsamblea, $idCondominio, $soloActivos);
      if (!empty($idAsamblea)) {
        $response['asamblea'] = $resultado;
      } else {
        $response['asambleas'] = $resultado;
      }

      $response['error'] = false;
      $response['msg'] = 'Información obtenida con éxito.';
      $responseCode = REST_Controller::HTTP_OK;
    } catch (Exception $e) {
      $response['msg'] = $e->getMessage();
    }
    $this->response($response, $responseCode);
  }

  // Listar detalle de la asamblea
  public function detalle_get()
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

      // Obtener el ID del registro
      $idAsamblea = $this->security->xss_clean($this->uri->segment(3));
      if (empty($idAsamblea) || !intval($idAsamblea) || intval($idAsamblea) < 1) {
        $respuesta['msg'] = 'Debe especificar un identificador válido.';
        $this->response($respuesta, $responseCode);
      }

      // Obtener registro(s)
      $response['asamblea'] = $this->Asamblea_model->detalle($idAsamblea);

      $response['error'] = false;
      $response['msg'] = 'Información obtenida con éxito.';
      $responseCode = REST_Controller::HTTP_OK;
    } catch (Exception $e) {
      $response['msg'] = $e->getMessage();
    }
    $this->response($response, $responseCode);
  }

  // Listar orden del día de la asamblea
  public function listar_orden_dia_get()
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

      // Obtener el ID del registro
      $idAsamblea = $this->security->xss_clean($this->uri->segment(3));
      if (empty($idAsamblea) || !intval($idAsamblea) || intval($idAsamblea) < 1) {
        $respuesta['msg'] = 'Debe especificar un identificador válido.';
        $this->response($respuesta, $responseCode);
      }

      // Obtener registro(s)
      $response['orden_dia'] = $this->Asamblea_model->listar_orden_dia($idAsamblea);

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
      $idCondominio = $token->data->id_condominio_usuario;
      $idUsuarioRegistro = $token->data->id;

      $data = $this->post();
      // Verificar que se haya enviado la información a insertar
      if (empty($data) || empty($data['orden_dia'])) {
        $respuesta['msg'] = 'Información a procesar no ha sido proporcionada o es incompleta.';
        $this->response($respuesta, $responseCode);
      }

      // Extraer atributos en texto enriquecido
      $dataOrdenDia = !empty($data['orden_dia']) ? $data['orden_dia'] : null;
      $fundamento_legal = !empty($data['fundamento_legal']) ? $data['fundamento_legal'] : null;
      $convocatoria_cierre = !empty($data['convocatoria_cierre']) ? $data['convocatoria_cierre'] : null;
      unset($data['orden_dia']);
      unset($data['fundamento_legal']);
      unset($data['convocatoria_cierre']);

      $data = $this->security->xss_clean($data);
      $data = nulificar_elementos_arreglo(trim_elementos_arreglo($data));
      $data = capitalizar_arreglo($data, ['lugar', 'convocatoria_ciudad', 'convocatoria_quien_emite']);
      $data['fundamento_legal'] = $fundamento_legal;
      $data['convocatoria_cierre'] = $convocatoria_cierre;

      /* print_r($data);
          exit(); */

      // Validar la información
      $this->form_validation->set_data($data);
      if (!$this->form_validation->run('asambleaInsertar')) {
        // Error al validar la información
        $respuesta['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
        foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
          $respuesta['msg'] .= $value . PHP_EOL;
        }
        $this->response($respuesta, $responseCode);
      }

      // Validar data del orden del día
      foreach ($dataOrdenDia as &$orden) {
        $orden = capitalizar_arreglo($orden);
        $this->form_validation->reset_validation();
        $this->form_validation->set_data($orden);
        if (!$this->form_validation->run('asambleaOrdenDiaInsertar')) {
          // Error al validar la información
          $respuesta['msg'] = 'Existen errores en la información proporcionada (Orden del día).' . PHP_EOL;
          foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
            $respuesta['msg'] .= $value . PHP_EOL;
          }
          $this->response($respuesta, $responseCode);
        }
      }

      // Información validada con éxito. Procede a la inserción
      $data = [
        'fk_id_tipo_asamblea' => $data['id_tipo_asamblea'],
        'fecha_hora' => $data['fecha_hora'],
        'lugar' => $data['lugar'],
        'fundamento_legal' => $data['fundamento_legal'],
        'convocatoria_cierre' => $data['convocatoria_cierre'],
        'convocatoria_fecha' => $data['convocatoria_fecha'],
        'convocatoria_ciudad' => $data['convocatoria_ciudad'],
        'convocatoria_quien_emite' => $data['convocatoria_quien_emite'],
        'dataOrdenDia' => $dataOrdenDia,
        'fk_id_condominio' => $idCondominio,
        'fk_id_usuario_registro' => $idUsuarioRegistro,
      ];

      $respuesta = $this->Asamblea_model->insertar($data);
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
      // $idCondominio = $token->data->id_condominio_usuario;
      $idUsuarioModifico = $token->data->id;

      // Verificar si se especifica el ID de un registro en particular
      $idAsamblea = $this->security->xss_clean($this->uri->segment(3));
      if (empty($idAsamblea) || !intval($idAsamblea) || intval($idAsamblea) < 1) {
        $respuesta['msg'] = 'Debe especificar un identificador válido.';
        $this->response($respuesta, $responseCode);
      }

      $data = $this->post();
      // Verificar que se haya enviado la información a actualizar
      if (empty($data) || empty($data['orden_dia'])) {
        $respuesta['msg'] = 'Información a procesar no ha sido proporcionada.';
        $this->response($respuesta, $responseCode);
      }

      // Extraer atributos en texto enriquecido
      $dataOrdenDia = !empty($data['orden_dia']) ? $data['orden_dia'] : null;
      unset($data['orden_dia']);
      $fundamento_legal = !empty($data['fundamento_legal']) ? $data['fundamento_legal'] : null;
      unset($data['fundamento_legal']);
      $convocatoria_cierre = !empty($data['convocatoria_cierre']) ? $data['convocatoria_cierre'] : null;
      unset($data['convocatoria_cierre']);

      $data = $this->security->xss_clean($data);
      $data = nulificar_elementos_arreglo(trim_elementos_arreglo($data));
      $data = capitalizar_arreglo($data, ['lugar', 'convocatoria_ciudad', 'convocatoria_quien_emite']);
      $data['fundamento_legal'] = $fundamento_legal;
      $data['convocatoria_cierre'] = $convocatoria_cierre;

      // Validar la información
      $this->form_validation->set_data($data);
      if (!$this->form_validation->run('asambleaInsertar')) {
        // Error al validar la información
        $respuesta['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
        foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
          $respuesta['msg'] .= $value . PHP_EOL;
        }
        $this->response($respuesta, $responseCode);
      }

      // Validar data del orden del día
      foreach ($dataOrdenDia as &$orden) {
        $orden = capitalizar_arreglo($orden);
        $this->form_validation->reset_validation();
        $this->form_validation->set_data($orden);
        if (!$this->form_validation->run('asambleaOrdenDiaInsertar')) {
          // Error al validar la información
          $respuesta['msg'] = 'Existen errores en la información proporcionada (Orden del día).' . PHP_EOL;
          foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
            $respuesta['msg'] .= $value . PHP_EOL;
          }
          $this->response($respuesta, $responseCode);
        }
      }

      // Información validada con éxito. Procede a la inserción
      $data = [
        'id_asamblea' => $idAsamblea,
        'fk_id_tipo_asamblea' => $data['id_tipo_asamblea'],
        'fecha_hora' => $data['fecha_hora'],
        'lugar' => $data['lugar'],
        'fundamento_legal' => $data['fundamento_legal'],
        'convocatoria_cierre' => $data['convocatoria_cierre'],
        'convocatoria_fecha' => $data['convocatoria_fecha'],
        'convocatoria_ciudad' => $data['convocatoria_ciudad'],
        'convocatoria_quien_emite' => $data['convocatoria_quien_emite'],
        'dataOrdenDia' => $dataOrdenDia,
        'fk_id_usuario_modifico' => $idUsuarioModifico,
      ];

      $respuesta = $this->Asamblea_model->actualizar($data);
      if (!$respuesta['err']) {
        $responseCode = REST_Controller::HTTP_OK;
      }
    } catch (Exception $e) {
      $respuesta['msg'] = $e->getMessage();
    }
    $this->response($respuesta, $responseCode);
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
      $idAsamblea = $this->security->xss_clean($this->uri->segment(3));
      if (empty($idAsamblea) || !intval($idAsamblea) || intval($idAsamblea) < 1) {
        $response['msg'] = 'Debe especificar un identificador válido.';
        $this->response($response, $responseCode);
      }

      // Información validada con éxito. Procede a la inserción
      $data = [
        'id_asamblea' => $idAsamblea,
        'id_usuario_modifico' => $idUsuarioModifico,
      ];

      // Información validada con éxito. Procede a la inserción
      $response = $this->Asamblea_model->eliminar($data);
      if (!$response['error']) {
        $responseCode = REST_Controller::HTTP_OK;
      }
    } catch (Exception $e) {
      $response['msg'] = $e->getMessage();
    }
    $this->response($response, $responseCode);
  }

  // Listar registro(s)
  public function listar_acta_get()
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
      $idAsamblea = $this->security->xss_clean($this->uri->segment(2));
      $idAsamblea = !empty($idAsamblea) && intval($idAsamblea) ? intval($idAsamblea) : 0;

      // Obtener registro(s)
      $response['acta'] = $this->Asamblea_model->listar_acta($idAsamblea);
      $response['msg'] = 'Información obtenida con éxito.';
      $response['error'] = false;
      $responseCode = REST_Controller::HTTP_OK;
    } catch (Exception $e) {
      $response['msg'] = $e->getMessage();
    }
    $this->response($response, $responseCode);
  }

  // Nuevo registro acta
  public function guardar_acta_post()
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

      // Verificar si se especifica el ID de un registro en particular
      $idAsamblea = $this->security->xss_clean($this->uri->segment(2));
      if (empty($idAsamblea) || !intval($idAsamblea) || intval($idAsamblea) < 1) {
        $respuesta['msg'] = 'Debe especificar un identificador válido.';
        $this->response($respuesta, $responseCode);
      }

      $data = $this->post();
      // Verificar que se haya enviado la información a insertar
      if (empty($data) || empty($data['pase_lista']) || empty($data['orden_dia'])) {
        $respuesta['msg'] = 'Información a procesar no ha sido proporcionada o es incompleta.';
        $this->response($respuesta, $responseCode);
      }

      // Extraer atributos en texto enriquecido
      $pase_lista = !empty($data['pase_lista']) ? $data['pase_lista'] : null;
      $orden_dia = !empty($data['orden_dia']) ? $data['orden_dia'] : null;
      $apertura = !empty($data['apertura']) ? $data['apertura'] : null;
      $cierre = !empty($data['cierre']) ? $data['cierre'] : null;
      unset($data['pase_lista']);
      unset($data['orden_dia']);
      unset($data['apertura']);
      unset($data['cierre']);

      $data = $this->security->xss_clean($data);
      $data = nulificar_elementos_arreglo(trim_elementos_arreglo($data));
      $data = capitalizar_arreglo($data, ['lugar', 'quien_emite']);
      $data['apertura'] = $apertura;
      $data['cierre'] = $cierre;

      $dataPaseLista = [];
      $dataOrdenDia = [];
      $dataVotacion = [];

      // Validar la información
      $this->form_validation->set_data($data);
      if (!$this->form_validation->run('asambleaActaGuardar')) {
        // Error al validar la información
        $respuesta['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
        foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
          $respuesta['msg'] .= $value . PHP_EOL;
        }
        $this->response($respuesta, $responseCode);
      }

      // Validar data del pase de lista
      foreach ($pase_lista as $pase) {
        $this->form_validation->reset_validation();
        $this->form_validation->set_data($pase);
        if (!$this->form_validation->run('asambleaActaPListaGuardar')) {
          // Error al validar la información
          $respuesta['msg'] = 'Existen errores en la información proporcionada (Pase de lista).' . PHP_EOL;
          foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
            $respuesta['msg'] .= $value . PHP_EOL;
          }
          $this->response($respuesta, $responseCode);
        }
        $dataPaseLista[] = [
          'fk_id_usuario' => $pase['id_usuario'],
          'asistencia' => $pase['asistencia'],
        ];
      }

      // Validar data de los puntos del orden del día
      foreach ($orden_dia as $orden) {
        /* print_r($orden);
        exit; */
        $votaciones = $orden['votacion'];
        unset($orden['votacion']);

        $this->form_validation->reset_validation();
        $this->form_validation->set_data($orden);
        if (!$this->form_validation->run('asambleaActaOrdenDiaGuardar')) {
          // Error al validar la información
          $respuesta['msg'] = 'Existen errores en la información proporcionada (Orden del día).' . PHP_EOL;
          foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
            $respuesta['msg'] .= $value . PHP_EOL;
          }
          $this->response($respuesta, $responseCode);
        }
        $dataOrdenDia[] = [
          'fk_id_asamblea_orden_dia' => $orden['id_asamblea_orden_dia'],
          'apertura' => $orden['apertura'],
          'cierre' => !empty($orden['cierre']) ? $orden['cierre'] : null,
        ];

        // Validar data de las votaciones de cada punto del orden del día
        if (!empty($votaciones)) {
          foreach ($votaciones as $votacion) {
            $votacion['id_asamblea_orden_dia'] = $orden['id_asamblea_orden_dia'];

            $this->form_validation->reset_validation();
            $this->form_validation->set_data($votacion);
            if (!$this->form_validation->run('asambleaActaVotacionODGuardar')) {
              // Error al validar la información
              $respuesta['msg'] = 'Existen errores en la información proporcionada (Orden del día - Votación).' . PHP_EOL;
              foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
                $respuesta['msg'] .= $value . PHP_EOL;
              }
              $this->response($respuesta, $responseCode);
            }
            $dataVotaciones[] = [
              'fk_id_asamblea_orden_dia' => $votacion['id_asamblea_orden_dia'],
              'fk_id_usuario' => $votacion['id_usuario'],
              'votacion' => $votacion['votacion'],
            ];
          }
        }
      }

      // Información validada con éxito. Procede a la inserción
      $data = [
        'fk_id_asamblea' => $data['id_asamblea'],
        'apertura' => $data['apertura'],
        'cierre' => $data['cierre'],
        'fecha_hora' => $data['fecha_hora'],
        'lugar' => $data['lugar'],
        'quien_emite' => $data['quien_emite'],
        'finalizada' => !empty($data['finalizada']) ? $data['finalizada'] : 0,
        'fk_id_usuario_registro' => $idUsuarioRegistro,
        'dataPaseLista' => cambiar_keys_arreglo($dataPaseLista, ['id_usuario' => 'fk_id_usuario']),
        'dataOrdenDia' => $dataOrdenDia,
        'dataVotaciones' => $dataVotaciones,
      ];

      /* print_r($data);
      exit; */

      $respuesta = $this->Asamblea_model->guardar_acta($data);
      if (!$respuesta['err']) {
        $responseCode = REST_Controller::HTTP_OK;
      }
    } catch (Exception $e) {
      $respuesta['msg'] = $e->getMessage();
    }
    $this->response($respuesta, $responseCode);
  }

  // Actualizar registro acta
  /* public function actualizar_acta_post()
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
      $idActaAsamblea = $this->security->xss_clean($this->uri->segment(2));
      if (empty($idActaAsamblea) || !intval($idActaAsamblea) || intval($idActaAsamblea) < 1) {
        $respuesta['msg'] = 'Debe especificar un identificador válido.';
        $this->response($respuesta, $responseCode);
      }

      $data = $this->post();
      // Verificar que se haya enviado la información a insertar
      if (empty($data) || empty($data['pase_lista'])) {
        $respuesta['msg'] = 'Información a procesar no ha sido proporcionada o es incompleta.';
        $this->response($respuesta, $responseCode);
      }

      // Extraer atributos en texto enriquecido
      $pase_lista = !empty($data['pase_lista']) ? $data['pase_lista'] : null;
      unset($data['pase_lista']);
      $votaciones_orden_dia = !empty($data['votaciones_orden_dia']) ? $data['votaciones_orden_dia'] : null;
      unset($data['votaciones_orden_dia']);
      $apertura = !empty($data['apertura']) ? $data['apertura'] : null;
      unset($data['apertura']);
      $cierre = !empty($data['cierre']) ? $data['cierre'] : null;
      unset($data['cierre']);

      $data = $this->security->xss_clean($data);
      $data = nulificar_elementos_arreglo(trim_elementos_arreglo($data));
      $data = capitalizar_arreglo($data, ['quien_emite']);
      $data['apertura'] = $apertura;
      $data['cierre'] = $cierre;

      $dataPaseLista = [];
      $dataVotaciones = [];

      // Validar la información
      $this->form_validation->set_data($data);
      if (!$this->form_validation->run('asambleaActaInsertar')) {
        // Error al validar la información
        $respuesta['msg'] = 'Existen errores en la información proporcionada.' . PHP_EOL;
        foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
          $respuesta['msg'] .= $value . PHP_EOL;
        }
        $this->response($respuesta, $responseCode);
      }

      // Validar data del pase de lista
      foreach ($pase_lista as $pase) {
        $this->form_validation->reset_validation();
        $this->form_validation->set_data($pase);
        if (!$this->form_validation->run('asambleaActaPaseListaInsertar')) {
          // Error al validar la información
          $respuesta['msg'] = 'Existen errores en la información proporcionada (Orden del día).' . PHP_EOL;
          foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
            $respuesta['msg'] .= $value . PHP_EOL;
          }
          $this->response($respuesta, $responseCode);
        }
        $dataPaseLista[] = [
          'fk_id_asamblea_acta' => $idActaAsamblea,
          'fk_id_usuario' => $pase['id_usuario'],
          'asistencia' => $pase['asistencia'],
        ];
      }

      // Validar data de las votaciones
      foreach ($votaciones_orden_dia as $votacion) {
        $this->form_validation->reset_validation();
        $this->form_validation->set_data($votacion);
        if (!$this->form_validation->run('asambleaActaVotacionODInsertar')) {
          // Error al validar la información
          $respuesta['msg'] = 'Existen errores en la información proporcionada (Orden del día).' . PHP_EOL;
          foreach ($this->form_validation->get_errores_arreglo() as $key => $value) {
            $respuesta['msg'] .= $value . PHP_EOL;
          }
          $this->response($respuesta, $responseCode);
        }
        $dataVotacion[] = [
          'fk_id_asamblea_acta' => $idActaAsamblea,
          'fk_id_asamblea_orden_dia' => $votacion['id_asamblea_orden_dia'],
          'fk_id_usuario' => $votacion['id_usuario'],
          'asistencia' => $votacion['asistencia'],
        ];
      }

      // Información validada con éxito. Procede a la actualización
      $data = [
        'id_asamblea_acta' => $idActaAsamblea,
        'apertura' => $data['apertura'],
        'cierre' => $data['cierre'],
        'fecha' => $data['fecha'],
        'ciudad' => $data['ciudad'],
        'quien_emite' => $data['quien_emite'],
        'finalizada' => !empty($data['finalizada']) ? $data['finalizada'] : 0,
        'dataPaseLista' => $dataPaseLista,
        'dataVotacion' => $dataVotacion,
        'fk_id_usuario_modifico' => $idUsuarioModifico,
      ];

      $respuesta = $this->Asamblea_model->actualizar_acta($data);
      if (!$respuesta['err']) {
        $responseCode = REST_Controller::HTTP_OK;
      }
    } catch (Exception $e) {
      $respuesta['msg'] = $e->getMessage();
    }
    $this->response($respuesta, $responseCode);
  } */

  // Eliminar registro acta
  public function eliminar_acta_post()
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
      $idActaAsamblea = $this->security->xss_clean($this->uri->segment(2));
      if (empty($idActaAsamblea) || !intval($idActaAsamblea) || intval($idActaAsamblea) < 1) {
        $respuesta['msg'] = 'Debe especificar un identificador válido.';
        $this->response($respuesta, $responseCode);
      }

      // Información validada con éxito. Procede a la actauzalización
      $data = [
        'id_asamblea_acta' => $idActaAsamblea,
        'fk_id_usuario_modifico' => $idUsuarioModifico,
      ];

      $respuesta = $this->Asamblea_model->eliminar_acta($data);
      if (!$respuesta['err']) {
        $responseCode = REST_Controller::HTTP_OK;
      }
    } catch (Exception $e) {
      $respuesta['msg'] = $e->getMessage();
    }
    $this->response($respuesta, $responseCode);
  }

  // Actualizar registro acta
  public function finalizar_acta_post()
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
      $idActaAsamblea = $this->security->xss_clean($this->uri->segment(2));
      if (empty($idActaAsamblea) || !intval($idActaAsamblea) || intval($idActaAsamblea) < 1) {
        $respuesta['msg'] = 'Debe especificar un identificador válido.';
        $this->response($respuesta, $responseCode);
      }

      // Información validada con éxito. Procede a la actauzalización
      $data = [
        'id_asamblea_acta' => $idActaAsamblea,
        'fk_id_usuario_modifico' => $idUsuarioModifico,
      ];

      $respuesta = $this->Asamblea_model->finalizar_acta($data);
      if (!$respuesta['err']) {
        $responseCode = REST_Controller::HTTP_OK;
      }
    } catch (Exception $e) {
      $respuesta['msg'] = $e->getMessage();
    }
    $this->response($respuesta, $responseCode);
  }
}

/* End of file Asambleas.php */
/* Location: ./application/controllers/Asambleas.php */
