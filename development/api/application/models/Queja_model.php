<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Modelo: Queja
 *
 * Este modelo realiza las operaciones requeridas sobre la información de las Quejas
 *
 * @package   CodeIgniter
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Queja_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper('File_Upload');
	}

	/*
    Obtener información del identificador especificado o todos los registros.
      $id => ID especifico a obtener, si se requiere
      $soloActivos => Determinar si se requieren todos los registros o sólo los activos
	*/
	public function listar($id = 0, $idCondominio = 0, $soloActivos = false)
	{
		try {
			// DATE_FORMAT(q.fecha_registro, "%Y-%m-%d") fecha,
			$this->db
				->select(
					'q.id_queja,
            q.titulo,
            q.fecha_registro fecha,
            eq.id_estatus_queja,
            eq.estatus_queja,
            ua.id_usuario id_usuario_asignado,
            ua.nombre usuario_asignado,
            q.fk_id_usuario_registro id_usuario_registro,
            ur.nombre usuario_registro,
            q.estatus'
				)
				->join('cat_estatus_quejas eq', 'eq.id_estatus_queja = q.fk_id_estatus_queja')
				->join('usuarios ua', 'ua.id_usuario = q.fk_id_usuario_asignado', 'left')
				->join('usuarios ur', 'ur.id_usuario = q.fk_id_usuario_registro', 'left');

			if ($id > 0) {
				$this->db
					->select(
						'q.descripcion,
            q.solucion'
					)
					->where(['q.id_queja' => $id]);
			} else {
				$this->db->where(['q.fk_id_condominio' => $idCondominio]);
			}
			if ($soloActivos) {
				$this->db->where(['q.estatus' => 1]);
			}

			$respuesta = $this->db->order_by('q.fecha_registro')->get('quejas q');
			if ($id > 0) {
				$respuesta = $respuesta->row_array();
				if (!empty($respuesta)) {
					$respuesta['seguimiento'] = $this->listar_seguimiento($id);
					$respuesta['archivos'] = $this->db
						->select(
							'qa.id_queja_archivo,
                qa.archivo'
						)
						->get_where('quejas_archivos qa', ['qa.fk_id_queja' => $id, 'qa.estatus' => 1])
						->result_array();
				}
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
    Obtener seguimientos de la queja.
      $id => ID especifico a obtener, si se requiere
      $soloActivos => Determinar si se requieren todos los registros o sólo los activos
	*/
	public function listar_seguimiento($idQueja = 0, $idSeguimiento = 0)
	{
		try {
			if (empty($idQueja) && empty($idSeguimiento)) {
				return null;
			}
			$this->db
				->select(
					's.id_queja_seguimiento,
            s.fk_id_queja id_queja,
            s.fecha,
            s.seguimiento,
            s.bloqueado,
            s.fk_id_usuario_registro id_usuario_registro,
            ur.nombre usuario_registro,
            s.estatus'
				)
				->join('usuarios ur', 'ur.id_usuario = s.fk_id_usuario_registro')
				->join('quejas q', 'q.id_queja = s.fk_id_queja AND q.estatus = 1');

			if ($idSeguimiento > 0) {
				$this->db->where(['s.id_queja_seguimiento' => $idSeguimiento]);
			} else {
				$this->db->where(['s.fk_id_queja' => $idQueja]);
			}

			$respuesta = $this->db->order_by('s.fecha DESC')->get_where('quejas_seguimientos s', ['s.estatus' => 1]);
			$respuesta = $idSeguimiento > 0 ? $respuesta->row_array() : $respuesta->result_array();

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

			$archivos = !empty($data['archivos']) ? $data['archivos'] : null;
			unset($data['archivos']);

			// Validar que los campos existan en la tabla
			if (!validar_campos('quejas', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con la base de datos.';
				return $respuesta;
			}

			// Define la carpeta para la carga de los archivos
			$carpeta_temporal_cargar_archivos = PATH_ARCHIVOS_QUEJAS . '/' . uniqid() . '/';
			$se_cargaron_archivos = false;
			/*
			  Intenta la carga de los archivos de las imágenes y agrega el nombre del archivo generado a la data.
				Si no se pudo cargar el archivo, aborta el proceso
      */
			$dataArchivosQueja = [];
			if (!empty($archivos) && $_FILES[$archivos]) {
				$_FILES = normalizeFiles($_FILES[$archivos]);
			} else {
				$_FILES = [];
			}
			for ($i = 0; $i < count($_FILES); $i++) {
				$cargar_archivo = subir_imagen($carpeta_temporal_cargar_archivos, $i, 'queja_img');

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
				$dataArchivosQueja[] = [
					'archivo' => $cargar_archivo['archivo_servidor'],
					'fk_id_usuario_registro' => $data['fk_id_usuario_registro'],
				];
			}

			// Almacenar información en BD
			// Inicializar la transaccion
			$this->db->trans_start();
			$this->db->trans_strict(false);

			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if (!$this->db->insert('quejas', $data)) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				$this->db->trans_rollback();
				return $respuesta;
			}
			$idQueja = $this->db->insert_id();

			$dataQuejaSeguimiento = [
				'fk_id_queja' => $idQueja,
				'fecha' => date('Y-m-d H:i:s'),
				'seguimiento' => 'QUEJA REGISTRADA',
				'bloqueado' => 1,
				'fk_id_usuario_registro' => $data['fk_id_usuario_registro'],
			];
			if (!$this->db->insert('quejas_seguimientos', $dataQuejaSeguimiento)) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				$this->db->trans_rollback();
				return $respuesta;
			}

			// Si se cargaron imágenes, almacena la info en quejas_archivos
			if (!empty($dataArchivosQueja)) {
				$dataArchivosQueja = agregar_columnas_arreglo($dataArchivosQueja, ['fk_id_queja' => $idQueja]);
				// Insertar registros
				// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
				if (!$this->db->insert_batch('quejas_archivos', $dataArchivosQueja)) {
					$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
					$this->db->trans_rollback();
					borrar_directorio($carpeta_temporal_cargar_archivos);
					return $respuesta;
				}
			}

			// Definir la carpeta donde se almacenarán finalmente los archivos
			$carpeta_cargar_archivos = PATH_ARCHIVOS_QUEJAS . '/' . $idQueja . '/';

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
				foreach ($dataArchivosQueja as $archivoCargado) {
					/**
					 * Intenta mover el archivo de la carpeta temporal a la carpeta del proyecto
					 * Si existe error al copiar el archivo:
					 *  - Deshace la transacción
					 *  - Borra la carpeta temporal
					 *  - Borra los archivos que si pudieron ser copiados
					 */
					if (
						!rename(
							$carpeta_temporal_cargar_archivos . $archivoCargado['archivo'],
							$carpeta_cargar_archivos . $archivoCargado['archivo']
						)
					) {
						$respuesta['msg'] = 'Error interno FILE_COPY.';
						$this->db->trans_rollback();
						borrar_directorio($carpeta_temporal_cargar_archivos);
						foreach ($dataArchivosQueja as $archivoCargadoBorrar) {
							borrar_directorio($carpeta_cargar_archivos . $archivoCargadoBorrar['archivo']);
						}
						return $respuesta;
					}
				}
				borrar_directorio($carpeta_temporal_cargar_archivos);
			}

			// Confirmar la transacción
			$this->db->trans_complete();
			// Confirmar la transacción y verificar el estatus de la misma
			if ($this->db->trans_status() === false) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $respuesta;
			}
			$respuesta['msg'] = 'Información registrada con éxito.';
			$respuesta['queja'] = $this->listar($idQueja);
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
			$idQueja = $data['id_queja'];
			$archivos = !empty($data['archivos']) ? $data['archivos'] : null;
			$archivos_borrar = !empty($data['archivos_borrar']) ? $data['archivos_borrar'] : null;
			unset($data['archivos_borrar']);
			unset($data['archivos']);
			unset($data['id_queja']);
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');

			// Validar que los campos existan en la tabla
			if (!validar_campos('quejas', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con la base de datos.';
				return $respuesta;
			}

			// Verificar cuantos registros serán actualizados
			$queja = $this->db->get_where('quejas', ['id_queja' => $idQueja, 'estatus' => 1])->num_rows();

			if ($queja != 1) {
				if ($queja == 0) {
					$respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
				} elseif ($queja > 1) {
					$respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
				}
				return $respuesta;
			}

			// Define la carpeta para la carga de los archivos
			$carpeta_temporal_cargar_archivos = PATH_ARCHIVOS_QUEJAS . '/' . uniqid() . '/';
			$se_cargaron_archivos = false;
			/*
			  Intenta la carga de los archivos de las imágenes y agrega el nombre del archivo generado a la data.
				Si no se pudo cargar el archivo, aborta el proceso
      */
			$dataArchivosQueja = [];
			if (!empty($archivos) && $_FILES[$archivos]) {
				$_FILES = normalizeFiles($_FILES[$archivos]);
			} else {
				$_FILES = [];
			}
			for ($i = 0; $i < count($_FILES); $i++) {
				$cargar_archivo = subir_imagen($carpeta_temporal_cargar_archivos, $i, 'queja_img');

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
				$dataArchivosQueja[] = [
					'fk_id_queja' => $idQueja,
					'archivo' => $cargar_archivo['archivo_servidor'],
					'fk_id_usuario_registro' => $data['fk_id_usuario_modifico'],
				];
			}

			// Almacenar información en BD
			// Inicializar la transaccion
			$this->db->trans_start();
			$this->db->trans_strict(false);

			// Si se especificaron imagenes a borrar, actualiza estatus en quejas_archivos
			if (!empty($archivos_borrar)) {
				// Obtener el nombre del archivo a borrar físicamente
				$archivosBorrados = $this->db
					->select('archivo')
					->where_in('id_queja_archivo', $archivos_borrar)
					->get_where('quejas_archivos', ['fk_id_queja' => $idQueja])
					->result_array();
				// Establecer la información que se actualizará
				$dataQuejaArchivosBorrar = [];
				foreach ($archivos_borrar as $archivoBorrar) {
					$dataQuejaArchivosBorrar[] = [
						'id_queja_archivo' => $archivoBorrar,
						/* 'fk_id_queja' => $idQueja, */
						'estatus' => 0,
						'fk_id_usuario_modifico' => $data['fk_id_usuario_modifico'],
						'fecha_modificacion' => $data['fecha_modificacion'],
					];
				}

				// Indicar que registros deben ser actualizados
				// Si existe error al actualizar el registro, obtiene descripción del mismo y aborta el proceso
				if (!$this->db->update_batch('quejas_archivos', $dataQuejaArchivosBorrar, 'id_queja_archivo')) {
					$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
					$this->db->trans_rollback();
					borrar_directorio($carpeta_temporal_cargar_archivos);
					return $respuesta;
				}
			}

			// Si se cargaron imágenes, almacena la info en quejas_archivos
			if (!empty($dataArchivosQueja)) {
				// Insertar registro
				// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
				if (!$this->db->insert_batch('quejas_archivos', $dataArchivosQueja)) {
					$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
					$this->db->trans_rollback();
					borrar_directorio($carpeta_temporal_cargar_archivos);
					return $respuesta;
				}
			}

			// Definir la carpeta donde se almacenarán finalmente los archivos
			$carpeta_cargar_archivos = PATH_ARCHIVOS_QUEJAS . '/' . $idQueja . '/';

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
				foreach ($dataArchivosQueja as $archivoCargado) {
					/**
					 * Intenta mover el archivo de la carpeta temporal a la carpeta del proyecto
					 * Si existe error al copiar el archivo:
					 *  - Deshace la transacción
					 *  - Borra la carpeta temporal
					 *  - Borra los archivos que si pudieron ser copiados
					 */
					if (
						!rename(
							$carpeta_temporal_cargar_archivos . $archivoCargado['archivo'],
							$carpeta_cargar_archivos . $archivoCargado['archivo']
						)
					) {
						$respuesta['msg'] = 'Error interno FILE_COPY.';
						$this->db->trans_rollback();
						borrar_directorio($carpeta_temporal_cargar_archivos);
						foreach ($dataArchivosQueja as $archivoCargadoBorrar) {
							borrar_directorio($carpeta_cargar_archivos . $archivoCargadoBorrar['archivo']);
						}
						return $respuesta;
					}
				}
				borrar_directorio($carpeta_temporal_cargar_archivos);
			}

			// Si se especificaron imagenes a borrar
			if (!empty($archivosBorrados)) {
				foreach ($archivosBorrados as $archivoBorrar) {
					borrar_directorio($carpeta_cargar_archivos . $archivoBorrar['archivo']);
				}
			}

			// Si existe error al actualizar el registro, obtiene descripción del mismo y aborta el proceso
			if (
				!$this->db->update('quejas', $data, [
					'id_queja' => $idQueja,
				])
			) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				$this->db->trans_rollback();
				borrar_directorio($carpeta_temporal_cargar_archivos);
				return $respuesta;
			}

			// Confirmar la transacción
			$this->db->trans_complete();
			// Confirmar la transacción y verificar el estatus de la misma
			if ($this->db->trans_status() === false) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $respuesta;
			}
			$respuesta['msg'] = 'Información actualizada con éxito.';
			$respuesta['queja'] = $this->listar($idQueja);
			$respuesta['err'] = false;

			/* $respuesta['err'] = !$this->db->update('quejas', $data, [
				'id_queja' => $idQueja,
			]);
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if ($respuesta['err']) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $respuesta;
			}

			$respuesta['msg'] = 'Información actualizada con éxito.';
			$respuesta['queja'] = $this->listar($idQueja); */
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
			$idQueja = $data['id_queja'];
			// Establecer la información que se actualizará
			$data = [
				'estatus' => 0,
				'fk_id_usuario_modifico' => $data['id_usuario_modifico'],
				'fecha_modificacion' => date('Y-m-d H:i:s'),
			];

			// Validar que los campos existan en la tabla usuarios
			if (!validar_campos('quejas', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con respecto a la base de datos.';
				return $respuesta;
			}

			// Verificar cuantos registros serán actualizados
			$queja = $this->db->get_where('quejas', [
				'id_queja' => $idQueja,
				'estatus' => 1,
			]);

			if ($queja->num_rows() != 1) {
				if ($queja->num_rows() == 0) {
					$respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
				} elseif ($queja->num_rows() > 1) {
					$respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
				}
				return $respuesta;
			}

			// Almacenar información en BD
			// Inicializar la transaccion
			$this->db->trans_start();
			$this->db->trans_strict(false);

			// Actualiza estatus de los archivos de la queja
			// Si existe error al actualizar el registro, obtiene descripción del mismo y aborta el proceso
			if (
				!$this->db->update(
					'quejas_archivos',
					['estatus' => 0],
					[
						'fk_id_queja' => $idQueja,
					]
				)
			) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				$this->db->trans_rollback();
				return $respuesta;
			}

			// Actualiza estatus de la queja
			// Si existe error al actualizar el registro, obtiene descripción del mismo y aborta el proceso
			if (
				!$this->db->update('quejas', $data, [
					'id_queja' => $idQueja,
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

			// Borrar la carpeta con los archivos de la queja
			borrar_directorio(PATH_ARCHIVOS_QUEJAS . '/' . $idQueja . '/');

			$respuesta['msg'] = 'Queja eliminada con éxito.';
			$respuesta['err'] = false;
		} catch (Exception $e) {
			throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
			die();
		}
		return $respuesta;
	}

	/*
    Asignar colaborador
      $data => Información a actualizar
	*/
	public function asignar_colaborador($data)
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
			$idQueja = $data['id_queja'];
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			unset($data['id_queja']);

			// Validar que los campos existan en la tabla
			if (!validar_campos('quejas', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con la base de datos.';
				return $respuesta;
			}

			// Verificar cuantos registros serán actualizados
			$queja = $this->db->get_where('quejas', ['id_queja' => $idQueja]);
			if ($queja->num_rows() != 1) {
				if ($queja->num_rows() == 0) {
					$respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
				} elseif ($queja->num_rows() > 1) {
					$respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
				}
				return $respuesta;
			}
			$queja = $queja->row_array();
			if ($queja['fk_id_estatus_queja'] == 1) {
				$data['fk_id_estatus_queja'] = 2;
			}

			// Obtener información del colaborador
			$colaborador = $this->db
				->select('nombre')
				->get_where('usuarios', ['id_usuario' => $data['fk_id_usuario_asignado']]);

			if ($colaborador->num_rows() != 1) {
				if ($colaborador->num_rows() == 0) {
					$respuesta['msg'] = 'No se encontró información del colaborador.';
				} elseif ($colaborador->num_rows() > 1) {
					$respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
				}
				return $respuesta;
			}
			$colaborador = $colaborador->row_array();

			$dataQuejaSeguimiento = [
				'fk_id_queja' => $idQueja,
				'fecha' => date('Y-m-d H:i:s'),
				'seguimiento' => 'COLABORADOR ASIGNADO. ' . $colaborador['nombre'],
				'bloqueado' => 1,
				'fk_id_usuario_registro' => $data['fk_id_usuario_modifico'],
			];

			// Almacenar información en BD
			// Inicializar la transaccion
			$this->db->trans_start();
			$this->db->trans_strict(false);

			if (!$this->db->insert('quejas_seguimientos', $dataQuejaSeguimiento)) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				$this->db->trans_rollback();
				return $respuesta;
			}

			if (
				!$this->db->update('quejas', $data, [
					'id_queja' => $idQueja,
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

			$respuesta['msg'] = 'Colaborador asignado con éxito.';
			$respuesta['queja'] = $this->listar($idQueja);
			$respuesta['err'] = false;
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
			die();
		}
		return $respuesta;
	}

	/*
    Actualizar estatus de la queja
      $data => Información a actualizar
	*/
	public function actualizar_estatus($data)
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
			$idQueja = $data['id_queja'];
			$idEstatusQueja = $data['fk_id_estatus_queja'];
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			unset($data['id_queja']);

			// Validar que los campos existan en la tabla
			if (!validar_campos('quejas', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con la base de datos.';
				return $respuesta;
			}

			// Verificar cuantos registros serán actualizados
			$queja = $this->db->get_where('quejas', ['id_queja' => $idQueja]);

			if ($queja->num_rows() != 1) {
				if ($queja->num_rows() == 0) {
					$respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
				} elseif ($queja->num_rows() > 1) {
					$respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
				}
				return $respuesta;
			}

			// Obtener el estatus de la queja
			$estatusQueja = $this->db->get_where('cat_estatus_quejas', ['id_estatus_queja' => $idEstatusQueja]);
			if ($estatusQueja->num_rows() != 1) {
				if ($estatusQueja->num_rows() == 0) {
					$respuesta['msg'] = 'No se encontró el estatus de la queja.';
				} elseif ($estatusQueja->num_rows() > 1) {
					$respuesta['msg'] =
						'Se detectó más de una coincidencia para el estatus de queja. Contactar al Administrador.';
				}
				return $respuesta;
			}
			$estatusQueja = $estatusQueja->row_array();

			if ($estatusQueja['debe_especificar_solucion'] == 1 && empty($data['solucion'])) {
				$respuesta['msg'] = 'Se debe especificar solución para la queja.';
				return $respuesta;
			} elseif ($estatusQueja['debe_especificar_solucion'] != 1) {
				$data['solucion'] = null;
			}

			$dataQuejaSeguimiento = [
				'fk_id_queja' => $idQueja,
				'fecha' => date('Y-m-d H:i:s'),
				'seguimiento' => !empty($data['solucion'])
					? 'QUEJA ATENDIDA.\r\nSOLUCIÓN: ' . $data['solucion']
					: 'ESTATUS ACTUALIZADO. ' . $estatusQueja['estatus_queja'],
				'bloqueado' => 1,
				'fk_id_usuario_registro' => $data['fk_id_usuario_modifico'],
			];

			// Almacenar información en BD
			// Inicializar la transaccion
			$this->db->trans_start();
			$this->db->trans_strict(false);

			if (!$this->db->insert('quejas_seguimientos', $dataQuejaSeguimiento)) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				$this->db->trans_rollback();
				return $respuesta;
			}

			if (
				!$this->db->update('quejas', $data, [
					'id_queja' => $idQueja,
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

			$respuesta['msg'] = 'Estatus actualizado con éxito.';
			$respuesta['queja'] = $this->listar($idQueja);
			$respuesta['err'] = false;
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
			die();
		}
		return $respuesta;
	}

	/*
    Insertar seguimiento
      $data => Información a insertar
	*/
	public function insertar_seguimiento($data)
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
			$idQueja = $data['fk_id_queja'];

			// Validar que los campos existan en la tabla
			if (!validar_campos('quejas_seguimientos', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con la base de datos.';
				return $respuesta;
			}

			// Verificar cuantos registros serán actualizados
			$queja = $this->db->get_where('quejas', ['id_queja' => $idQueja, 'estatus' => 1])->num_rows();

			if ($queja != 1) {
				if ($queja == 0) {
					$respuesta['msg'] = 'No se encontró la queja especificada.';
				} elseif ($queja > 1) {
					$respuesta['msg'] = 'Se detectó más de una queja. Contactar al Administrador.';
				}
				return $respuesta;
			}

			// Almacenar información en BD
			$respuesta['err'] = !$this->db->insert('quejas_seguimientos', $data, [
				'id_queja' => $idQueja,
			]);
			// Si existe error al actualizar el registro, obtiene descripción del mismo y aborta el proceso
			if ($respuesta['err']) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $respuesta;
			}
			$idSeguimiento = $this->db->insert_id();

			$respuesta['msg'] = 'Seguimiento registrado con éxito.';
			$respuesta['seguimiento'] = $this->listar_seguimiento($idQueja, $idSeguimiento);
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
			die();
		}
		return $respuesta;
	}

	/*
    Actualizar seguimiento
      $data => Información a insertar
	*/
	public function actualizar_seguimiento($data)
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
			$idSeguimiento = $data['id_queja_seguimiento'];
			unset($data['id_queja_seguimiento']);
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');

			// Validar que los campos existan en la tabla
			if (!validar_campos('quejas_seguimientos', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con la base de datos.';
				return $respuesta;
			}

			// Verificar cuantos registros serán actualizados
			$seguimiento = $this->db
				->join('quejas q', 'q.id_queja = s.fk_id_queja AND q.estatus = 1')
				->get_where('quejas_seguimientos s', ['s.id_queja_seguimiento' => $idSeguimiento, 's.estatus' => 1])
				->num_rows();

			if ($seguimiento != 1) {
				if ($seguimiento == 0) {
					$respuesta['msg'] = 'No se encontró el seguimiento especificado.';
				} elseif ($seguimiento > 1) {
					$respuesta['msg'] = 'Se detectó más de un seguimiento. Contactar al Administrador.';
				}
				return $respuesta;
			}

			// Almacenar información en BD
			$respuesta['err'] = !$this->db->update('quejas_seguimientos', $data, [
				'id_queja_seguimiento' => $idSeguimiento,
			]);
			// Si existe error al actualizar el registro, obtiene descripción del mismo y aborta el proceso
			if ($respuesta['err']) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $respuesta;
			}
			// $idSeguimiento = $this->db->insert_id();

			$respuesta['msg'] = 'Seguimiento actualizado con éxito.';
			$respuesta['seguimiento'] = $this->listar_seguimiento(0, $idSeguimiento);
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
			die();
		}
		return $respuesta;
	}

	/*
    Eliminar seguimiento
      $data => Información a insertar
	*/
	public function eliminar_seguimiento($data)
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
			$idSeguimiento = $data['id_queja_seguimiento'];

			// Establecer la información que se actualizará
			$data = [
				'estatus' => 0,
				'fk_id_usuario_modifico' => $data['id_usuario_modifico'],
				'fecha_modificacion' => date('Y-m-d H:i:s'),
			];

			// Validar que los campos existan en la tabla
			if (!validar_campos('quejas_seguimientos', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con la base de datos.';
				return $respuesta;
			}

			// Verificar cuantos registros serán actualizados
			$seguimiento = $this->db
				->join('quejas q', 'q.id_queja = s.fk_id_queja AND q.estatus = 1')
				->get_where('quejas_seguimientos s', ['s.id_queja_seguimiento' => $idSeguimiento, 's.estatus' => 1])
				->num_rows();

			if ($seguimiento != 1) {
				if ($seguimiento == 0) {
					$respuesta['msg'] = 'No se encontró el seguimiento especificado.';
				} elseif ($seguimiento > 1) {
					$respuesta['msg'] = 'Se detectó más de un seguimiento. Contactar al Administrador.';
				}
				return $respuesta;
			}

			// Almacenar información en BD
			$respuesta['err'] = !$this->db->update('quejas_seguimientos', $data, [
				'id_queja_seguimiento' => $idSeguimiento,
			]);
			// Si existe error al actualizar el registro, obtiene descripción del mismo y aborta el proceso
			if ($respuesta['err']) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $respuesta;
			}
			// $idSeguimiento = $this->db->insert_id();

			$respuesta['msg'] = 'Seguimiento eliminado con éxito.';
			// $respuesta['seguimiento'] = $this->listar_seguimiento($idQueja, $idSeguimiento);
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
			die();
		}
		return $respuesta;
	}
}

/* End of file Queja_model.php */
/* Location: ./application/models/Queja_model.php */
