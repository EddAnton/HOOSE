<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Modelo: Proyecto_model
 *
 * Este modelo realiza las operaciones requeridas sobre los Proyectos
 *
 * @package   CodeIgniter
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Proyecto_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper('File_Upload');
	}

	/*
    Obtener información del identificador especificado.
	*/
	public function listar($idProyecto = 0, $idCondominio = 0, $soloActivos = false)
	{
		try {
			if (empty($idProyecto) && empty($idCondominio)) {
				return false;
			}
			$this->db->select(
				'id_proyecto,
          titulo,
          presupuesto,
          fecha_inicio,
          fecha_fin,
          porcentaje_avance,
          estatus'
			);

			if (!empty($idProyecto)) {
				$this->db->select('descripcion')->where([
					'id_proyecto' => $idProyecto,
				]);
			} else {
				$this->db->where(['fk_id_condominio' => $idCondominio]);
			}
			if ($soloActivos) {
				$this->db->where(['estatus' => 1]);
			}

			$result = $this->db->get('proyectos');
			$result = !empty($idProyecto) ? $result->row_array() : $result->result_array();
			if (!empty($idProyecto)) {
				// $this->db->select('imagen_archivo imagen')->where(['estatus' => 1]);

				$result['imagenes'] = $this->db
					->select(
						'id_proyecto_imagen,
              imagen_archivo imagen'
					)
					->order_by('id_proyecto_imagen')
					->get_where('proyectos_imagenes', ['fk_id_proyecto' => $idProyecto, 'estatus' => 1])
					->result_array();
			}

			return $result;
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

			// Obtener los archivos con las imágenes del proyecto
			$archivos_imagenes = !empty($data['archivos_imagenes']) ? $data['archivos_imagenes'] : null;
			unset($data['archivos_imagenes']);

			// Validar que los campos existan en la tabla
			if (!validar_campos('proyectos', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con la base de datos.';
				return $respuesta;
			}

			// Define la carpeta temporal para la carga de los archivos
			$carpeta_temporal_cargar_archivos = PATH_ARCHIVOS_PROYECTOS . '/' . uniqid() . '/';
			$se_cargaron_archivos = false;

			/*
			  Intenta la carga de los archivos de las imágenes y agrega el nombre del archivo generado a la data.
				Si no se pudo cargar el archivo, aborta el proceso
      */
			$dataImagenesProyecto = [];
			if (!empty($archivos_imagenes) && $_FILES[$archivos_imagenes]) {
				$_FILES = normalizeFiles($_FILES[$archivos_imagenes]);
			} else {
				$_FILES = [];
			}

			for ($i = 0; $i < count($_FILES); $i++) {
				$cargar_archivo = subir_imagen($carpeta_temporal_cargar_archivos, $i, 'proyecto_img');

				if ($cargar_archivo['error']) {
					$respuesta['msg'] = 'Error al cargar imagen.' . PHP_EOL . $cargar_archivo['msg'];
					borrar_directorio($carpeta_temporal_cargar_archivos);
					return $respuesta;
				}

				$redimensionar_imagen = redimensionar_imagen(
					$cargar_archivo['ruta_archivo'] . $cargar_archivo['archivo_servidor'],
					1024,
					768
				);
				if ($redimensionar_imagen['error']) {
					$respuesta['msg'] = 'Error al redimensionar imagen.' . PHP_EOL . $redimensionar_imagen['msg'];
					borrar_directorio($carpeta_temporal_cargar_archivos);
					return $respuesta;
				}

				$se_cargaron_archivos = true;
				$dataImagenesProyecto[] = [
					'imagen_archivo' => $cargar_archivo['archivo_servidor'],
					'fk_id_usuario_registro' => $data['fk_id_usuario_registro'],
				];
			}

			/* print_r($dataImagenesProyecto);
			 exit(); */

			// Almacenar información en BD
			// Inicializar la transaccion
			$this->db->trans_start();
			$this->db->trans_strict(false);

			// Insertar registro en proyectos
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if (!$this->db->insert('proyectos', $data)) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				$this->db->trans_rollback();
				borrar_directorio($carpeta_temporal_cargar_archivos);
				return $respuesta;
			}

			// Obtener nuevo ID del registro
			$nuevoID = $this->db->insert_id();

			// Si se cargaron imágenes, almacena la info en proyectos_imagenes
			if (!empty($dataImagenesProyecto)) {
				// Agregar ID del proyecto al arreglo con las imagenes cargadas
				$dataImagenesProyecto = agregar_columnas_arreglo($dataImagenesProyecto, [
					'fk_id_proyecto' => $nuevoID,
				]);

				// Insertar registro
				// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
				if (!$this->db->insert_batch('proyectos_imagenes', $dataImagenesProyecto)) {
					$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
					$this->db->trans_rollback();
					borrar_directorio($carpeta_temporal_cargar_archivos);
					return $respuesta;
				}
			}

			// Si no existió error al almacenar la información y se cargaron archivos, renombra la carpeta temporal
			if ($se_cargaron_archivos) {
				$carpeta_cargar_archivos = PATH_ARCHIVOS_PROYECTOS . '/' . $nuevoID . '/';
				// Renombrar el directorio temporal de carga de archivos
				if (!rename($carpeta_temporal_cargar_archivos, $carpeta_cargar_archivos)) {
					$respuesta['msg'] = 'Error interno FOLDER_RENAME.';
					$this->db->trans_rollback();
					borrar_directorio($carpeta_temporal_cargar_archivos);
					borrar_directorio($carpeta_cargar_archivos);
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

			$respuesta['proyecto'] = $this->listar($nuevoID);
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
			$idProyecto = $data['id_proyecto'];
			// Obtener el archivo del comprobante
			$archivos_imagenes = !empty($data['archivos_imagenes']) ? $data['archivos_imagenes'] : null;
			$imagenes_borrar = !empty($data['imagenes_borrar']) ? $data['imagenes_borrar'] : null;
			unset($data['imagenes_borrar']);
			unset($data['archivos_imagenes']);
			unset($data['id_proyecto']);
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');

			// Validar que los campos existan en la tabla
			if (!validar_campos('proyectos', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con la base de datos.';
				return $respuesta;
			}

			// Verificar cuantos registros serán actualizados
			$proyecto = $this->db->get_where('proyectos', [
				'id_proyecto' => $idProyecto,
				'estatus' => 1,
			]);

			if ($proyecto->num_rows() != 1) {
				if ($proyecto->num_rows() == 0) {
					$respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
				} elseif ($proyecto->num_rows() > 1) {
					$respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
				}
				return $respuesta;
			}
			$proyecto = $proyecto->row_array();

			// Define la carpeta para la carga de los archivos
			$carpeta_cargar_archivos = PATH_ARCHIVOS_PROYECTOS . '/' . $idProyecto . '/';
			$carpeta_temporal_cargar_archivos = PATH_ARCHIVOS_PROYECTOS . '/' . uniqid() . '/';
			$se_cargaron_archivos = false;
			/*
			  Intenta la carga de los archivos de las imágenes y agrega el nombre del archivo generado a la data.
				Si no se pudo cargar el archivo, aborta el proceso
      */
			$dataImagenesProyecto = [];
			if (!empty($archivos_imagenes) && $_FILES[$archivos_imagenes]) {
				$_FILES = normalizeFiles($_FILES[$archivos_imagenes]);
			} else {
				$_FILES = [];
			}
			for ($i = 0; $i < count($_FILES); $i++) {
				$cargar_archivo = subir_imagen($carpeta_temporal_cargar_archivos, $i, 'proyecto_img');

				if ($cargar_archivo['error']) {
					$respuesta['msg'] = 'Error al cargar imagen.' . PHP_EOL . $cargar_archivo['msg'];
					borrar_directorio($carpeta_temporal_cargar_archivos);
					return $respuesta;
				}

				$redimensionar_imagen = redimensionar_imagen(
					$cargar_archivo['ruta_archivo'] . $cargar_archivo['archivo_servidor'],
					1024,
					768
				);
				if ($redimensionar_imagen['error']) {
					$respuesta['msg'] = 'Error al redimensionar imagen.' . PHP_EOL . $redimensionar_imagen['msg'];
					borrar_directorio($carpeta_temporal_cargar_archivos);
					return $respuesta;
				}

				$se_cargaron_archivos = true;
				$dataImagenesProyecto[] = [
					'fk_id_proyecto' => $idProyecto,
					'imagen_archivo' => $cargar_archivo['archivo_servidor'],
					'fk_id_usuario_registro' => $data['fk_id_usuario_modifico'],
				];
			}

			// Almacenar información en BD
			// Inicializar la transaccion
			$this->db->trans_start();
			$this->db->trans_strict(false);

			// Si existe error al actualizar el registro, obtiene descripción del mismo y aborta el proceso
			if (
				!$this->db->update('proyectos', $data, [
					'id_proyecto' => $idProyecto,
				])
			) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				$this->db->trans_rollback();
				borrar_directorio($carpeta_temporal_cargar_archivos);
				return $respuesta;
			}

			// Si se especificaron imagenes a borrar, actualiza estatus en proyectos_imagenes
			if (!empty($imagenes_borrar)) {
				// Obtener el nombre del archivo a borrar físicamente
				$imagenesBorradas = $this->db
					->select('imagen_archivo')
					->where_in('id_proyecto_imagen', $imagenes_borrar)
					->get_where('proyectos_imagenes', ['fk_id_proyecto' => $idProyecto])
					->result_array();
				// Establecer la información que se actualizará
				$dataProyectoImagenesBorrar = [];
				foreach ($imagenes_borrar as $imagenBorrar) {
					$dataProyectoImagenesBorrar[] = [
						'id_proyecto_imagen' => $imagenBorrar,
						'fk_id_proyecto' => $idProyecto,
						'estatus' => 0,
						'fk_id_usuario_modifico' => $data['fk_id_usuario_modifico'],
						'fecha_modificacion' => $data['fecha_modificacion'],
					];
				}

				// Indicar que registros deben ser actualizados
				// $this->db->where_in('id_proyecto_imagen', $imagenes_borrar);
				// Si existe error al actualizar el registro, obtiene descripción del mismo y aborta el proceso
				if (!$this->db->update_batch('proyectos_imagenes', $dataProyectoImagenesBorrar, 'id_proyecto_imagen')) {
					$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
					$this->db->trans_rollback();
					borrar_directorio($carpeta_temporal_cargar_archivos);
					return $respuesta;
				}
			}

			// Si se cargaron imágenes, almacena la info en proyectos_imagenes
			if (!empty($dataImagenesProyecto)) {
				// Agregar ID del proyecto al arreglo con las imagenes cargadas
				// $dataImagenesProyecto = agregar_columnas_arreglo($dataImagenesProyecto, ['fk_id_proyecto' => $idProyecto]);

				// Insertar registro
				// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
				if (!$this->db->insert_batch('proyectos_imagenes', $dataImagenesProyecto)) {
					$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
					$this->db->trans_rollback();
					borrar_directorio($carpeta_temporal_cargar_archivos);
					return $respuesta;
				}
			}

			// Definir la carpeta donde se almacenarán finalmente los archivos
			$carpeta_cargar_archivos = PATH_ARCHIVOS_PROYECTOS . '/' . $idProyecto . '/';

			// Si no existió error al almacenar la información y se cargaron archivos,
			// copia los archivos cargados en la carpeta temporal a la carpeta del proyecto
			if ($se_cargaron_archivos) {
				// Si no existe, crea la ruta donde se almacenarán las imagenes
				if (!file_exists($carpeta_cargar_archivos)) {
					if (!mkdir($carpeta_cargar_archivos, 0777, true)) {
						$respuesta['msg'] = 'Imposible crear la ruta para las imágenes';
						return $this->db->trans_rollback();
						borrar_directorio($carpeta_temporal_cargar_archivos);
						$respuesta;
					}
				}
				foreach ($dataImagenesProyecto as $archivoCargado) {
					/**
					 * Intenta mover el archivo de la carpeta temporal a la carpeta del proyecto
					 * Si existe error al copiar el archivo:
					 *  - Deshace la transacción
					 *  - Borra la carpeta temporal
					 *  - Borra los archivos que si pudieron ser copiados
					 */
					if (
						!rename(
							$carpeta_temporal_cargar_archivos . $archivoCargado['imagen_archivo'],
							$carpeta_cargar_archivos . $archivoCargado['imagen_archivo']
						)
					) {
						$respuesta['msg'] = 'Error interno FILE_COPY.';
						$this->db->trans_rollback();
						borrar_directorio($carpeta_temporal_cargar_archivos);
						foreach ($dataImagenesProyecto as $archivoCargadoBorrar) {
							borrar_directorio($carpeta_cargar_archivos . $archivoCargadoBorrar['imagen_archivo']);
						}
						return $respuesta;
					}
				}
				borrar_directorio($carpeta_temporal_cargar_archivos);
			}

			// Si se especificaron imagenes a borrar
			if (!empty($imagenesBorradas)) {
				foreach ($imagenesBorradas as $archivoBorrar) {
					borrar_directorio($carpeta_cargar_archivos . $archivoBorrar['imagen_archivo']);
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

			$respuesta['proyecto'] = $this->listar($idProyecto);
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
			$idProyecto = $data['id_proyecto'];
			// Establecer la información que se actualizará
			$data = [
				'estatus' => 0,
				'fk_id_usuario_modifico' => $data['id_usuario_modifico'],
				'fecha_modificacion' => date('Y-m-d H:i:s'),
			];
			$dataProyectoImagenes = [
				'estatus' => 0,
				'fk_id_usuario_modifico' => $data['fk_id_usuario_modifico'],
				'fecha_modificacion' => $data['fecha_modificacion'],
			];

			// Validar que los campos existan en la tabla usuarios
			if (!validar_campos('proyectos', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con respecto a la base de datos.';
				return $respuesta;
			}

			// Verificar cuantos registros serán actualizados
			$proyecto = $this->db->get_where('proyectos', [
				'id_proyecto' => $idProyecto,
				'estatus' => 1,
			]);

			if ($proyecto->num_rows() != 1) {
				if ($proyecto->num_rows() == 0) {
					$respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
				} elseif ($proyecto->num_rows() > 1) {
					$respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
				}
				return $respuesta;
			}

			// Almacenar información en BD
			// Inicializar la transaccion
			$this->db->trans_start();
			$this->db->trans_strict(false);

			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if (!$this->db->update('proyectos', $data, ['id_proyecto' => $idProyecto])) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				$this->db->trans_rollback();
				return $respuesta;
			}

			// Si existe error al actualizar el registro, obtiene descripción del mismo y aborta el proceso
			if (
				!$this->db->update('proyectos_imagenes', $dataProyectoImagenes, [
					'fk_id_proyecto' => $idProyecto,
				])
			) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				$this->db->trans_rollback();
				return $respuesta;
			}

			// Eliminar la carpeta con las imágenes del proyecto
			$carpeta_archivos = PATH_ARCHIVOS_PROYECTOS . '/' . $idProyecto . '/';
			borrar_directorio($carpeta_archivos);

			// Confirmar la transacción
			$this->db->trans_complete();
			// Confirmar la transacción y verificar el estatus de la misma
			if ($this->db->trans_status() === false) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $respuesta;
			} else {
				$respuesta['msg'] = 'Proyecto eliminado con éxito.';
			}
			$respuesta['err'] = false;
		} catch (Exception $e) {
			throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
			die();
		}
		return $respuesta;
	}
}

/* End of file Proyecto_model.php */
/* Location: ./application/models/Proyecto_model.php */
