<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Tareas_model extends CI_Model
{
  public function __construct()
  {
    parent::__construct();
  }

  private function consultaBase($dataUsuario, $id = null)
  {
    $idUsuario       = $dataUsuario['idUsuario'];
    $idPerfilUsuario = $dataUsuario['idPerfilUsuario'];
    $idCondominio    = $dataUsuario['idCondominio'] ?? 0;

    $where = 't.estatus = 1';

    if ($idPerfilUsuario == PERFIL_USUARIO_SUPER_ADMINISTRADOR) {
      // ve todo
    } elseif ($idPerfilUsuario == PERFIL_USUARIO_ADMINISTRADOR) {
      $where .= ' AND t.fk_id_condominio = ' . (int)$idCondominio;
    } else {
      $where .= ' AND (t.fk_id_usuario_responsable = ' . (int)$idUsuario . ' OR t.fk_id_usuario_creador = ' . (int)$idUsuario . ')';
    }

    if (!empty($id)) {
      $where .= ' AND t.id_tarea = ' . (int)$id;
    }

    $sql = "SELECT
      t.id_tarea,
      t.titulo,
      t.descripcion,
      t.fecha_limite,
      t.prioridad,
      CASE t.prioridad WHEN 1 THEN 'Alta' WHEN 2 THEN 'Media' ELSE 'Baja' END AS prioridad_label,
      t.fk_id_estatus,
      CASE t.fk_id_estatus WHEN 1 THEN 'Pendiente' WHEN 2 THEN 'En proceso' ELSE 'Completada' END AS estatus_label,
      t.fecha_completada,
      t.recordatorio_activo,
      t.recordatorio_tiempo,
      t.recordatorio_enviado,
      t.modulo_vinculado,
      t.id_registro_vinculado,
      t.created_at,
      uc.nombre AS creador_nombre,
      ur.nombre AS responsable_nombre,
      ur.id_usuario AS responsable_id
    FROM tareas t
    JOIN usuarios uc ON uc.id_usuario = t.fk_id_usuario_creador
    JOIN usuarios ur ON ur.id_usuario = t.fk_id_usuario_responsable
    WHERE $where
    ORDER BY t.prioridad ASC, t.fecha_limite ASC";

    return $sql;
  }

  public function listar($dataUsuario, $id = null)
  {
    $sql = $this->consultaBase($dataUsuario, $id);
    $query = $this->db->query($sql);

    if (!empty($id)) {
      return $query->row_array();
    }
    return $query->result_array();
  }

  public function insertar($dataUsuario, $data)
  {
    $idUsuario    = $dataUsuario['idUsuario'];
    $idCondominio = $dataUsuario['idCondominio'] ?? 0;

    if (empty($data['titulo'])) throw new Exception('El título es requerido.');
    if (empty($data['fk_id_usuario_responsable'])) throw new Exception('El responsable es requerido.');

    $insert = [
      'titulo'                    => $data['titulo'],
      'descripcion'               => $data['descripcion'] ?? null,
      'fecha_limite'              => !empty($data['fecha_limite']) ? $data['fecha_limite'] : null,
      'prioridad'                 => $data['prioridad'] ?? 2,
      'fk_id_usuario_creador'     => $idUsuario,
      'fk_id_usuario_responsable' => $data['fk_id_usuario_responsable'],
      'fk_id_condominio'          => $idCondominio,
      'recordatorio_activo'       => $data['recordatorio_activo'] ?? 0,
      'recordatorio_tiempo'       => !empty($data['recordatorio_tiempo']) ? $data['recordatorio_tiempo'] : null,
      'modulo_vinculado'          => $data['modulo_vinculado'] ?? null,
      'id_registro_vinculado'     => $data['id_registro_vinculado'] ?? null,
      'fk_id_estatus'             => 1,
    ];

    $this->db->insert('tareas', $insert);
    $id = $this->db->insert_id();
    return $this->listar($dataUsuario, $id);
  }

  public function actualizar($dataUsuario, $id, $data)
  {
    $update = [];
    if (!empty($data['titulo']))                    $update['titulo']                    = $data['titulo'];
    if (isset($data['descripcion']))                $update['descripcion']               = $data['descripcion'];
    if (isset($data['fecha_limite']))               $update['fecha_limite']              = $data['fecha_limite'];
    if (!empty($data['prioridad']))                 $update['prioridad']                 = $data['prioridad'];
    if (!empty($data['fk_id_usuario_responsable'])) $update['fk_id_usuario_responsable'] = $data['fk_id_usuario_responsable'];
    if (isset($data['recordatorio_activo']))        $update['recordatorio_activo']       = $data['recordatorio_activo'];
    if (isset($data['recordatorio_tiempo']))        $update['recordatorio_tiempo']       = !empty($data['recordatorio_tiempo']) ? $data['recordatorio_tiempo'] : null;
    if (isset($data['modulo_vinculado']))           $update['modulo_vinculado']          = $data['modulo_vinculado'];
    if (isset($data['id_registro_vinculado']))      $update['id_registro_vinculado']     = $data['id_registro_vinculado'];
    if (empty($update)) throw new Exception('No hay datos para actualizar.');
    $this->db->where('id_tarea', $id)->update('tareas', $update);
    return $this->listar($dataUsuario, $id);
  }

  public function cambiar_estatus($dataUsuario, $id, $nuevoEstatus)
  {
    $update = ['fk_id_estatus' => $nuevoEstatus];
    $update['fecha_completada'] = ($nuevoEstatus == 3) ? date('Y-m-d H:i:s') : null;
    $this->db->where('id_tarea', $id)->update('tareas', $update);
    return $this->listar($dataUsuario, $id);
  }

  public function eliminar($dataUsuario, $id)
  {
    $this->db->where('id_tarea', $id)->update('tareas', ['estatus' => 0]);
    return true;
  }

  public function listar_usuarios_asignables($idPerfilUsuario)
  {
    $sql = "SELECT id_usuario, nombre, fk_id_perfil_usuario
            FROM usuarios
            WHERE estatus = 1
              AND fk_id_perfil_usuario IN(1,2,3)
            ORDER BY fk_id_perfil_usuario ASC, nombre ASC";
    return $this->db->query($sql)->result_array();
  }

}
