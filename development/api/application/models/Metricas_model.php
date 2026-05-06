<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Metricas_model extends CI_Model
{
  public function tablero($idCondominio, $comparativo = 'mes_anterior')
  {
    $anioActual = intval(date('Y'));
    $mesActual  = intval(date('m'));

    if ($comparativo === 'mes_anterior') {
      $anioAnterior = intval(date('Y', strtotime('-1 month')));
      $mesAnterior  = intval(date('m', strtotime('-1 month')));
    } else {
      $anioAnterior = $anioActual - 1;
      $mesAnterior  = $mesActual;
    }

    $inicioActual   = "$anioActual-" . str_pad($mesActual, 2, '0', STR_PAD_LEFT) . "-01";
    $finActual      = date('Y-m-t', strtotime($inicioActual));
    $inicioAnterior = "$anioAnterior-" . str_pad($mesAnterior, 2, '0', STR_PAD_LEFT) . "-01";
    $finAnterior    = date('Y-m-t', strtotime($inicioAnterior));

    return [
      'ocupacion'   => $this->getOcupacion($idCondominio),
      'quejas'      => $this->getQuejas($idCondominio, $inicioActual, $finActual, $inicioAnterior, $finAnterior),
      'asambleas'   => $this->getAsambleas($idCondominio, $anioActual, $anioAnterior),
      'visitas'     => $this->getVisitas($idCondominio, $inicioActual, $finActual, $inicioAnterior, $finAnterior),
      'proyectos'   => $this->getProyectos($idCondominio, $inicioActual, $finActual, $inicioAnterior, $finAnterior),
      'fondos'      => $this->getFondos($idCondominio),
      'financieras' => $this->getFinancieras($idCondominio, $anioActual, $mesActual, $anioAnterior, $mesAnterior),
    ];
  }

  private function calcularComparativo($actual, $anterior)
  {
    $diferencia = $actual - $anterior;
    $porcentaje = $anterior > 0 ? round(($diferencia / $anterior) * 100, 1) : 0;
    return [
      'actual'     => $actual,
      'anterior'   => $anterior,
      'diferencia' => $diferencia,
      'porcentaje' => $porcentaje,
      'tendencia'  => $diferencia > 0 ? 'up' : ($diferencia < 0 ? 'down' : 'equal'),
    ];
  }

  private function getOcupacion($idCondominio)
  {
    $sql = "SELECT COUNT(*) total, COUNT(up.fk_id_unidad) ocupadas
    FROM unidades u
    JOIN edificios e ON e.id_edificio = u.fk_id_edificio AND e.estatus = 1
    LEFT JOIN unidades_propietarios up ON up.fk_id_unidad = u.id_unidad
    WHERE e.fk_id_condominio = ? AND u.estatus = 1";
    $row = $this->db->query($sql, [$idCondominio])->row_array();
    $total   = intval($row['total'] ?? 0);
    $ocupadas = intval($row['ocupadas'] ?? 0);
    return [
      'total'      => $total,
      'ocupadas'   => $ocupadas,
      'porcentaje' => $total > 0 ? round(($ocupadas / $total) * 100, 1) : 0,
    ];
  }

  private function getQuejas($idCondominio, $inicioActual, $finActual, $inicioAnterior, $finAnterior)
  {
    $sql = "SELECT
      SUM(CASE WHEN DATE(fecha_registro) BETWEEN ? AND ? THEN 1 ELSE 0 END) actual,
      SUM(CASE WHEN DATE(fecha_registro) BETWEEN ? AND ? THEN 1 ELSE 0 END) anterior,
      SUM(CASE WHEN fk_id_estatus_queja = 1 AND estatus = 1 THEN 1 ELSE 0 END) sin_revisar,
      SUM(CASE WHEN fk_id_estatus_queja = 2 AND estatus = 1 THEN 1 ELSE 0 END) en_proceso,
      SUM(CASE WHEN fk_id_estatus_queja = 3 AND estatus = 1 THEN 1 ELSE 0 END) atendidas
    FROM quejas WHERE fk_id_condominio = ? AND estatus = 1";
    $row = $this->db->query($sql, [$inicioActual, $finActual, $inicioAnterior, $finAnterior, $idCondominio])->row_array();
    $comp = $this->calcularComparativo(intval($row['actual']), intval($row['anterior']));
    $comp['sin_revisar'] = intval($row['sin_revisar']);
    $comp['en_proceso']  = intval($row['en_proceso']);
    $comp['atendidas']   = intval($row['atendidas']);
    return $comp;
  }

  private function getAsambleas($idCondominio, $anioActual, $anioAnterior)
  {
    $sql = "SELECT
      SUM(CASE WHEN YEAR(fecha_hora) = ? THEN 1 ELSE 0 END) actual,
      SUM(CASE WHEN YEAR(fecha_hora) = ? THEN 1 ELSE 0 END) anterior,
      SUM(CASE WHEN fk_id_tipo_asamblea = 1 AND YEAR(fecha_hora) = ? THEN 1 ELSE 0 END) ordinarias,
      SUM(CASE WHEN fk_id_tipo_asamblea = 2 AND YEAR(fecha_hora) = ? THEN 1 ELSE 0 END) extraordinarias
    FROM asambleas WHERE fk_id_condominio = ? AND estatus = 1";
    $row = $this->db->query($sql, [$anioActual, $anioAnterior, $anioActual, $anioActual, $idCondominio])->row_array();
    $comp = $this->calcularComparativo(intval($row['actual']), intval($row['anterior']));
    $comp['ordinarias']      = intval($row['ordinarias']);
    $comp['extraordinarias'] = intval($row['extraordinarias']);
    return $comp;
  }

  private function getVisitas($idCondominio, $inicioActual, $finActual, $inicioAnterior, $finAnterior)
  {
    $sql = "SELECT
      SUM(CASE WHEN DATE(v.fecha_hora_entrada) BETWEEN ? AND ? THEN 1 ELSE 0 END) actual,
      SUM(CASE WHEN DATE(v.fecha_hora_entrada) BETWEEN ? AND ? THEN 1 ELSE 0 END) anterior
    FROM visitas v
    JOIN unidades u ON u.id_unidad = v.fk_id_unidad AND u.estatus = 1
    JOIN edificios e ON e.id_edificio = u.fk_id_edificio AND e.estatus = 1
    WHERE e.fk_id_condominio = ? AND v.estatus = 1";
    $row = $this->db->query($sql, [$inicioActual, $finActual, $inicioAnterior, $finAnterior, $idCondominio])->row_array();
    return $this->calcularComparativo(intval($row['actual']), intval($row['anterior']));
  }

  private function getProyectos($idCondominio, $inicioActual, $finActual, $inicioAnterior, $finAnterior)
  {
    $sql = "SELECT COUNT(*) total,
      SUM(CASE WHEN estatus=1 THEN 1 ELSE 0 END) en_curso,
      SUM(CASE WHEN estatus=2 THEN 1 ELSE 0 END) completados,
      SUM(presupuesto) inversion_total,
      SUM(CASE WHEN DATE(fecha_registro) BETWEEN ? AND ? THEN 1 ELSE 0 END) actual,
      SUM(CASE WHEN DATE(fecha_registro) BETWEEN ? AND ? THEN 1 ELSE 0 END) anterior
    FROM proyectos WHERE fk_id_condominio = ? AND estatus IN(1,2)";
    $row = $this->db->query($sql, [$inicioActual, $finActual, $inicioAnterior, $finAnterior, $idCondominio])->row_array();
    $comp = $this->calcularComparativo(intval($row['actual']), intval($row['anterior']));
    $comp['total']           = intval($row['total']);
    $comp['en_curso']        = intval($row['en_curso']);
    $comp['completados']     = intval($row['completados']);
    $comp['inversion_total'] = floatval($row['inversion_total']);
    return $comp;
  }

  private function getFondos($idCondominio)
  {
    $sql = "SELECT fondo_monetario, banco, saldo FROM fondos_monetarios WHERE fk_id_condominio = ? AND estatus = 1 ORDER BY saldo DESC";
    $fondos = $this->db->query($sql, [$idCondominio])->result_array();
    return ['total' => floatval(array_sum(array_column($fondos, 'saldo'))), 'fondos' => $fondos];
  }

  private function getFinancieras($idCondominio, $anioActual, $mesActual, $anioAnterior, $mesAnterior)
  {
    $sqlRec = "SELECT
      SUM(CASE WHEN anio=? AND mes=? THEN renta+agua+energia_electrica+gas+seguridad+servicios_publicos+otros_servicios ELSE 0 END) actual,
      SUM(CASE WHEN anio=? AND mes=? THEN renta+agua+energia_electrica+gas+seguridad+servicios_publicos+otros_servicios ELSE 0 END) anterior
    FROM recaudaciones r
    JOIN unidades u ON u.id_unidad = r.fk_id_unidad AND u.estatus=1
    JOIN edificios e ON e.id_edificio = u.fk_id_edificio AND e.fk_id_condominio=?
    WHERE r.estatus=1";
    $rec = $this->db->query($sqlRec, [$anioActual, $mesActual, $anioAnterior, $mesAnterior, $idCondominio])->row_array();

    $sqlCuotas = "SELECT
      SUM(CASE WHEN anio=? AND mes=? THEN ordinaria ELSE 0 END) ord_actual,
      SUM(CASE WHEN anio=? AND mes=? THEN ordinaria ELSE 0 END) ord_anterior,
      SUM(CASE WHEN anio=? AND mes=? THEN extraordinaria ELSE 0 END) ext_actual,
      SUM(CASE WHEN anio=? AND mes=? THEN extraordinaria ELSE 0 END) ext_anterior
    FROM cuotas_mantenimiento cm
    JOIN unidades u ON u.id_unidad = cm.fk_id_unidad AND u.estatus=1
    JOIN edificios e ON e.id_edificio = u.fk_id_edificio AND e.fk_id_condominio=?
    WHERE cm.estatus=1";
    $cuotas = $this->db->query($sqlCuotas, [
      $anioActual, $mesActual, $anioAnterior, $mesAnterior,
      $anioActual, $mesActual, $anioAnterior, $mesAnterior,
      $idCondominio
    ])->row_array();

    $recActual      = floatval($rec['actual'] ?? 0);
    $recAnterior    = floatval($rec['anterior'] ?? 0);
    $ordActual      = floatval($cuotas['ord_actual'] ?? 0);
    $ordAnterior    = floatval($cuotas['ord_anterior'] ?? 0);
    $extActual      = floatval($cuotas['ext_actual'] ?? 0);
    $extAnterior    = floatval($cuotas['ext_anterior'] ?? 0);
    $cuotasActual   = $ordActual + $extActual;
    $cuotasAnterior = $ordAnterior + $extAnterior;
    $egresosActual  = 0;
    $egresosAnterior = 0;
    $saldoActual    = $recActual + $cuotasActual - $egresosActual;
    $saldoAnterior  = $recAnterior + $cuotasAnterior - $egresosAnterior;

    return [
      'recaudaciones' => array_merge($this->calcularComparativo($recActual, $recAnterior), ['valor' => $recActual]),
      'cuotas' => array_merge($this->calcularComparativo($cuotasActual, $cuotasAnterior), [
        'valor' => $cuotasActual, 'ordinarias' => $ordActual, 'extraordinarias' => $extActual,
        'ordinarias_anterior' => $ordAnterior, 'extraordinarias_anterior' => $extAnterior,
      ]),
      'egresos' => array_merge($this->calcularComparativo($egresosActual, $egresosAnterior), ['valor' => $egresosActual]),
      'saldo'   => array_merge($this->calcularComparativo($saldoActual, $saldoAnterior), ['valor' => $saldoActual]),
    ];
  }
}
