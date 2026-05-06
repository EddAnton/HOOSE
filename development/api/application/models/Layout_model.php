<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Layout_model extends CI_Model
{
  private $defaultLayout = [
    ['seccion' => 'catalogo',     'orden' => 1, 'visible' => 1, 'col_size' => '12'],
    ['seccion' => 'financieras',  'orden' => 2, 'visible' => 1, 'col_size' => '12'],
    ['seccion' => 'fondos',       'orden' => 3, 'visible' => 1, 'col_size' => '12'],
    ['seccion' => 'grafica',      'orden' => 4, 'visible' => 1, 'col_size' => '12'],
    ['seccion' => 'operativas',   'orden' => 5, 'visible' => 1, 'col_size' => '12'],
    ['seccion' => 'tareas',       'orden' => 6, 'visible' => 1, 'col_size' => '6'],
    ['seccion' => 'gastos',       'orden' => 7, 'visible' => 1, 'col_size' => '6'],
    ['seccion' => 'graficas_ext', 'orden' => 8, 'visible' => 1, 'col_size' => '12'],
  ];

  public function getLayout($idUsuario)
  {
    $rows = $this->db->where('fk_id_usuario', $idUsuario)
      ->order_by('orden')
      ->get('usuarios_tablero_layout')
      ->result_array();

    if (empty($rows)) return $this->defaultLayout;

    return array_map(function($r) {
      return [
        'seccion'  => $r['seccion'],
        'orden'    => intval($r['orden']),
        'visible'  => intval($r['visible']),
        'col_size' => $r['col_size'],
      ];
    }, $rows);
  }

  public function saveLayout($idUsuario, $secciones)
  {
    foreach ($secciones as $s) {
      $this->db->replace('usuarios_tablero_layout', [
        'fk_id_usuario' => $idUsuario,
        'seccion'       => $s['seccion'],
        'orden'         => $s['orden'],
        'visible'       => $s['visible'],
        'col_size'      => $s['col_size'] ?? '12',
      ]);
    }
  }
}
