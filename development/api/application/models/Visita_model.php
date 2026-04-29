<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Modelo: Visita
 *
 * Este modelo realiza las operaciones requeridas sobre la información de los Visitas
 *
 * @package   CodeIgniter
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Visita_model extends CI_Model
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
	public function listar($idVisita = 0, $idCondominio = 0, $soloActivos = false)
	{
		try {
			// Si no se proporciona ID de registro ni ID condominio, aborta el proceso
			if (empty($idVisita) && empty($idCondominio)) {
				return false;
			}

			$this->db
				->select(
					'v.id_visita,
            v.visitante,
            v.telefono,
            v.domicilio,
            v.identificacion_folio,
            u.id_unidad,
            u.unidad,
            v.fecha_hora_entrada,
            v.fecha_hora_salida,
            u.estatus'
				)
				->join('unidades u', 'u.id_unidad = v.fk_id_unidad')
				->join('edificios e', 'e.id_edificio = u.fk_id_edificio');

			if ($idVisita > 0) {
				$this->db->where(['v.id_visita' => $idVisita]);
			} else {
				$this->db->where(['e.fk_id_condominio' => $idCondominio]);
			}
			if ($soloActivos) {
				$this->db->where(['v.estatus' => 1]);
			}

			$respuesta = $this->db->order_by('v.fecha_hora_entrada, v.visitante')->get('visitas v');

			return $idVisita == 0 ? $respuesta->result_array() : $respuesta->row_array();
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
			if (!validar_campos('visitas', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con la base de datos.';
				return $respuesta;
			}

			// Almacenar información en BD
			$respuesta['err'] = !$this->db->insert('visitas', $data);
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if ($respuesta['err']) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $respuesta;
			}

			$nuevoID = $this->db->insert_id();
			$respuesta['msg'] = 'Información registrada con éxito';
			$respuesta['visita'] = $this->listar($nuevoID);
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
			// Validar que la información a insertar sea proporcionada
			if (empty($data)) {
				$respuesta['msg'] = 'Debe especificar la información a actualizar.';
				return $respuesta;
			}

			// Obtener ID del registro a actualizar y establecer la fecha de modificación
			$idVisita = $data['id_visita'];
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			unset($data['id_visita']);

			// Validar que los campos existan en la tabla
			if (!validar_campos('visitas', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con la base de datos.';
				return $respuesta;
			}

			// Verificar cuantos registros serán actualizados
			$registros_coincidentes = $this->db->get_where('visitas', ['id_visita' => $idVisita])->num_rows();

			if ($registros_coincidentes != 1) {
				if ($registros_coincidentes == 0) {
					$respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
				} elseif ($registros_coincidentes > 1) {
					$respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
				}
				return $respuesta;
			}

			$respuesta['err'] = !$this->db->update('visitas', $data, [
				'id_visita' => $idVisita,
			]);

			if ($respuesta['err']) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
			} else {
				$respuesta['visita'] = $this->listar($idVisita);
				$respuesta['msg'] = 'Información actualizada con éxito.';
			}
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
			die();
		}
		return $respuesta;
	}

	/*
    Establecer como pagada la recaudación
      $data => Información a procesar
	*/
	public function registrar_salida($data)
	{
		$respuesta = [
			'error' => true,
			'msg' => null,
		];

		try {
			// Validar que la información a insertar sea proporcionada
			if (empty($data)) {
				$respuesta['msg'] = 'Debe especificar la información a insertar.';
				return $respuesta;
			}

			// Obtener ID del registro a actualizar
			$idVisita = $data['id_visita'];
			$dataVisita = [
				'fecha_hora_salida' => $data['fecha_hora_salida'],
				'fk_id_usuario_modifico' => $data['id_usuario_modifico'],
				'fecha_modificacion' => date('Y-m-d H:i:s'),
			];

			// Validar que los campos existan en la tabla condominos_contratos
			if (!validar_campos('visitas', $dataVisita)) {
				$respuesta['msg'] = 'Error de integridad de la información con respecto a la base de datos.';
				return $respuesta;
			}

			// Verificar cuantos registros serán actualizados
			$visita = $this->db->get_where('visitas', [
				'id_visita' => $idVisita,
				'estatus' => 1,
			]);

			if ($visita->num_rows() != 1) {
				if ($visita->num_rows() == 0) {
					$respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
				} elseif ($visita->num_rows() > 1) {
					$respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
				}
				return $respuesta;
			}

			// Almacenar información en BD
			// Actualizar datos en visitas
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if (
				!$this->db->update('visitas', $dataVisita, [
					'id_visita' => $idVisita,
				])
			) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $respuesta;
			}

			$respuesta['visita'] = $this->listar($idVisita);
			$respuesta['msg'] = 'Salida registrada con éxito.';
			$respuesta['error'] = false;
		} catch (Exception $e) {
			throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
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
			'error' => true,
			'msg' => null,
		];

		try {
			// Validar que la información a procesar sea proporcionada
			if (empty($data)) {
				$respuesta['msg'] = 'Debe especificar la información a procesar.';
				return $respuesta;
			}

			// Obtener ID del registro a actualizar
			$idVisita = $data['id_visita'];
			$data = [
				'estatus' => 0,
				'fk_id_usuario_modifico' => $data['id_usuario_modifico'],
				'fecha_modificacion' => date('Y-m-d H:i:s'),
			];

			// Validar que los campos existan en la tabla usuarios
			if (!validar_campos('visitas', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con respecto a la base de datos.';
				return $respuesta;
			}

			// Verificar cuantos registros serán actualizados
			$asamblea = $this->db->get_where('visitas', [
				'id_visita' => $idVisita,
				'estatus' => 1,
			]);

			if ($asamblea->num_rows() != 1) {
				if ($asamblea->num_rows() == 0) {
					$respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
				} elseif ($asamblea->num_rows() > 1) {
					$respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
				}
				return $respuesta;
			}

			// Almacenar información en BD
			// Actualizar datos en visitas
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if (!$this->db->update('visitas', $data, ['id_visita' => $idVisita])) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $respuesta;
			}

			$respuesta['msg'] = 'Visita eliminada con éxito.';
			$respuesta['error'] = false;
		} catch (Exception $e) {
			throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
			die();
		}
		return $respuesta;
	}
}

/* End of file Visita_model.php */
/* Location: ./application/models/Visita_model.php */
