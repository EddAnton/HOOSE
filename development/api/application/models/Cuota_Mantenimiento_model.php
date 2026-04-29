<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Modelo: Cuota_Mantenimiento_model
 *
 * Este modelo realiza las operaciones requeridas sobre las Cuotas de mantenimiento
 *
 * @package   CodeIgniter
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Cuota_Mantenimiento_model extends CI_Model
{
  public function __construct()
  {
    parent::__construct();
    $this->load->model('Recaudacion_model');
  }

  private function listar_pagos($idCuotaMantenimiento = 0)
  {
    if (empty($idCuotaMantenimiento)) {
      return null;
    }
    return $this->db
      ->select(
        'p.id_cuota_mantenimiento_pago,
          p.importe,
          p.fecha_pago,
          fp.id_forma_pago,
          fp.forma_pago,
          p.numero_referencia,
          CONCAT(tfm.tipo_fondo, " - ", fm.fondo_monetario) fondo_monetario'
      )
      ->join('cat_formas_pago fp', 'fp.id_forma_pago = p.fk_id_forma_pago')
      ->join(
        'fondos_monetarios_movimientos m',
        'm.id_fondo_monetario_movimiento = p.fk_id_fondo_monetario_movimiento',
        'left'
      )
      ->join('fondos_monetarios fm', 'fm.id_fondo_monetario = m.fk_id_fondo_monetario', 'left')
      ->join('cat_tipos_fondos_monetarios tfm', 'tfm.id_tipo_fondo_monetario = fm.fk_id_tipo_fondo_monetario', 'left')
      ->order_by('p.fecha_pago DESC, p.id_cuota_mantenimiento_pago DESC')
      ->get_where('cuotas_mantenimiento_pagos p', [
        'p.fk_id_cuota_mantenimiento' => $idCuotaMantenimiento,
        'p.estatus' => 1,
      ])
      ->result_array();
  }

  /*
     Obtener información del identificador especificado.
   */
  public function listar(
    $idCuotaMantenimiento = 0,
    $idUsuario = 0,
    $idCondominio = 0,
    $esUsuarioPropietario = false,
    $soloActivos = false
  ) {
    try {
      if (!empty($idCuotaMantenimiento)) {
        $cuotaMantenimiento = $this->db
          ->select(
            'cm.id_cuota_mantenimiento,
              e.id_edificio,
              e.edificio,
              u.id_unidad,
              u.unidad,
              pu.id_perfil_usuario id_perfil_usuario_paga,
              pu.perfil_usuario perfil_usuario_paga,
              us.id_usuario id_usuario_paga,
              us.nombre usuario_paga,
              cm.anio,
              cm.mes,
              cm.ordinaria,
              cm.extraordinaria,
              cm.otros_servicios,
              cm.descuento_pronto_pago,
              (cm.total - cm.saldo) pagado,
              cm.saldo,
              cm.total,
              cm.fecha_limite_pago,
              er.id_estatus_recaudacion,
              er.estatus_recaudacion,
              cm.notas,
              cm.estatus'
          )
          ->join('unidades u', 'u.id_unidad = cm.fk_id_unidad')
          ->join('edificios e', 'e.id_edificio = u.fk_id_edificio')
          ->join('cat_perfiles_usuarios pu', 'pu.id_perfil_usuario = cm.fk_id_perfil_usuario_paga')
          ->join('usuarios us', 'us.id_usuario = cm.fk_id_usuario_paga')
          ->join('cat_estatus_recaudacion er', 'er.id_estatus_recaudacion = cm.fk_id_estatus_recaudacion')
          ->get_where('cuotas_mantenimiento cm', [
            'cm.id_cuota_mantenimiento' => $idCuotaMantenimiento,
          ])
          ->row_array();
        // Obtener los pagos de la cuota de mantenimiento
        if (!empty($cuotaMantenimiento)) {
          $cuotaMantenimiento['pagos'] = $this->listar_pagos($idCuotaMantenimiento);
        }
      } else {
        if (empty($idUsuario) && empty($idCondominio)) {
          return false;
        }
        $this->db
          ->select(
            'cm.id_cuota_mantenimiento,
              pu.id_perfil_usuario id_perfil_usuario_paga,
              pu.perfil_usuario perfil_usuario_paga,
              us.id_usuario id_usuario_paga,
              us.nombre usuario_paga,
              e.id_edificio,
              e.edificio,
              u.id_unidad,
              u.unidad,
              cm.anio,
              cm.mes,
              (cm.total - cm.saldo) pagado,
              cm.saldo,
              cm.total,
              er.id_estatus_recaudacion,
              er.estatus_recaudacion,
              cm.estatus'
          )
          ->join('unidades u', 'u.id_unidad = cm.fk_id_unidad')
          ->join('edificios e', 'e.id_edificio = u.fk_id_edificio AND e.fk_id_condominio = ' . $idCondominio)
          ->join('cat_perfiles_usuarios pu', 'pu.id_perfil_usuario = cm.fk_id_perfil_usuario_paga')
          ->join('usuarios us', 'us.id_usuario = cm.fk_id_usuario_paga')
          ->join('cat_estatus_recaudacion er', 'er.id_estatus_recaudacion = cm.fk_id_estatus_recaudacion');
        if ($idUsuario > 0) {
          if ($esUsuarioPropietario) {
            $this->db->join(
              'unidades_propietarios up',
              'up.fk_id_unidad = cm.fk_id_unidad AND up.fk_id_usuario = ' . $idUsuario
            );
          } else {
            $this->db->where(['cm.fk_id_usuario_paga' => $idUsuario]);
          }
        }
        if ($soloActivos || $idUsuario > 0) {
          $this->db->where(['cm.estatus' => 1]);
        }
        $cuotasMantenimiento = $this->db->get('cuotas_mantenimiento cm')->result_array();
      }
      $result = !empty($idCuotaMantenimiento) ? $cuotaMantenimiento : $cuotasMantenimiento;

      return $result;
    } catch (Exception $e) {
      throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
      die();
    }
  }

  /*
     Obtener unidades para generar masivamente las cuotas de mantenimiento
       $idCondominio => ID del condominio
       $anio => Año para generar las cuotas de mantenimiento
       $mes => Mes para generar las cuotas de mantenimiento
   */
  public function listar_para_generacion_masiva($data)
  {
    try {
      if (empty($data['idCondominio']) || empty($data['anio']) || empty($data['mes'])) {
        return false;
      }

      return $this->db
        ->select(
          'u.id_unidad fk_id_unidad,
            IFNULL(puc.id_perfil_usuario, pup.id_perfil_usuario) fk_id_perfil_usuario_paga,
            IFNULL(usc.id_usuario, usp.id_usuario) fk_id_usuario_paga,
            u.cuota_mantenimiento_ordinaria ordinaria,
            u.cuota_mantenimiento_ordinaria total,
            u.cuota_mantenimiento_ordinaria saldo'
        )
        ->join('edificios e', 'e.id_edificio = u.fk_id_edificio')
        ->join('unidades_propietarios up', 'up.fk_id_unidad = u.id_unidad')
        ->join('usuarios usp', 'usp.id_usuario = up.fk_id_usuario')
        ->join('cat_perfiles_usuarios pup', 'pup.id_perfil_usuario = usp.fk_id_perfil_usuario')
        ->join('condominos_contratos cc', 'cc.fk_id_unidad = u.id_unidad AND cc.estatus = 1', 'left')
        ->join('usuarios usc', 'usc.id_usuario = cc.fk_id_usuario', 'left')
        ->join('cat_perfiles_usuarios puc', 'puc.id_perfil_usuario = usc.fk_id_perfil_usuario', 'left')
        ->join(
          'cuotas_mantenimiento cm',
          'cm.fk_id_unidad = u.id_unidad AND cm.anio = ' .
          $data['anio'] .
          ' AND cm.mes = ' .
          $data['mes'] .
          ' AND cm.estatus = 1',
          'left'
        )
        ->where('cm.id_cuota_mantenimiento IS NULL')
        ->where('u.cuota_mantenimiento_ordinaria > 0')
        ->where(['e.fk_id_condominio' => $data['idCondominio'], 'u.estatus' => 1])
        ->order_by('e.edificio, u.unidad')
        ->get('unidades u')
        ->result_array();
    } catch (Exception $e) {
      throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
      die();
    }
  }

  /*
     Obtener información para el recibo de pago.
   */
  public function listar_recibo_pago($idCuotaMantenimiento = 0)
  {
    if (empty($idCuotaMantenimiento)) {
      return false;
    }
    try {
      $cuotaMantenimiento = $this->db
        ->select(
          'cm.id_cuota_mantenimiento,
            CAST(cm.fecha_registro AS DATE) fecha_registro,
            cm.anio,
            cm.mes,
            c.condominio,
            c.domicilio,
            c.telefono,
            c.email,
            us.nombre destinatario_nombre,
            e.edificio,
            u.unidad,
            us.telefono destinatario_telefono,
            us.email destinatario_email,
            cm.ordinaria,
            cm.extraordinaria,
            cm.otros_servicios,
            cm.descuento_pronto_pago,
            cm.total,
            (cm.total - cm.saldo) pagado,
            cm.saldo,
            er.id_estatus_recaudacion,
            er.estatus_recaudacion,
            cm.notas'
        )
        ->join('unidades u', 'u.id_unidad = cm.fk_id_unidad')
        ->join('edificios e', 'e.id_edificio = u.fk_id_edificio')
        ->join('condominios c', 'c.id_condominio = e.fk_id_condominio')
        ->join('usuarios us', 'us.id_usuario = cm.fk_id_usuario_paga')
        ->join('cat_estatus_recaudacion er', 'er.id_estatus_recaudacion = cm.fk_id_estatus_recaudacion')
        ->get_where('cuotas_mantenimiento cm', [
          'cm.id_cuota_mantenimiento' => $idCuotaMantenimiento,
          'cm.estatus' => 1,
        ])
        ->row_array();
      // Obtener los pagos de la cuota de mantenimiento
      if (!empty($cuotaMantenimiento)) {
        $cuotaMantenimiento['pagos'] = $this->listar_pagos($idCuotaMantenimiento);
      }
      return $cuotaMantenimiento;
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
      'err' => true,
      'msg' => null,
    ];

    try {
      // Validar que la información a insertar sea proporcionada
      if (empty($data)) {
        $respuesta['msg'] = 'Debe especificar la información a insertar.';
        return $respuesta;
      }

      $dataPago = !empty($data['pago']) ? $data['pago'] : null;
      $dataMovimiento = !empty($data['movimiento']) ? $data['movimiento'] : null;
      unset($data['pago']);
      unset($data['movimiento']);

      // Validar que los campos existan en la tabla cuotas_mantenimiento
      if (!validar_campos('cuotas_mantenimiento', $data)) {
        $respuesta['msg'] = 'Error de integridad de la información con la base de datos (Cuota mantenimiento).';
        return $respuesta;
      }

      // Valida si el número de serie es requerido y especificado
      if (!empty($dataPago['importe'])) {
        $validacionNumeroReferencia = $this->Recaudacion_model->validar_numero_referencia(
          $dataPago['fk_id_forma_pago'],
          $dataPago['numero_referencia']
        );
        if ($validacionNumeroReferencia['err']) {
          return $validacionNumeroReferencia;
        }
      }

      // Validar que los campos existan en la tabla cuotas_mantenimiento_pagos
      if (!empty($dataPago) && !validar_campos('cuotas_mantenimiento_pagos', $dataPago)) {
        $respuesta['msg'] = 'Error de integridad de la información con la base de datos (Pago).';
        return $respuesta;
      }
      // Validar que los campos existan en la tabla fondos_monetarios_movimientos
      if (!empty($dataMovimiento)) {
        if (!validar_campos('fondos_monetarios_movimientos', $dataMovimiento)) {
          $respuesta['msg'] = 'Error de integridad de la información con la base de datos (Movimiento).';
          return $respuesta;
        }

        // Obtener registro de fondos_monetarios
        $idFondoMonetario = $dataMovimiento['fk_id_fondo_monetario'];
        $fondoMonetario = $this->db->get_where('fondos_monetarios', [
          'id_fondo_monetario' => $idFondoMonetario,
          'estatus' => 1,
        ]);

        if ($fondoMonetario->num_rows() != 1) {
          if ($fondoMonetario->num_rows() == 0) {
            $respuesta['msg'] = 'No se encontró fondo monetario.';
          } elseif ($fondoMonetario->num_rows() > 1) {
            $respuesta['msg'] = 'Se detectó más de un fondo monetario. Contactar al Administrador.';
          }
          return $respuesta;
        }
        $fondoMonetario = $fondoMonetario->row_array();

        // Obtener información para el concepto de la cuota de mantenimiento
        $datosConcepto = $this->db
          ->select('u.unidad, us.nombre usuario, pu.perfil_usuario perfil')
          ->join('usuarios us', 'us.id_usuario = ' . $data['fk_id_usuario_paga'])
          ->join('cat_perfiles_usuarios pu', 'pu.id_perfil_usuario = ' . $data['fk_id_perfil_usuario_paga'])
          ->get_where('unidades u', ['u.id_unidad' => $data['fk_id_unidad']])
          ->row_array();
        if (empty($datosConcepto)) {
          $respuesta['msg'] = 'No se pudo obtener información para generar el concepto del pago.';
          return $respuesta;
        }

        // Establecer el concepto de la cuota de mantenimiento
        $dataMovimiento['concepto'] =
          'CUOTA MANTENIMIENTO. ' .
          $datosConcepto['perfil'] .
          ': ' .
          $datosConcepto['usuario'] .
          '. UNIDAD: ' .
          $datosConcepto['unidad'];

        $dataFondoMonetario = [
          'saldo' => floatval($fondoMonetario['saldo']) + floatval($dataMovimiento['importe']),
        ];
        $dataMovimiento['saldo_anterior'] = $fondoMonetario['saldo'];
        $dataMovimiento['saldo_nuevo'] = $dataFondoMonetario['saldo'];
      }

      // Almacenar información en BD
      // Inicializar la transaccion
      $this->db->trans_start();
      $this->db->trans_strict(false);

      // Insertar registro en cuotas_mantenimiento
      // Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
      if (!$this->db->insert('cuotas_mantenimiento', $data)) {
        $respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
        $this->db->trans_rollback();
        return $respuesta;
      }

      // Obtener nuevo ID del registro
      $nuevoID = $this->db->insert_id();

      // Si hay afectación del fondo monetario
      if (!empty($dataPago)) {
        // Insertar registro en fondos_monetarios_movimientos
        // Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
        if (!$this->db->insert('fondos_monetarios_movimientos', $dataMovimiento)) {
          $respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
          $this->db->trans_rollback();
          return $respuesta;
        }
        $dataPago['fk_id_fondo_monetario_movimiento'] = $this->db->insert_id();

        // Actualizar datos en fondos_monetarios
        // Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
        if (
          !$this->db->update('fondos_monetarios', $dataFondoMonetario, [
            'id_fondo_monetario' => $idFondoMonetario,
          ])
        ) {
          $respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
          $this->db->trans_rollback();
          return $respuesta;
        }

        $dataPago['fk_id_cuota_mantenimiento'] = $nuevoID;

        // Insertar registro en cuotas_mantenimiento_pagos
        // Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
        if (!$this->db->insert('cuotas_mantenimiento_pagos', $dataPago)) {
          $respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
          $this->db->trans_rollback();
          return $respuesta;
        }
      }

      // Confirmar la transacción
      $this->db->trans_complete();
      // Confirmar la transacción y verificar el estatus de la misma
      if ($this->db->trans_status() === false) {
        $respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
        return $respuesta;
      } else {
        $respuesta['msg'] = 'Información almacenada con éxito.';
      }

      $respuesta['cuota_mantenimiento'] = $this->listar($nuevoID);
      $respuesta['err'] = false;
    } catch (Exception $e) {
      throw new Exception($e->getMessage());
      die();
    }
    return $respuesta;
  }

  /*
     Generar masivamente las cuotas de mantenimiento
       $idCondominio => ID del condominio
       $anio => Año para generar las cuotas de mantenimiento
       $mes => Mes para generar las cuotas de mantenimiento
   */
  public function generar_masivamente($data)
  {
    $respuesta = [
      'err' => true,
      'msg' => null,
    ];

    try {
      $dataCM = $this->listar_para_generacion_masiva($data);

      if (empty($dataCM)) {
        $respuesta['msg'] = 'No existen unidades pendientes de generar cuotas de mantenimiento.';
        return $respuesta;
      }

      $dataCM = agregar_columnas_arreglo($dataCM, [
        'anio' => $data['anio'],
        'mes' => $data['mes'],
        'fecha_limite_pago' => date(
          'Y-m-d',
          strtotime('-1 day', strtotime($data['anio'] . '-' . $data['mes'] . '-01'))
        ),
        'fk_id_estatus_recaudacion' => 1,
        'generada_lote' => 1,
        'fk_id_usuario_registro' => $data['idUsuarioRegistro'],
      ]);

      $dataCMA = [
        'fk_id_condominio' => $data['idCondominio'],
        'anio' => $data['anio'],
        'mes' => $data['mes'],
        'fk_id_usuario_registro' => $data['idUsuarioRegistro'],
      ];
      /* print_r($dataCM);
          exit(); */

      // Almacenar información en BD
      // Inicializar la transaccion
      $this->db->trans_start();
      $this->db->trans_strict(false);

      // Insertar registro en cuotas_mantenimiento
      // Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
      if (!$this->db->insert_batch('cuotas_mantenimiento', $dataCM)) {
        $respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
        $this->db->trans_rollback();
        return $respuesta;
      }

      // Insertar registro en cuotas_mantenimiento_autogeneradas
      // Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
      if (!$this->db->insert('cuotas_mantenimiento_autogeneradas', $dataCMA)) {
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
      }

      $respuesta['msg'] = 'Cuotas de mantenimiento generadas con éxito.';
      $respuesta['err'] = false;
    } catch (Exception $e) {
      throw new Exception($e->getMessage());
      die();
    }
    return $respuesta;
  }

  /*
     Actualizar registro
       $data => Información a actualizar
   */
  public function actualizar($data)
  {
    $respuesta = [
      'err' => true,
      'msg' => null,
    ];

    try {
      // Validar que la información a actualizar sea proporcionada
      if (empty($data)) {
        $respuesta['msg'] = 'Debe especificar la información a actualizar.';
        return $respuesta;
      }

      // Obtener ID del registro a actualizar y establecer la fecha de modificación
      $idCuotaMantenimiento = $data['id_cuota_mantenimiento'];
      $dataPago = !empty($data['pago']) ? $data['pago'] : null;
      unset($data['pago']);
      unset($data['id_cuota_mantenimiento']);
      $data['fecha_modificacion'] = date('Y-m-d H:i:s');

      // Validar que los campos existan en la tabla
      if (!validar_campos('cuotas_mantenimiento', $data)) {
        $respuesta['msg'] = 'Error de integridad de la información con la base de datos (Cuota mantenimiento).';
        return $respuesta;
      }

      // Verificar cuantos registros serán actualizados
      $registrosEncontrados = $this->db
        ->select('IF(id_cuota_mantenimiento_pago IS NOT NULL, 1, 0) tiene_pago')
        ->join(
          'cuotas_mantenimiento_pagos p',
          'p.fk_id_cuota_mantenimiento = cm.id_cuota_mantenimiento AND p.estatus = 1',
          'left'
        )
        ->limit(1)
        ->get_where('cuotas_mantenimiento cm', ['cm.id_cuota_mantenimiento' => $idCuotaMantenimiento]);

      if ($registrosEncontrados->num_rows() != 1) {
        if ($registrosEncontrados->num_rows() == 0) {
          $respuesta['msg'] = 'No se encontró coincidencia para actualizar.';
        } elseif ($registrosEncontrados->num_rows() > 1) {
          $respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
        }
        return $respuesta;
      }

      // Validar que no tenga pago registrado
      if ($registrosEncontrados->row()->tiene_pago == 1) {
        $respuesta['msg'] = 'La Cuota de Mantenimiento tiene un pago registrado.';
        return $respuesta;
      }

      // Valida si el número de serie es requerido y especificado
      // if ($data['fk_id_estatus_recaudacion'] == 2) {
      if (!empty($dataPago['importe'])) {
        $validacionNumeroReferencia = $this->Recaudacion_model->validar_numero_referencia(
          $dataPago['fk_id_forma_pago'],
          $dataPago['numero_referencia']
        );
        if ($validacionNumeroReferencia['err']) {
          return $validacionNumeroReferencia;
        }
      }

      // Validar que los campos existan en la tabla cuotas_mantenimiento_pagos
      if (!empty($dataPago) && !validar_campos('cuotas_mantenimiento_pagos', $dataPago)) {
        $respuesta['msg'] = 'Error de integridad de la información con la base de datos (Pago).';
        return $respuesta;
      }

      // Almacenar información en BD
      // Inicializar la transaccion
      $this->db->trans_start();
      $this->db->trans_strict(false);

      // Actualizar registro en cuotas_mantenimiento
      // Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
      if (
        !$this->db->update('cuotas_mantenimiento', $data, [
          'id_cuota_mantenimiento' => $idCuotaMantenimiento,
        ])
      ) {
        $respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
        $this->db->trans_rollback();
        return $respuesta;
      }

      // Insertar registro en cuotas_mantenimiento_pagos
      if (!empty($dataPago)) {
        // Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
        if (!$this->db->insert('cuotas_mantenimiento_pagos', $dataPago)) {
          $respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
          $this->db->trans_rollback();
          return $respuesta;
        }
      }

      // Confirmar la transacción
      $this->db->trans_complete();
      // Confirmar la transacción y verificar el estatus de la misma
      if ($this->db->trans_status() === false) {
        $respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
        return $respuesta;
      } else {
        $respuesta['msg'] = 'Información actualizada con éxito.';
      }

      $respuesta['cuota_mantenimiento'] = $this->listar($idCuotaMantenimiento);
      $respuesta['err'] = false;
    } catch (Exception $e) {
      throw new Exception($e->getMessage());
      die();
    }
    return $respuesta;
  }

  /*
     Establecer como pagada la recaudación
       $data => Información a procesar
   */
  public function registrar_pago($data)
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
      $idCuotaMantenimiento = $data['fk_id_cuota_mantenimiento'];
      $dataMovimiento = $data['movimiento'];
      unset($data['movimiento']);

      // Verificar cuantos registros serán actualizados
      $cuotaMantenimiento = $this->db->get_where('cuotas_mantenimiento', [
        'id_cuota_mantenimiento' => $idCuotaMantenimiento,
        'estatus' => 1,
      ]);

      if ($cuotaMantenimiento->num_rows() != 1) {
        if ($cuotaMantenimiento->num_rows() == 0) {
          $respuesta['msg'] = 'No se pudo obtener Cuota de mantenimiento.';
        } elseif ($cuotaMantenimiento->num_rows() > 1) {
          $respuesta['msg'] = 'Se detectó más de una Cuota de mantenimiento. Contactar al Administrador.';
        }
        return $respuesta;
      }
      $cuotaMantenimiento = $cuotaMantenimiento->row_array();

      // Obtener registro de fondos_monetarios
      $idFondoMonetario = $dataMovimiento['fk_id_fondo_monetario'];
      $fondoMonetario = $this->db->get_where('fondos_monetarios', [
        'id_fondo_monetario' => $idFondoMonetario,
        'estatus' => 1,
      ]);

      if ($fondoMonetario->num_rows() != 1) {
        if ($fondoMonetario->num_rows() == 0) {
          $respuesta['msg'] = 'No se encontró fondo monetario.';
        } elseif ($fondoMonetario->num_rows() > 1) {
          $respuesta['msg'] = 'Se detectó más de un fondo monetario. Contactar al Administrador.';
        }
        return $respuesta;
      }
      $fondoMonetario = $fondoMonetario->row_array();

      if ($cuotaMantenimiento['fk_id_estatus_recaudacion'] == 3) {
        $respuesta['msg'] = 'La Cuota de mantenimiento no tiene saldo pendiente.';
        return $respuesta;
      }

      if ($data['importe'] > floatval($cuotaMantenimiento['saldo'])) {
        $respuesta['msg'] = 'El importe es mayor al saldo pendiente.';
        return $respuesta;
      }

      // Valida si el número de serie es requerido y especificado
      $validacionNumeroReferencia = $this->Recaudacion_model->validar_numero_referencia(
        $data['fk_id_forma_pago'],
        $data['numero_referencia']
      );
      if ($validacionNumeroReferencia['err']) {
        return $validacionNumeroReferencia;
      }

      $dataCuotaMantenimiento = [
        'notas' => !empty($data['notas']) ? $data['notas'] : null,
        'saldo' => floatval($cuotaMantenimiento['saldo']) - $data['importe'],
        'fk_id_estatus_recaudacion' => 0,
      ];
      // Actualizar estatus de recaudación de la cuota de mantenimiento
      if (floatval($dataCuotaMantenimiento['saldo']) == 0) {
        $dataCuotaMantenimiento['fk_id_estatus_recaudacion'] = 3;
      } elseif (
        floatval($dataCuotaMantenimiento['saldo']) > 0 &&
        floatval($dataCuotaMantenimiento['saldo']) < floatval($cuotaMantenimiento['total'])
      ) {
        $dataCuotaMantenimiento['fk_id_estatus_recaudacion'] = 2;
      }
      unset($data['notas']);

      // Validar que los campos existan en la tabla cuotas_mantenimiento
      if (!validar_campos('cuotas_mantenimiento', $dataCuotaMantenimiento)) {
        $respuesta['msg'] = 'Error de integridad de la información con la base de datos (Cuota mantenimiento).';
        return $respuesta;
      }
      // Validar que los campos existan en la tabla cuotas_mantenimiento_pagos
      if (!validar_campos('cuotas_mantenimiento_pagos', $data)) {
        $respuesta['msg'] = 'Error de integridad de la información con la base de datos (Pago).';
        return $respuesta;
      }

      // Validar que los campos existan en la tabla fondos_monetarios_movimientos
      if (!validar_campos('fondos_monetarios_movimientos', $dataMovimiento)) {
        $respuesta['msg'] = 'Error de integridad de la información con la base de datos (Movimiento).';
        return $respuesta;
      }

      // Obtener información para el concepto de la cuota de mantenimiento
      $datosConcepto = $this->db
        ->select('u.unidad, us.nombre usuario, pu.perfil_usuario perfil')
        ->join('usuarios us', 'us.id_usuario = ' . $cuotaMantenimiento['fk_id_usuario_paga'])
        ->join('cat_perfiles_usuarios pu', 'pu.id_perfil_usuario = ' . $cuotaMantenimiento['fk_id_perfil_usuario_paga'])
        ->get_where('unidades u', ['u.id_unidad' => $cuotaMantenimiento['fk_id_unidad']])
        ->row_array();
      if (empty($datosConcepto)) {
        $respuesta['msg'] = 'No se pudo obtener información para generar el concepto del pago.';
        return $respuesta;
      }

      // Establecer el concepto de la cuota de mantenimiento
      $dataMovimiento['concepto'] =
        'CUOTA MANTENIMIENTO. ' .
        $datosConcepto['perfil'] .
        ': ' .
        $datosConcepto['usuario'] .
        '. UNIDAD: ' .
        $datosConcepto['unidad'];

      $dataFondoMonetario = [
        'saldo' => floatval($fondoMonetario['saldo']) + floatval($dataMovimiento['importe']),
      ];
      $dataMovimiento['saldo_anterior'] = $fondoMonetario['saldo'];
      $dataMovimiento['saldo_nuevo'] = $dataFondoMonetario['saldo'];

      // Almacenar información en BD
      // Inicializar la transaccion
      $this->db->trans_start();
      $this->db->trans_strict(false);

      // Actualizar datos en cuotas_mantenimiento
      // Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
      if (
        !$this->db->update('cuotas_mantenimiento', $dataCuotaMantenimiento, [
          'id_cuota_mantenimiento' => $idCuotaMantenimiento,
        ])
      ) {
        $respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
        $this->db->trans_rollback();
        return $respuesta;
      }

      // Actualizar datos en fondos_monetarios
      // Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
      if (
        !$this->db->update('fondos_monetarios', $dataFondoMonetario, [
          'id_fondo_monetario' => $idFondoMonetario,
        ])
      ) {
        $respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
        $this->db->trans_rollback();
        return $respuesta;
      }

      // Insertar registro en fondos_monetarios_movimientos
      // Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
      if (!$this->db->insert('fondos_monetarios_movimientos', $dataMovimiento)) {
        $respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
        $this->db->trans_rollback();
        return $respuesta;
      }
      $data['fk_id_fondo_monetario_movimiento'] = $this->db->insert_id();

      // Insertar registro en cuotas_mantenimiento_pagos
      // Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
      if (!$this->db->insert('cuotas_mantenimiento_pagos', $data)) {
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
      }

      $respuesta['cuota_mantenimiento'] = $this->listar($idCuotaMantenimiento);
      $respuesta['msg'] = 'Pago registrado con éxito.';
      $respuesta['err'] = false;
    } catch (Exception $e) {
      throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
      die();
    }
    return $respuesta;
  }

  /*
     Eliminar logicamente una cuota de mantenimiento
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
      $idCuotaMantenimiento = $data['id_cuota_mantenimiento'];

      // Verificar cuantos registros serán actualizados
      $cuotaMantenimiento = $this->db->get_where('cuotas_mantenimiento', [
        'id_cuota_mantenimiento' => $idCuotaMantenimiento,
        'estatus' => 1,
      ]);

      if ($cuotaMantenimiento->num_rows() != 1) {
        if ($cuotaMantenimiento->num_rows() == 0) {
          $respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
        } elseif ($cuotaMantenimiento->num_rows() > 1) {
          $respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
        }
        return $respuesta;
      }

      // Obtener los fondos monetarios relacionados a los pagos
      $fondosMonetarios = $this->db
        ->select(
          'fm.id_fondo_monetario,
            fm.saldo saldo_actual,
            (fm.saldo - SUM(m.importe)) saldo_nuevo'
        )
        ->join(
          'fondos_monetarios_movimientos m',
          'm.id_fondo_monetario_movimiento = p.fk_id_fondo_monetario_movimiento AND m.estatus = 1'
        )
        ->join('fondos_monetarios fm', 'fm.id_fondo_monetario = m.fk_id_fondo_monetario AND fm.estatus = 1')
        ->group_by('fm.id_fondo_monetario')
        ->get_where('cuotas_mantenimiento_pagos p', [
          'p.fk_id_cuota_mantenimiento' => $idCuotaMantenimiento,
          'p.estatus' => 1,
        ])
        ->result_array();

      // Si existen fondos monetarios relacionados con pagos
      if (!empty($fondosMonetarios)) {
        // Si saldo nuevo de algún fondo monetario será menor a cer
        if (
          count(
            array_filter($fondosMonetarios, function ($r) {
              return floatval($r['saldo_nuevo']) < 0;
            })
          )
        ) {
          $respuesta['msg'] = 'Saldo del fondo monetario insuficiente.';
          return $respuesta;
        }

        // Obtener IDs de los movimientos a los fondos monetarios
        $movimientosFondosMonetarios = $this->db
          ->select('m.id_fondo_monetario_movimiento')
          ->join(
            'fondos_monetarios_movimientos m',
            'm.id_fondo_monetario_movimiento = p.fk_id_fondo_monetario_movimiento AND m.estatus = 1'
          )
          ->get_where('cuotas_mantenimiento_pagos p', [
            'p.fk_id_cuota_mantenimiento' => $idCuotaMantenimiento,
            'p.estatus' => 1,
          ])
          ->result_array();

        if (empty($movimientosFondosMonetarios)) {
          $respuesta['msg'] = 'No se pudieron obtener los movimientos de fondos monetarios.';
          return $respuesta;
        }
      }

      $data = [
        'estatus' => 0,
        'fk_id_usuario_modifico' => $data['id_usuario_modifico'],
        'fecha_modificacion' => date('Y-m-d H:i:s'),
      ];

      // Validar que los campos existan en la tabla cuotas_mantenimiento
      if (!validar_campos('cuotas_mantenimiento', $data)) {
        $respuesta['msg'] = 'Error de integridad de la información con respecto a la base de datos.';
        return $respuesta;
      }

      // Almacenar información en BD
      // Inicializar la transaccion
      $this->db->trans_start();
      $this->db->trans_strict(false);

      // Actualizar registros en fondos_monetarios_movimientos
      foreach ($movimientosFondosMonetarios as $movimiento) {
        // Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
        if (
          !$this->db->update(
            'fondos_monetarios_movimientos',
            ['estatus' => 0],
            ['id_fondo_monetario_movimiento' => $movimiento['id_fondo_monetario_movimiento']]
          )
        ) {
          $this->db->trans_rollback();
          $respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
          return $respuesta;
        }
      }
      // Actualizar registros en fondos_monetarios
      foreach ($fondosMonetarios as $fondoMonetario) {
        // Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
        if (
          !$this->db->update(
            'fondos_monetarios',
            ['saldo' => $fondoMonetario['saldo_nuevo']],
            ['id_fondo_monetario' => $fondoMonetario['id_fondo_monetario']]
          )
        ) {
          $this->db->trans_rollback();
          $respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
          return $respuesta;
        }
      }

      // Actualizar cuotas_mantenimiento
      // Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
      if (!$this->db->update('cuotas_mantenimiento', $data, ['id_cuota_mantenimiento' => $idCuotaMantenimiento])) {
        $this->db->trans_rollback();
        $respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
        return $respuesta;
      }

      // Confirmar la transacción
      $this->db->trans_complete();
      // Confirmar la transacción y verificar el estatus de la misma
      if ($this->db->trans_status() === false) {
        $respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
        return $respuesta;
      }

      $respuesta['msg'] = 'Cuota de mantenimiento eliminada con éxito.';
      $respuesta['err'] = false;
    } catch (Exception $e) {
      throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
      die();
    }
    return $respuesta;
  }

  /*
     Eliminar logicamente el pago de una cuota de mantenimiento
       $data => Información a procesar
   */
  public function eliminar_pago($data)
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
      $idCuotaMantenimientoPago = $data['id_cuota_mantenimiento_pago'];

      // Obtener registro de cuotas_mantenimiento_pagos
      $cuotaMantenimientoPago = $this->db->get_where('cuotas_mantenimiento_pagos', [
        'id_cuota_mantenimiento_pago' => $idCuotaMantenimientoPago,
        'estatus' => 1,
      ]);

      if ($cuotaMantenimientoPago->num_rows() != 1) {
        if ($cuotaMantenimientoPago->num_rows() == 0) {
          $respuesta['msg'] = 'No se encontró el Pago de la cuota de mantenimiento.';
        } elseif ($cuotaMantenimientoPago->num_rows() > 1) {
          $respuesta['msg'] = 'Se detectó más de un Pago a la Cuota de mantenimiento. Contactar al Administrador.';
        }
        return $respuesta;
      }
      $cuotaMantenimientoPago = $cuotaMantenimientoPago->row_array();

      // Obtener registro de cuotas_mantenimiento
      $idCuotaMantenimiento = $cuotaMantenimientoPago['fk_id_cuota_mantenimiento'];
      $cuotaMantenimiento = $this->db->get_where('cuotas_mantenimiento', [
        'id_cuota_mantenimiento' => $idCuotaMantenimiento,
        'estatus' => 1,
      ]);

      if ($cuotaMantenimiento->num_rows() != 1) {
        if ($cuotaMantenimiento->num_rows() == 0) {
          $respuesta['msg'] = 'No se encontró Cuota de mantenimiento.';
        } elseif ($cuotaMantenimientoPago->num_rows() > 1) {
          $respuesta['msg'] = 'Se detectó más de una Cuota de mantenimiento. Contactar al Administrador.';
        }
        return $respuesta;
      }
      $cuotaMantenimiento = $cuotaMantenimiento->row_array();

      // Si existe movimiento en fondo monetario, obtener registro de fondos_monetarios_movimientos
      $idFondoMonetarioMovimiento = 0;
      $idFondoMonetario = 0;
      if (!empty($cuotaMantenimientoPago['fk_id_fondo_monetario_movimiento'])) {
        $idFondoMonetarioMovimiento = $cuotaMantenimientoPago['fk_id_fondo_monetario_movimiento'];
        $datosFondoMonetario = $this->db
          ->select(
            'fm.id_fondo_monetario,
              fm.saldo saldo_actual,
              (fm.saldo - m.importe) saldo_nuevo'
          )
          ->join('fondos_monetarios fm', 'fm.id_fondo_monetario = m.fk_id_fondo_monetario AND fm.estatus = 1')
          ->get_where('fondos_monetarios_movimientos m', [
            'm.id_fondo_monetario_movimiento' => $idFondoMonetarioMovimiento,
            'm.estatus' => 1,
          ]);

        if ($datosFondoMonetario->num_rows() != 1) {
          if ($datosFondoMonetario->num_rows() == 0) {
            $respuesta['msg'] = 'No se pudo obtener información del fondo monetario.';
          } elseif ($datosFondoMonetario->num_rows() > 1) {
            $respuesta['msg'] = 'Se detectó más de un fondo monetario. Contactar al Administrador.';
          }
          return $respuesta;
        }
        $datosFondoMonetario = $datosFondoMonetario->row_array();

        if ($datosFondoMonetario['saldo_nuevo'] < 0) {
          $respuesta['msg'] = 'Saldo del fondo monetario insuficiente.';
          return $respuesta;
        }
        $idFondoMonetario = $datosFondoMonetario['id_fondo_monetario'];
        $dataFondoMonetario = [
          'saldo' => $datosFondoMonetario['saldo_nuevo'],
        ];
        $dataFondoMonetarioMovimiento = [
          'estatus' => 0,
        ];
      }

      $dataPago = [
        'estatus' => 0,
        'fk_id_usuario_modifico' => $data['id_usuario_modifico'],
        'fecha_modificacion' => date('Y-m-d H:i:s'),
      ];

      $dataCuotaMantenimiento = [
        'saldo' => floatval($cuotaMantenimiento['saldo']) + floatval($cuotaMantenimientoPago['importe']),
        'fk_id_estatus_recaudacion' => 0,
      ];

      if (floatval($dataCuotaMantenimiento['saldo']) == floatval($cuotaMantenimiento['total'])) {
        $dataCuotaMantenimiento['fk_id_estatus_recaudacion'] = 1;
      } elseif (
        floatval($dataCuotaMantenimiento['saldo']) > 0 &&
        floatval($dataCuotaMantenimiento['saldo']) < floatval($cuotaMantenimiento['total'])
      ) {
        $dataCuotaMantenimiento['fk_id_estatus_recaudacion'] = 2;
      }

      // Validar que los campos existan en la tabla cuotas_mantenimiento_pagos
      if (!validar_campos('cuotas_mantenimiento_pagos', $dataPago)) {
        $respuesta['msg'] = 'Error de integridad de la información con respecto a la base de datos (Pagos).';
        return $respuesta;
      }
      // Validar que los campos existan en la tabla cuotas_mantenimiento
      if (!validar_campos('cuotas_mantenimiento', $dataCuotaMantenimiento)) {
        $respuesta['msg'] =
          'Error de integridad de la información con respecto a la base de datos (Cuotas mantenimiento).';
        return $respuesta;
      }
      // Validar que los campos existan en la tabla fondos_monetarios
      if (!validar_campos('fondos_monetarios', $dataFondoMonetario)) {
        $respuesta['msg'] = 'Error de integridad de la información con respecto a la base de datos (Fondo monetario).';
        return $respuesta;
      }
      // Validar que los campos existan en la tabla fondos_monetarios_movimientos
      if (!validar_campos('fondos_monetarios_movimientos', $dataFondoMonetarioMovimiento)) {
        $respuesta['msg'] =
          'Error de integridad de la información con respecto a la base de datos (Movimiento Fondo monetario).';
        return $respuesta;
      }

      // Almacenar información en BD
      // Inicializar la transaccion
      $this->db->trans_start();
      $this->db->trans_strict(false);

      // Actualizar datos en cuotas_mantenimiento_pagos
      // Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
      if (
        !$this->db->update('cuotas_mantenimiento_pagos', $dataPago, [
          'id_cuota_mantenimiento_pago' => $idCuotaMantenimientoPago,
        ])
      ) {
        $respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
        $this->db->trans_rollback();
        return $respuesta;
      }

      // Actualizar datos en cuotas_mantenimiento
      // Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
      if (
        !$this->db->update('cuotas_mantenimiento', $dataCuotaMantenimiento, [
          'id_cuota_mantenimiento' => $idCuotaMantenimiento,
        ])
      ) {
        $respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
        $this->db->trans_rollback();
        return $respuesta;
      }

      // Actualizar datos en fondos_monetarios_movimientos
      // Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
      if (
        !$this->db->update('fondos_monetarios_movimientos', $dataFondoMonetarioMovimiento, [
          'id_fondo_monetario_movimiento' => $idFondoMonetarioMovimiento,
        ])
      ) {
        $respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
        $this->db->trans_rollback();
        return $respuesta;
      }

      // Actualizar datos en fondos_monetarios
      // Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
      if (
        !$this->db->update('fondos_monetarios', $dataFondoMonetario, [
          'id_fondo_monetario' => $idFondoMonetario,
        ])
      ) {
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
      }

      $respuesta['cuota_mantenimiento'] = $this->listar($idCuotaMantenimiento);
      $respuesta['msg'] = 'Pago de la cuota de mantenimiento eliminado con éxito.';
      $respuesta['err'] = false;
    } catch (Exception $e) {
      throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
      die();
    }
    return $respuesta;
  }
}

/* End of file Cuota_Mantenimiento_model.php */
/* Location: ./application/models/Cuota_Mantenimiento_model.php */
