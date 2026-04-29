<?php

function combinar_arreglos($arrayA, $arrayB)
{
  if (!is_array($arrayA) || !is_array($arrayB)) {
    return false;
  }

  $countArrayA = count($arrayA);
  $countArrayB = count($arrayB);
  $maxCount = $countArrayA > $countArrayB ? $countArrayA : $countArrayB;
  $arrayA = array_slice($arrayA, 0, $maxCount, true);
  $arrayB = array_slice($arrayB, 0, $maxCount, true);
  return array_combine($arrayA, $arrayB);
}

/**
 * Return the specified multiple columns in the array
 *
 * @param    Array $arreglo needs to take out the multidimensional array of the array column
 * @param    String $columnas The column names to be retrieved, separated by commas, if not passed, all columns will be returned
 * @param    String $index as the index column of the returned array
 * @return Array
 */
function array_columns($arreglo, $columnas = null, $index = null)
{
  $newArr = [];
  $keys = isset($columnas) ? explode(',', preg_replace('/\s+/', '', $columnas)) : [];

  if ($arreglo) {
    foreach ($arreglo as $k => $v) {
      if ($keys) {
        $tmp = [];
        foreach ($keys as $key) {
          $tmp[$key] = $v[$key];
        }
      } else {
        $tmp = $v;
      }

      if (isset($index)) {
        $newArr[$v[$index]] = $tmp;
      } else {
        $newArr[] = $tmp;
      }
    }
  }

  return $newArr;
}

function agregar_columnas_arreglo($arreglo, $columnas)
{
  if (is_array($arreglo) && is_array($columnas)) {
    foreach ($arreglo as &$fila) {
      foreach ($columnas as $key => $value) {
        $fila[$key] = $value;
      }
    }
    unset($fila);
  }
  return $arreglo;
}

function borrar_columnas_arreglo($arreglo, $columnas = null)
{
  $columnas = isset($columnas) ? explode(',', preg_replace('/\s+/', '', $columnas)) : [];

  foreach ($columnas as $columna) {
    array_walk($arreglo, function (&$v) use ($columna) {
      unset($v[$columna]);
    });
  }

  return $arreglo;
}

function borrar_columna_arreglo($arreglo, $columna)
{
  array_walk($arreglo, function (&$v) use ($columna) {
    unset($v[$columna]);
  });
  return $arreglo;
}

function cambiar_keys_arreglo($arreglo, $keys)
{
  if (is_array($arreglo) && is_array($keys)) {
    $newArr = [];
    foreach ($arreglo as $k => $v) {
      $key = array_key_exists($k, $keys) ? $keys[$k] : $k;
      $newArr[$key] = is_array($v) ? cambiar_keys_arreglo($v, $keys) : $v;
    }
    return $newArr;
  }
  return $arreglo;
}

function obtener_keys_arreglo(array $array)
{
  $keys = [];

  foreach ($array as $key => $value) {
    if (is_array($value)) {
      $keys = array_merge($keys, obtener_keys_arreglo($value));
    } else {
      $keys[] = $key;
    }
  }
  return $keys;
}

function obtener_valores_arreglo($arreglo)
{
  $arreglo_tmp = [];
  foreach ($arreglo as $llave => $valor) {
    array_push($arreglo_tmp, $valor);
  }
  return $arreglo_tmp;
}

function agrupar_arreglo($arreglo, $key)
{
  $newArr = [];

  foreach ($arreglo as $val) {
    // if (array_key_exists($key, $val)) {
    // if (!empty($key) && array_key_exists($key, $val)) {
    $newArr[$val[$key]][] = $val;
    //} else {
    //	$newArr[''][] = $val;
    //}
  }
  return $newArr;
}

function eliminar_duplicados_arreglo($arreglo, $keep_key_assoc = false)
{
  /*
   $newArr = array_map('unserialize', array_unique(array_map('serialize', $arreglo)));
   return (array) $newArr;
   */
  $duplicate_keys = [];
  $tmp = [];

  foreach ($arreglo as $key => $val) {
    // convert objects to arrays, in_array() does not support objects
    if (is_object($val)) {
      $val = (array) $val;
    }

    if (!in_array($val, $tmp)) {
      $tmp[] = $val;
    } else {
      $duplicate_keys[] = $key;
    }
  }

  foreach ($duplicate_keys as $key) {
    unset($arreglo[$key]);
  }

  return $keep_key_assoc ? $arreglo : array_values($arreglo);
}

