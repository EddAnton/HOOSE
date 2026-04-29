<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Modelo: Nomina_model
 *
 * Este modelo realiza las operaciones requeridas sobre los pagos de Nómina
 *
 * @package   CodeIgniter
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Nomina_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	/*
    Obtener información de todos los registros o de alguno en particular
	*/
	public function listar($idNomina = 0, $idCondominio = 0, $idColaborador = 0, $soloActivos = false)
	{
		try {
			if (empty($idNomina) && empty($idCondominio)) {
				return false;
			}
			/* echo $idNomina . PHP_EOL;
			echo $idCondominio . PHP_EOL;
			echo $idColaborador;
			exit(); */
			$this->db
				->select(
					'n.id_colaborador_nomina,
            u.nombre colaborador,
            tm.tipo_miembro puesto,
            n.anio,
            n.mes,
            n.importe,
            n.fecha_pago,
            n.estatus'
				)
				->join('usuarios u', 'u.id_usuario = n.fk_id_usuario')
				->join('colaboradores_contratos cc', 'cc.fk_id_usuario = u.id_usuario')
				->join('cat_tipos_miembros tm', 'tm.id_tipo_miembro = cc.fk_id_tipo_miembro');

			if ($idNomina > 0) {
				$this->db
					->select(
						'u.id_usuario id_colaborador,
              u.imagen_archivo,
              u.email,
	            u.telefono,
              CONCAT(tfm.tipo_fondo, " - ", fm.fondo_monetario) fondo_monetario'
					)
					->join(
						'fondos_monetarios_movimientos m',
						'm.id_fondo_monetario_movimiento = n.fk_id_fondo_monetario_movimiento AND m.estatus = 1',
						'left'
					)
					->join('fondos_monetarios fm', 'fm.id_fondo_monetario = m.fk_id_fondo_monetario AND fm.estatus = 1', 'left')
					->join(
						'cat_tipos_fondos_monetarios tfm',
						'tfm.id_tipo_fondo_monetario = fm.fk_id_tipo_fondo_monetario',
						'left'
					)
					->where(['n.id_colaborador_nomina' => $idNomina]);
			} else {
				$this->db->where(['u.fk_id_condominio' => $idCondominio]);
			}
			if ($idColaborador > 0) {
				$this->db->where(['n.fk_id_usuario' => $idColaborador]);
			}
			if ($soloActivos || $idNomina > 0) {
				$this->db->where(['n.estatus' => 1]);
			}

			$result = $this->db->get('colaboradores_nominas n');
			/* 			print_r($result->result_array());
			 exit(); */
			$result = !empty($idNomina) ? $result->row_array() : $result->result_array();

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

			$idCondominio = $data['id_condominio'];
			$dataMovimiento = !empty($data['movimiento']) ? $data['movimiento'] : null;
			unset($data['id_condominio']);
			unset($data['movimiento']);

			// Validar que los campos existan en la tabla
			if (!validar_campos('colaboradores_nominas', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con la base de datos.';
				return $respuesta;
			}

			// Verificar que el usuario proporcionado sea colaborador activo del condominio actual
			$existeColaborador = $this->db
				->join('colaboradores_contratos cc', 'cc.fk_id_usuario = u.id_usuario')
				->get_where('usuarios u', [
					'u.fk_id_condominio' => $idCondominio,
					'u.id_usuario' => $data['fk_id_usuario'],
					'u.estatus' => 1,
				])
				->num_rows();
			if ($existeColaborador != 1) {
				$respuesta['msg'] = 'Imposible obtener información del Colaborador.';
				return $respuesta;
			}

			/* // Verificar que el usuario proporcionado sea colaborador activo del condominio actual
			$existePago = $this->db
				->get_where('colaboradores_nominas', [
					'id_usuario' => $data['fk_id_usuario'],
          'anio' => $data['anio'],
          'mes' => $data['mes'],
					'estatus' => 1,
				])
				->num_rows();
			if ($existePago != 0) {
				$respuesta['msg'] = 'Ya existe un pago al Colaborador para el Año y Mes.';
				return $respuesta;
			} */

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

				// Obtener información para el concepto de la cuota de mantenimiento
				$datosConcepto = $this->db
					->select('us.nombre usuario, pu.perfil_usuario perfil')
					->join('cat_perfiles_usuarios pu', 'pu.id_perfil_usuario = us.fk_id_perfil_usuario')
					->get_where('usuarios us', ['us.id_usuario' => $data['fk_id_usuario']])
					->row_array();
				if (empty($datosConcepto)) {
					$respuesta['msg'] = 'No se pudo obtener información para generar el concepto del pago.';
					return $respuesta;
				}

				// Establecer el concepto de la cuota de mantenimiento
				$dataMovimiento['concepto'] =
					'PAGO NÓMINA. ' .
					$data['anio'] .
					'/' .
					$data['mes'] .
					' ' .
					$datosConcepto['usuario'] .
					' (' .
					$datosConcepto['perfil'] .
					')';

				// Establecer el valores faltantes
				$dataMovimiento['saldo_anterior'] = $fondoMonetario['saldo'];
				$dataMovimiento['saldo_nuevo'] = $dataFondoMonetario['saldo'];
			}

			// Almacenar información en BD
			// Inicializar la transaccion
			$this->db->trans_start();
			$this->db->trans_strict(false);

			// Insertar registro en fondos_monetarios_movimientos
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if (!$this->db->insert('fondos_monetarios_movimientos', $dataMovimiento)) {
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
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				$this->db->trans_rollback();
				return $respuesta;
			}

			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if (!$this->db->insert('colaboradores_nominas', $data)) {
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

			$respuesta['pago'] = $this->listar($nuevoID);
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
			$idNomina = $data['id_colaborador_nomina'];
			$idCondominio = $data['id_condominio'];
			unset($data['id_colaborador_nomina']);
			unset($data['id_condominio']);
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');

			// Validar que los campos existan en la tabla
			if (!validar_campos('colaboradores_nominas', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con la base de datos.';
				return $respuesta;
			}

			// Verificar que el usuario proporcionado sea colaborador activo del condominio actual
			$existeColaborador = $this->db
				->join('colaboradores_contratos cc', 'cc.fk_id_usuario = u.id_usuario')
				->get_where('usuarios u', [
					'u.fk_id_condominio' => $idCondominio,
					'u.id_usuario' => $data['fk_id_usuario'],
					'u.estatus' => 1,
				])
				->num_rows();
			if ($existeColaborador != 1) {
				$respuesta['msg'] = 'Imposible obtener información del Colaborador.';
				return $respuesta;
			}

			// Verificar cuantos registros serán actualizados
			$registrosEncontrados = $this->db
				->get_where('colaboradores_nominas', ['id_colaborador_nomina' => $idNomina])
				->num_rows();

			if ($registrosEncontrados != 1) {
				if ($registrosEncontrados == 0) {
					$respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
				} elseif ($registrosEncontrados > 1) {
					$respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
				}
				return $respuesta;
			}

			// Almacenar información en BD
			$respuesta['err'] = !$this->db->update('colaboradores_nominas', $data, [
				'id_colaborador_nomina' => $idNomina,
			]);
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if ($respuesta['err']) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $respuesta;
			}

			$respuesta['msg'] = 'Información actualizada con éxito.';
			$respuesta['pago'] = $this->listar($idNomina);
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
			$idNomina = $data['id_colaborador_nomina'];
			$data = [
				'estatus' => 0,
				'fk_id_usuario_modifico' => $data['id_usuario_modifico'],
				'fecha_modificacion' => date('Y-m-d H:i:s'),
			];

			// Verificar cuantos registros serán actualizados
			$pagoNomina = $this->db
				->select(
					'n.*,
            m.fk_id_fondo_monetario id_fondo_monetario,
            m.id_fondo_monetario_movimiento'
				)
				->join(
					'fondos_monetarios_movimientos m',
					'm.id_fondo_monetario_movimiento = n.fk_id_fondo_monetario_movimiento AND m.estatus = 1',
					'left'
				)
				->get_where('colaboradores_nominas n', [
					'n.id_colaborador_nomina' => $idNomina,
					'n.estatus' => 1,
				]);

			if ($pagoNomina->num_rows() != 1) {
				if ($pagoNomina->num_rows() == 0) {
					$respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
				} elseif ($pagoNomina->num_rows() > 1) {
					$respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
				}
				return $respuesta;
			}
			$pagoNomina = $pagoNomina->row_array();
			$idFondoMonetario = 0;
			$idMovimiento = 0;

			if (!empty($pagoNomina['id_fondo_monetario'])) {
				// Obtener registro de fondos_monetarios
				$idFondoMonetario = $pagoNomina['id_fondo_monetario'];
				$idMovimiento = $pagoNomina['id_fondo_monetario_movimiento'];

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
					'saldo' => floatval($fondoMonetario['saldo']) + floatval($pagoNomina['importe']),
				];
				$dataMovimiento = ['estatus' => 0];
			}

			// Validar que los campos existan en la tabla usuarios
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
			if (!validar_campos('colaboradores_nominas', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con respecto a la base de datos.';
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

			// Actualizar registro en colaboradores_nominas
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if (!$this->db->update('colaboradores_nominas', $data, ['id_colaborador_nomina' => $idNomina])) {
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
				$respuesta['msg'] = 'Pago de nómina eliminado con éxito.';
			}

			$respuesta['err'] = false;
		} catch (Exception $e) {
			throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
			die();
		}
		return $respuesta;
	}
}

/* End of file Nomina_model.php */
/* Location: ./application/models/Nomina_model.php */
