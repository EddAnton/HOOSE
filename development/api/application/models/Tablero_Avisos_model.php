<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Modelo: Tablero_Avisos
 *
 * Este modelo realiza las operaciones requeridas sobre la información de los Tableros de avisos
 *
 * @package   CodeIgniter
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Tablero_Avisos_model extends CI_Model
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
	//		$idUsuario = 0
	public function listar(
		$idAviso = 0,
		$idCondominio = 0,
		$idPerfilUsuarioDestino = 0,
		$soloActivos = false,
		$soloPublicados = false
	) {
		try {
			// Si no se proporciona ID de registro ni IDs condominio/perfil usuario, aborta el proceso
			if (empty($idAviso) && (empty($idCondominio) || empty($idPerfilUsuarioDestino))) {
				return false;
			}

			$this->db->select(
				'a.id_aviso,
          a.titulo,
          a.publicado,
          a.fecha_publicacion,
          a.estatus'
			);

			if (!empty($idAviso)) {
				$this->db->select('a.descripcion');
				/* 				if (!empty($idUsuario)) {
					$this->db
						->select(
							'COUNT(al.id_aviso_leido) leido,
                al.fecha_lectura'
						)
						->join(
							'tablero_avisos_leidos al',
							'al.fk_id_aviso = a.id_aviso AND al.fk_id_usuario = ' . $idUsuario,
							'left'
						);
				} */
				$this->db->where([
					'a.id_aviso' => $idAviso,
				]);
			} else {
				$this->db->where([
					'a.fk_id_condominio' => $idCondominio,
					'a.fk_id_perfil_usuario_destino' => $idPerfilUsuarioDestino,
				]);
			}

			if ($soloActivos) {
				$this->db->where(['a.estatus' => 1]);
			}
			if ($soloPublicados) {
				$this->db->where(['a.estatus' => 1, 'a.publicado' => 1]);
			}

			$respuesta = $this->db->order_by('a.fecha_publicacion, a.titulo')->get('tablero_avisos a');
			return $idAviso == 0 ? $respuesta->result_array() : $respuesta->row_array();
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

			$data['fecha_publicacion'] = intval($data['publicado']) == 1 ? date('Y-m-d H:i:s') : null;

			// Validar que los campos existan en la tabla
			if (!validar_campos('tablero_avisos', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con la base de datos.';
				return $respuesta;
			}

			// Almacenar información en BD
			$respuesta['err'] = !$this->db->insert('tablero_avisos', $data);
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if ($respuesta['err']) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $respuesta;
			}

			$nuevoID = $this->db->insert_id();
			$respuesta['msg'] = 'Información registrada con éxito';
			$respuesta['aviso'] = $this->listar($nuevoID);
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
			$idAviso = $data['id_aviso'];
			$data['fecha_publicacion'] = intval($data['publicado']) == 1 ? date('Y-m-d H:i:s') : null;
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			unset($data['id_aviso']);

			// Validar que los campos existan en la tabla
			if (!validar_campos('tablero_avisos', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con la base de datos.';
				return $respuesta;
			}

			// Verificar cuantos registros serán actualizados
			$registros_coincidentes = $this->db->get_where('tablero_avisos', ['id_aviso' => $idAviso])->num_rows();

			if ($registros_coincidentes != 1) {
				if ($registros_coincidentes == 0) {
					$respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
				} elseif ($registros_coincidentes > 1) {
					$respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
				}
				return $respuesta;
			}

			$respuesta['err'] = !$this->db->update('tablero_avisos', $data, [
				'id_aviso' => $idAviso,
			]);

			if ($respuesta['err']) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
			} else {
				$respuesta['aviso'] = $this->listar($idAviso);
				$respuesta['msg'] = 'Información actualizada con éxito.';
			}
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
			die();
		}
		return $respuesta;
	}

	/*
    Alterna el estatus de publicacion del aviso
      $data => Información del registro a actualizar
	*/
	public function alternar_estatus_publicado($data)
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

			$idAviso = $data['id_aviso'];

			// Verificar cuantos registros serán actualizados
			$aviso = $this->db->get_where('tablero_avisos', [
				'id_aviso' => $idAviso,
			]);

			if ($aviso->num_rows() != 1) {
				if ($aviso->num_rows() == 0) {
					$respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
				} elseif ($aviso->num_rows() > 1) {
					$respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
				}
				return $respuesta;
			}

			$publicado = 0;
			// Si no se especifica valor para publicado, se alterna el valor
			if (!empty($data['publicado']) && intval($data['publicado'])) {
				$publicado = intval($data['publicado']) != 0 ? 1 : 0;
			} else {
				$publicado = !$aviso->row()->publicado;
			}

			$data = [
				'publicado' => $publicado,
				'fecha_publicacion' => $publicado == 1 ? date('Y-m-d H:i:s') : null,
				'fk_id_usuario_modifico' => $data['fk_id_usuario_modifico'],
				'fecha_modificacion' => date('Y-m-d H:i:s'),
			];

			// Almacenar información en BD
			$respuesta['err'] = !$this->db->update('tablero_avisos', $data, [
				'id_aviso' => $idAviso,
			]);

			if ($respuesta['err']) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
			} else {
				$respuesta['aviso'] = $this->listar($idAviso);
				$respuesta['msg'] = 'Estatus modificado con éxito.';
			}

			/* $respuesta['msg'] = $respuesta['err']
				? 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message']
				: 'Estatus modificado con éxito.'; */
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
			$idAviso = $data['id_aviso'];
			$data = [
				'estatus' => 0,
				'fk_id_usuario_modifico' => $data['id_usuario_modifico'],
				'fecha_modificacion' => date('Y-m-d H:i:s'),
			];

			// Validar que los campos existan en la tabla usuarios
			if (!validar_campos('tablero_avisos', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con respecto a la base de datos.';
				return $respuesta;
			}

			// Verificar cuantos registros serán actualizados
			$asamblea = $this->db->get_where('tablero_avisos', [
				'id_aviso' => $idAviso,
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
			// Actualizar datos en tablero_avisos
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if (!$this->db->update('tablero_avisos', $data, ['id_aviso' => $idAviso])) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $respuesta;
			}

			$respuesta['msg'] = 'Aviso eliminado con éxito.';
			$respuesta['error'] = false;
		} catch (Exception $e) {
			throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
			die();
		}
		return $respuesta;
	}
}

/* End of file Tablero_Avisos_model.php */
/* Location: ./application/models/Tablero_Avisos_model.php */
