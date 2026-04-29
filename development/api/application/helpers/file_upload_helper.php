<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Helpers File_Upload_helper
 *
 * Helpers contiene rutinas para la carga de archivo
 *
 * @package   CodeIgniter
 * @category  Helpers
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

defined('IMAGE_MAX_WIDTH') or define('IMAGE_MAX_WIDTH', 1024); // Ancho máximo para las imagenes de las promociones
defined('IMAGE_MAX_HEIGHT') or define('IMAGE_MAX_HEIGHT', 768); // Alto máximo para las imagenes de las promociones

/**
 * extension_valida
 *
 * Valida que la extensión de un archivo se encuentre dentro de las permitidas
 *
 */
function extension_valida($archivo, $extensiones_validas = [])
{
	return in_array(strtolower(pathinfo($archivo, PATHINFO_EXTENSION)), $extensiones_validas);
}

/**
 * subir_archivo
 *
 * Método general para subir cualquier archivo
 *
 * @param
 *  $ruta -> Ubicación en el servidor donde se guardará el archivo. Si no existe, se crea
 *  $campo -> Campo donde se envía el archivo
 *  $tipo_archivo -> Extensiones de los tipos de archivo a subir. Se puede especificar más de una, separándolas por pipe
 *  $prefijo_uniqid -> Prefijo que se antepondrá al nombre único con el que se almacenará el archivo
 */
function subir_archivo($ruta = null, $campo = null, $tipo_archivo = null, $prefijo_uniqid = null)
{
	$CI = &get_instance();

	$respuesta = [
		'error' => true,
	];

	if (empty($ruta)) {
		$respuesta['msg'] = 'No se especificó la ruta donde se almacenará el archivo';
		return $respuesta;
	}
	if ($campo === null) {
		$respuesta['msg'] = 'No se especificó el archivo a almacenar';
		return $respuesta;
	}
	if (empty($tipo_archivo) || strlen($tipo_archivo) < 2) {
		$respuesta['msg'] = 'No se especificó el tipo de archivo a almacenar';
		return $respuesta;
	}

	if (@unserialize($tipo_archivo) === false) {
		$tipo_archivo = [$tipo_archivo];
	} else {
		$tipo_archivo = unserialize($tipo_archivo);
	}

	if (!extension_valida($_FILES[$campo]['name'], $tipo_archivo)) {
		$respuesta['msg'] = 'El tipo de archivo no es de los tipos permitidos';
		return $respuesta;
	}

	// Crea la ruta donde se almacenará el archivo, si no existe
	if (!file_exists($ruta)) {
		if (!mkdir($ruta, 0777, true)) {
			$respuesta['msg'] = 'Imposible crear la ruta donde se subirá el archivo';
			return $respuesta;
		}
	}

	// Genera el nombre del archivo único
	$nombre_interno = uniqid(!empty($prefijo_uniqid) ? $prefijo_uniqid . '_' : '');

	// Configura las opciones de carga de archivos
	$config = [
		'upload_path' => $ruta,
		'file_name' => $nombre_interno,
		// 'allowed_types' => implode('|', $tipo_archivo),
		// 'allowed_types' => $tipo_archivo,
		'allowed_types' => '*',
		'max_size' => 0,
	];
	$CI->load->library('upload');
	$CI->upload->initialize($config);

	// Sube el archivo
	$old_memory_limit = ini_set('memory_limit', '512M');
	if (!$CI->upload->do_upload($campo)) {
		$respuesta['msg'] = strip_tags($CI->upload->display_errors());
		$old_memory_limit = ini_set('memory_limit', $old_memory_limit);
		return $respuesta;
	}

	// Recuperar la información del archivo subido
	$old_memory_limit = ini_set('memory_limit', $old_memory_limit);

	$archivo_subido = $CI->upload->data();
	$respuesta['error'] = false;
	$respuesta['archivo_original'] = $archivo_subido['client_name'];
	$respuesta['archivo_servidor'] = $archivo_subido['file_name'];
	$respuesta['ruta_archivo'] = $archivo_subido['file_path'];
	$respuesta['es_imagen'] = $archivo_subido['is_image'];

	return $respuesta;
}

