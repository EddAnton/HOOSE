<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Modelo: Gasto_Mantenimiento
 *
 * Este modelo realiza las operaciones requeridas sobre los Gastos de mantenimiento
 *
 * @package   CodeIgniter
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Gasto_Mantenimiento_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper('File_Upload');
	}

	/*
    Obtener información del identificador especificado.
	*/
	public function listar($idGastoMantenimiento = 0, $idCondominio = 0, $soloActivos = false)
	{
		try {
			if (empty($idGastoMantenimiento) && empty($idCondominio)) {
				return false;
			}
			$this->db
				->select(
					'gm.id_gasto_mantenimiento,
            IFNULL(gf.id_gasto_fijo, 0) id_gasto_fijo,
            IFNULL(gf.gasto_fijo, gm.concepto) concepto,
            gm.fecha,
            gm.importe,
            gm.descripcion,
            gm.es_deducible,
            gm.comprobante_archivo comprobante,
            gm.estatus'
				)
				->join('cat_gastos_fijos gf', 'gf.id_gasto_fijo = gm.fk_id_gasto_fijo', 'left');

			if (!empty($idGastoMantenimiento)) {
				$this->db
					->select('CONCAT(tfm.tipo_fondo, " - ", fm.fondo_monetario) fondo_monetario')
					->join(
						'fondos_monetarios_movimientos m',
						'm.id_fondo_monetario_movimiento = gm.fk_id_fondo_monetario_movimiento',
						'left'
					)
					->join('fondos_monetarios fm', 'fm.id_fondo_monetario = m.fk_id_fondo_monetario', 'left')
					->join(
						'cat_tipos_fondos_monetarios tfm',
						'tfm.id_tipo_fondo_monetario = fm.fk_id_tipo_fondo_monetario',
						'left'
					)
					->where([
						'gm.id_gasto_mantenimiento' => $idGastoMantenimiento,
					]);
			} else {
				$this->db->where(['fk_id_condominio' => $idCondominio]);
			}
			if ($soloActivos) {
				$this->db->where(['gm.estatus' => 1]);
			}
			$result = $this->db->get('gastos_mantenimiento gm');
			$result = !empty($idGastoMantenimiento) ? $result->row_array() : $result->result_array();

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

			$idCondominio = $data['fk_id_condominio'];
			// Obtener el archivo del comprobante
			$archivo_comprobante = !empty($data['archivo_comprobante']) ? $data['archivo_comprobante'] : null;
			$dataMovimiento = !empty($data['movimiento']) ? $data['movimiento'] : null;
			unset($data['archivo_comprobante']);
			unset($data['movimiento']);

			// Validar que los campos existan en la tabla
			if (!validar_campos('gastos_mantenimiento', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con la base de datos.';
				return $respuesta;
			}

			// Validar que los campos existan en la tabla fondos_monetarios_movimientos
			if (!empty($dataMovimiento)) {
				if (!validar_campos('fondos_monetarios_movimientos', $dataMovimiento)) {
					$respuesta['msg'] = 'Error de integridad de la información con la base de datos (Movimiento).';
					return $respuesta;
				}

				// Obtener registro de fondos_monetarios
				$idFondoMonetario = $dataMovimiento['fk_id_fondo_monetario'];
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
				$dataMovimiento['importe'] = $dataMovimiento['importe'] * -1;

				$dataFondoMonetario = [
					'saldo' => floatval($fondoMonetario['saldo']) + $dataMovimiento['importe'],
				];

				if ($dataFondoMonetario['saldo'] < 0) {
					$respuesta['msg'] = 'El saldo del fondo monetario quedaría con saldo inferior a cero.';
					return $respuesta;
				}

				if (empty($data['concepto'])) {
					// Obtener información para el concepto de la cuota de mantenimiento
					$datosConcepto = $this->db
						->select('gasto_fijo')
						->get_where('cat_gastos_fijos', ['id_gasto_fijo' => $data['fk_id_gasto_fijo'], 'estatus' => 1])
						->row_array();
					if (empty($datosConcepto)) {
						$respuesta['msg'] = 'No se pudo obtener información para generar el concepto del pago.';
						return $respuesta;
					}
					$dataMovimiento['concepto'] = $datosConcepto['gasto_fijo'];
				} else {
					// El concepto del movimiento es igual al concepto del gasto de mantenimiento
					$dataMovimiento['concepto'] = $data['concepto'];
				}
				$dataMovimiento['concepto'] = 'GASTO MANTENIMIENTO. ' . $dataMovimiento['concepto'];
				// Establecer el valores faltantes
				$dataMovimiento['saldo_anterior'] = $fondoMonetario['saldo'];
				$dataMovimiento['saldo_nuevo'] = $dataFondoMonetario['saldo'];
			}

			// Define la carpeta para la carga de los archivos
			$carpeta_cargar_archivos = PATH_ARCHIVOS_GASTOS_MANTENIMIENTO . '/' . $idCondominio . '/';
			/*
			  Si se especificó documento del contrato intenta la carga del mismo y agrega el nombre del archivo cargado a la data.
				Si no se pudo cargar el archivo, borra carpeta temporal y aborta el proceso
      */
			if (!empty($archivo_comprobante)) {
				$cargar_archivo = subir_archivo(
					$carpeta_cargar_archivos,
					$archivo_comprobante,
					serialize(['png', 'jpg', 'jpeg', 'pdf']),
					'gasto_mantenimiento'
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
			if (!$this->db->insert('fondos_monetarios_movimientos', $dataMovimiento)) {
				borrar_directorio($carpeta_cargar_archivos . $data['comprobante_archivo']);
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				$this->db->trans_rollback();
				return $respuesta;
			}
			$data['fk_id_fondo_monetario_movimiento'] = $this->db->insert_id();

			// Actualizar datos en fondos_monetarios
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if (
				!$this->db->update('fondos_monetarios', $dataFondoMonetario, [
					'id_fondo_monetario' => $idFondoMonetario,
				])
			) {
				borrar_directorio($carpeta_cargar_archivos . $data['comprobante_archivo']);
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				$this->db->trans_rollback();
				return $respuesta;
			}

			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if (!$this->db->insert('gastos_mantenimiento', $data)) {
				borrar_directorio($carpeta_cargar_archivos . $data['comprobante_archivo']);
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				$this->db->trans_rollback();
				return $respuesta;
			}
			$nuevoID = $this->db->insert_id();

			// Confirmar la transacción
			$this->db->trans_complete();
			// Confirmar la transacción y verificar el estatus de la misma
			if ($this->db->trans_status() === false) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $respuesta;
			} else {
				$respuesta['msg'] = 'Información almacenada con éxito.';
			}

			$respuesta['gasto_mantenimiento'] = $this->listar($nuevoID);
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
			$idGastoMantenimiento = $data['id_gasto_mantenimiento'];
			// Obtener el archivo del comprobante
			$archivo_comprobante = !empty($data['archivo_comprobante']) ? $data['archivo_comprobante'] : null;
			$borrar_comprobante = $data['borrar_comprobante'];
			unset($data['borrar_comprobante']);
			unset($data['archivo_comprobante']);
			unset($data['id_gasto_mantenimiento']);
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');

			// Validar que los campos existan en la tabla
			if (!validar_campos('gastos_mantenimiento', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con la base de datos.';
				return $respuesta;
			}

			// Verificar cuantos registros serán actualizados
			$gastoMantenimiento = $this->db->get_where('gastos_mantenimiento', [
				'id_gasto_mantenimiento' => $idGastoMantenimiento,
				'estatus' => 1,
			]);

			if ($gastoMantenimiento->num_rows() != 1) {
				if ($gastoMantenimiento->num_rows() == 0) {
					$respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
				} elseif ($gastoMantenimiento->num_rows() > 1) {
					$respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
				}
				return $respuesta;
			}
			$gastoMantenimiento = $gastoMantenimiento->row_array();

			// Define la carpeta para la carga de los archivos
			$carpeta_cargar_archivos =
				PATH_ARCHIVOS_GASTOS_MANTENIMIENTO . '/' . $gastoMantenimiento['fk_id_condominio'] . '/';
			$se_cargaron_archivos = false;
			$comprobante_archivo_existente = $gastoMantenimiento['comprobante_archivo'];
			/*
			  Si se especificó documento del contrato intenta la carga del mismo y agrega el nombre del archivo cargado a la data.
				Si no se pudo cargar el archivo, borra carpeta temporal y aborta el proceso
      */
			if (!empty($archivo_comprobante)) {
				$cargar_archivo = subir_archivo(
					$carpeta_cargar_archivos,
					$archivo_comprobante,
					serialize(['png', 'jpg', 'jpeg', 'pdf']),
					'gasto_mantenimiento'
				);

				if ($cargar_archivo['error']) {
					$respuesta['msg'] = 'Error al cargar el comprobante.' . PHP_EOL . $cargar_archivo['msg'];
					return $respuesta;
				}
				$data['comprobante_archivo'] = $cargar_archivo['archivo_servidor'];
				$se_cargaron_archivos = true;
			} elseif (!empty($comprobante_archivo_existente) && $borrar_comprobante) {
				$data['comprobante_archivo'] = null;
				$se_cargaron_archivos = true;
			}

			// Almacenar información en BD
			$respuesta['err'] = !$this->db->update('gastos_mantenimiento', $data, [
				'id_gasto_mantenimiento' => $idGastoMantenimiento,
			]);
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if ($respuesta['err']) {
				if (!empty($data['comprobante_archivo'])) {
					unlink($carpeta_cargar_archivos . $data['comprobante_archivo']);
				}
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $respuesta;
			}

			if ($se_cargaron_archivos) {
				if (!empty($comprobante_archivo_existente) && $borrar_comprobante) {
					unlink($carpeta_cargar_archivos . $comprobante_archivo_existente);
				}
			}

			$respuesta['msg'] = 'Información actualizada con éxito.';
			$respuesta['gasto_mantenimiento'] = $this->listar($idGastoMantenimiento);
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
			$idGastoMantenimiento = $data['id_gasto_mantenimiento'];
			$data = [
				'estatus' => 0,
				'fk_id_usuario_modifico' => $data['id_usuario_modifico'],
				'fecha_modificacion' => date('Y-m-d H:i:s'),
			];

			// Verificar cuantos registros serán actualizados
			$gastoMantenimiento = $this->db
				->select(
					'gm.*,
          m.fk_id_fondo_monetario id_fondo_monetario,
          m.id_fondo_monetario_movimiento'
				)
				->join(
					'fondos_monetarios_movimientos m',
					'm.id_fondo_monetario_movimiento = gm.fk_id_fondo_monetario_movimiento AND m.estatus = 1',
					'left'
				)
				->get_where('gastos_mantenimiento gm', [
					'gm.id_gasto_mantenimiento' => $idGastoMantenimiento,
					'gm.estatus' => 1,
				]);

			if ($gastoMantenimiento->num_rows() != 1) {
				if ($gastoMantenimiento->num_rows() == 0) {
					$respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
				} elseif ($gastoMantenimiento->num_rows() > 1) {
					$respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
				}
				return $respuesta;
			}
			$gastoMantenimiento = $gastoMantenimiento->row_array();
			$idFondoMonetario = 0;
			$idMovimiento = 0;

			if (!empty($gastoMantenimiento['id_fondo_monetario'])) {
				// Obtener registro de fondos_monetarios
				$idFondoMonetario = $gastoMantenimiento['id_fondo_monetario'];
				$idMovimiento = $gastoMantenimiento['id_fondo_monetario_movimiento'];

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

				$dataFondoMonetario = [
					'saldo' => floatval($fondoMonetario['saldo']) + floatval($gastoMantenimiento['importe']),
				];
				$dataMovimiento = ['estatus' => 0];
			}

			// Validar que los campos existan en la tabla gastos_mantenimiento
			if ($idMovimiento > 0 && !validar_campos('fondos_monetarios_movimientos', $dataMovimiento)) {
				$respuesta['msg'] =
					'Error de integridad de la información con respecto a la base de datos (Fondos monetarios movimientos).';
				return $respuesta;
			}
			if ($idFondoMonetario > 0 && !validar_campos('fondos_monetarios', $dataFondoMonetario)) {
				$respuesta['msg'] =
					'Error de integridad de la información con respecto a la base de datos (Fondos monetarios).';
				return $respuesta;
			}
			if (!validar_campos('gastos_mantenimiento', $data)) {
				$respuesta['msg'] =
					'Error de integridad de la información con respecto a la base de datos (Gastos mantenimiento).';
				return $respuesta;
			}

			// Almacenar información en BD
			// Inicializar la transaccion
			$this->db->trans_start();
			$this->db->trans_strict(false);

			// Actualizar registro en fondos_monetarios_movimientos
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if (
				$idMovimiento > 0 &&
				!$this->db->update('fondos_monetarios_movimientos', $dataMovimiento, [
					'id_fondo_monetario_movimiento' => $idMovimiento,
				])
			) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				$this->db->trans_rollback();
				return $respuesta;
			}

			// Actualizar registro en fondos_monetarios
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if (
				$idFondoMonetario > 0 &&
				!$this->db->update('fondos_monetarios', $dataFondoMonetario, ['id_fondo_monetario' => $idFondoMonetario])
			) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				$this->db->trans_rollback();
				return $respuesta;
			}

			// Actualizar registro en gastos_mantenimiento
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if (!$this->db->update('gastos_mantenimiento', $data, ['id_gasto_mantenimiento' => $idGastoMantenimiento])) {
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
			} else {
				$respuesta['msg'] = 'Gasto de mantenimiento eliminado con éxito.';
			}

			$respuesta['err'] = false;
		} catch (Exception $e) {
			throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
			die();
		}
		return $respuesta;
	}
}

/* End of file Gasto_Mantenimiento_model.php */
/* Location: ./application/models/Gasto_Mantenimiento_model.php */