function capitalizar_arreglo($arreglo, $campos_capitalizar = [])
{
  foreach ($arreglo as $llave => $valor) {
    if (is_array($valor)) {
      $arreglo[$llave] = capitalizar_arreglo($valor, $campos_capitalizar);
    } elseif (empty($campos_capitalizar) or in_array($llave, array_values($campos_capitalizar))) {
      if (!empty($arreglo[$llave])) {
        $arreglo[$llave] = mb_strtoupper($valor);
      }
    }
  }
  return $arreglo;
}

function minusculas_arreglo($arreglo, $campos_capitalizar = [])
{
  foreach ($arreglo as $llave => $valor) {
    if (is_array($valor)) {
      $arreglo[$llave] = minusculas_arreglo($valor, $campos_capitalizar);
    } elseif (empty($campos_capitalizar) or in_array($llave, array_values($campos_capitalizar))) {
      if (!empty($arreglo[$llave])) {
        $arreglo[$llave] = mb_strtolower($valor);
      }
    }
  }
  return $arreglo;
}

function trim_elementos_arreglo($arreglo, $campos_trim = [])
{
  foreach ($arreglo as $llave => $valor) {
    if (is_array($valor)) {
      $arreglo[$llave] = trim_elementos_arreglo($valor, $campos_trim);
    } elseif (empty($campos_trim) or in_array($llave, array_values($campos_trim))) {
      if (!empty($arreglo[$llave])) {
        $arreglo[$llave] = trim($valor);
      }
    }
  }
  return $arreglo;
}

function limpiar_cadena($texto)
{
  $texto = preg_replace('([^A-Za-z0-9_])', '', $texto);
  return $texto;
}

function limpiar_cadena_arreglo($arreglo, $campos = [])
{
  foreach ($arreglo as $llave => $valor) {
    if (is_array($valor)) {
      $arreglo[$llave] = limpiar_cadena_arreglo($valor, $campos);
    } elseif (empty($campos) or in_array($llave, array_values($campos))) {
      if (!empty($arreglo[$llave])) {
        $arreglo[$llave] = limpiar_cadena($valor);
      }
    }
  }
  return $arreglo;
}

function nulificar_elementos_arreglo($arreglo, $campos = [])
{
  foreach ($arreglo as $llave => $valor) {
    if (is_array($valor)) {
      $arreglo[$llave] = nulificar_elementos_arreglo($valor, $campos);
    } elseif (empty($campos) or in_array($llave, array_values($campos))) {
      if ($arreglo[$llave] == '') {
        $arreglo[$llave] = null;
      }
    }
  }
  return $arreglo;
}

function eliminar_acentos($texto)
{
  //Reemplazamos la A y a
  $texto = str_replace(
    ['Á', 'À', 'Â', 'Ä', 'á', 'à', 'ä', 'â', 'ª'],
    ['A', 'A', 'A', 'A', 'a', 'a', 'a', 'a', 'a'],
    $texto
  );
  //Reemplazamos la E y e
  $texto = str_replace(['É', 'È', 'Ê', 'Ë', 'é', 'è', 'ë', 'ê'], ['E', 'E', 'E', 'E', 'e', 'e', 'e', 'e'], $texto);
  //Reemplazamos la I y i
  $texto = str_replace(['Í', 'Ì', 'Ï', 'Î', 'í', 'ì', 'ï', 'î'], ['I', 'I', 'I', 'I', 'i', 'i', 'i', 'i'], $texto);
  //Reemplazamos la O y o
  $texto = str_replace(['Ó', 'Ò', 'Ö', 'Ô', 'ó', 'ò', 'ö', 'ô'], ['O', 'O', 'O', 'O', 'o', 'o', 'o', 'o'], $texto);
  //Reemplazamos la U y u
  $texto = str_replace(['Ú', 'Ù', 'Û', 'Ü', 'ú', 'ù', 'ü', 'û'], ['U', 'U', 'U', 'U', 'u', 'u', 'u', 'u'], $texto);
  //Reemplazamos la N, n, C y c
  $texto = str_replace(['Ñ', 'ñ', 'Ç', 'ç'], ['Ni', 'ni', 'C', 'c'], $texto);

  return $texto;
}