if (!function_exists('subir_archivo_word')) {
	/**
	 * subir_archivo_word
	 *
	 * Subir un archivo de Word
	 *
	 * @param
	 *  $ruta -> Ubicación en el servidor donde se guardará el archivo. Si no existe, se crea
	 *  $campo -> Campo donde se envía el archivo
	 *  $prefijo_uniqid -> Prefijo que se antepondrá al nombre único con el que se almacenará el archivo
	 */
	function subir_archivo_word($ruta = null, $campo = null, $prefijo_uniqid = null)
	{
		return subir_archivo($ruta, $campo, WORD_FILE_TYPE, $prefijo_uniqid);
	}
}

if (!function_exists('subir_documento')) {
	/**
	 * subir_documento
	 *
	 * Subir un documento
	 *
	 * @param
	 *  $ruta -> Ubicación en el servidor donde se guardará el archivo. Si no existe, se crea
	 *  $campo -> Campo donde se envía el archivo
	 *  $prefijo_uniqid -> Prefijo que se antepondrá al nombre único con el que se almacenará el archivo
	 */
	function subir_documento($ruta = null, $campo = null, $prefijo_uniqid = null)
	{
		return subir_archivo($ruta, $campo, DOCUMENT_FILE_TYPE, $prefijo_uniqid);
	}
}

if (!function_exists('subir_imagen')) {
	/**
	 * subir_imagen
	 *
	 * Subir una imagen
	 *
	 * @param
	 *  $ruta -> Ubicación en el servidor donde se guardará el archivo. Si no existe, se crea
	 *  $campo -> Campo donde se envía el archivo
	 *  $prefijo_uniqid -> Prefijo que se antepondrá al nombre único con el que se almacenará el archivo
	 */
	function subir_imagen($ruta = null, $campo = null, $prefijo_uniqid = null)
	{
		return subir_archivo($ruta, $campo, IMAGE_FILE_TYPE, $prefijo_uniqid);
	}
}

/*
 * Borrar carpeta de forma recursiva, incluyendo los archivos de la misma
 */
/**
 * borrar_directorio
 *
 * Borrar directorio de forma recursiva, incluyendo los archivos de la misma
 *
 * @param
 *  $directorio -> Nombre del directorio a borrar. Debe incluir la ruta completa
 */
if (!function_exists('borrar_directorio')) {
	function borrar_directorio($directorio)
	{
		if (is_dir($directorio)) {
			$archivos = glob($directorio . '*', GLOB_MARK); //GLOB_MARK adds a slash to directories returned

			foreach ($archivos as $archivo) {
				borrar_directorio($archivo);
			}

			rmdir($directorio);
		} elseif (is_file($directorio)) {
			unlink($directorio);
		}
	}
}

/**
 * redimensionar_imagen
 *
 * Redimensionar una imagen
 *
 * @param
 *  $archivo_imagen -> Archivo con la imagen a redimensionar. Debe incluir la ruta completa
 *  $ancho -> Ancho de la imagen redimensionada
 *  $alto -> Alto de la imagen redimensionada
 * @return  ...
 */

if (!function_exists('redimensionar_imagen')) {
	function redimensionar_imagen($archivo_imagen, $ancho = 0, $alto = 0)
	{
		$CI = &get_instance();

		$respuesta = [
			'error' => true,
			'msg' => null,
		];

		if (!file_exists($archivo_imagen)) {
			$respuesta['mensaje'] = 'El archivo con la imagen no existe.';
			return $respuesta;
		}

		$config = [
			'image_library' => 'gd2',
			'source_image' => $archivo_imagen,
			'maintain_ratio' => true,
			'width' => $ancho != 0 ? $ancho : IMAGE_MAX_WIDTH,
			'height' => $alto != 0 ? $alto : IMAGE_MAX_HEIGHT,
		];
		$CI->load->library('image_lib', $config);
		$CI->image_lib->clear();
		$CI->image_lib->initialize($config);

		$old_memory_limit = ini_set('memory_limit', '512M');
		$respuesta['error'] = !$CI->image_lib->resize();

		if ($respuesta['error']) {
			$respuesta['msg'] = strip_tags($CI->image_lib->display_errors());
		}
		ini_set('memory_limit', $old_memory_limit);

		return $respuesta;
	}
}

