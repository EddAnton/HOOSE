<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Modelo: Dashboard_model
 *
 * Este modelo realiza las operaciones requeridas sobre el Dashboard
 *
 * @package   CodeIgniter
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Dashboard_model extends CI_Model
{
  var $idCondominio = '';
  var $whereCondominio = '';
  var $rangoFechas = [0, 0, 0, 0];

  public function __construct()
  {
    parent::__construct();
    $this->db->query('SET lc_time_names = "es_MX";');
  }

  public function calcularRangoFechas($anios, $meses)
  {
    $f = [0, 0, 0, 0];

    if (is_array($anios) && count($anios) >= 2) {
      $f[0] = $anios[0];
      $f[1] = $anios[1];
    } else {
      $f[0] = $anios;
      $f[1] = $anios;
    }
    if (is_array($meses) && count($meses) >= 2) {
      $f[2] = $meses[0];
      $f[3] = $meses[1];
    } else {
      $f[2] = $meses;
      $f[3] = $meses;
    }

    return $f;
  }

  private function generarLeyendas($anios = 0, $meses = 0)
  {
    $anioInicial = 0;
    $anioFinal = 0;
    $mesInicial = 0;
    $mesFinal = 0;

    if (is_array($anios) && count($anios) >= 2) {
      $anioInicial = $anios[0];
      $anioFinal = $anios[1];
    } else {
      $anioInicial = $anios;
      $anioFinal = $anios;
    }
    if (is_array($meses) && count($meses) >= 2) {
      $mesInicial = $meses[0];
      $mesFinal = $meses[1];
    } else {
      $mesInicial = $meses;
      $mesFinal = $meses;
    }

    $legends = [];
    $sql = '';
    for ($anio = $anioInicial; $anio <= $anioFinal; $anio++) {
      for ($mes = $mesInicial; $mes <= $mesFinal; $mes++) {
        // $sql .= 'SELECT UPPER(DATE_FORMAT("' . $anio . '-' . $mes . '-01", "%b' . ($anioInicial != $anioFinal ? '-%Y' : '') . '")) legend UNION ';
        $sql .= 'SELECT UPPER(DATE_FORMAT("' . $anio . '-' . $mes . '-01", "%b-%Y")) legend UNION ';
      }
    }
    $sql = substr($sql, 0, -6);
    $legends = $this->db->query($sql)->result_array();
    if (!empty($legends)) {
      $legends = array_column($legends, 'legend');
    }

    return $legends;
  }

  private function generarData($legends = [], $datasets = [])
  {
    $data = [];
    $keys = [];
    $datasets = array_filter($datasets, function ($item) {
      return !is_null($item) && !empty($item);
    });

    $totalDatasets = count($datasets);
    if ($totalDatasets < 1) {
      return $data;
    }

    foreach ($datasets as $dataset) {
      if (empty($dataset)) {
        unset($dataset);
        continue;
      }

      $key = array_filter(array_unique(obtener_keys_arreglo($dataset)), function ($item) {
        return ($item !== 'legend');
      });
      if (!empty($key)) {
        $keys[] = $key;
      }
    }

    $totalDatasets = count($datasets);
    if (empty($totalDatasets) || empty($keys)) {
      return $data;
    }

    foreach ($legends as $legend) {
      $item = [];
      for ($i = 0; $i < $totalDatasets; $i++) {
        $dataset[$i] = array_values(array_filter($datasets[$i], function ($d) use ($legend) {
          return ($d['legend'] == $legend);
        }));

        if (!empty($dataset[$i])) {
          $dataset[$i] = $dataset[$i][0];
          unset($dataset[$i]['legend']);
        } else {
          $dataset[$i] = [];
          foreach ($keys[$i] as $key) {
            $dataset[$i][$key] = 0.00;
          }
        }
        $item = array_merge($item, $dataset[$i]);
      }
      $data[] = (object) $item;
    }
    return $data;
  }

  /*
     Obtener información del dashboard
   */
  public function listar($dataUsuario = [], $anios, $meses)
  {
    $this->idCondominio = !empty($dataUsuario['idCondominio']) ? $dataUsuario['idCondominio'] : '';
    $idUsuario = !empty($dataUsuario['idUsuario']) ? $dataUsuario['idUsuario'] : '';
    $idPerfilUsuario = !empty($dataUsuario['idPerfilUsuario']) ? $dataUsuario['idPerfilUsuario'] : '';

    $this->whereIdCondominio = !empty($this->idCondominio) ? ' AND e.fk_id_condominio = ' . $this->idCondominio : '';
    $this->rangoFechas = $this->calcularRangoFechas($anios, $meses);

    /* if (VALIDATE_TOKEN) {
      $anio = date('Y');
      $mes = date('m');
    } else {
      $anio = 2023;
      $mes = 5;
    }
 */
    // Data Proyectos
    $dataProyectos = $this->dataProyectos();
    // $dataProyectos['proyectos'] = empty($dataProyectos['proyectos']) ? '0' : $dataProyectos['proyectos'];
    $cardProyectos = [
      'subtitle' => 'Proyectos',
      'title' => $dataProyectos['proyectos'],
      'content' => null,
      'path' => '/proyectos',
    ];
    $cardInversionProyectos = [
      'subtitle' => 'Proyectos',
      'title' => $dataProyectos['inversion'],
      'content' => 'Inversión',
      'path' => '/proyectos',
    ];
    // Data Unidad
    $dataUnidad = $this->dataUnidad($idPerfilUsuario, $idUsuario);

    // Data Gastos mantenimiento
    $dataGastosMantenimiento = $this->dataGastosMantenimientoErogacion();
    $cardGastosMantenimiento = [
      'subtitle' => 'Gastos mantenimiento',
      'title' => $dataGastosMantenimiento['total'],
      'content' => 'Erogación',
      'path' => '/gastos-mantenimiento',
    ];
    // Data Nómina total
    $dataNomina = $this->dataNomina();
    $cardNomina = [
      'subtitle' => 'Nómina',
      'title' => $dataNomina['total'],
      'content' => 'Erogación',
      'path' => '/nomina',
    ];
    $caracteresEliminar = ["$", ","];

    $egresos = floatval(
      str_replace($caracteresEliminar, '', $dataGastosMantenimiento['total']) +
      str_replace($caracteresEliminar, '', $dataProyectos['inversion']) +
      str_replace($caracteresEliminar, '', $dataNomina['total'])
    );

    $ingresos = $this->cuotasMantenimientoIngresos()['total'];
    $cardEgresos = [
      'subtitle' => 'Egresos',
      'title' => '$' . number_format($egresos, 2, '.', ','),
      'content' => null,
      'path' => null,
    ];
    $cardSaldoPeriodo = [
      'subtitle' => 'Saldo periodo',
      'title' => '$' . number_format($ingresos - $egresos, 2, '.', ','),
      'content' => null,
      'path' => null,
    ];

    switch ($idPerfilUsuario) {
      case PERFIL_USUARIO_ADMINISTRADOR:
      case PERFIL_USUARIO_COLABORADOR:
        $cardNominaColaborador = $this->cardNominaColaborador($idUsuario);
        break;
      case PERFIL_USUARIO_PROPIETARIO:
        $dataArrendamientos = $this->dataArrendamientos($idUsuario);
        $cardAdeudo = [
          'subtitle' => 'Adeudo',
          'title' => $dataUnidad['adeudo'],
          'content' => null,
          'path' => null,
        ];
        $cardArrendamientosRecaudacion = [
          'subtitle' => 'Arrendamientos',
          'title' => '$' . $dataArrendamientos['recaudacion'],
          'content' => 'Recaudación',
          'path' => null,
        ];
        $cardArrendamientosOcupacion = $this->cardArrendamientosOcupacion($idUsuario);
        $cardArrendamientosMorosidad = [
          'subtitle' => 'Arrendamientos',
          'title' =>
            number_format(
              floatval($dataArrendamientos['adeudos'] / $dataArrendamientos['recaudaciones'] * 100)
              ,
              2,
              '.',
              ','
            ) . '%',
          'content' => 'Morisidad',
          'path' => null,
        ];
        $cardArrendamientosAdeudos = [
          'subtitle' => 'Arrendamientos',
          'title' => '$' . $dataArrendamientos['adeudo'],
          'content' => 'Adeudos',
          'path' => null,
        ];
        break;

      case PERFIL_USUARIO_CONDOMINO:
        $cardUnidad = [
          'subtitle' => 'Unidad',
          'title' => $dataUnidad['unidad'],
          'content' => null,
          'path' => null,
        ];
        $cardCuotaOrdinaria = [
          'subtitle' => 'Cuota ordinaria',
          'title' => $dataUnidad['cuota_mantenimiento_ordinaria'],
          'content' => null,
          'path' => null,
        ];
        $cardRenta = [
          'subtitle' => 'Renta',
          'title' => $dataUnidad['renta'],
          'content' => null,
          'path' => null,
        ];
        $cardAdeudo = [
          'subtitle' => 'Adeudo',
          'title' => $dataUnidad['adeudo'],
          'content' => null,
          'path' => null,
        ];
        break;

      default:
        break;
    }

    $result = [];
    switch ($idPerfilUsuario) {
      case PERFIL_USUARIO_SUPER_ADMINISTRADOR:
        // Cards
        $result['cards'][] = $this->cardCondominios();
        $result['cards'][] = $this->cardEdificiosPisos();
        $result['cards'][] = $this->cardUnidades();
        $result['cards'][] = $this->cardPropietarios();
        $result['cards'][] = $this->cardCondominos();
        $result['cards'][] = $this->cardColaboradores();
        $result['cards'][] = $cardProyectos;
        $result['cards'][] = $this->cardAreasComunes();
        $result['cards'][] = $this->cardQuejas();
        $result['cards'][] = $this->cardAvisos();
        $result['cards'][] = $this->cardVisitas();
        $result['cards'][] = $this->cardCuotasMantenimientoOrdinarias();
        $result['cards'][] = $this->cardCuotasMantenimientoExtraordinarias();
        $result['cards'][] = $this->cardOtrosIngresos();
        $result['cards'][] = $this->cardTicketPromedioCuotasMantenimiento();
        $result['cards'][] = $this->cardMorosidadCuotasMantenimiento();
        $result['cards'][] = $this->cardCuotasMantenimientoSaldo();
        $result['cards'][] = $cardGastosMantenimiento;
        $result['cards'][] = $cardNomina;
        $result['cards'][] = $cardInversionProyectos;
        $result['cards'][] = $cardEgresos;
        $result['cards'][] = $cardSaldoPeriodo;
        $result['cards'][] = $this->cardFondosMonetariosTotal();
        // Gráficos
        $result['charts'][] = $this->grpIngresosPorTipo($anios, $meses);
        $result['charts'][] = $this->grpEgresosPorTipo($anios, $meses);
        $result['charts'][] = $this->grpFondosMonetarios();
        $result['charts'][] = $this->grpVisitas($anios, $meses);
        // $result['charts'][] = $this->grpAsambleas($anios, $meses);
        $result['charts'][] = $this->grpQuejas($anios, $meses);
        break;
      case PERFIL_USUARIO_ADMINISTRADOR:
        // Cards
        $result['cards'][] = $this->cardEdificiosPisos();
        $result['cards'][] = $this->cardUnidades();
        $result['cards'][] = $this->cardPropietarios();
        $result['cards'][] = $this->cardCondominos();
        $result['cards'][] = $this->cardColaboradores();
        $result['cards'][] = $cardProyectos;
        $result['cards'][] = $this->cardAreasComunes();
        $result['cards'][] = $this->cardQuejas();
        $result['cards'][] = $this->cardAvisos();
        $result['cards'][] = $this->cardVisitas();
        $result['cards'][] = $this->cardCuotasMantenimientoOrdinarias();
        $result['cards'][] = $this->cardCuotasMantenimientoExtraordinarias();
        $result['cards'][] = $this->cardOtrosIngresos();
        $result['cards'][] = $this->cardTicketPromedioCuotasMantenimiento();
        $result['cards'][] = $this->cardMorosidadCuotasMantenimiento();
        $result['cards'][] = $this->cardCuotasMantenimientoSaldo();
        $result['cards'][] = $cardGastosMantenimiento;
        $result['cards'][] = $cardNomina;
        $result['cards'][] = $cardNominaColaborador;
        $result['cards'][] = $cardInversionProyectos;
        $result['cards'][] = $cardEgresos;
        $result['cards'][] = $cardSaldoPeriodo;
        $result['cards'][] = $this->cardFondosMonetariosTotal();
        // Gráficos
        $result['charts'][] = $this->grpIngresosPorTipo($anios, $meses);
        $result['charts'][] = $this->grpEgresosPorTipo($anios, $meses);
        $result['charts'][] = $this->grpFondosMonetarios();
        $result['charts'][] = $this->grpVisitas($anios, $meses);
        // $result['charts'][] = $this->grpAsambleas($anios, $meses);
        $result['charts'][] = $this->grpQuejas($anios, $meses);
        break;
      case PERFIL_USUARIO_COLABORADOR:
        // Cards
        $result['cards'][] = $this->cardUnidades();
        $result['cards'][] = $this->cardAreasComunes();
        $result['cards'][] = $this->cardQuejas();
        $result['cards'][] = $this->cardAvisos();
        $result['cards'][] = $this->cardVisitas();
        $result['cards'][] = $cardNominaColaborador;
        // Gráficos
        $result['charts'][] = $this->grpVisitas($anios, $meses);
        // $result['charts'][] = $this->grpAsambleas($anios, $meses);
        $result['charts'][] = $this->grpQuejas($anios, $meses);
        break;
      case PERFIL_USUARIO_PROPIETARIO:
        // Cards
        $result['cards'][] = $this->cardUnidades();
        $result['cards'][] = $this->cardColaboradores();
        $result['cards'][] = $cardProyectos;
        $result['cards'][] = $this->cardAreasComunes();
        $result['cards'][] = $this->cardQuejas();
        $result['cards'][] = $this->cardAvisos();
        $result['cards'][] = $this->cardVisitas();
        /**
         * Falta
         *  - Vivienda habitada por el propietario
         *  - Cuota ordinaria del mes
         *  - Renta mensual
         */
        $result['cards'][] = $this->cardCuotasMantenimientoOrdinarias();
        $result['cards'][] = $this->cardCuotasMantenimientoExtraordinarias();
        $result['cards'][] = $this->cardOtrosIngresos();
        $result['cards'][] = $this->cardTicketPromedioCuotasMantenimiento();
        $result['cards'][] = $cardAdeudo;
        $result['cards'][] = $this->cardMorosidadCuotasMantenimiento();
        $result['cards'][] = $this->cardCuotasMantenimientoSaldo();
        $result['cards'][] = $cardGastosMantenimiento;
        $result['cards'][] = $cardNomina;
        $result['cards'][] = $cardInversionProyectos;
        $result['cards'][] = $cardEgresos;
        $result['cards'][] = $cardSaldoPeriodo;
        $result['cards'][] = $this->cardFondosMonetariosTotal();
        $result['cards'][] = $cardArrendamientosRecaudacion;
        $result['cards'][] = $cardArrendamientosOcupacion;
        $result['cards'][] = $cardArrendamientosMorosidad;
        $result['cards'][] = $cardArrendamientosAdeudos;
        // Gráficos
        $result['charts'][] = $this->grpIngresosPorTipo($anios, $meses);
        $result['charts'][] = $this->grpEgresosPorTipo($anios, $meses);
        $result['charts'][] = $this->grpFondosMonetarios();
        $result['charts'][] = $this->grpVisitas($anios, $meses);
        // $result['charts'][] = $this->grpAsambleas($anios, $meses);
        $result['charts'][] = $this->grpQuejas($anios, $meses);
        break;
      case PERFIL_USUARIO_CONDOMINO:
        // Cards
        $result['cards'][] = $this->cardUnidades();
        $result['cards'][] = $this->cardColaboradores();
        $result['cards'][] = $cardProyectos;
        $result['cards'][] = $this->cardAreasComunes();
        $result['cards'][] = $this->cardQuejas();
        $result['cards'][] = $this->cardAvisos();
        $result['cards'][] = $this->cardVisitas();
        $result['cards'][] = $cardUnidad;
        $result['cards'][] = $cardCuotaOrdinaria;
        $result['cards'][] = $cardRenta;
        $result['cards'][] = $this->cardCuotasMantenimientoOrdinarias();
        $result['cards'][] = $this->cardCuotasMantenimientoExtraordinarias();
        $result['cards'][] = $this->cardOtrosIngresos();
        $result['cards'][] = $this->cardTicketPromedioCuotasMantenimiento();
        $result['cards'][] = $cardAdeudo;
        $result['cards'][] = $this->cardMorosidadCuotasMantenimiento();
        $result['cards'][] = $this->cardCuotasMantenimientoSaldo();
        $result['cards'][] = $cardGastosMantenimiento;
        $result['cards'][] = $cardNomina;
        $result['cards'][] = $cardInversionProyectos;
        $result['cards'][] = $cardEgresos;
        $result['cards'][] = $cardSaldoPeriodo;
        $result['cards'][] = $this->cardFondosMonetariosTotal();
        // Gráficos
        $result['charts'][] = $this->grpIngresosPorTipo($anios, $meses);
        $result['charts'][] = $this->grpEgresosPorTipo($anios, $meses);
        $result['charts'][] = $this->grpFondosMonetarios();
        $result['charts'][] = $this->grpVisitas($anios, $meses);
        // $result['charts'][] = $this->grpAsambleas($anios, $meses);
        $result['charts'][] = $this->grpQuejas($anios, $meses);
        break;
      default:
        break;
    }
    return $result;
  }

  public function dataUnidad($idPerfilUsuario, $idUsuario)
  {
    switch ($idPerfilUsuario) {
      case PERFIL_USUARIO_PROPIETARIO:
        return $this->db
          ->select(
            'CONCAT("$", FORMAT(IFNULL(SUM(cm.saldo), 0), 2)) adeudo'
          )
          ->join('unidades_propietarios up', 'up.fk_id_usuario = us.id_usuario AND up.estatus = 1')
          ->join('unidades u', 'u.id_unidad = up.fk_id_unidad AND u.estatus = 1')
          ->join('edificios e', 'e.id_edificio = u.fk_id_edificio AND e.estatus = 1')
          ->join(
            'cuotas_mantenimiento cm',
            'cm.fk_id_unidad = u.id_unidad
              AND cm.fk_id_usuario_paga = us.id_usuario
              AND cm.anio BETWEEN ' . $this->rangoFechas[0] . ' AND ' . $this->rangoFechas[1] . '
              AND cm.mes BETWEEN ' . $this->rangoFechas[2] . ' AND ' . $this->rangoFechas[3] . '
              AND cm.estatus = 1'
          )
          ->where(
            [
              'us.id_usuario =' => $idUsuario,
              'us.estatus' => 1,
            ]
          )
          ->get('usuarios us')
          ->row_array();
        break;
      case PERFIL_USUARIO_CONDOMINO:
        return $this->db
          ->select(
            'CONCAT_WS(" / ", e.edificio, u.unidad) unidad,
              u.cuota_mantenimiento_ordinaria,
              cc.renta,
              CONCAT("$", FORMAT(IFNULL(SUM(cm.saldo), 0), 2)) adeudo'
          )
          ->join('condominos_contratos cc', 'cc.fk_id_usuario = us.id_usuario AND cc.estatus = 1')
          ->join('unidades u', 'u.id_unidad = cc.fk_id_unidad AND u.estatus = 1')
          ->join('edificios e', 'e.id_edificio = u.fk_id_edificio AND e.estatus = 1')
          ->join(
            'cuotas_mantenimiento cm',
            'cm.fk_id_unidad = u.id_unidad
          AND cm.fk_id_usuario_paga = us.id_usuario
          AND cm.anio BETWEEN ' . $this->rangoFechas[0] . ' AND ' . $this->rangoFechas[1] . '
          AND cm.mes BETWEEN ' . $this->rangoFechas[2] . ' AND ' . $this->rangoFechas[3] . '
          AND cm.estatus = 1'
          )
          ->where(
            [
              'us.id_usuario =' => $idUsuario,
              'us.estatus' => 1,
            ]
          )
          ->get('usuarios us')
          ->row_array();
        break;
      default:
        break;
    }

  }

  public function dataProyectos()
  {
    $fechaInicial = "'" . $this->rangoFechas[0] . "-" . str_repeat("0", 2 - strlen($this->rangoFechas[2])) . $this->rangoFechas[2] . "-01'";
    $fechaFinal = "'" . (new DateTime(date("Y-m-t", strtotime($this->rangoFechas[1] . "-" . $this->rangoFechas[3] . "-01"))))->format("Y-m-d") . "'";

    try {
      $sql =
        "SELECT
          COUNT(*) proyectos,
          CONCAT('$', FORMAT(IFNULL(SUM(presupuesto), 0), 2)) inversion
        FROM proyectos e
        WHERE e.estatus = 1
          AND (e.fecha_inicio >= " . $fechaInicial . " 
          OR e.fecha_fin <= " . $fechaFinal . ") " .
        $this->whereIdCondominio;
      return $this->db->query($sql)->row_array();
    } catch (Exception $e) {
      throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
    }
  }

  public function cardCondominios()
  {
    try {
      $sql =
        "SELECT
            'Condominios' subtitle,	
            FORMAT(IFNULL(COUNT(*), 0), 0) title,
            NULL content,
            '/catalogos/condominios' path
          FROM condominios
          WHERE estatus = 1";
      return $this->db->query($sql)->row_array();
    } catch (Exception $e) {
      throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
    }
  }

  public function cardEdificiosPisos()
  {
    try {
      $sql =
        "SELECT
          'Edificios / Pisos' subtitle,	
          FORMAT(IFNULL(COUNT(*), 0), 0) title,
          NULL content,
          '/catalogos/edificios' path
        FROM edificios e
        WHERE e.estatus = 1" .
        $this->whereIdCondominio;
      return $this->db->query($sql)->row_array();
    } catch (Exception $e) {
      throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
    }
  }

  public function cardUnidades()
  {
    try {
      $sql =
        "SELECT
          'Unidades' subtitle,
          FORMAT(IFNULL(COUNT(*), 0), 0) title,
          NULL content,
          '/catalogos/unidades' path
        FROM unidades u
        JOIN edificios e
          ON e.id_edificio = u.fk_id_edificio
          AND e.estatus = 1
        WHERE u.estatus = 1" .
        $this->whereIdCondominio;
      return $this->db->query($sql)->row_array();
    } catch (Exception $e) {
      throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
    }
  }

  public function cardPropietarios()
  {
    try {
      $sql =
        "SELECT
          'Propietarios' subtitle,
          FORMAT(IFNULL(COUNT(*), 0), 0) title,
          NULL content,
          '/catalogos/propietarios' path
        FROM usuarios e
        WHERE e.estatus = 1
          AND e.fk_id_perfil_usuario = 4" .
        $this->whereIdCondominio;
      return $this->db->query($sql)->row_array();
    } catch (Exception $e) {
      throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
    }
  }

  // Variable
  // Agregar rango de años/meses
  public function cardCondominos()
  {
    try {
      $sql =
        "SELECT
          'Condóminos' subtitle,
          FORMAT(IFNULL(COUNT(*), 0), 0) title,
          NULL content,
          '/catalogos/condominios' path
        FROM usuarios e
        WHERE e.estatus = 1
          AND e.fk_id_perfil_usuario = 5" .
        $this->whereIdCondominio;
      return $this->db->query($sql)->row_array();
    } catch (Exception $e) {
      throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
    }
  }

  // Variable
  // Agregar rango de años/meses
  public function cardColaboradores()
  {
    try {
      $sql =
        "SELECT
          'Colaboradores' subtitle,
          FORMAT(IFNULL(COUNT(*), 0), 0) title,
          NULL content,
          '/catalogos/colaboradores' path
        FROM usuarios e
        WHERE e.estatus = 1
          AND e.fk_id_perfil_usuario = 3" .
        $this->whereIdCondominio;
      return $this->db->query($sql)->row_array();
    } catch (Exception $e) {
      throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
    }
  }

  /* public function cardProyectos()
  {
    $fechaInicial = "'" . $this->rangoFechas[0] . "-" . str_repeat("0", 2 - strlen($this->rangoFechas[2])) . $this->rangoFechas[2] . "-01'";
    $fechaFinal = "'" . (new DateTime(date("Y-m-t", strtotime($this->rangoFechas[1] . "-" . $this->rangoFechas[3] . "-01"))))->format("Y-m-d") . "'";

    try {
      $sql =
        "SELECT
          'Proyectos' subtitle,
          FORMAT(IFNULL(COUNT(*), 0), 0) title,
          NULL content,
          '/proyectos' path
        FROM proyectos e
        WHERE e.estatus = 1
          AND (e.fecha_inicio >= " . $fechaInicial . " 
          OR e.fecha_fin <= " . $fechaFinal . ") " .
        $this->whereIdCondominio;
      return $this->db->query($sql)->row_array();
    } catch (Exception $e) {
      throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
    }
  } */

  public function cardAreasComunes()
  {
    try {
      $sql =
        "SELECT
          'Áreas comunes' subtitle,
          FORMAT(IFNULL(COUNT(*), 0), 0) title,
          NULL content,
          '/areas-comunes' path
        FROM areas_comunes e
        WHERE e.estatus = 1 " .
        $this->whereIdCondominio;
      return $this->db->query($sql)->row_array();
    } catch (Exception $e) {
      throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
    }
  }

  public function cardQuejas()
  {
    try {
      $sql =
        "SELECT
          'Quejas' subtitle,
          FORMAT(IFNULL(COUNT(*), 0), 0) title,
          NULL content,
          '/quejas' path
        FROM quejas e
        WHERE e.estatus = 1" .
        $this->whereIdCondominio;
      return $this->db->query($sql)->row_array();
    } catch (Exception $e) {
      throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
    }
  }

  public function cardAvisos()
  {
    $fechaInicial = "'" . $this->rangoFechas[0] . "-" . str_repeat("0", 2 - strlen($this->rangoFechas[2])) . $this->rangoFechas[2] . "-01'";
    $fechaFinal = "'" . (new DateTime(date("Y-m-t", strtotime($this->rangoFechas[1] . "-" . $this->rangoFechas[3] . "-01"))))->format("Y-m-d") . "'";
    try {
      $sql =
        "SELECT
          'Avisos' subtitle,
          FORMAT(IFNULL(COUNT(*), 0), 0) title,
          NULL content,
          '/tablero-avisos' path
        FROM tablero_avisos e
        WHERE e.estatus = 1
          AND e.fecha_publicacion BETWEEN " . $fechaInicial . " AND " . $fechaFinal .
        $this->whereIdCondominio;
      return $this->db->query($sql)->row_array();
    } catch (Exception $e) {
      throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
    }
  }

  public function cardVisitas()
  {
    $fechaInicial = "'" . $this->rangoFechas[0] . "-" . str_repeat("0", 2 - strlen($this->rangoFechas[2])) . $this->rangoFechas[2] . "-01'";
    $fechaFinal = "'" . (new DateTime(date("Y-m-t", strtotime($this->rangoFechas[1] . "-" . $this->rangoFechas[3] . "-01"))))->format("Y-m-d") . "'";
    try {
      $sql =
        "SELECT
          'Visitas' subtitle,
          FORMAT(IFNULL(COUNT(*), 0), 0) title,
          NULL content,
          '/visitas' path
        FROM visitas v
        JOIN unidades u
          ON u.id_unidad = v.fk_id_unidad
          AND u.estatus = 1
        JOIN edificios e
          ON e.id_edificio = u.fk_id_edificio
          AND e.estatus = 1
        WHERE v.estatus = 1
          AND v.fecha_hora_entrada BETWEEN " . $fechaInicial . " AND " . $fechaFinal .
        $this->whereIdCondominio;
      return $this->db->query($sql)->row_array();
    } catch (Exception $e) {
      throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
    }
  }

  public function cardCuotasMantenimientoOrdinarias()
  {
    try {
      $sql =
        "SELECT
          'Cuotas mantenimiento' subtitle,
          CONCAT('$', FORMAT(IFNULL(SUM(cm.ordinaria), 0), 2)) title,
          'Orinarias' content,
          '/cuotas-mantenimiento' path
        FROM cuotas_mantenimiento cm
        JOIN unidades u
          ON u.id_unidad = cm.fk_id_unidad
          AND u.estatus = 1
        JOIN edificios e
          ON e.id_edificio = u.fk_id_edificio
          AND e.estatus = 1
        WHERE cm.estatus = 1
          AND cm.anio BETWEEN " . $this->rangoFechas[0] . " AND " . $this->rangoFechas[1] . "
          AND cm.mes BETWEEN " . $this->rangoFechas[2] . " AND " . $this->rangoFechas[3] .
        $this->whereIdCondominio;
      return $this->db->query($sql)->row_array();
    } catch (Exception $e) {
      throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
    }
  }

  public function cardCuotasMantenimientoExtraordinarias()
  {
    try {
      $sql =
        "SELECT
          'Cuotas mantenimiento' subtitle,
          CONCAT('$', FORMAT(IFNULL(SUM(cm.extraordinaria), 0), 2)) title,
          'Extraordinarias' content,
          '/cuotas-mantenimiento' path
        FROM cuotas_mantenimiento cm
        JOIN unidades u
          ON u.id_unidad = cm.fk_id_unidad
          AND u.estatus = 1
        JOIN edificios e
          ON e.id_edificio = u.fk_id_edificio
          AND e.estatus = 1
        WHERE cm.estatus = 1
          AND cm.anio BETWEEN " . $this->rangoFechas[0] . " AND " . $this->rangoFechas[1] . "
          AND cm.mes BETWEEN " . $this->rangoFechas[2] . " AND " . $this->rangoFechas[3] .
        $this->whereIdCondominio;
      return $this->db->query($sql)->row_array();
    } catch (Exception $e) {
      throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
    }
  }

  public function cuotasMantenimientoIngresos()
  {
    try {
      $sql =
        "SELECT
          SUM(cm.total - cm.saldo) total
        FROM cuotas_mantenimiento cm
        JOIN unidades u
          ON u.id_unidad = cm.fk_id_unidad
          AND u.estatus = 1
        JOIN edificios e
          ON e.id_edificio = u.fk_id_edificio
          AND e.estatus = 1
        WHERE cm.estatus = 1
          AND cm.anio BETWEEN " . $this->rangoFechas[0] . " AND " . $this->rangoFechas[1] . "
          AND cm.mes BETWEEN " . $this->rangoFechas[2] . " AND " . $this->rangoFechas[3] .
        $this->whereIdCondominio;
      return $this->db->query($sql)->row_array();
    } catch (Exception $e) {
      throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
    }
  }

  public function cardCuotasMantenimientoSaldo()
  {
    try {
      $sql =
        "SELECT
          'Cuotas mantenimiento' subtitle,
          CONCAT('$', FORMAT(IFNULL(SUM(cm.saldo), 0), 2)) title,
          'Saldo pendiente' content,
          '/cuotas-mantenimiento' path
        FROM cuotas_mantenimiento cm
        JOIN unidades u
          ON u.id_unidad = cm.fk_id_unidad
          AND u.estatus = 1
        JOIN edificios e
          ON e.id_edificio = u.fk_id_edificio
          AND e.estatus = 1
        WHERE cm.estatus = 1
          AND cm.anio BETWEEN " . $this->rangoFechas[0] . " AND " . $this->rangoFechas[1] . "
          AND cm.mes BETWEEN " . $this->rangoFechas[2] . " AND " . $this->rangoFechas[3] .
        $this->whereIdCondominio;
      return $this->db->query($sql)->row_array();
    } catch (Exception $e) {
      throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
    }
  }

  public function cardOtrosIngresos()
  {
    try {
      $sql =
        "SELECT
          'Otros servicios' subtitle,
          CONCAT('$', FORMAT(IFNULL(SUM(cm.otros_servicios), 0), 2)) title,
          NULL content,
          NULL path
        FROM cuotas_mantenimiento cm
        JOIN unidades u
          ON u.id_unidad = cm.fk_id_unidad
          AND u.estatus = 1
        JOIN edificios e
          ON e.id_edificio = u.fk_id_edificio
          AND e.estatus = 1
        WHERE cm.estatus = 1
          AND cm.anio BETWEEN " . $this->rangoFechas[0] . " AND " . $this->rangoFechas[1] . "
          AND cm.mes BETWEEN " . $this->rangoFechas[2] . " AND " . $this->rangoFechas[3] .
        $this->whereIdCondominio;
      return $this->db->query($sql)->row_array();
    } catch (Exception $e) {
      throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
    }
  }

  public function cardTicketPromedioCuotasMantenimiento()
  {
    try {
      $sql =
        "SELECT
          'Cuotas mantenimiento' subtitle,
          CONCAT('$', 
            FORMAT(
              IF(
                IFNULL(COUNT(cm.id_cuota_mantenimiento), 0) <= 0,
                0,
                SUM(cm.ordinaria) / COUNT(cm.id_cuota_mantenimiento)
              ),
            2)) title,
          'Ticket promedio' content,
          NULL path
        FROM cuotas_mantenimiento cm
        JOIN unidades u
          ON u.id_unidad = cm.fk_id_unidad
          AND u.estatus = 1
        JOIN edificios e
          ON e.id_edificio = u.fk_id_edificio
          AND e.estatus = 1
        WHERE cm.estatus = 1 " .
        $this->whereIdCondominio;
      return $this->db->query($sql)->row_array();
    } catch (Exception $e) {
      throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
    }
  }

  public function cardMorosidadCuotasMantenimiento()
  {
    try {
      $sql =
        "SELECT
          'Cuotas mantenimiento' subtitle,
          CONCAT( 
            FORMAT(
              IFNULL(
                IF(
                  SUM(cm.total) = 0,
                  0,
                  SUM(cm.saldo) / SUM(cm.total) * 100
                )
              , 0),
            2), '%') title,
          'Morosidad' content,
          NULL path
        FROM cuotas_mantenimiento cm
        JOIN unidades u
          ON u.id_unidad = cm.fk_id_unidad
          AND u.estatus = 1
        JOIN edificios e
          ON e.id_edificio = u.fk_id_edificio
          AND e.estatus = 1
        WHERE cm.estatus = 1 
          AND cm.anio BETWEEN " . $this->rangoFechas[0] . " AND " . $this->rangoFechas[1] . "
          AND cm.mes BETWEEN " . $this->rangoFechas[2] . " AND " . $this->rangoFechas[3] .
        $this->whereIdCondominio;
      return $this->db->query($sql)->row_array();
    } catch (Exception $e) {
      throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
    }
  }

  public function dataGastosMantenimientoErogacion()
  {
    $fechaInicial = "'" . $this->rangoFechas[0] . "-" . str_repeat("0", 2 - strlen($this->rangoFechas[2])) . $this->rangoFechas[2] . "-01'";
    $fechaFinal = "'" . (new DateTime(date("Y-m-t", strtotime($this->rangoFechas[1] . "-" . $this->rangoFechas[3] . "-01"))))->format("Y-m-d") . "'";

    /*           AND YEAR(e.fecha) = " .
        $anio .
        "
          AND MONTH(e.fecha) = " .
        $mes . 
*/

    try {
      $sql =
        "SELECT
          CONCAT('$', FORMAT(IFNULL(SUM(e.importe), 0), 2)) total
        FROM gastos_mantenimiento e
        WHERE e.estatus = 1
          AND e.fecha BETWEEN " . $fechaInicial . " AND " . $fechaFinal .
        $this->whereIdCondominio;
      return $this->db->query($sql)->row_array();
    } catch (Exception $e) {
      throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
    }
  }

  public function dataNomina()
  {
    /*
              AND c.anio = " .
            $anio .
            "
              AND c.mes = " .
            $mes .
    */
    try {
      $sql =
        "SELECT
          CONCAT('$', FORMAT(IFNULL(SUM(c.importe), 0), 2)) total
        FROM colaboradores_nominas c
        JOIN usuarios e
          ON e.estatus = 1
          AND e.id_usuario = c.fk_id_usuario
        WHERE c.estatus = 1
          AND c.anio BETWEEN " . $this->rangoFechas[0] . " AND " . $this->rangoFechas[1] . " 
	        AND c.mes BETWEEN " . $this->rangoFechas[2] . " AND " . $this->rangoFechas[3] .
        $this->whereIdCondominio;
      return $this->db->query($sql)->row_array();
    } catch (Exception $e) {
      throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
    }
  }

  public function cardNominaColaborador($idUsuario)
  {
    try {
      $sql =
        "SELECT
          'Nómina' subtitle,
          CONCAT('$', FORMAT(IFNULL(SUM(cc.salario), 0), 2)) title,
          'Individual' content,
          '/nomina' path
        FROM usuarios e
        JOIN colaboradores_contratos cc
	        ON cc.fk_id_usuario = e.id_usuario
        WHERE e.id_usuario =  " . $idUsuario . "
          AND e.fk_id_perfil_usuario IN(" . PERFIL_USUARIO_ADMINISTRADOR . ", " . PERFIL_USUARIO_COLABORADOR . ")
          AND e.estatus = 1" .
        $this->whereIdCondominio;
      return $this->db->query($sql)->row_array();
    } catch (Exception $e) {
      throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
    }
  }
  public function cardFondosMonetariosTotal()
  {
    try {
      $sql =
        "SELECT
          'Fondos monetarios' subtitle,
          CONCAT('$', FORMAT(IFNULL(SUM(e.saldo), 0), 2)) title,
          'Total' content,
          '/fondos-monetarios' path
        FROM fondos_monetarios e
        WHERE e.estatus = 1" .
        $this->whereIdCondominio;
      return $this->db->query($sql)->row_array();
    } catch (Exception $e) {
      throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
    }
  }

  public function dataArrendamientos($idUsuario)
  {
    try {
      $sql =
        "SELECT
          COUNT(*) recaudaciones,
          FORMAT(SUM(IF(r.fk_id_estatus_recaudacion = 3, r.renta, 0)), 2) recaudacion,
          SUM(IF(r.fk_id_estatus_recaudacion <> 3, 1, 0)) adeudos,
          FORMAT(SUM(IF(r.fk_id_estatus_recaudacion <> 3, r.renta, 0)), 2) adeudo
        FROM recaudaciones r
        JOIN unidades u
          ON u.id_unidad = r.fk_id_unidad
          AND u.estatus = 1
        JOIN unidades_propietarios up
          ON up.fk_id_unidad = u.id_unidad
          AND up.estatus = 1
        JOIN edificios e
          ON e.id_edificio = u.fk_id_edificio
          AND e.estatus = 1
        WHERE r.estatus = 1 
          AND up.fk_id_usuario = " . $idUsuario . "
          AND r.fk_id_perfil_usuario_paga = " . PERFIL_USUARIO_CONDOMINO . "
          AND r.anio BETWEEN " . $this->rangoFechas[0] . " AND " . $this->rangoFechas[1] . "
          AND r.mes BETWEEN " . $this->rangoFechas[2] . " AND " . $this->rangoFechas[3] .
        $this->whereIdCondominio;
      return $this->db->query($sql)->row_array();
    } catch (Exception $e) {
      throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
    }
  }

  public function cardArrendamientosOcupacion($idUsuario)
  {
    $fechaInicial = "'" . $this->rangoFechas[0] . "-" . str_repeat("0", 2 - strlen($this->rangoFechas[2])) . $this->rangoFechas[2] . "-01'";
    $fechaFinal = "'" . (new DateTime(date("Y-m-t", strtotime($this->rangoFechas[1] . "-" . $this->rangoFechas[3] . "-01"))))->format("Y-m-d") . "'";

    try {
      $sql =
        "SELECT
          'Arrendamientos' subtitle,
          COUNT(*) title,
          'Ocupación' content,
          NULL path
        FROM unidades u
        JOIN unidades_propietarios up
          ON up.fk_id_unidad = u.id_unidad
        JOIN edificios e
          ON e.id_edificio = u.fk_id_edificio
          AND e.estatus = 1
        JOIN condominos_contratos cc
          ON cc.fk_id_unidad = u.id_unidad
        WHERE u.estatus = 1
          AND up.fk_id_usuario = " . $idUsuario . "
          AND cc.fecha_inicio BETWEEN " . $fechaInicial . " AND " . $fechaFinal . "
	        AND (ISNULL(cc.fecha_fin)
		        OR cc.fecha_fin >= " . $fechaFinal . ") " .
        $this->whereIdCondominio;
      return $this->db->query($sql)->row_array();
    } catch (Exception $e) {
      throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
    }
  }

  public function grpIngresosPorTipo($anios, $meses)
  {
    $anioInicial = 0;
    $anioFinal = 0;
    $mesInicial = 0;
    $mesFinal = 0;

    $datasets = [];

    if (is_array($anios) && count($anios) >= 2) {
      $anioInicial = $anios[0];
      $anioFinal = $anios[1];
    } else {
      $anioInicial = $anios;
      $anioFinal = $anios;
    }
    if (is_array($meses) && count($meses) >= 2) {
      $mesInicial = $meses[0];
      $mesFinal = $meses[1];
    } else {
      $mesInicial = $meses;
      $mesFinal = $meses;
    }

    $this->db
      ->select(
        'UPPER(DATE_FORMAT(CONCAT(cm.anio, "-", cm.mes, "-01"), "%b-%Y")) legend,
          IFNULL(SUM(cm.ordinaria), 0) `Ordinaria`,
          IFNULL(SUM(cm.extraordinaria), 0) `Extraordinaria`,
          IFNULL(SUM(cm.otros_servicios), 0) `Otros servicios`'
      )
      ->join('unidades u', 'u.id_unidad = cm.fk_id_unidad AND u.estatus = 1')
      ->join('edificios e', 'e.id_edificio = u.fk_id_edificio AND e.estatus = 1')
      ->where(
        [
          'e.fk_id_condominio' => $this->idCondominio,
          'cm.anio >=' => $anioInicial,
          'cm.anio <=' => $anioFinal,
          'cm.mes >=' => $mesInicial,
          'cm.mes <=' => $mesFinal,
          'cm.estatus' => 1,
        ]
      )
      ->group_by('cm.anio, cm.mes')
      ->order_by('cm.anio, cm.mes');

    $cuotasMantenimiento = $this->db
      ->get('cuotas_mantenimiento cm')
      ->result_array();

    $legends = $this->generarLeyendas($anios, $meses);
    $datasets = $this->generarData($legends, [$cuotasMantenimiento]);
    $type = ($anioInicial <> $anioFinal || ($anioInicial == $anioFinal && $mesInicial <> $mesFinal)) ? 'stacked' : 'pie';

    return array_merge(['_title' => 'Ingresos por Tipo', '_type' => $type, '_legends' => $legends, 'datasets' => $datasets]);
  }

  public function grpEgresosPorTipo($anios, $meses)
  {
    $anioInicial = 0;
    $anioFinal = 0;
    $mesInicial = 0;
    $mesFinal = 0;

    $datasets = [];

    if (is_array($anios) && count($anios) >= 2) {
      $anioInicial = $anios[0];
      $anioFinal = $anios[1];
    } else {
      $anioInicial = $anios;
      $anioFinal = $anios;
    }
    if (is_array($meses) && count($meses) >= 2) {
      $mesInicial = $meses[0];
      $mesFinal = $meses[1];
    } else {
      $mesInicial = $meses;
      $mesFinal = $meses;
    }

    $gastosMantenimiento = $this->db
      ->select(
        // 'UPPER(DATE_FORMAT(fecha, "%b' . ($anioInicial != $anioFinal ? '-%Y' : '') . '")) legend,
        'UPPER(DATE_FORMAT(fecha, "%b-%Y")) legend,
          IFNULL(SUM(IF(!ISNULL(fk_id_gasto_fijo), importe, 0)), 0) `Gastos de Mantenimiento Fijos`,
          IFNULL(SUM(IF(ISNULL(fk_id_gasto_fijo), importe, 0)), 0) `Gastos de Mantenimiento Variables`'
      )
      ->group_by('YEAR(fecha), MONTH(fecha)')
      ->order_by('YEAR(fecha), MONTH(fecha)')
      ->get_where(
        'gastos_mantenimiento',
        [
          //          'fk_id_condominio' => !empty($this->idCondominio) ? $this->idCondominio : 'fk_id_condominio',
          'fk_id_condominio' => $this->idCondominio,
          'YEAR(fecha) >=' => $anioInicial,
          'YEAR(fecha) <=' => $anioFinal,
          'MONTH(fecha) >=' => $mesInicial,
          'MONTH(fecha) <=' => $mesFinal,
          'estatus' => 1
        ]
      )
      ->result_array();

    $nomina = $this->db
      ->select(
        'UPPER(DATE_FORMAT(CONCAT(n.anio, "-", n.mes, "-01"), "%b-%Y")) legend,
          IFNULL(SUM(n.importe), 0) `Nómina`'
      )
      ->join('usuarios u', 'u.id_usuario = n.fk_id_usuario AND u.estatus = n.estatus')
      ->group_by('n.anio, n.mes')
      ->order_by('n.anio, n.mes')
      ->get_where(
        'colaboradores_nominas n',
        [
          //          'u.fk_id_condominio' => !empty($this->idCondominio) ? $this->idCondominio : 'u.fk_id_condominio',
          'u.fk_id_condominio' => $this->idCondominio,
          'n.anio >=' => $anioInicial,
          'n.anio <=' => $anioFinal,
          'n.mes >=' => $mesInicial,
          'n.mes <=' => $mesFinal,
          'n.estatus' => 1
        ]
      )
      ->result_array();

    $legends = $this->generarLeyendas($anios, $meses);
    $datasets = $this->generarData($legends, [$gastosMantenimiento, $nomina]);
    $type = ($anioInicial <> $anioFinal || ($anioInicial == $anioFinal && $mesInicial <> $mesFinal)) ? 'stacked' : 'pie';

    return array_merge(['_title' => 'Egresos por Tipo', '_type' => $type, '_legends' => $legends, 'datasets' => $datasets]);
  }

  public function grpFondosMonetarios()
  {
    $fondosMonetarios = $this->db
      ->select(
        'CONCAT(UCASE(LEFT(fm.fondo_monetario, 1)), LCASE(SUBSTRING(fm.fondo_monetario, 2))) fondo_monetario,
	        fm.saldo'
      )
      ->order_by('fm.saldo')
      ->get_where(
        'fondos_monetarios fm',
        [
          'fm.fk_id_condominio' => $this->idCondominio,
          'fm.estatus' => 1
        ]
      )
      ->result_array();

    $dataset = [];
    foreach ($fondosMonetarios as $fondo) {
      $dataset[$fondo['fondo_monetario']] = $fondo['saldo'];
    }

    return array_merge(['_title' => 'Fondos Monetarios', '_type' => 'pie', '_legends' => null, 'datasets' => $dataset ? [$dataset] : $dataset]);
  }

  public function grpVisitas($anios, $meses)
  {
    $anioInicial = 0;
    $anioFinal = 0;
    $mesInicial = 0;
    $mesFinal = 0;

    $datasets = [];

    if (is_array($anios) && count($anios) >= 2) {
      $anioInicial = $anios[0];
      $anioFinal = $anios[1];
    } else {
      $anioInicial = $anios;
      $anioFinal = $anios;
    }
    if (is_array($meses) && count($meses) >= 2) {
      $mesInicial = $meses[0];
      $mesFinal = $meses[1];
    } else {
      $mesInicial = $meses;
      $mesFinal = $meses;
    }

    $visitas = $this->db
      ->select(
        'UPPER(DATE_FORMAT(v.fecha_hora_entrada, "%b-%Y")) legend,
          COUNT(v.id_visita) `Visitas`'
      )
      ->join('unidades u', 'u.id_unidad = v.fk_id_unidad AND u.estatus = 1')
      ->join('edificios e', 'e.id_edificio = u.fk_id_edificio AND e.estatus = 1')
      ->where(
        [
          'e.fk_id_condominio' => $this->idCondominio,
          'YEAR(v.fecha_hora_entrada) >=' => $anioInicial,
          'YEAR(v.fecha_hora_entrada) <=' => $anioFinal,
          'MONTH(v.fecha_hora_entrada) >=' => $mesInicial,
          'MONTH(v.fecha_hora_entrada) <=' => $mesFinal,
          'v.estatus' => 1,
        ]
      )
      ->group_by('YEAR(v.fecha_hora_entrada), MONTH(v.fecha_hora_entrada)')
      ->order_by('YEAR(v.fecha_hora_entrada), MONTH(v.fecha_hora_entrada)')
      ->get('visitas v')
      ->result_array();

    $legends = $this->generarLeyendas($anios, $meses);
    $datasets = $this->generarData($legends, [$visitas]);

    return array_merge(['_title' => 'Visitas', '_type' => 'line', '_legends' => $legends, 'datasets' => $datasets]);
  }

  public function grpAsambleas($anios, $meses)
  {
    $anioInicial = 0;
    $anioFinal = 0;
    $mesInicial = 0;
    $mesFinal = 0;

    $datasets = [];

    if (is_array($anios) && count($anios) >= 2) {
      $anioInicial = $anios[0];
      $anioFinal = $anios[1];
    } else {
      $anioInicial = $anios;
      $anioFinal = $anios;
    }
    if (is_array($meses) && count($meses) >= 2) {
      $mesInicial = $meses[0];
      $mesFinal = $meses[1];
    } else {
      $mesInicial = $meses;
      $mesFinal = $meses;
    }

    $quejas = $this->db
      ->select(
        'UPPER(DATE_FORMAT(a.fecha, "%b-%Y")) legend,
          COUNT(a.id_asamblea) `Asambleas`'
      )
      ->where(
        [
          'a.fk_id_condominio' => $this->idCondominio,
          'YEAR(a.fecha) >=' => $anioInicial,
          'YEAR(a.fecha) <=' => $anioFinal,
          'MONTH(a.fecha) >=' => $mesInicial,
          'MONTH(a.fecha) <=' => $mesFinal,
          'a.estatus' => 1,
        ]
      )
      ->group_by('YEAR(a.fecha), MONTH(a.fecha)')
      ->order_by('YEAR(a.fecha), MONTH(a.fecha)')
      ->get('asambleas a')
      ->result_array();

    $legends = $this->generarLeyendas($anios, $meses);
    $datasets = $this->generarData($legends, [$quejas]);

    return array_merge(['_title' => 'Asambleas', '_type' => 'line', '_legends' => $legends, 'datasets' => $datasets]);
  }

  public function grpQuejas($anios, $meses)
  {
    $anioInicial = 0;
    $anioFinal = 0;
    $mesInicial = 0;
    $mesFinal = 0;

    $datasets = [];

    if (is_array($anios) && count($anios) >= 2) {
      $anioInicial = $anios[0];
      $anioFinal = $anios[1];
    } else {
      $anioInicial = $anios;
      $anioFinal = $anios;
    }
    if (is_array($meses) && count($meses) >= 2) {
      $mesInicial = $meses[0];
      $mesFinal = $meses[1];
    } else {
      $mesInicial = $meses;
      $mesFinal = $meses;
    }

    $quejas = $this->db
      ->select(
        'UPPER(DATE_FORMAT(q.fecha_registro, "%b-%Y")) legend,
          COUNT(q.id_queja) `Quejas`'
      )
      ->where(
        [
          'q.fk_id_condominio' => $this->idCondominio,
          'YEAR(q.fecha_registro) >=' => $anioInicial,
          'YEAR(q.fecha_registro) <=' => $anioFinal,
          'MONTH(q.fecha_registro) >=' => $mesInicial,
          'MONTH(q.fecha_registro) <=' => $mesFinal,
          'q.estatus' => 1,
        ]
      )
      ->group_by('YEAR(q.fecha_registro), MONTH(q.fecha_registro)')
      ->order_by('YEAR(q.fecha_registro), MONTH(q.fecha_registro)')
      ->get('quejas q')
      ->result_array();

    $legends = $this->generarLeyendas($anios, $meses);
    $datasets = $this->generarData($legends, [$quejas]);

    return array_merge(['_title' => 'Quejas', '_type' => 'line', '_legends' => $legends, 'datasets' => $datasets]);
  }
}

/* End of file Dashboard_model.php */
/* Location: ./application/models/Dashboard_model.php */
