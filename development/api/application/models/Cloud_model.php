<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Modelo: Cloud
 *
 * Este modelo realiza las operaciones requeridas sobre la nube de archivos
 *
 * @package   CodeIgniter
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Cloud_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	/*
    Obtener contenido de la carpeta
      $idCondominio => ID del condominio
      $idCarpeta => ID de la carpeta
	*/
	public function listar_carpeta($idCondominio = 0, $idCarpeta = 0)
	{
		try {
			// Si no se proporciona ID de condominio ni ID de carpeta, aborta el proceso
			if (empty($idCondominio) && empty($idCarpeta)) {
				return false;
			}

			$this->db->select(
				'id_cloud_carpeta id,
          carpeta nombre,
          nivel,
          fk_id_cloud_carpeta_padre id_cloud_carpeta_padre'
			);
			if ($idCarpeta > 0) {
				$this->db->where(['id_cloud_carpeta' => $idCarpeta]);
			} else {
				$this->db->where(['carpeta' => '/']);
			}

			$carpeta = $this->db
				->get_where('cloud_carpetas', ['fk_id_condominio' => $idCondominio, 'estatus' => 1])
				->row_array();

			if (empty($carpeta)) {
				return false;
			}

			$carpetas = $this->db
				->select(
					'1 tipo,
            id_cloud_carpeta id,
            0 archivo,
            carpeta nombre,
            0 tamanio,
            0 unidad_medida,
            0 archivo_interno,
            fecha_registro,
            fecha_modificacion'
				)
				->order_by('carpeta')
				->get_where('cloud_carpetas', [
					'fk_id_condominio' => $idCondominio,
					'fk_id_cloud_carpeta_padre' => $carpeta['id'],
					'estatus' => 1,
				])
				->result_array();

			$archivos = $this->db
				->select(
					'2 tipo,
            id_cloud_archivo id,
            archivo,
            CONCAT_WS(".", archivo, extension) nombre,
            tamanio,
            tamanio_unidad_medida unidad_medida,
            archivo_interno,
            fecha_registro,
            fecha_modificacion'
				)
				->order_by('archivo')
				->get_where('cloud_archivos', [
					'fk_id_cloud_carpeta' => $carpeta['id'],
					'estatus' => 1,
				])
				->result_array();

			$resultado['carpeta'] = $carpeta;
			$resultado['contenido'] = array_merge($carpetas, $archivos);

			return $resultado;
		} catch (Exception $e) {
			throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
			die();
		}
	}

	/*
    Crear carpeta
      $data => Información a procesar
	*/
	public function crear_carpeta($data)
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

			// Validar que los campos existan en la tabla
			if (!validar_campos('cloud_carpetas', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con la base de datos.';
				return $respuesta;
			}

			$data['nivel'] = $this->db
				->select('nivel')
				->get_where('cloud_carpetas', [
					'fk_id_condominio' => $data['fk_id_condominio'],
					'id_cloud_carpeta' => $data['fk_id_cloud_carpeta_padre'],
				])
				->row_array()['nivel'];
			// Si no pudo obtener el nivel de la carpeta
			if (empty($data['nivel'])) {
				$respuesta['msg'] = 'Error al obtener nivel de anidamiento.';
				return $respuesta;
			}
			if (intval($data['nivel']) >= 7) {
				$respuesta['msg'] = 'Se ha alcanzado el máximo nivel de anidamiento de carpetas.';
				return $respuesta;
			}
			$data['nivel'] = intval($data['nivel']) + 1;

			// Almacenar información en BD
			$respuesta['err'] = !$this->db->insert('cloud_carpetas', $data);
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if ($respuesta['err']) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $respuesta;
			}

			$nuevoID = $this->db->insert_id();
			$respuesta['carpeta'] = $this->db
				->select(
					'1 tipo,
            id_cloud_carpeta id,
            0 archivo,
            carpeta nombre,
            0 tamanio,
            0 unidad_medida,
            0 archivo_interno,
            fecha_registro,
            fecha_modificacion'
				)
				->get_where('cloud_carpetas', [
					'id_cloud_carpeta' => $nuevoID,
				])
				->row_array();
			$respuesta['msg'] = 'Carpeta creada con éxito.';
			// $respuesta['contenido'] = $this->listar($this->db->insert_id());
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
			die();
		}
		return $respuesta;
	}

	/*
    Renombrar carpeta
      $data => Información a procesar
	*/
	public function renombrar_carpeta($data)
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

			$idCondominio = $data['id_condominio'];
			$idCarpeta = $data['id_carpeta'];
			unset($data['id_condominio']);
			unset($data['id_carpeta']);
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');

			$carpeta = $this->db
				->get_where('cloud_carpetas', [
					'fk_id_condominio' => $idCondominio,
					'id_cloud_carpeta' => $idCarpeta,
					'estatus' => 1,
				])
				->row_array();

			if (empty($carpeta)) {
				$respuesta['msg'] = 'Imposible obtener información de la carpeta.';
				return $respuesta;
			}
			if ($carpeta['carpeta'] == '/') {
				$respuesta['msg'] = 'Imposible renombrar la carpeta principal.';
				return $respuesta;
			}

			// Determinar si ya xiste una carpeta con el nuevo nombre
			if (
				$this->db
					->get_where('cloud_carpetas', [
						'fk_id_condominio' => $idCondominio,
						'id_cloud_carpeta !=' => $idCarpeta,
						'carpeta' => $data['carpeta'],
						'estatus' => 1,
					])
					->num_rows() > 0
			) {
				$respuesta['msg'] = 'Ya existe una carpeta con ese nombre.';
				return $respuesta;
			}

			// Validar que los campos existan en la tabla
			if (!validar_campos('cloud_carpetas', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con la base de datos.';
				return $respuesta;
			}

			// Almacenar información en BD
			$respuesta['err'] = !$this->db->update('cloud_carpetas', $data, ['id_cloud_carpeta' => $idCarpeta]);
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if ($respuesta['err']) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $respuesta;
			}

			$respuesta['carpeta'] = $this->db
				->select(
					'1 tipo,
            id_cloud_carpeta id,
            0 archivo,
            carpeta nombre,
            0 tamanio,
            0 unidad_medida,
            0 archivo_interno,
            fecha_registro,
            fecha_modificacion'
				)
				->get_where('cloud_carpetas', [
					'id_cloud_carpeta' => $idCarpeta,
				])
				->row_array();
			$respuesta['msg'] = 'Carpeta renombrada con éxito.';
			// $respuesta['contenido'] = $this->listar($this->db->insert_id());
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
			die();
		}
		return $respuesta;
	}

	/*
    Alterna el estatus de la carpeta
      $data => Información del registro a procesar
	*/
	public function alternar_estatus_carpeta($data)
	{
		$respuesta = [
			'err' => true,
			'msg' => null,
		];

		try {
			// Validar que sea proporcionada la información requerida para la actualizacion
			if (empty($data)) {
				$respuesta['msg'] = 'Debe especificar la información a actualizar.';
				return $respuesta;
			}

			$idCondominio = $data['id_condominio'];
			$idCarpeta = $data['id_carpeta'];

			// Verificar cuantos registros serán actualizados
			$carpeta = $this->db->get_where('cloud_carpetas', [
				'fk_id_condominio' => $idCondominio,
				'id_cloud_carpeta' => $idCarpeta,
			]);

			if ($carpeta->num_rows() != 1) {
				if ($carpeta->num_rows() == 0) {
					$respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
				} elseif ($carpeta->num_rows() > 1) {
					$respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
				}
				return $respuesta;
			}
			$carpeta = $carpeta->row();

			$estatus = 0;
			// Si no se especifica valor para estatus, se alterna el valor
			if (isset($data['estatus']) && intval($data['estatus'])) {
				$estatus = intval($data['estatus']) != 0 ? 1 : 0;
			} else {
				$estatus = !$carpeta->estatus;
			}

			if ($estatus == 0) {
				if ($carpeta->carpeta == '/') {
					$respuesta['msg'] = 'Imposible eliminar la carpeta principal.';
				} elseif (!empty($this->listar_carpeta($idCondominio, $idCarpeta)['contenido'])) {
					$respuesta['msg'] = 'Imposible eliminar la carpeta, contiene información.';
				}
			}
			if (!empty($respuesta['msg'])) {
				return $respuesta;
			}

			$data = [
				'estatus' => $estatus,
				'fk_id_usuario_modifico' => $data['fk_id_usuario_modifico'],
				'fecha_modificacion' => date('Y-m-d H:i:s'),
			];

			// Almacenar información en BD
			$respuesta['err'] = !$this->db->update('cloud_carpetas', $data, [
				'id_cloud_carpeta' => $idCarpeta,
			]);
			$respuesta['msg'] = $respuesta['err']
				? 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message']
				: (isset($estatus) ? 'Carpeta ' . ($estatus == 0 ? 'borrada' : 'reactivada') : 'Estatus modificado') .
					' con éxito.';
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
			die();
		}
		return $respuesta;
	}

	/*
    Subir archivo
      $data => Información a procesar
	*/
	public function subir_archivo($data)
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

			$carpeta = $this->db
				->select('IF(ISNULL(a.id_cloud_archivo), 0, 1) existe_archivo')
				->join(
					'cloud_archivos a',
					'a.fk_id_cloud_carpeta = c.id_cloud_carpeta AND CONCAT_WS(".", archivo, extension) = "' .
						$data['archivo']['name'] .
						'" AND a.estatus = 1',
					'left'
				)
				->get_where('cloud_carpetas c', [
					'c.fk_id_condominio' => $data['id_condominio'],
					'c.id_cloud_carpeta' => $data['id_carpeta'],
					'c.estatus' => 1,
				])
				->row_array();

			if (empty($carpeta)) {
				$respuesta['msg'] = 'Imposible obtener información de la carpeta.';
				return $respuesta;
			}

			if ($carpeta['existe_archivo'] != 0) {
				$respuesta['msg'] = 'Archivo ya existe.';
				return $respuesta;
			}

			$tamanio_archivo = formatBytes($data['archivo']['size']);
			$dataArchivo = [
				'fk_id_cloud_carpeta' => $data['id_carpeta'],
				'archivo' => basename($data['archivo']['name'], '.' . pathinfo($data['archivo']['name'], PATHINFO_EXTENSION)),
				'extension' => pathinfo($data['archivo']['name'], PATHINFO_EXTENSION),
				'tamanio' => $tamanio_archivo['size'],
				'tamanio_unidad_medida' => $tamanio_archivo['unit'],
				'fk_id_usuario_registro' => $data['id_usuario'],
			];

			// Define la carpeta temporal para la carga de los archivos
			$carpeta_cargar_archivos = PATH_ARCHIVOS_CLOUD . '/' . $data['id_condominio'] . '/';
			$cargar_archivo = subir_archivo($carpeta_cargar_archivos, 'archivo', CLOUD_FILE_TYPE, 'cloud_file');
			if ($cargar_archivo['error']) {
				$respuesta['msg'] = 'Error al cargar el archivo.' . PHP_EOL . $cargar_archivo['msg'];
				return $respuesta;
			}
			$dataArchivo['archivo_interno'] = $cargar_archivo['archivo_servidor'];

			// Validar que los campos existan en la tabla
			if (!validar_campos('cloud_archivos', $dataArchivo)) {
				borrar_directorio($carpeta_cargar_archivos . $dataArchivo['archivo_interno']);
				$respuesta['msg'] = 'Error de integridad de la información con la base de datos.';
				return $respuesta;
			}

			// Almacenar información en BD
			if (!$this->db->insert('cloud_archivos', $dataArchivo)) {
				borrar_directorio($carpeta_cargar_archivos . $dataArchivo['archivo_interno']);
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $respuesta;
			}
			$nuevoID = $this->db->insert_id();

			$respuesta['archivo'] = $this->db
				->select(
					'2 tipo,
            id_cloud_archivo id,
            archivo,
            CONCAT_WS(".", archivo, extension) nombre,
            tamanio,
            tamanio_unidad_medida unidad_medida,
            archivo_interno,
            fecha_registro,
            fecha_modificacion'
				)
				->get_where('cloud_archivos', [
					'id_cloud_archivo' => $nuevoID,
				])
				->row_array();
			$respuesta['msg'] = 'Archivo subido con éxito.';
			$respuesta['err'] = false;
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
			die();
		}
		return $respuesta;
	}

	/*
    Renombrar archivo
      $data => Información a procesar
	*/
	public function renombrar_archivo($data)
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

			$idCondominio = $data['id_condominio'];
			$idArchivo = $data['id_archivo'];
			unset($data['id_condominio']);
			unset($data['id_archivo']);
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');

			// Obtener archivo a renombrar
			$archivoExistente = $this->db
				->select(
					'c.id_cloud_carpeta,
            IF(ISNULL(ae.id_cloud_archivo), 0, 1) existe_nuevo_nombre'
				)
				->join(
					'cloud_carpetas c',
					'c.fk_id_condominio = ' . $idCondominio . ' AND c.id_cloud_carpeta = a.fk_id_cloud_carpeta AND c.estatus = 1'
				)
				->join(
					'cloud_archivos ae',
					'ae.fk_id_cloud_carpeta = a.fk_id_cloud_carpeta AND CONCAT_WS(".", ae.archivo, ae.extension) = CONCAT_WS(".", "' .
						$data['archivo'] .
						'", a.extension) AND ae.id_cloud_archivo != a.id_cloud_archivo AND ae.estatus = 1',
					'left'
				)
				->get_where('cloud_archivos a', [
					'a.id_cloud_archivo' => $idArchivo,
					'a.estatus' => 1,
				])
				->row_array();

			if (empty($archivoExistente)) {
				$respuesta['msg'] = 'Imposible obtener información del archivo.';
				return $respuesta;
			}

			// Determinar si ya existe en la carpeta un archivo con el nuevo nombre
			if ($archivoExistente['existe_nuevo_nombre'] == 1) {
				$respuesta['msg'] = 'Ya existe un archivo con ese nombre.';
				return $respuesta;
			}

			// Validar que los campos existan en la tabla
			if (!validar_campos('cloud_archivos', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con la base de datos.';
				return $respuesta;
			}

			// Almacenar información en BD
			$respuesta['err'] = !$this->db->update('cloud_archivos', $data, ['id_cloud_archivo' => $idArchivo]);
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if ($respuesta['err']) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $respuesta;
			}

			$respuesta['archivo'] = $this->db
				->select(
					'2 tipo,
            id_cloud_archivo id,
            archivo,
            CONCAT_WS(".", archivo, extension) nombre,
            tamanio,
            tamanio_unidad_medida unidad_medida,
            archivo_interno,
            fecha_registro,
            fecha_modificacion'
				)
				->get_where('cloud_archivos', [
					'id_cloud_archivo' => $idArchivo,
				])
				->row_array();
			$respuesta['msg'] = 'Archivo renombrado con éxito.';
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
			die();
		}
		return $respuesta;
	}

	/*
    Alterna el estatus del archivo
      $data => Información del registro a procesar
	*/
	public function alternar_estatus_archivo($data)
	{
		$respuesta = [
			'err' => true,
			'msg' => null,
		];

		try {
			// Validar que sea proporcionada la información requerida para la actualizacion
			if (empty($data)) {
				$respuesta['msg'] = 'Debe especificar la información a actualizar.';
				return $respuesta;
			}

			$idCondominio = $data['id_condominio'];
			$idArchivo = $data['id_archivo'];

			// Obtener archivo a alternar estatus
			$archivo = $this->db
				->select(
					'a.archivo_interno,
            a.estatus'
				)
				->join(
					'cloud_carpetas c',
					'c.fk_id_condominio = ' . $idCondominio . ' AND c.id_cloud_carpeta = a.fk_id_cloud_carpeta AND c.estatus = 1'
				)
				->get_where('cloud_archivos a', [
					'a.id_cloud_archivo' => $idArchivo,
				]);

			if ($archivo->num_rows() != 1) {
				if ($archivo->num_rows() == 0) {
					$respuesta['msg'] = 'No se encontró el archivo especificado.';
				} elseif ($archivo->num_rows() > 1) {
					$respuesta['msg'] = 'Se detectó más de un archivo. Contactar al Administrador.';
				}
				return $respuesta;
			}
			$archivo = $archivo->row();

			$estatus = 0;
			// Si no se especifica valor para estatus, se alterna el valor
			if (isset($data['estatus']) && intval($data['estatus'])) {
				$estatus = intval($data['estatus']) != 0 ? 1 : 0;
			} else {
				$estatus = !$archivo->estatus;
			}

			$data = [
				'estatus' => $estatus,
				'fk_id_usuario_modifico' => $data['fk_id_usuario_modifico'],
				'fecha_modificacion' => date('Y-m-d H:i:s'),
			];

			// Almacenar información en BD
			$respuesta['err'] = !$this->db->update('cloud_archivos', $data, [
				'id_cloud_archivo' => $idArchivo,
			]);

			if (!$respuesta['err'] && $estatus == 0) {
				borrar_directorio(PATH_ARCHIVOS_CLOUD . '/' . $idCondominio . '/' . $archivo->archivo_interno);
			}
			$respuesta['msg'] = $respuesta['err']
				? 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message']
				: (isset($estatus) ? 'Archivo ' . ($estatus == 0 ? 'borrado' : 'reactivado') : 'Estatus modificado') .
					' con éxito.';
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
			die();
		}
		return $respuesta;
	}
}

/* End of file Cloud_model.php */
/* Location: ./application/models/Cloud_model.php */