function eliminar_acentos_arreglo($arreglo, $campos = [])
{
  foreach ($arreglo as $llave => $valor) {
    if (is_array($valor)) {
      $arreglo[$llave] = eliminar_acentos_arreglo($valor, $campos);
    } elseif (empty($campos) or in_array($llave, array_values($campos))) {
      $arreglo[$llave] = eliminar_acentos($valor);
    }
  }
  return $arreglo;
}

function solo_numerico_con_decimales($texto)
{
  return preg_replace('/\./', '', preg_replace('/[^0-9\.]/', '', $texto), substr_count($texto, '.') - 1);
}

function solo_numerico_con_decimales_arreglo($arreglo, $campos = [])
{
  foreach ($arreglo as $llave => $valor) {
    if (is_array($valor)) {
      $arreglo[$llave] = solo_numerico_con_decimales_arreglo($valor, $campos);
    } elseif (empty($campos) or in_array($llave, array_values($campos))) {
      $arreglo[$llave] = solo_numerico_con_decimales($valor);
    }
  }
  return $arreglo;
}

function obtener_mes($mes)
{
  $respuesta = '';
  $meses = [
    'enero',
    'febrero',
    'marzo',
    'abril',
    'mayo',
    'junio',
    'julio',
    'agosto',
    'septiembre',
    'octubre',
    'noviembre',
    'diciembre',
  ];

  if (is_numeric($mes) && $mes >= 1 && $mes <= 12) {
    $respuesta = $meses[$mes - 1];
  }

  return $respuesta;
}

function fechaFormatoValido($fecha)
{
  $d1 = DateTime::createFromFormat('Y-m-d', $fecha);
  $d2 = DateTime::createFromFormat('Y/m/d', $fecha);
  $d3 = DateTime::createFromFormat('Y-m-d H:i:s', $fecha);
  $d4 = DateTime::createFromFormat('Y/m/d H:i:s', $fecha);
  return ($d1 && $d1->format('Y-m-d') === $fecha) ||
    ($d2 && $d2->format('Y/m/d') === $fecha) ||
    ($d3 && $d3->format('Y-m-d H:i:s') === $fecha) ||
    ($d4 && $d4->format('Y/m/d H:i:s') === $fecha);
}

function fechaValidaNoMenorActual($fecha)
{
  $d1 = DateTime::createFromFormat('Y-m-d', $fecha);
  $d2 = DateTime::createFromFormat('Y/m/d', $fecha);

  $dc1 = DateTime::createFromFormat('Y-m-d', date('Y-m-d'));
  $dc2 = DateTime::createFromFormat('Y/m/d', date('Y/m/d'));

  return fechaFormatoValido($fecha);
}

