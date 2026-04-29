<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Modelo: Area_Comun
 *
 * Este modelo realiza las operaciones requeridas sobre la información de las Áreas comunes
 *
 * @package   CodeIgniter
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Area_Comun_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	/*
    Obtener información del identificador especificado o todos los registros.
      $id => ID especifico a obtener, si se requiere
      $soloActivos => Determinar si se requieren todos los registros o sólo los activos
	*/
	public function listar($id = 0, $soloActivos = false)
	{
		try {
			$this->db->select(
				'id_area_comun,
          nombre,
          descripcion,
          importe_hora,
          estatus'
			);

			if ($id > 0) {
				$this->db->where(['id_area_comun' => $id]);
			} elseif ($soloActivos) {
				$this->db->where(['estatus' => 1]);
			}

			$respuesta = $this->db->order_by('nombre')->get('areas_comunes');
			return $id > 0 ? $respuesta->row_array() : $respuesta->result_array();

			return $respuesta;
		} catch (Exception $e) {
			throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
			die();
		}
	}

	/*
    Obtener información las reservaciones
      $id => ID especifico a obtener, si se requiere
	*/
	public function listar_reservaciones($data = null)
	{
		try {
			$dia_inicio = empty($data['dia']) ? '01' : $data['dia'];
			$fecha_inicio = $data['anio'] . '-' . $data['mes'] . '-' . $dia_inicio . ' 00:00:00';
			if (!empty($data['dia'])) {
				$fecha_fin = $data['anio'] . '-' . $data['mes'] . '-' . $dia_inicio . ' 23:59:59';
			} else {
				$dia_final = cal_days_in_month(CAL_GREGORIAN, $data['mes'], $data['anio']);
				$fecha_fin = $data['anio'] . '-' . $data['mes'] . '-' . $dia_final . ' 23:59:59';
			}

			$result = $this->db
				->select(
					'r.id_area_comun_reservacion,
              ac.nombre area_comun,
              u.nombre usuario,
              r.fecha_inicio,
              r.fecha_fin,
              IF(r.fecha_pago IS NULL, 0, 1) pagado'
				)
				->join('areas_comunes ac', 'ac.id_area_comun = r.fk_id_area_comun')
				->join('usuarios u', 'u.id_usuario = r.fk_id_usuario')
				->where([
					'r.fecha_inicio >=' => $fecha_inicio,
					'r.fecha_fin <=' => $fecha_fin,
					'r.estatus' => 1,
				])
				->order_by('r.fecha_inicio')
				->get('areas_comunes_reservaciones r')
				->result_array();
			return $result;
		} catch (Exception $e) {
			throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
			die();
		}
	}

	/*
    Obtener información de la o las reservaciones
      $id => ID especifico a obtener, si se requiere
	*/
	public function listar_reservacion($idReservacion = 0)
	{
		try {
			return $this->db
				->select(
					'r.id_area_comun_reservacion,
            ac.id_area_comun,
            ac.nombre area_comun,
            ac.importe_hora,
            u.id_usuario,
            u.nombre usuario,
            r.fecha_inicio,
            r.fecha_fin,
            r.importe_total,
            IF(r.fecha_pago IS NULL, 0, 1) pagado,
            r.fecha_pago'
				)
				->join('areas_comunes ac', 'ac.id_area_comun = r.fk_id_area_comun')
				->join('usuarios u', 'u.id_usuario = r.fk_id_usuario')
				->get_where('areas_comunes_reservaciones r', [
					'r.id_area_comun_reservacion' => $idReservacion,
					'r.estatus' => 1,
				])
				->row_array();
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

			// Validar que los campos existan en la tabla
			if (!validar_campos('areas_comunes', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con la base de datos.';
				return $respuesta;
			}

			// Almacenar información en BD
			$respuesta['err'] = !$this->db->insert('areas_comunes', $data);
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if ($respuesta['err']) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $respuesta;
			}

			$respuesta['msg'] = 'Información registrada con éxito.';
			$respuesta['area_comun'] = $this->listar($this->db->insert_id());
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
			$idAreaComun = $data['id_area_comun'];
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			unset($data['id_area_comun']);

			// Validar que los campos existan en la tabla
			if (!validar_campos('areas_comunes', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con la base de datos.';
				return $respuesta;
			}

			// Verificar cuantos registros serán actualizados
			$areaComun = $this->db->get_where('areas_comunes', ['id_area_comun' => $idAreaComun])->num_rows();

			if ($areaComun != 1) {
				if ($areaComun == 0) {
					$respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
				} elseif ($areaComun > 1) {
					$respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
				}
				return $respuesta;
			}

			// Almacenar información en BD
			$respuesta['err'] = !$this->db->update('areas_comunes', $data, [
				'id_area_comun' => $idAreaComun,
			]);
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if ($respuesta['err']) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $respuesta;
			}

			$respuesta['msg'] = 'Información actualizada con éxito.';
			$respuesta['area_comun'] = $this->listar($idAreaComun);
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
			die();
		}
		return $respuesta;
	}

	/*
    Alterna el estatus del registro
      $data => Información del registro a actualizar
	*/
	public function alternar_estatus($data)
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

			$idAreaComun = $data['id_area_comun'];

			// Verificar cuantos registros serán actualizados
			$areaComun = $this->db->get_where('areas_comunes', [
				'id_area_comun' => $idAreaComun,
			]);

			if ($areaComun->num_rows() != 1) {
				if ($areaComun->num_rows() == 0) {
					$respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
				} elseif ($areaComun->num_rows() > 1) {
					$respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
				}
				return $respuesta;
			}

			$estatus = 0;
			// Si no se especifica valor para estatus, se alterna el valor
			if (!empty($data['estatus']) && intval($data['estatus'])) {
				$estatus = intval($data['estatus']) != 0 ? 1 : 0;
			} else {
				$estatus = !$areaComun->row()->estatus;
			}

			$data = [
				'estatus' => $estatus,
				'fk_id_usuario_modifico' => $data['fk_id_usuario_modifico'],
				'fecha_modificacion' => date('Y-m-d H:i:s'),
			];

			// Almacenar información en BD
			$respuesta['err'] = !$this->db->update('areas_comunes', $data, [
				'id_area_comun' => $idAreaComun,
			]);
			$respuesta['msg'] = $respuesta['err']
				? 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message']
				: 'Estatus modificado con éxito.';
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
			die();
		}
		return $respuesta;
	}

	// Determinar si el área común ya se encuentra reservada dentro las fechas especificadas
	public function area_comun_ocupada($id = 0, $fecha_inicio = null, $fecha_fin = null)
	{
		$resultado = true;

		if (empty($id) || empty($fecha_inicio) || empty($fecha_fin)) {
			return $resultado;
		}

		$resultado =
			$this->db
				->where([
					'fk_id_area_comun' => $id,
					'estatus' => 1,
				])
				->where(
					/* '(fecha_inicio BETWEEN "' .
					$fecha_inicio .
					'" AND "' .
					$fecha_fin .
					'" OR ' .
					'fecha_fin BETWEEN "' .
					$fecha_inicio .
					'" AND "' .
					$fecha_fin .
					'")' */
					'(' .
						$this->db->escape($fecha_inicio) .
						' BETWEEN fecha_inicio AND fecha_fin ' .
						' OR ' .
						$this->db->escape($fecha_fin) .
						' BETWEEN fecha_inicio AND fecha_fin)'
				)
				->get('areas_comunes_reservaciones')
				->num_rows() != 0;
		return $resultado;
	}

	/*
    Insertar reservación de área común
      $data => Información a actualizar
	*/
	public function insertar_reservacion($data)
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
			$idAreaComun = $data['fk_id_area_comun'];

			// Validar que los campos existan en la tabla
			if (!validar_campos('areas_comunes_reservaciones', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con la base de datos.';
				return $respuesta;
			}

			// Determinar si el área común ya se encuentra reservada
			$resultado = $this->area_comun_ocupada($idAreaComun, $data['fecha_inicio'], $data['fecha_fin']);
			if ($this->area_comun_ocupada($idAreaComun, $data['fecha_inicio'], $data['fecha_fin'])) {
				$respuesta['msg'] = 'Área ya reservada dentro de fechas y horas especificadas.';
				return $respuesta;
			}

			// Almacenar información en BD
			$respuesta['err'] = !$this->db->insert('areas_comunes_reservaciones', $data);
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if ($respuesta['err']) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $respuesta;
			}
			$nuevoID = $this->db->insert_id();

			$respuesta['reservacion'] = $this->listar_reservacion($nuevoID);
			$respuesta['msg'] = 'Reservación registrada con éxito.';
			// $respuesta['area_comun'] = $this->listar($idAreaComun);
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
			die();
		}
		return $respuesta;
	}

	/*
    Actualizar reservación de área común
      $data => Información a actualizar
	*/
	public function actualizar_reservacion($data)
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
			$idReservacion = $data['id_area_comun_reservacion'];
			unset($data['id_area_comun_reservacion']);

			// Validar que los campos existan en la tabla
			if (!validar_campos('areas_comunes_reservaciones', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con la base de datos.';
				return $respuesta;
			}

			// Verificar cuantos registros serán actualizados
			$areaComun = $this->db
				->select('ac.id_area_comun')
				->join('areas_comunes ac', 'ac.id_area_comun = r.fk_id_area_comun AND ac.estatus = 1')
				->get_where('areas_comunes_reservaciones r', [
					'r.id_area_comun_reservacion' => $idReservacion,
					'r.estatus' => 1,
				]);

			if ($areaComun->num_rows() != 1) {
				if ($areaComun->num_rows() == 0) {
					$respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
				} elseif ($areaComun->num_rows() > 1) {
					$respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
				}
				return $respuesta;
			}
			$areaComun = $areaComun->row_array();

			// Verificar cuantos registros serán actualizados
			$areaComunOcupada = $this->db
				->where([
					'id_area_comun_reservacion !=' => $idReservacion,
					'fk_id_area_comun' => $areaComun['id_area_comun'],
					'estatus' => 1,
				])
				->where(
					'(fecha_inicio BETWEEN ' .
						$this->db->escape($data['fecha_inicio']) .
						' AND ' .
						$this->db->escape($data['fecha_fin']) .
						' OR ' .
						'fecha_fin BETWEEN ' .
						$this->db->escape($data['fecha_inicio']) .
						' AND ' .
						$this->db->escape($data['fecha_fin']) .
						')'
				)
				->get('areas_comunes_reservaciones')
				->num_rows();

			if ($areaComunOcupada != 0) {
				$respuesta['msg'] = 'Área ya reservada dentro de fechas y horas especificadas.';
				return $respuesta;
			}

			// Almacenar información en BD
			$respuesta['err'] = !$this->db->update('areas_comunes_reservaciones', $data, [
				'id_area_comun_reservacion' => $idReservacion,
			]);
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if ($respuesta['err']) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $respuesta;
			}

			$respuesta['reservacion'] = $this->listar_reservacion($idReservacion);
			$respuesta['msg'] = 'Reservación actualizada con éxito.';
			// $respuesta['area_comun'] = $this->listar($idAreaComun);
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
			die();
		}
		return $respuesta;
	}

	/*
    Registrar el pago a una reservación
      $data => Información a procesar
	*/
	public function registrar_pago_reservacion($data)
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
			$idReservacion = $data['id_area_comun_reservacion'];
			// Establecer la información que se actualizará
			$data = [
				'fecha_pago' => $data['fecha_pago'],
				'fk_id_usuario_modifico' => $data['id_usuario_modifico'],
				'fecha_modificacion' => date('Y-m-d H:i:s'),
			];

			// Validar que los campos existan en la tabla usuarios
			if (!validar_campos('areas_comunes_reservaciones', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con respecto a la base de datos.';
				return $respuesta;
			}

			// Verificar cuantos registros serán actualizados
			$areaComunReservacion = $this->db->where('fecha_pago IS NULL')->get_where('areas_comunes_reservaciones', [
				'id_area_comun_reservacion' => $idReservacion,
				'estatus' => 1,
			]);

			if ($areaComunReservacion->num_rows() != 1) {
				if ($areaComunReservacion->num_rows() == 0) {
					$respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
				} elseif ($areaComunReservacion->num_rows() > 1) {
					$respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
				}
				return $respuesta;
			}

			// Almacenar información en BD
			$respuesta['err'] = !$this->db->update('areas_comunes_reservaciones', $data, [
				'id_area_comun_reservacion' => $idReservacion,
			]);
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if ($respuesta['err']) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $respuesta;
			}

			$respuesta['msg'] = 'Pago registrado con éxito.';
		} catch (Exception $e) {
			throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
			die();
		}
		return $respuesta;
	}

	/*
    Cancela una reservación
      $data => Información a procesar
	*/
	public function cancelar_reservacion($data)
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
			$idReservacion = $data['id_reservacion'];
			// Establecer la información que se actualizará
			$data = [
				'estatus' => 0,
				'fk_id_usuario_modifico' => $data['id_usuario_modifico'],
				'fecha_modificacion' => date('Y-m-d H:i:s'),
			];

			// Validar que los campos existan en la tabla usuarios
			if (!validar_campos('areas_comunes_reservaciones', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con respecto a la base de datos.';
				return $respuesta;
			}

			// Verificar cuantos registros serán actualizados
			$areaComunReservacion = $this->db->get_where('areas_comunes_reservaciones', [
				'id_area_comun_reservacion' => $idReservacion,
				'estatus' => 1,
			]);

			if ($areaComunReservacion->num_rows() != 1) {
				if ($areaComunReservacion->num_rows() == 0) {
					$respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
				} elseif ($areaComunReservacion->num_rows() > 1) {
					$respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
				}
				return $respuesta;
			}

			// Almacenar información en BD
			$respuesta['err'] = !$this->db->update('areas_comunes_reservaciones', $data, [
				'id_area_comun_reservacion' => $idReservacion,
			]);
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if ($respuesta['err']) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $respuesta;
			}

			$respuesta['msg'] = 'Reservación cancelada con éxito.';
		} catch (Exception $e) {
			throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
			die();
		}
		return $respuesta;
	}
}

/* End of file Area_Comun_model.php */
/* Location: ./application/models/Area_Comun_model.php */
