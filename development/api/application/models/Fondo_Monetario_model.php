<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Modelo: Fondo_Monetario_model
 *
 * Este modelo realiza las operaciones requeridas sobre los Fondos monetarios
 *
 * @package   CodeIgniter
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Fondo_Monetario_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper('File_Upload');
	}

	public function listar_movimientos($idFondoMonetario = 0, $idMovimiento = 0)
	{
		if (empty($idFondoMonetario) && empty($idMovimiento)) {
			return null;
		}

		$this->db
			->select(
				'm.id_fondo_monetario_movimiento,
          m.fk_id_fondo_monetario id_fondo_monetario,
          tm.id_tipo_movimiento,
          tm.tipo_movimiento,
          m.fecha,
          m.concepto,
          m.importe,
          m.saldo_anterior,
          m.saldo_nuevo,
          m.comprobante_archivo,
          m.es_externo,
          m.es_traspaso,
          m.estatus,
          IF(m.estatus = 1, 0, 1) cancelado,
          m.fecha_registro'
			)
			->join('cat_tipos_movimientos_fondos tm', 'tm.id_tipo_movimiento = m.fk_id_tipo_movimiento')
			->order_by('m.fecha, m.fecha_registro, m.id_fondo_monetario_movimiento');
		if (!empty($idMovimiento)) {
			$this->db
				->select('m.fk_id_fondo_monetario id_fondo_monetario')
				->where(['m.id_fondo_monetario_movimiento' => $idMovimiento]);
		} else {
			$this->db->where(['m.fk_id_fondo_monetario' => $idFondoMonetario]);
		}
		$result = $this->db->get('fondos_monetarios_movimientos m');

		if (!empty($idMovimiento)) {
			return $result->row_array();
		} else {
			return $result->result_array();
		}
	}

	/*
    Obtener información del identificador especificado.
	*/
	public function listar($idFondoMonetario = 0, $idCondominio = 0, $soloActivos = false)
	{
		try {
			if (empty($idFondoMonetario) && empty($idCondominio)) {
				return false;
			}

			$this->db
				->select(
					'fm.id_fondo_monetario,
          tfm.tipo_fondo,
          fm.fondo_monetario,
          fm.banco,
          fm.numero_cuenta,
          fm.clabe,
          fm.saldo,
          fm.estatus'
				)
				->join('cat_tipos_fondos_monetarios tfm', 'tfm.id_tipo_fondo_monetario = fk_id_tipo_fondo_monetario');
			if (!empty($idFondoMonetario)) {
				$resultado = $this->db
					->select(
						'tfm.id_tipo_fondo_monetario,
          tfm.requiere_datos_bancarios'
					)
					->get_where('fondos_monetarios fm', [
						'fm.id_fondo_monetario' => $idFondoMonetario,
					])
					->row_array();
				// Obtener los pagos de la cuota de mantenimiento
				/* if (!empty($resultado)) {
					$resultado['movimientos'] = $this->listar_movimientos($idFondoMonetario);
				} */
			} else {
				$this->db->where(['fm.fk_id_condominio' => $idCondominio]);
				if ($soloActivos) {
					$this->db->where(['fm.estatus' => 1]);
				}
				$resultado = $this->db
					->order_by('tfm.tipo_fondo, fm.fondo_monetario')
					->get('fondos_monetarios fm')
					->result_array();
			}
			$result = $resultado;

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

			// Validar que los campos existan en la tabla fondos_monetarios
			if (!validar_campos('fondos_monetarios', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con la base de datos.';
				return $respuesta;
			}

			// Almacenar información en BD

			// Insertar registro en fondos_monetarios
			$respuesta['err'] = !$this->db->insert('fondos_monetarios', $data);
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if ($respuesta['err']) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $respuesta;
			}

			// Obtener nuevo ID del registro
			$respuesta['fondo_monetario'] = $this->listar($this->db->insert_id());
			$respuesta['msg'] = 'Información almacenada con éxito.';
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
			$idFondoMonetario = $data['id_fondo_monetario'];
			unset($data['id_fondo_monetario']);
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');

			// Validar que los campos existan en la tabla
			if (!validar_campos('fondos_monetarios', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con la base de datos (Cuota mantenimiento).';
				return $respuesta;
			}

			// Verificar cuantos registros serán actualizados
			$registrosEncontrados = $this->db->get_where('fondos_monetarios', ['id_fondo_monetario' => $idFondoMonetario]);
			if ($registrosEncontrados->num_rows() != 1) {
				if ($registrosEncontrados->num_rows() == 0) {
					$respuesta['msg'] = 'No se encontró coincidencia para actualizar.';
				} elseif ($registrosEncontrados->num_rows() > 1) {
					$respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
				}
				return $respuesta;
			}

			// Almacenar información en BD

			// Actualizar registro en fondos_monetarios
			$respuesta['err'] = !$this->db->update('fondos_monetarios', $data, [
				'id_fondo_monetario' => $idFondoMonetario,
			]);
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if ($respuesta['err']) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $respuesta;
			}

			$respuesta['fondo_monetario'] = $this->listar($idFondoMonetario);
			$respuesta['msg'] = 'Información actualizada con éxito.';
			$respuesta['err'] = false;
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
			die();
		}
		return $respuesta;
	}

	/*
    Eliminar logicamente una cuota de mantenimiento
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
			$idFondoMonetario = $data['id_fondo_monetario'];

			// Verificar cuantos registros serán actualizados
			$fondoMonetario = $this->db->get_where('fondos_monetarios', [
				'id_fondo_monetario' => $idFondoMonetario,
				'estatus' => 1,
			]);

			if ($fondoMonetario->num_rows() != 1) {
				if ($fondoMonetario->num_rows() == 0) {
					$respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
				} elseif ($fondoMonetario->num_rows() > 1) {
					$respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
				}
				return $respuesta;
			}

			$data = [
				'estatus' => 0,
				'fk_id_usuario_modifico' => $data['id_usuario_modifico'],
				'fecha_modificacion' => date('Y-m-d H:i:s'),
			];

			// Validar que los campos existan en la tabla fondos_monetarios
			if (!validar_campos('fondos_monetarios', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con respecto a la base de datos.';
				return $respuesta;
			}

			// Almacenar información en BD
			$respuesta['err'] = !$this->db->update('fondos_monetarios', $data, ['id_fondo_monetario' => $idFondoMonetario]);
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if ($respuesta['err']) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $respuesta;
			}

			$respuesta['msg'] = 'fondo monetario eliminado con éxito.';
		} catch (Exception $e) {
			throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
			die();
		}
		return $respuesta;
	}

	/*
    Establecer como pagada la recaudación
      $data => Información a procesar
	*/
	public function traspaso($data)
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
			$idFondoMonetarioOrigen = $data['id_fondo_monetario_origen'];
			$idFondoMonetarioDestino = $data['id_fondo_monetario_destino'];
			$idTipoMovimiento = $data['id_tipo_movimiento'];
			// Obtener el archivo del comprobante
			$archivo_comprobante = !empty($data['archivo_comprobante']) ? $data['archivo_comprobante'] : null;
			unset($data['archivo_comprobante']);

			// Verificar cuantos registros del fondo monetario origen serán actualizados
			$fondoMonetarioOrigen = $this->db->get_where('fondos_monetarios', [
				'id_fondo_monetario' => $idFondoMonetarioOrigen,
				'estatus' => 1,
			]);

			if ($fondoMonetarioOrigen->num_rows() != 1) {
				if ($fondoMonetarioOrigen->num_rows() == 0) {
					$respuesta['msg'] = 'No se encontró información del fondo monetario origen.';
				} elseif ($fondoMonetarioOrigen->num_rows() > 1) {
					$respuesta['msg'] = 'Se detectó más de una Cuota de mantenimiento. Contactar al Administrador.';
				}
				return $respuesta;
			}

			// Verificar cuantos registros del fondo monetario destino serán actualizados
			$fondoMonetarioDestino = $this->db->get_where('fondos_monetarios', [
				'id_fondo_monetario' => $idFondoMonetarioDestino,
				'estatus' => 1,
			]);

			if ($fondoMonetarioDestino->num_rows() != 1) {
				if ($fondoMonetarioDestino->num_rows() == 0) {
					$respuesta['msg'] = 'No se encontró información del fondo monetario destino.';
				} elseif ($fondoMonetarioDestino->num_rows() > 1) {
					$respuesta['msg'] = 'Se detectó más de una Cuota de mantenimiento. Contactar al Administrador.';
				}
				return $respuesta;
			}

			$fondoMonetarioOrigen = $fondoMonetarioOrigen->row_array();
			$fondoMonetarioDestino = $fondoMonetarioDestino->row_array();

			// Data del fondo y movimiento origen
			$dataFondoOrigen = [
				'saldo' => floatval($fondoMonetarioOrigen['saldo']) - floatval($data['importe']),
			];
			if ($dataFondoOrigen['saldo'] < 0) {
				$respuesta['msg'] = 'El nuevo saldo del fondo monetario origen no puede ser menor a cero.';
				return $respuesta;
			}

			$dataMovimientoOrigen = [
				'fk_id_fondo_monetario' => $idFondoMonetarioOrigen,
				'fk_id_tipo_movimiento' => 3,
				'fecha' => $data['fecha'],
				'concepto' => 'TRASPASO ENVIADO A ' . $fondoMonetarioDestino['fondo_monetario'],
				'importe' => floatval('-' . $data['importe']),
				'saldo_anterior' => floatval($fondoMonetarioOrigen['saldo']),
				'saldo_nuevo' => floatval($fondoMonetarioOrigen['saldo']) - floatval($data['importe']),
				'comprobante_archivo' => null,
				'es_traspaso' => 1,
				'fk_id_usuario_registro' => $data['id_usuario_registro'],
			];

			// Data del fondo y movimiento destino
			$dataFondoDestino = [
				'saldo' => floatval($fondoMonetarioDestino['saldo']) + floatval($data['importe']),
			];

			$dataMovimientoDestino = [
				'fk_id_fondo_monetario' => $idFondoMonetarioDestino,
				'fk_id_tipo_movimiento' => 3,
				'fecha' => $data['fecha'],
				'concepto' => 'TRASPASO RECIBIDO DE ' . $fondoMonetarioOrigen['fondo_monetario'],
				'importe' => floatval($data['importe']),
				'saldo_anterior' => floatval($fondoMonetarioDestino['saldo']),
				'saldo_nuevo' => floatval($fondoMonetarioDestino['saldo']) + floatval($data['importe']),
				'comprobante_archivo' => null,
				'es_traspaso' => 1,
				'fk_id_usuario_registro' => $data['id_usuario_registro'],
			];

			// Validar que los campos existan en la tabla fondos_monetarios
			if (!validar_campos('fondos_monetarios_movimientos', $dataMovimientoOrigen)) {
				$respuesta['msg'] =
					'Error de integridad de la información con respecto a la base de datos (Movimiento Origen).';
				return $respuesta;
			}
			if (!validar_campos('fondos_monetarios_movimientos', $dataMovimientoDestino)) {
				$respuesta['msg'] =
					'Error de integridad de la información con respecto a la base de datos (Movimiento Destino).';
				return $respuesta;
			}

			// Validar que los campos existan en la tabla fondos_monetarios
			if (!validar_campos('fondos_monetarios', $dataFondoOrigen)) {
				$respuesta['msg'] = 'Error de integridad de la información con respecto a la base de datos (Fondo Origen).';
				return $respuesta;
			}
			if (!validar_campos('fondos_monetarios', $dataFondoDestino)) {
				$respuesta['msg'] = 'Error de integridad de la información con respecto a la base de datos (Fondo Destino).';
				return $respuesta;
			}

			// Define la carpeta para la carga de los archivos
			$carpeta_cargar_archivos =
				PATH_ARCHIVOS_FONDOS_MONETARIOS . '/' . $fondoMonetarioOrigen['fk_id_condominio'] . '/';
			/*
			  Si se especificó documento del contrato intenta la carga del mismo y agrega el nombre del archivo cargado a la data.
				Si no se pudo cargar el archivo, borra carpeta temporal y aborta el proceso
      */
			if (!empty($archivo_comprobante)) {
				$cargar_archivo = subir_archivo(
					$carpeta_cargar_archivos,
					$archivo_comprobante,
					serialize(['png', 'jpg', 'jpeg', 'pdf']),
					'fondo_monetario_movimiento'
				);

				if ($cargar_archivo['error']) {
					$respuesta['msg'] = 'Error al cargar el comprobante.' . PHP_EOL . $cargar_archivo['msg'];
					return $respuesta;
				}
				$dataMovimientoOrigen['comprobante_archivo'] = $cargar_archivo['archivo_servidor'];
				$dataMovimientoDestino['comprobante_archivo'] = $dataMovimientoOrigen['comprobante_archivo'];
			}

			// Almacenar información en BD
			// Inicializar la transaccion
			$this->db->trans_start();
			$this->db->trans_strict(false);

			// Insertar registro origen en fondos_monetarios_movimientos
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if (!$this->db->insert('fondos_monetarios_movimientos', $dataMovimientoOrigen)) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				$this->db->trans_rollback();
				if (!empty($dataMovimientoOrigen['comprobante_archivo'])) {
					unlink($carpeta_cargar_archivos . $dataMovimientoOrigen['comprobante_archivo']);
				}
				return $respuesta;
			}
			$idMovimientoOrigen = $this->db->insert_id();

			// Insertar registro destino en fondos_monetarios_movimientos
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if (!$this->db->insert('fondos_monetarios_movimientos', $dataMovimientoDestino)) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				$this->db->trans_rollback();
				if (!empty($dataMovimientoOrigen['comprobante_archivo'])) {
					unlink($carpeta_cargar_archivos . $dataMovimientoOrigen['comprobante_archivo']);
				}
				return $respuesta;
			}
			$idMovimientoDestino = $this->db->insert_id();

			// Actualizar datos origen en fondos_monetarios
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if (
				!$this->db->update('fondos_monetarios', $dataFondoOrigen, [
					'id_fondo_monetario' => $idFondoMonetarioOrigen,
				])
			) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				$this->db->trans_rollback();
				if (!empty($dataMovimientoOrigen['comprobante_archivo'])) {
					unlink($carpeta_cargar_archivos . $dataMovimientoOrigen['comprobante_archivo']);
				}
				return $respuesta;
			}

			// Actualizar datos destino en fondos_monetarios
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if (
				!$this->db->update('fondos_monetarios', $dataFondoDestino, [
					'id_fondo_monetario' => $idFondoMonetarioDestino,
				])
			) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				$this->db->trans_rollback();
				if (!empty($dataMovimientoOrigen['comprobante_archivo'])) {
					unlink($carpeta_cargar_archivos . $dataMovimientoOrigen['comprobante_archivo']);
				}
				return $respuesta;
			}

			// Confirmar la transacción
			$this->db->trans_complete();
			// Confirmar la transacción y verificar el estatus de la misma
			if ($this->db->trans_status() === false) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				if (!empty($dataMovimientoOrigen['comprobante_archivo'])) {
					unlink($carpeta_cargar_archivos . $dataMovimientoOrigen['comprobante_archivo']);
				}
				return $respuesta;
			}

			$respuesta['movimientos'] = [
				'origen' => $this->listar_movimientos(0, $idMovimientoOrigen),
				'destino' => $this->listar_movimientos(0, $idMovimientoDestino),
			];
			$respuesta['msg'] = 'Traspaso registrado con éxito.';
			$respuesta['err'] = false;
		} catch (Exception $e) {
			throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
			die();
		}
		return $respuesta;
	}

	/*
    Establecer como pagada la recaudación
      $data => Información a procesar
	*/
	public function registrar_movimiento($data)
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
			$idFondoMonetario = $data['fk_id_fondo_monetario'];
			$idTipoMovimiento = $data['fk_id_tipo_movimiento'];
			// Obtener el archivo del comprobante
			$archivo_comprobante = !empty($data['archivo_comprobante']) ? $data['archivo_comprobante'] : null;
			unset($data['archivo_comprobante']);

			// Verificar cuantos registros serán actualizados
			$fondoMonetario = $this->db->get_where('fondos_monetarios', [
				'id_fondo_monetario' => $idFondoMonetario,
				'estatus' => 1,
			]);

			if ($fondoMonetario->num_rows() != 1) {
				if ($fondoMonetario->num_rows() == 0) {
					$respuesta['msg'] = 'No se encontró fondo monetario.';
				} elseif ($fondoMonetario->num_rows() > 1) {
					$respuesta['msg'] = 'Se detectó más de un fondo monetario. Contactar al Administrador.';
				}
				return $respuesta;
			}
			$fondoMonetario = $fondoMonetario->row_array();

			$tipoMovimiento = $this->db
				->select('signo')
				->get_where('cat_tipos_movimientos_fondos', ['id_tipo_movimiento' => $idTipoMovimiento, 'estatus' => 1])
				->row_array();
			if (empty($tipoMovimiento)) {
				$respuesta['msg'] = 'No se encontró el Tipo de movimiento.';
				return $respuesta;
			}

			$data['importe'] = floatval(
				(!empty($tipoMovimiento['signo']) ? $tipoMovimiento['signo'] : '') . $data['importe']
			);
			$data['saldo_anterior'] = floatval($fondoMonetario['saldo']);
			$dataFondosMonetarios = [
				'saldo' => floatval($fondoMonetario['saldo']) + $data['importe'],
			];
			if ($dataFondosMonetarios['saldo'] < 0) {
				$respuesta['msg'] = 'El saldo del fondo monetario no puede ser menor a cero.';
				return $respuesta;
			}
			$data['saldo_nuevo'] = $dataFondosMonetarios['saldo'];

			// Validar que los campos existan en la tabla fondos_monetarios
			if (!validar_campos('fondos_monetarios_movimientos', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con respecto a la base de datos (Movimientos).';
				return $respuesta;
			}

			// Validar que los campos existan en la tabla fondos_monetarios
			if (!validar_campos('fondos_monetarios', $dataFondosMonetarios)) {
				$respuesta['msg'] = 'Error de integridad de la información con respecto a la base de datos (Fondo).';
				return $respuesta;
			}

			// Define la carpeta para la carga de los archivos
			$carpeta_cargar_archivos = PATH_ARCHIVOS_FONDOS_MONETARIOS . '/' . $fondoMonetario['fk_id_condominio'] . '/';
			/*
			  Si se especificó documento del contrato intenta la carga del mismo y agrega el nombre del archivo cargado a la data.
				Si no se pudo cargar el archivo, borra carpeta temporal y aborta el proceso
      */
			if (!empty($archivo_comprobante)) {
				$cargar_archivo = subir_archivo(
					$carpeta_cargar_archivos,
					$archivo_comprobante,
					serialize(['png', 'jpg', 'jpeg', 'pdf']),
					'fondo_monetario_movimiento'
				);

				if ($cargar_archivo['error']) {
					$respuesta['msg'] = 'Error al cargar el comprobante.' . PHP_EOL . $cargar_archivo['msg'];
					return $respuesta;
				}
				$data['comprobante_archivo'] = $cargar_archivo['archivo_servidor'];
			}

			// Almacenar información en BD
			// Inicializar la transaccion
			$this->db->trans_start();
			$this->db->trans_strict(false);

			// Insertar registro en fondos_monetarios_movimientos
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if (!$this->db->insert('fondos_monetarios_movimientos', $data)) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				$this->db->trans_rollback();
				if (!empty($data['comprobante_archivo'])) {
					unlink($carpeta_cargar_archivos . $data['comprobante_archivo']);
				}
				return $respuesta;
			}
			$idMovimiento = $this->db->insert_id();

			// Actualizar datos en fondos_monetarios
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if (
				!$this->db->update('fondos_monetarios', $dataFondosMonetarios, [
					'id_fondo_monetario' => $idFondoMonetario,
				])
			) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				$this->db->trans_rollback();
				if (!empty($data['comprobante_archivo'])) {
					unlink($carpeta_cargar_archivos . $data['comprobante_archivo']);
				}
				return $respuesta;
			}

			// Confirmar la transacción
			$this->db->trans_complete();
			// Confirmar la transacción y verificar el estatus de la misma
			if ($this->db->trans_status() === false) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				if (!empty($data['comprobante_archivo'])) {
					unlink($carpeta_cargar_archivos . $data['comprobante_archivo']);
				}
				return $respuesta;
			}

			$respuesta['movimiento'] = $this->listar_movimientos(0, $idMovimiento);
			$respuesta['msg'] = 'Movimiento registrado con éxito.';
			$respuesta['err'] = false;
		} catch (Exception $e) {
			throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
			die();
		}
		return $respuesta;
	}

	/*
    Eliminar logicamente el pago de una cuota de mantenimiento
      $data => Información a procesar
	*/
	public function eliminar_movimiento($data)
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
			$idFondoMonetarioMovimiento = $data['id_fondo_monetario_movimiento'];

			// Obtener registro de fondos_monetarios_movimientos
			$fondoMonetarioMovimiento = $this->db->get_where('fondos_monetarios_movimientos', [
				'id_fondo_monetario_movimiento' => $idFondoMonetarioMovimiento,
				'estatus' => 1,
			]);

			if ($fondoMonetarioMovimiento->num_rows() != 1) {
				if ($fondoMonetarioMovimiento->num_rows() == 0) {
					$respuesta['msg'] = 'No se pudo obtener información del movimiento.';
				} elseif ($fondoMonetarioMovimiento->num_rows() > 1) {
					$respuesta['msg'] = 'Se detectó más de un movimiento. Contactar al Administrador.';
				}
				return $respuesta;
			}
			$fondoMonetarioMovimiento = $fondoMonetarioMovimiento->row_array();
			$idFondoMonetario = $fondoMonetarioMovimiento['fk_id_fondo_monetario'];

			// Obtener registro de fondos_monetarios
			$fondoMonetario = $this->db->get_where('fondos_monetarios', [
				'id_fondo_monetario' => $idFondoMonetario,
				'estatus' => 1,
			]);

			if ($fondoMonetario->num_rows() != 1) {
				if ($fondoMonetario->num_rows() == 0) {
					$respuesta['msg'] = 'No se encontró fondo monetario.';
				} elseif ($fondoMonetarioMovimiento->num_rows() > 1) {
					$respuesta['msg'] = 'Se detectó más de un fondo monetario. Contactar al Administrador.';
				}
				return $respuesta;
			}
			$fondoMonetario = $fondoMonetario->row_array();

			$data = [
				'estatus' => 0,
				'fk_id_usuario_modifico' => $data['id_usuario_modifico'],
				'fecha_modificacion' => date('Y-m-d H:i:s'),
			];

			$dataFondoMovimiento = [
				'saldo' => floatval($fondoMonetario['saldo']) + floatval($fondoMonetarioMovimiento['importe']) * -1,
			];
			if ($dataFondoMovimiento['saldo'] < 0) {
				$respuesta['msg'] = 'El saldo del fondo monetario no puede ser menor a cero.';
				return $respuesta;
			}

			// Validar que los campos existan en la tabla fondos_monetarios_movimientos
			if (!validar_campos('fondos_monetarios_movimientos', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con respecto a la base de datos (Pagos).';
				return $respuesta;
			}
			// Validar que los campos existan en la tabla fondos_monetarios
			if (!validar_campos('fondos_monetarios', $dataFondoMovimiento)) {
				$respuesta['msg'] =
					'Error de integridad de la información con respecto a la base de datos (Cuotas mantenimiento).';
				return $respuesta;
			}

			// Almacenar información en BD
			// Inicializar la transaccion
			$this->db->trans_start();
			$this->db->trans_strict(false);

			// Actualizar datos en fondos_monetarios_movimientos
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if (
				!$this->db->update('fondos_monetarios_movimientos', $data, [
					'id_fondo_monetario_movimiento' => $idFondoMonetarioMovimiento,
				])
			) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				$this->db->trans_rollback();
				return $respuesta;
			}

			// Actualizar datos en fondos_monetarios
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if (
				!$this->db->update('fondos_monetarios', $dataFondoMovimiento, [
					'id_fondo_monetario' => $idFondoMonetario,
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

			if (!empty($fondoMonetarioMovimiento['comprobante_archivo'])) {
				unlink(
					PATH_ARCHIVOS_FONDOS_MONETARIOS .
						'/' .
						$fondoMonetario['fk_id_condominio'] .
						'/' .
						$fondoMonetarioMovimiento['comprobante_archivo']
				);
			}

			// $respuesta['fondo_monetario'] = $this->listar($idFondoMonetario);
			$respuesta['msg'] = 'Movimiento eliminado con éxito.';
			$respuesta['err'] = false;
		} catch (Exception $e) {
			throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
			die();
		}
		return $respuesta;
	}
}

/* End of file Fondo_Monetario_model.php */
/* Location: ./application/models/Fondo_Monetario_model.php */
