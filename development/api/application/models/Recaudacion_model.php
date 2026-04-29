<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Modelo: Recaudaciones
 *
 * Este modelo realiza las operaciones requeridas sobre las Recaudaciones
 *
 * @package   CodeIgniter
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Recaudacion_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	/*
    Valida que se haya especificado número de referencia para el pago cuando este sea obligatorio
  */
	public function validar_numero_referencia($idFormaPago = 0, &$numeroReferencia = null)
	{
		$respuesta = [
			'err' => true,
			'msg' => null,
		];
		$formaPago = $this->db->get_where('cat_formas_pago', ['id_forma_pago' => $idFormaPago])->row_array();
		if (empty($formaPago)) {
			$respuesta['msg'] = 'Forma de pago no válida.';
			return $respuesta;
		}

		if ($formaPago['requiere_numero_referencia'] == 1) {
			if ($numeroReferencia == null) {
				$respuesta['msg'] = 'Debe especificar el número de referencia.';
				return $respuesta;
			}
		} else {
			$numeroReferencia = null;
		}

		$respuesta['err'] = false;
		return $respuesta;
	}

	/*
    Obtener información del identificador especificado.
	*/
	public function listar($idRecaudacion = 0, $idUsuarioPropietario = 0, $idCondominio = 0, $soloActivos = false)
	{
		try {
			if (!empty($idRecaudacion)) {
				// IFNULL(usc.nombre, "-- DESOCUPADA --") condomino,
				$this->db
					->select(
						'r.id_recaudacion,
              e.id_edificio,
              e.edificio,
              u.id_unidad,
              u.unidad,
              pu.id_perfil_usuario id_perfil_usuario_paga,
              pu.perfil_usuario perfil_usuario_paga,
              us.id_usuario id_usuario_paga,
              us.nombre usuario_paga,
              r.anio,
              r.mes,
              r.renta,
              r.agua,
              r.energia_electrica,
              r.gas,
              r.seguridad,
              r.servicios_publicos,
              r.otros_servicios,
              r.fecha_limite_pago,
              er.id_estatus_recaudacion,
              er.estatus_recaudacion,
              r.fecha_pago,
              r.fk_id_forma_pago id_forma_pago,
              r.numero_referencia,
              r.notas,
              r.estatus,
              (r.renta + r.agua + r.energia_electrica + r.gas + r.seguridad + r.servicios_publicos + r.otros_servicios) total'
					)
					->join('unidades u', 'u.id_unidad = r.fk_id_unidad')
					->join('edificios e', 'e.id_edificio = u.fk_id_edificio')
					->join('cat_perfiles_usuarios pu', 'pu.id_perfil_usuario = r.fk_id_perfil_usuario_paga')
					->join('usuarios us', 'us.id_usuario = r.fk_id_usuario_paga')
					->join('cat_estatus_recaudacion er', 'er.id_estatus_recaudacion = r.fk_id_estatus_recaudacion')
					/* ->join('cat_perfiles_usuarios pu', 'pu.id_perfil_usuario = r.fk_id_perfil_usuario_paga')
					->join('cat_estatus_recaudacion er', 'er.id_estatus_recaudacion = r.fk_id_estatus_recaudacion')
					->join('condominos_contratos cc', 'cc.fk_id_unidad = u.id_unidad AND cc.estatus = 1', 'left')
					->join('usuarios usc', 'usc.id_usuario = cc.fk_id_usuario', 'left') */
					->where([
						'r.id_recaudacion' => $idRecaudacion,
					]);
			} else {
				if (empty($idUsuarioPropietario) && empty($idCondominio)) {
					return false;
				}
				$this->db
					->select(
						'r.id_recaudacion,
              pu.id_perfil_usuario id_perfil_usuario_paga,
              pu.perfil_usuario perfil_usuario_paga,
              us.id_usuario id_usuario_paga,
              us.nombre usuario_paga,
              e.id_edificio,
              e.edificio,
              u.id_unidad,
              u.unidad,
              r.anio,
              r.mes,
              er.id_estatus_recaudacion,
              er.estatus_recaudacion,
              (r.renta + r.agua + r.energia_electrica + r.gas + r.seguridad + r.servicios_publicos + r.otros_servicios) total,
              r.estatus'
					)
					->join('unidades u', 'u.id_unidad = r.fk_id_unidad')
					->join('edificios e', 'e.id_edificio = u.fk_id_edificio AND e.fk_id_condominio = ' . $idCondominio)
					->join('cat_perfiles_usuarios pu', 'pu.id_perfil_usuario = r.fk_id_perfil_usuario_paga')
					->join('usuarios us', 'us.id_usuario = r.fk_id_usuario_paga')
					->join('cat_estatus_recaudacion er', 'er.id_estatus_recaudacion = r.fk_id_estatus_recaudacion');
				/* ->join('unidades_propietarios up', 'up.fk_id_unidad = u.id_unidad')
					->join('usuarios usp', 'usp.id_usuario = up.fk_id_usuario')
					->join('cat_perfiles_usuarios pup', 'pup.id_perfil_usuario = usp.fk_id_perfil_usuario')
					->join('cat_estatus_recaudacion er', 'er.id_estatus_recaudacion = r.fk_id_estatus_recaudacion')
					->join('condominos_contratos cc', 'cc.fk_id_unidad = u.id_unidad AND cc.estatus = 1', 'left')
					->join('usuarios usc', 'usc.id_usuario = cc.fk_id_usuario', 'left')
					->join('cat_perfiles_usuarios puc', 'puc.id_perfil_usuario = usc.fk_id_perfil_usuario', 'left'); */
				if ($idUsuarioPropietario > 0) {
					$this->db->where(['r.fk_id_usuario_paga' => $idUsuarioPropietario]);
				}
				if ($soloActivos || $idUsuarioPropietario > 0) {
					$this->db->where(['r.estatus' => 1]);
				}
			}
			/* echo $this->db->get_compiled_select();
			 exit(); */
			$result = !empty($idRecaudacion)
				? $this->db->get('recaudaciones r')->row_array()
				: $this->db->get('recaudaciones r')->result_array();
			// $result = $this->db->get('recaudaciones r')->result_array();

			return $result;
		} catch (Exception $e) {
			throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
			die();
		}
	}

	/*
    Obtener información para el recibo de pago.
	*/
	public function listar_recibo_pago($idRecaudacion = 0)
	{
		if (empty($idRecaudacion)) {
			return false;
		}
		try {
			/*
        IFNULL(usc.nombre, usp.nombre) destinatario_nombre,
        IFNULL(usc.telefono,usp.telefono) destinatario_telefono,
        IFNULL(usc.email, usp.email) destinatario_email,
      */

			$this->db
				->select(
					'r.id_recaudacion,
              CAST(r.fecha_registro AS DATE) fecha_registro,
              r.fecha_pago,
              r.anio,
              r.mes,
              c.condominio,
              c.domicilio,
              c.telefono,
              c.email,
              us.nombre destinatario_nombre,
              e.edificio,
              u.unidad,
              us.telefono destinatario_telefono,
              us.email destinatario_email,
              r.renta,
              r.agua,
              r.energia_electrica,
              r.gas,
              r.seguridad,
              r.servicios_publicos,
              r.otros_servicios,
              (r.renta + r.agua + r.energia_electrica + r.gas + r.seguridad + r.servicios_publicos + r.otros_servicios) total,
              er.id_estatus_recaudacion,
              er.estatus_recaudacion,
              fp.forma_pago,
              r.notas,
              r.numero_referencia'
				)
				->join('unidades u', 'u.id_unidad = r.fk_id_unidad')
				->join('edificios e', 'e.id_edificio = u.fk_id_edificio')
				->join('condominios c', 'c.id_condominio = e.fk_id_condominio')
				->join('usuarios us', 'us.id_usuario = r.fk_id_usuario_paga')
				->join('cat_estatus_recaudacion er', 'er.id_estatus_recaudacion = r.fk_id_estatus_recaudacion')
				->join('cat_formas_pago fp', 'fp.id_forma_pago = r.fk_id_forma_pago', 'left')

				/*
				->join('unidades_propietarios up', 'up.fk_id_unidad = u.id_unidad')
				->join('usuarios usp', 'usp.id_usuario = up.fk_id_usuario')
				->join('cat_estatus_recaudacion er', 'er.id_estatus_recaudacion = r.fk_id_estatus_recaudacion')
				->join('condominos_contratos cc', 'cc.fk_id_unidad = u.id_unidad AND cc.estatus = 1', 'left')
				->join('usuarios usc', 'usc.id_usuario = cc.fk_id_usuario', 'left')
        */
				->where([
					'r.id_recaudacion' => $idRecaudacion,
				]);
			return $this->db->get('recaudaciones r')->row_array();
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
			if (!validar_campos('recaudaciones', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con la base de datos.';
				return $respuesta;
			}

			// Verificar si ya existe una recaudación para unidad/perfil_usuario/año/mes
			if (
				$this->db
					->get_where('recaudaciones', [
						'fk_id_unidad' => $data['fk_id_unidad'],
						'fk_id_perfil_usuario_paga' => $data['fk_id_perfil_usuario_paga'],
						'anio' => $data['anio'],
						'mes' => $data['mes'],
						'estatus' => 1,
					])
					->num_rows() != 0
			) {
				$respuesta['msg'] =
					'Ya existe una recaudación para la unidad, perfil usuario que paga, año y mes especificados.';
				return $respuesta;
			}

			// Valida si el número de serie es requerido y especificado
			if ($data['fk_id_estatus_recaudacion'] == 3) {
				$validacionNumeroReferencia = $this->validar_numero_referencia(
					$data['fk_id_forma_pago'],
					$data['numero_referencia']
				);
				if ($validacionNumeroReferencia['err']) {
					return $validacionNumeroReferencia;
				}
			}

			// Almacenar información en BD
			$respuesta['err'] = !$this->db->insert('recaudaciones', $data);
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if ($respuesta['err']) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $respuesta;
			}

			$respuesta['msg'] = 'Información registrada con éxito.';
			$respuesta['recaudacion'] = $this->listar($this->db->insert_id());
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
			$idRecaudacion = $data['id_recaudacion'];
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			unset($data['id_recaudacion']);

			// Validar que los campos existan en la tabla
			if (!validar_campos('recaudaciones', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con la base de datos.';
				return $respuesta;
			}

			// Verificar si ya existe otra recaudación para unidad/perfil_usuario/año/mes
			if (
				$this->db
					->get_where('recaudaciones', [
						'id_recaudacion !=' => $idRecaudacion,
						'fk_id_unidad' => $data['fk_id_unidad'],
						'fk_id_perfil_usuario_paga' => $data['fk_id_perfil_usuario_paga'],
						'anio' => $data['anio'],
						'mes' => $data['mes'],
						'estatus' => 1,
					])
					->num_rows() != 0
			) {
				$respuesta['msg'] =
					'Ya existe otra recaudación para la unidad, perfil usuario que paga, año y mes especificados.';
				return $respuesta;
			}

			// Verificar cuantos registros serán actualizados
			$registrosEncontrados = $this->db->get_where('recaudaciones', ['id_recaudacion' => $idRecaudacion])->num_rows();

			if ($registrosEncontrados != 1) {
				if ($registrosEncontrados == 0) {
					$respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
				} elseif ($registrosEncontrados > 1) {
					$respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
				}
				return $respuesta;
			}

			/* if ($data['id_estatus_recaudacion'] == 2) {
				$formaPago = $this->db
					->get_where('cat_formas_pago', ['id_forma_pago' => $data['fk_id_forma_pago']])
					->row_array();
				if (empty($formaPago)) {
					$respuesta['msg'] = 'Forma de pago no válida.';
					return $respuesta;
				}

				if ($formaPago['requiere_numero_referencia'] == 1 && $data['numero_referencia'] == null) {
					$respuesta['msg'] = 'Faltó especificar el número de referencia.';
					return $respuesta;
				}
			} */

			// Valida si el número de serie es requerido y especificado
			if ($data['fk_id_estatus_recaudacion'] == 3) {
				$validacionNumeroReferencia = $this->validar_numero_referencia(
					$data['fk_id_forma_pago'],
					$data['numero_referencia']
				);
				if ($validacionNumeroReferencia['err']) {
					return $validacionNumeroReferencia;
				}
			}

			// Almacenar información en BD
			$respuesta['err'] = !$this->db->update('recaudaciones', $data, [
				'id_recaudacion' => $idRecaudacion,
			]);
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if ($respuesta['err']) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $respuesta;
			}

			$respuesta['msg'] = 'Información registrada con éxito.';
			$respuesta['recaudacion'] = $this->listar($idRecaudacion);
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
	public function registrar_pago($data)
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
			$idRecaudacion = $data['id_recaudacion'];
			$dataRecaudacion = [
				'fk_id_estatus_recaudacion' => 3,
				'fecha_pago' => $data['fecha_pago'],
				'fk_id_forma_pago' => $data['id_forma_pago'],
				'numero_referencia' => $data['numero_referencia'],
				'fk_id_usuario_modifico' => $data['id_usuario_modifico'],
				'fecha_modificacion' => date('Y-m-d H:i:s'),
			];

			// Validar que los campos existan en la tabla condominos_contratos
			if (!validar_campos('recaudaciones', $dataRecaudacion)) {
				$respuesta['msg'] = 'Error de integridad de la información con respecto a la base de datos.';
				return $respuesta;
			}

			// Verificar cuantos registros serán actualizados
			$recaudacion = $this->db->get_where('recaudaciones', [
				'id_recaudacion' => $idRecaudacion,
				'estatus' => 1,
			]);

			if ($recaudacion->num_rows() != 1) {
				if ($recaudacion->num_rows() == 0) {
					$respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
				} elseif ($recaudacion->num_rows() > 1) {
					$respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
				}
				return $respuesta;
			}

			// Valida si el número de serie es requerido y especificado
			$validacionNumeroReferencia = $this->validar_numero_referencia(
				$data['id_forma_pago'],
				$data['numero_referencia']
			);
			if ($validacionNumeroReferencia['err']) {
				return $validacionNumeroReferencia;
			}

			// Almacenar información en BD
			// Inicializar la transaccion
			$this->db->trans_start();
			$this->db->trans_strict(false);

			// Actualizar datos en recaudaciones
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if (
				!$this->db->update('recaudaciones', $dataRecaudacion, [
					'id_recaudacion' => $idRecaudacion,
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

			$respuesta['recaudacion'] = $this->listar($idRecaudacion);
			$respuesta['msg'] = 'Pago registrado con éxito.';
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
			$idRecaudacion = $data['id_recaudacion'];
			$data = [
				'estatus' => 0,
				'fk_id_usuario_modifico' => $data['id_usuario_modifico'],
				'fecha_modificacion' => date('Y-m-d H:i:s'),
			];

			// Validar que los campos existan en la tabla usuarios
			if (!validar_campos('recaudaciones', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con respecto a la base de datos.';
				return $respuesta;
			}

			// Verificar cuantos registros serán actualizados
			$recaudacion = $this->db->get_where('recaudaciones', [
				'id_recaudacion' => $idRecaudacion,
				'estatus' => 1,
			]);

			if ($recaudacion->num_rows() != 1) {
				if ($recaudacion->num_rows() == 0) {
					$respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
				} elseif ($recaudacion->num_rows() > 1) {
					$respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
				}
				return $respuesta;
			}

			// Almacenar información en BD
			// Inicializar la transaccion
			$this->db->trans_start();
			$this->db->trans_strict(false);

			// Actualizar datos en recaudaciones
			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if (!$this->db->update('recaudaciones', $data, ['id_recaudacion' => $idRecaudacion])) {
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

			$respuesta['msg'] = 'Recaudación eliminada con éxito.';
			$respuesta['error'] = false;
		} catch (Exception $e) {
			throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
			die();
		}
		return $respuesta;
	}
}

/* End of file Recaudacion_model.php */
/* Location: ./application/models/Recaudacion_model.php */
