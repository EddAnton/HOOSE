<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Modelo: Asamblea
 *
 * Este modelo realiza las operaciones requeridas sobre la información de las Asambleas
 *
 * @package   CodeIgniter
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Asamblea_model extends CI_Model
{
  public function __construct()
  {
    parent::__construct();
  }

  /*
     Obtener información del identificador especificado o todos los registros.
       $id => ID especifico a obtener, si se requiere
       $soloActivos => Determinar si se requieren todos los registros o sólo los activos
   */
  public function listar($idAsamblea = 0, $idCondominio = 0, $soloActivos = false)
  {
    try {
      // Si no se proporciona ID de condominio, aborta el proceso
      if (empty($idAsamblea) && empty($idCondominio)) {
        return false;
      }

      $this->db
        ->select(
          'a.id_asamblea,
            ta.id_tipo_asamblea,
            ta.tipo_asamblea,
            a.fecha_hora,
            a.lugar,
            a.convocatoria_quien_emite,
            IF(a.fecha_hora <= NOW(), 1, 0) convocatoria_vencida,
            IF(ISNULL(aa.id_asamblea_acta), 0, 1) tiene_acta,
            IFNULL(aa.id_asamblea_acta, 0) id_acta,
            IFNULL(aa.finalizada, 0) acta_finalizada,
            a.estatus'
        )
        ->join('cat_tipos_asambleas ta', 'ta.id_tipo_asamblea = a.fk_id_tipo_asamblea')
        ->join('asambleas_actas aa', 'aa.fk_id_asamblea = a.id_asamblea AND aa.estatus = 1', 'left');

      if ($idAsamblea > 0) {
        $this->db->where(['a.id_asamblea' => $idAsamblea]);
      } else {
        $this->db->where(['a.fk_id_condominio' => $idCondominio]);
      }
      if ($soloActivos) {
        $this->db->where(['a.estatus' => 1]);
      }

      $respuesta = $this->db->order_by('a.fecha_hora DESC')->get('asambleas a');
      if ($idAsamblea > 0) {
        $respuesta = $respuesta->row_array();
      } else {
        $respuesta = $respuesta->result_array();
      }

      return $respuesta;
    } catch (Exception $e) {
      throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
      die();
    }
  }

  /*
     Obtener información del identificador especificado o todos los registros.
       $id => ID especifico a obtener, si se requiere
       $soloActivos => Determinar si se requieren todos los registros o sólo los activos
   */
  public function detalle($idAsamblea = 0)
  {
    try {
      // Si no se proporciona ID de condominio, aborta el proceso
      if (empty($idAsamblea)) {
        return false;
      }

      $respuesta = $this->db
        ->select(
          'a.id_asamblea,
            ta.id_tipo_asamblea,
            ta.tipo_asamblea,
            a.fecha_hora,
            a.lugar,
            a.convocatoria_quien_emite,
            IF(a.fecha_hora <= NOW(), 1, 0) convocatoria_vencida,
            IF(ISNULL(aa.id_asamblea_acta), 0, 1) tiene_acta,
            IFNULL(aa.id_asamblea_acta, 0) id_acta,
            IFNULL(aa.finalizada, 0) acta_finalizada,
            a.fundamento_legal,
            a.convocatoria_cierre,
            a.convocatoria_fecha,
            a.convocatoria_ciudad,
            a.estatus'
        )
        ->join('cat_tipos_asambleas ta', 'ta.id_tipo_asamblea = a.fk_id_tipo_asamblea')
        ->join('asambleas_actas aa', 'aa.fk_id_asamblea = a.id_asamblea AND aa.estatus = 1', 'left')
        ->where(['a.id_asamblea' => $idAsamblea, 'a.estatus' => 1])
        ->order_by('a.fecha_hora DESC')
        ->get('asambleas a')
        ->row_array();

      if (empty($respuesta)) {
        return false;
      }

      $respuesta['orden_dia'] = $this->db
        ->select(
          'id_asamblea_orden_dia,
            orden_dia,
            requiere_votacion'
        )
        ->order_by('id_asamblea_orden_dia')
        ->get_where('asambleas_orden_dia', ['fk_id_asamblea' => $idAsamblea])
        ->result_array();
      $respuesta['acta'] = $this->listar_acta($respuesta['id_acta']);

      return $respuesta;
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

      $dataOrdenDia = $data['dataOrdenDia'];
      unset($data['dataOrdenDia']);

      // Validar que los campos existan en la tabla
      if (!validar_campos('asambleas', $data)) {
        $respuesta['msg'] = 'Error de integridad de la información con la base de datos (Asamblea).';
        return $respuesta;
      }
      // Validar que los campos existan en la tabla
      foreach ($dataOrdenDia as $ordenDia) {
        if (!validar_campos('asambleas_orden_dia', $ordenDia)) {
          $respuesta['msg'] = 'Error de integridad de la información con la base de datos (Orden del día).';
          return $respuesta;
        }
      }

      // Almacenar información en BD
      // Inicializar la transaccion
      $this->db->trans_start();
      $this->db->trans_strict(false);

      // Si existe error al almacenar el asamblea, obtiene descripción del mismo y aborta el proceso
      if (!$this->db->insert('asambleas', $data)) {
        $respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
        $this->db->trans_rollback();
        return $respuesta;
      }
      $nuevoID = $this->db->insert_id();

      $dataOrdenDia = agregar_columnas_arreglo($dataOrdenDia, ['fk_id_asamblea' => $nuevoID]);
      // Si existe error al almacenar el orden del día, obtiene descripción del mismo y aborta el proceso
      if (!$this->db->insert_batch('asambleas_orden_dia', $dataOrdenDia)) {
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

      $respuesta['asamblea'] = $this->listar($nuevoID);
      $respuesta['msg'] = 'Información registrada con éxito';
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
      // Validar que la información a insertar sea proporcionada
      if (empty($data)) {
        $respuesta['msg'] = 'Debe especificar la información a actualizar.';
        return $respuesta;
      }

      // Obtener ID del registro a actualizar y establecer la fecha de modificación
      $idAsamblea = $data['id_asamblea'];
      $data['fecha_modificacion'] = date('Y-m-d H:i:s');
      $dataOrdenDia = $data['dataOrdenDia'];
      unset($data['id_asamblea']);
      unset($data['dataOrdenDia']);

      $dataOrdenDia = agregar_columnas_arreglo($dataOrdenDia, ['fk_id_asamblea' => $idAsamblea]);

      // Validar que los campos existan en la tabla
      if (!validar_campos('asambleas', $data)) {
        $respuesta['msg'] = 'Error de integridad de la información con la base de datos (.';
        return $respuesta;
      }

      // Validar que los campos existan en la tabla asambleas_orden_dia
      foreach ($dataOrdenDia as $orden) {
        if (!validar_campos('asambleas_orden_dia', $orden)) {
          $respuesta['msg'] = 'Error de integridad de la información con la base de datos (Ordenes del día).';
          return $respuesta;
        }
      }

      // Verificar cuantos registros serán actualizados
      $registros_coincidentes = $this->db->get_where('asambleas', ['id_asamblea' => $idAsamblea])->num_rows();

      if ($registros_coincidentes != 1) {
        if ($registros_coincidentes == 0) {
          $respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
        } elseif ($registros_coincidentes > 1) {
          $respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
        }
        return $respuesta;
      }

      // Almacenar información en BD
      // Inicializar la transaccion
      $this->db->trans_start();
      $this->db->trans_strict(false);

      // Borrar el orden del día existente
      // Si existe error al borrar los registros, obtiene descripción del mismo y aborta el proceso
      if (!$this->db->delete('asambleas_orden_dia', ['fk_id_asamblea' => $idAsamblea])) {
        $respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
        $this->db->trans_rollback();
        return $respuesta;
      }

      // Insertar registro en asambleas_orden_dia
      // Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
      if (!$this->db->insert_batch('asambleas_orden_dia', $dataOrdenDia)) {
        $respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
        $this->db->trans_rollback();
        return $respuesta;
      }

      // Actualizar registro en asambleas
      // Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
      if (!$this->db->update('asambleas', $data, ['id_asamblea' => $idAsamblea])) {
        $respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
        $this->db->trans_rollback();
        return $respuesta;
      }

      /* $respuesta['err'] = !$this->db->update('asambleas', $data, [
        'id_asamblea' => $idAsamblea,
      ]); */

      // Confirmar la transacción
      $this->db->trans_complete();
      // Confirmar la transacción y verificar el estatus de la misma
      if ($this->db->trans_status() === false) {
        $respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
        return $respuesta;
      }

      $respuesta['asamblea'] = $this->listar($idAsamblea);
      $respuesta['msg'] = 'Información actualizada con éxito.';
      $respuesta['err'] = false;

    } catch (Exception $e) {
      throw new Exception($e->getMessage());
      die();
    }
    return $respuesta;
  }

  /*
     Eliminar logicamente un registro
       $data => Información a procesar
   */
  public function eliminar($data)
  {
    $respuesta = [
      'error' => true,
      'msg' => null,
    ];

    try {
      // Validar que la información a procesar sea proporcionada
      if (empty($data)) {
        $respuesta['msg'] = 'Debe especificar la información a procesar.';
        return $respuesta;
      }

      // Obtener ID del registro a actualizar
      $idAsamblea = $data['id_asamblea'];
      $data = [
        'estatus' => 0,
        'fk_id_usuario_modifico' => $data['id_usuario_modifico'],
        'fecha_modificacion' => date('Y-m-d H:i:s'),
      ];

      // Validar que los campos existan en la tabla usuarios
      if (!validar_campos('asambleas', $data)) {
        $respuesta['msg'] = 'Error de integridad de la información con respecto a la base de datos.';
        return $respuesta;
      }

      // Verificar cuantos registros serán actualizados
      $asamblea = $this->db->get_where('asambleas', [
        'id_asamblea' => $idAsamblea,
        'estatus' => 1,
      ]);

      if ($asamblea->num_rows() != 1) {
        if ($asamblea->num_rows() == 0) {
          $respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
        } elseif ($asamblea->num_rows() > 1) {
          $respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
        }
        return $respuesta;
      }

      // Almacenar información en BD
      // Actualizar datos en asambleas
      // Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
      if (!$this->db->update('asambleas', $data, ['id_asamblea' => $idAsamblea])) {
        $respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
        return $respuesta;
      }

      $respuesta['msg'] = 'Asamblea eliminada con éxito.';
      $respuesta['error'] = false;
    } catch (Exception $e) {
      throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
      die();
    }
    return $respuesta;
  }


  /*
     Obtener el orden del día de la asamblea
       $id => ID especifico a obtener, si se requiere
       $soloActivos => Determinar si se requieren todos los registros o sólo los activos
   */
  public function listar_orden_dia($idAsamblea = 0)
  {
    try {
      return $this->db
        ->select(
          'id_asamblea_orden_dia,
            orden_dia,
            requiere_votacion'
        )
        ->order_by('id_asamblea_orden_dia')
        ->get_where('asambleas_orden_dia', ['fk_id_asamblea' => $idAsamblea])
        ->result_array();
    } catch (Exception $e) {
      throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
      die();
    }
  }

  /*
  Obtener información del acta de la asamblea
     $id => ID especifico a obtener, si se requiere
*/
  function listar_acta($idAsamblea)
  {
    try {
      // Acta
      $acta = $this->db->select(
        'id_asamblea_acta id_acta,
          fk_id_asamblea id_asamblea,
          fecha_hora,
          lugar,
          apertura,
          cierre,
          quien_emite,
          finalizada'
      )
        ->where(['fk_id_asamblea' => $idAsamblea])
        ->get_where('asambleas_actas', ['estatus' => 1])
        ->row_array();

      if (empty($acta)) {
        return $acta;
      }
      $idActa = $acta['id_acta'];

      // Pase lista
      $acta['pase_lista'] = $this->db
        ->select(
          'u.id_usuario,
            u.nombre usuario,
            pu.perfil_usuario perfil,
            CASE
              WHEN pu.id_perfil_usuario = 4 THEN CONCAT(ep.edificio, " · ", up.unidad)
              WHEN pu.id_perfil_usuario = 5 THEN CONCAT(ec.edificio, " · ", uc.unidad)
              ELSE "SIN DEFINIR"
            END unidad,
            apl.asistencia'
        )
        ->join('usuarios u', 'u.id_usuario = apl.fk_id_usuario')
        ->join('cat_perfiles_usuarios pu', 'pu.id_perfil_usuario = u.fk_id_perfil_usuario')

        ->join('unidades_propietarios upp', 'upp.fk_id_usuario = u.id_usuario AND upp.estatus = 1', 'left')
        ->join('unidades up', 'up.id_unidad = upp.fk_id_unidad AND up.estatus = 1', 'left')
        ->join('edificios ep', 'ep.id_edificio = up.fk_id_edificio AND ep.estatus = 1', 'left')

        ->join('condominos_contratos cc', 'cc.fk_id_usuario = u.id_usuario AND cc.estatus = 1', 'left')
        ->join('unidades uc', 'uc.id_unidad = cc.fk_id_unidad AND uc.estatus = 1', 'left')
        ->join('edificios ec', 'ec.id_edificio = uc.fk_id_edificio AND ec.estatus = 1', 'left')

        ->get_where('asambleas_actas_pase_lista apl', ['apl.fk_id_asamblea_acta' => $idActa])
        ->result_array();

      // Orden del día
      $acta['orden_dia'] = $this->db
        ->select(
          'od.id_asamblea_orden_dia,
            od.orden_dia,
            od.requiere_votacion,
            aod.apertura,
            aod.cierre'
        )
        ->join(
          'asambleas_actas_orden_dia aod',
          'aod.fk_id_asamblea_acta = ' . $idActa . ' AND aod.fk_id_asamblea_orden_dia = od.id_asamblea_orden_dia',
          'left'
        )
        ->get_where('asambleas_orden_dia od', [
          'od.fk_id_asamblea' => $acta['id_asamblea'],
        ])
        ->result_array();

      // Votaciones orden del día
      // 
      $orden_dia_votaciones = $this->db
        ->select(
          'aodv.fk_id_asamblea_orden_dia id_asamblea_orden_dia,
            u.id_usuario,
            u.nombre usuario,
            pu.perfil_usuario perfil,
            CASE
              WHEN pu.id_perfil_usuario = 4 THEN CONCAT(ep.edificio, " · ", up.unidad)
              WHEN pu.id_perfil_usuario = 5 THEN CONCAT(ec.edificio, " · ", uc.unidad)
              ELSE "SIN DEFINIR"
            END unidad,
            IFNULL(apl.asistencia, 0) puede_votar,
            aodv.votacion'
        )
        ->join('usuarios u', 'u.id_usuario = aodv.fk_id_usuario')
        ->join('cat_perfiles_usuarios pu', 'pu.id_perfil_usuario = u.fk_id_perfil_usuario')

        ->join('unidades_propietarios upp', 'upp.fk_id_usuario = u.id_usuario AND upp.estatus = 1', 'left')
        ->join('unidades up', 'up.id_unidad = upp.fk_id_unidad AND up.estatus = 1', 'left')
        ->join('edificios ep', 'ep.id_edificio = up.fk_id_edificio AND ep.estatus = 1', 'left')

        ->join('condominos_contratos cc', 'cc.fk_id_usuario = u.id_usuario AND cc.estatus = 1', 'left')
        ->join('unidades uc', 'uc.id_unidad = cc.fk_id_unidad AND uc.estatus = 1', 'left')
        ->join('edificios ec', 'ec.id_edificio = uc.fk_id_edificio AND ec.estatus = 1', 'left')

        ->join(
          'asambleas_actas_pase_lista apl',
          'apl.fk_id_asamblea_acta = aodv.fk_id_asamblea_acta
            AND apl.fk_id_usuario = aodv.fk_id_usuario',
          'left'
        )
        ->get_where('asambleas_actas_orden_dia_votaciones aodv', [
          'aodv.fk_id_asamblea_acta' => $idActa,
        ])
        ->result_array();

      // Agregar las votaciones al orden del día correspondiente
      if (!empty($orden_dia_votaciones)) {
        foreach ($acta['orden_dia'] as &$orden_dia_acta) {
          $orden_dia_acta['votaciones'] = array_values(array_filter($orden_dia_votaciones, function ($od) use ($orden_dia_acta) {
            return $od['id_asamblea_orden_dia'] == $orden_dia_acta['id_asamblea_orden_dia'];
          }));
        }
      }

      return $acta;
    } catch (Exception $e) {
      throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
      die();
    }
  }

  /*
     Guardar acta
       $data => Información a insertar
   */
  public function guardar_acta($data)
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

      $dataPaseLista = $data['dataPaseLista'];
      $dataOrdenDia = $data['dataOrdenDia'];
      $dataVotaciones = $data['dataVotaciones'];
      unset($data['dataVotaciones']);
      unset($data['dataOrdenDia']);
      unset($data['dataPaseLista']);

      // Validar que los campos existan en la tabla: asambleas_actas
      if (!validar_campos('asambleas_actas', $data)) {
        $respuesta['msg'] = 'Error de integridad de la información con la base de datos (Acta).';
        return $respuesta;
      }
      // Validar que los campos existan en la tabla: asambleas_actas_pase_lista
      foreach ($dataPaseLista as $pase) {
        if (!validar_campos('asambleas_actas_pase_lista', $pase)) {
          $respuesta['msg'] = 'Error de integridad de la información con la base de datos (Pase lista).';
          return $respuesta;
        }
      }


      // Validar que los campos existan en la tabla: asambleas_actas_orden_dia
      foreach ($dataOrdenDia as $orden) {
        if (!validar_campos('asambleas_actas_orden_dia', $orden)) {
          $respuesta['msg'] = 'Error de integridad de la información con la base de datos (Orden del día).';
          return $respuesta;
        }
      }
      // Validar que los campos existan en la tabla: asambleas_actas_orden_dia_votaciones
      foreach ($dataVotaciones as $votacion) {
        if (!validar_campos('asambleas_actas_orden_dia_votaciones', $votacion)) {
          $respuesta['msg'] = 'Error de integridad de la información con la base de datos (Votaciones).';
          return $respuesta;
        }
      }

      // Verificar si se creará acta o se modificará la existente
      $idActa = $this->db
        ->select('id_asamblea_acta')
        ->get_where('asambleas_actas', [
          'fk_id_asamblea' => $data['fk_id_asamblea'],
        ]);

      if ($idActa->num_rows() > 1) {
        $respuesta['msg'] = 'Existe más de un Acta para la Asamblea. Contactar al Administrador.';
        return $respuesta;
      }

      $idActa = ($idActa->num_rows() == 1) ? $idActa->row_array()['id_asamblea_acta'] : 0;
      $bInsertar = $idActa == 0;

      // Almacenar información en BD
      // Inicializar la transaccion
      $this->db->trans_start();
      $this->db->trans_strict(false);

      $resultadoOperacion = ($bInsertar)
        ? $this->db->insert('asambleas_actas', $data)
        : $this->db->update('asambleas_actas', $data, ['id_asamblea_acta' => $idActa]);

      // Si existe error al almacenar el acta, obtiene descripción del mismo y aborta el proceso
      if (!$resultadoOperacion) {
        $respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
        $this->db->trans_rollback();
        return $respuesta;
      }

      if ($bInsertar) {
        $idActa = $this->db->insert_id();
      }

      // Agregar ID del acta al pase de lista
      $dataPaseLista = agregar_columnas_arreglo($dataPaseLista, ['fk_id_asamblea_acta' => $idActa]);
      // Agregar ID del acta a los puntos del orden del día
      $dataOrdenDia = agregar_columnas_arreglo($dataOrdenDia, ['fk_id_asamblea_acta' => $idActa]);
      // Agregar ID del acta a las votaciones de los puntos del orden del día
      $dataVotaciones = agregar_columnas_arreglo($dataVotaciones, ['fk_id_asamblea_acta' => $idActa]);

      if (!$bInsertar) {
        // Borrar las votaciones existentes para los puntos del orden del día
        // Si existe error al borrar los registros, obtiene descripción del mismo y aborta el proceso
        if (!$this->db->delete('asambleas_actas_orden_dia_votaciones', ['fk_id_asamblea_acta' => $idActa])) {
          $respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
          $this->db->trans_rollback();
          return $respuesta;
        }

        // Borrar info de los puntos del orden del día
        // Si existe error al borrar los registros, obtiene descripción del mismo y aborta el proceso
        if (!$this->db->delete('asambleas_actas_orden_dia', ['fk_id_asamblea_acta' => $idActa])) {
          $respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
          $this->db->trans_rollback();
          return $respuesta;
        }

        // Borrar el pase de lista
        // Si existe error al borrar los registros, obtiene descripción del mismo y aborta el proceso
        if (!$this->db->delete('asambleas_actas_pase_lista', ['fk_id_asamblea_acta' => $idActa])) {
          $respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
          $this->db->trans_rollback();
          return $respuesta;
        }
      }

      // Si existe error al almacenar el pase de lista, obtiene descripción del mismo y aborta el proceso
      if (!$this->db->insert_batch('asambleas_actas_pase_lista', $dataPaseLista)) {
        $respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
        $this->db->trans_rollback();
        return $respuesta;
      }
      // Si existe error al almacenar la info de los puntos del orden del día, obtiene descripción del mismo y aborta el proceso
      if (!$this->db->insert_batch('asambleas_actas_orden_dia', $dataOrdenDia)) {
        $respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
        $this->db->trans_rollback();
        return $respuesta;
      }
      // Si existe error al almacenar las votaciones, obtiene descripción del mismo y aborta el proceso
      if (!$this->db->insert_batch('asambleas_actas_orden_dia_votaciones', $dataVotaciones)) {
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

      $respuesta['asamblea'] = $this->listar($data['fk_id_asamblea']);
      if ($data['finalizada'] == 1) {
        $respuesta['acta'] = $this->listar_acta($data['fk_id_asamblea']);
      }
      $respuesta['msg'] = 'Información registrada con éxito';
      $respuesta['err'] = false;
    } catch (Exception $e) {
      throw new Exception($e->getMessage());
      die();
    }
    return $respuesta;
  }
}

/* End of file Asamblea_model.php */
/* Location: ./application/models/Asamblea_model.php */