/**
 * imagen_crear_thumb
 *
 * Crea una miniatura de la imagen
 *
 * @param
 *  $archivo_imagen -> Archivo con la imagen. Debe incluir la ruta completa
 *  $path_imagen -> ruta relativa donde será almacenada la miniatura
 *  $ancho -> Ancho de la imagen miniatura
 *  $alto -> Alto de la imagen miniatura
 * @return  ...
 */
if (!function_exists('imagen_crear_thumb')) {
	function imagen_crear_thumb($archivo_imagen, $path_imagen = null, $ancho = 0, $alto = 0)
	{
		$CI = &get_instance();

		$respuesta = [
			'error' => true,
			'msg' => null,
		];

		if (empty($path_imagen)) {
			$path_imagen = dirname($archivo_imagen) . '/thumbs/';
		}

		if (!file_exists($path_imagen)) {
			if (!mkdir($path_imagen, 0777, true)) {
				$respuesta['mensaje'] = 'Imposible crear la ruta donde se subirá la imagen miniatura.';
				return $respuesta;
			}
		}

		$config = [
			'image_library' => 'gd2',
			'source_image' => $archivo_imagen,
			'create_thumb' => true,
			'maintain_ratio' => true,
			'width' => $ancho != 0 ? $ancho : IMAGE_THUMB_MAX_WIDTH,
			'height' => $alto != 0 ? $alto : IMAGE_THUMB_MAX_HEIGHT,
			'new_image' => $path_imagen,
		];
		$CI->load->library('image_lib', $config);
		$CI->image_lib->clear();
		$CI->image_lib->initialize($config);
		$respuesta['error'] = !$CI->image_lib->resize();

		if ($respuesta['error']) {
			$respuesta['msg'] = strip_tags($CI->image_lib->display_errors());
		} else {
			$archivo_thumb = pathinfo($archivo_imagen);
			$respuesta['file_name'] = $archivo_thumb['filename'] . '_thumb.' . $archivo_thumb['extension'];
			$respuesta['file_path'] = $path_imagen;
			$respuesta['full_path'] = $path_imagen . $respuesta['file_name'];
		}

		return $respuesta;
	}
}

if (!function_exists('formatBytes')) {
	function formatBytes($bytes, $precision = 2)
	{
		$units = ['B', 'KB', 'MB', 'GB', 'TB'];

		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);

		// Uncomment one of the following alternatives
		$bytes /= pow(1024, $pow);
		// $bytes /= 1 << 10 * $pow;

		return [
			'size' => round($bytes, $precision),
			'unit' => $units[$pow],
		];
	}
}

if (!function_exists('normalizeFiles')) {
	/**
	 * normalizeFiles
	 *
	 * Subir un archivo de Word
	 *
	 * @param
	 *  $ruta -> Ubicación en el servidor donde se guardará el archivo. Si no existe, se crea
	 *  $campo -> Campo donde se envía el archivo
	 *  $prefijo_uniqid -> Prefijo que se antepondrá al nombre único con el que se almacenará el archivo
	 */
	// function normalizeFiles()
	function normalizeFiles(&$files)
	{
		$_files = [];
		$_files_count = count($files['name']);
		$_files_keys = array_keys($files);
		$_idx = 0;
		$_empty = false;

		for ($i = 0; $i < $_files_count; $i++) {
			$_empty = false;
			foreach ($_files_keys as $key) {
				if ($key == 'name' && empty($files[$key][$i])) {
					$_empty = true;
					break;
				}
				$_files[$_idx][$key] = $files[$key][$i];
			}
			if (!$_empty) {
				$_idx++;
			}
		}

		return $_files;
		/* $out = [];
		foreach ($_FILES as $key => $file) {
			if (isset($file['name']) && is_array($file['name'])) {
				$new = [];
				foreach (['name', 'type', 'tmp_name', 'error', 'size'] as $k) {
					array_walk_recursive(
						$file[$k],
						function (&$data, $key, $k) {
							$data = [$k => $data];
						},
						$k
					);
					$new = array_replace_recursive($new, $file[$k]);
				}
				$out[$key] = $new;
			} else {
				$out[$key] = $file;
			}
		}
		return $out; */
	}
}
?>