function fechaHoraValidaNoMenorActual($fecha)
{
  $d1 = DateTime::createFromFormat('Y-m-d H:i:s', $fecha);
  $d2 = DateTime::createFromFormat('Y/m/d H:i:s', $fecha);

  /* print_r(fechaFormatoValido($d1));
  exit; */

  return fechaFormatoValido($fecha) &&
    (($d1 &&
      $d1->format('Y-m-d H:i:s') >=
      DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s'))->format('Y-m-d H:i:s')) ||
      ($d2 &&
        $d2->format('Y/m/d H:i:s') >=
        DateTime::createFromFormat('Y/m/d H:i:s', date('Y/m/d H:i:s'))->format('Y/m/d H:i:s')));
}

function fechaValidaNoMayorActual($fecha)
{
  $d1 = DateTime::createFromFormat('Y-m-d', $fecha);
  $d2 = DateTime::createFromFormat('Y/m/d', $fecha);

  return fechaFormatoValido($fecha) &&
    (($d1 && $d1->format('Y-m-d') <= DateTime::createFromFormat('Y-m-d', date('Y-m-d'))->format('Y-m-d')) ||
      ($d2 && $d2->format('Y/m/d') <= DateTime::createFromFormat('Y/m/d', date('Y/m/d'))->format('Y/m/d')));
}

function fechaHoraValidaNoMayorActual($fecha)
{
  $d1 = DateTime::createFromFormat('Y-m-d H:i:s', $fecha);
  $d2 = DateTime::createFromFormat('Y/m/d H:i:s', $fecha);

  return fechaFormatoValido($fecha) &&
    (($d1 &&
      $d1->format('Y-m-d H:i:s') <=
      DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s'))->format('Y-m-d H:i:s')) ||
      ($d2 &&
        $d2->format('Y/m/d H:i:s') <=
        DateTime::createFromFormat('Y/m/d H:i:s', date('Y/m/d H:i:s'))->format('Y/m/d H:i:s')));
}

function horaFormatoValido($hora, $formato = 'H:i')
{
  $d = DateTime::createFromFormat('Y-m-d ' . $formato, '2023-01-01 ' . $hora);
  return $d && $d->format($formato) == $hora;
}

function periodoContratacion($fechaInicial, $fechaFinal)
{
  $intervalo = (new DateTime($fechaFinal))->add(new DateInterval('P1D'))->diff(new DateTime($fechaInicial));
  return (object) [
    'a' => $intervalo->y,
    'm' => $intervalo->m,
    'd' => $intervalo->d,
    'td' => $intervalo->days,
  ];
}

function fechasDiferencia($intervalo, $fechaInicial, $fechaFinal, $relativa = false)
{
  if (is_string($fechaInicial)) {
    $fechaInicial = date_create($fechaInicial);
  } else {
    return false;
  }
  if (is_string($fechaFinal)) {
    $fechaFinal = date_create($fechaFinal);
  } else {
    return false;
  }

  $diferencia = date_diff($fechaInicial, $fechaFinal, !$relativa);

  switch ($intervalo) {
    case 'a':
      $total = $diferencia->y + $diferencia->m / 12 + $diferencia->d / 365.25;
      break;
    case 'm':
      $total = $diferencia->y * 12 + $diferencia->m + $diferencia->d / 30 + $diferencia->h / 24;
      break;
    case 'd':
      $total =
        $diferencia->y * 365.25 + $diferencia->m * 30 + $diferencia->d + $diferencia->h / 24 + $diferencia->i / 60;
      break;
    case 'h':
      $total =
        ($diferencia->y * 365.25 + $diferencia->m * 30 + $diferencia->d) * 24 + $diferencia->h + $diferencia->i / 60;
      break;
    case 'i':
      $total =
        (($diferencia->y * 365.25 + $diferencia->m * 30 + $diferencia->d) * 24 + $diferencia->h) * 60 +
        $diferencia->i +
        $diferencia->s / 60;
      break;
    case 's':
      $total =
        ((($diferencia->y * 365.25 + $diferencia->m * 30 + $diferencia->d) * 24 + $diferencia->h) * 60 +
          $diferencia->i) *
        60 +
        $diferencia->s;
      break;
  }

  if ($diferencia->invert) {
    return -1 * $total;
  } else {
    return $total;
  }
}

function quitarElementosVacios($array)
{
  return array_filter($array, function ($value) {
    return !empty($value) || $value === 0;
  });
}

function tamanioMinimoArreglo($array, $tamanio)
{
  $array = quitarElementosVacios($array);

  if (!is_array($array)) {
    return false;
  }
  return count($array) >= $tamanio;
}

function tamanioMaximoArreglo($array, $tamanio)
{
  $array = quitarElementosVacios($array);

  if (!is_array($array)) {
    return false;
  }

  return count($array) <= $tamanio;
}

function md5Valido($md5Hash = '')
{
  return preg_match('/^[a-f0-9]{32}$/', $md5Hash);
}

function emailValido($email)
{
  return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function extraerErrorDesdeJSON($error)
{
  $errorDecoded = json_decode($error, true);
  if (json_last_error() === JSON_ERROR_NONE) {
    if (!empty($errorDecoded['error']['message'])) {
      $error = $errorDecoded['error']['message'];
    }
  }
  return $error;
}
?>