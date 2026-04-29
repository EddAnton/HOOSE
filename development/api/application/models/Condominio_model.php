<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Modelo: Condominio
 *
 * Este modelo realiza las operaciones requeridas sobre la información de los Condominios
 *
 * @package   CodeIgniter
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Condominio_model extends CI_Model
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
		// REPLACE(REPLACE(c.domicilio, "\r\n", "<br>"), "\n", "<br>") domicilio,
		try {
			$this->db->select(
				'c.id_condominio,
          c.condominio,
          c.email,
          c.telefono,
          c.domicilio,
          c.telefono_guardia,
          c.telefono_secretaria,
          c.telefono_moderador,
          c.anio_construccion,
          c.imagen,
          c.estatus'
			);

			if ($id > 0) {
				$this->db
					->select(
						'c.constructora,
            c.constructora_telefono,
            c.constructora_domicilio,
            c.reglamento'
					)
					->where(['c.id_condominio' => $id]);
			}
			if ($soloActivos) {
				$this->db->where(['c.estatus' => 1]);
			}

			$respuesta = $this->db->order_by('c.condominio')->get('condominios c');

			return $id == 0 ? $respuesta->result_array() : $respuesta->row_array();
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

			// Obtener identificador para la imagen del condominio
			$archivo_imagen = !empty($data['archivo_imagen']) ? $data['archivo_imagen'] : null;
			unset($data['archivo_imagen']);

			// Validar que los campos existan en la tabla
			if (!validar_campos('condominios', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con la base de datos.';
				return $respuesta;
			}

			// Verificar si el email ya se encuentra registrado en otro condominio
			if (!empty($data['email'])) {
				if (
					$this->db
						->get_where('condominios', [
							'email' => $data['email'],
							'estatus' => 1,
						])
						->num_rows() != 0
				) {
					$respuesta['msg'] = 'El Email ya se encuentra registrado.';
					return $respuesta;
				}
			}

			// Almacenar información en BD
			// Inicializar la transaccion
			$this->db->trans_start();
			$this->db->trans_strict(false);

			// Si existe error al almacenar el registro, obtiene descripción del mismo y aborta el proceso
			if (!$this->db->insert('condominios', $data)) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				$this->db->trans_rollback();
				return $respuesta;
			}

			$nuevoID = $this->db->insert_id();
			$dataCloudCarpeta = [
				'fk_id_condominio' => $nuevoID,
				'fk_id_usuario_registro' => $data['fk_id_usuario_registro'],
			];
			// Crea la carpeta raíz en la nube de archivos
			if (!$this->db->insert('cloud_carpetas', $dataCloudCarpeta)) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				$this->db->trans_rollback();
				return $respuesta;
			}

			$respuesta['msg'] = 'Información registrada con éxito';

			/*
			  Si se especificó imagen del condominio:
          - Intenta la carga del archivo
				  - Si no ocurrió algún error, establece actualizar el campo imagen
      */
			if (!empty($archivo_imagen)) {
				$ruta_cargar_archivos = PATH_ARCHIVOS_CONDOMINIOS . '/' . $nuevoID . '/';
				$cargar_archivo = subir_imagen($ruta_cargar_archivos, $archivo_imagen, 'img');

				// Si no se pudo cargar el archivo con la imagen, obtiene el mensaje de error para retornarlo
				if ($cargar_archivo['error']) {
					$respuesta['msg'] .=
						', pero no se pudo almacenar la imagen del condominio.' . PHP_EOL . ' - ' . $cargar_archivo['msg'];
				} else {
					// Archivo cargado con éxito, establece en el campo imagen el nombre del archivo cargado
					if (
						!$this->db->update(
							'condominios',
							['imagen' => $cargar_archivo['archivo_servidor']],
							['id_condominio' => $nuevoID]
						)
					) {
						/*
              Si ocurrió error al actualizar la información, borra el archivo cargado y
              obtiene el mensaje de error para retornarlo
            */
						unlink($cargar_archivo['ruta_archivo'] . $cargar_archivo['archivo_servidor']);
						$respuesta['msg'] .=
							', pero no se pudo actualizar información de la imagen del condominio.' .
							PHP_EOL .
							' - ' .
							'Código: ' .
							$this->db->error()['code'] .
							' - ' .
							$this->db->error()['message'];
						$this->db->trans_rollback();
					}
				}
			}

			// Confirmar la transacción
			$this->db->trans_complete();
			// Confirmar la transacción y verificar el estatus de la misma
			if ($this->db->trans_status() === false) {
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
				return $respuesta;
			}

			$respuesta['err'] = false;
			$respuesta['msg'] .= '.';
			$respuesta['condominio'] = $this->listar($nuevoID);
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
			$idCondominio = $data['id_condominio'];
			$archivo_imagen = !empty($data['archivo_imagen']) ? $data['archivo_imagen'] : null;
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			unset($data['id_condominio']);
			unset($data['archivo_imagen']);

			// Validar que los campos existan en la tabla
			if (!validar_campos('condominios', $data)) {
				$respuesta['msg'] = 'Error de integridad de la información con la base de datos.';
				return $respuesta;
			}

			// Verificar si el email ya se encuentra registrado en otro condominio
			if (!empty($data['email'])) {
				if (
					$this->db
						->get_where('condominios', [
							'email' => $data['email'],
							'id_condominio !=' => $idCondominio,
							'estatus' => 1,
						])
						->num_rows() != 0
				) {
					$respuesta['msg'] = 'El Email ya se encuentra registrado.';
					return $respuesta;
				}
			}

			// Verificar cuantos registros serán actualizados
			$condominio = $this->db->select('imagen')->get_where('condominios', ['id_condominio' => $idCondominio]);

			if ($condominio->num_rows() != 1) {
				if ($condominio->num_rows() == 0) {
					$respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
				} elseif ($condominio->num_rows() > 1) {
					$respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
				}
				return $respuesta;
			}

			// Si se especificó imagen de condominio, carga el archivo
			$ruta_cargar_archivos = PATH_ARCHIVOS_CONDOMINIOS . '/' . $idCondominio . '/';
			$imagen_actual = $condominio->row()->imagen;
			$archivo_cargado = false;
			if (!empty($archivo_imagen)) {
				// $imagen_actual = $condominio->row()->imagen;
				$cargar_archivo = subir_imagen($ruta_cargar_archivos, $archivo_imagen, 'img');

				if ($cargar_archivo['error']) {
					$respuesta = $cargar_archivo;
					return $respuesta;
				}
				$archivo_cargado = true;
				$data['imagen'] = $cargar_archivo['archivo_servidor'];
			} elseif ($imagen_actual != '') {
				$archivo_cargado = true;
				$data['imagen'] = null;
			}

			$respuesta['err'] = !$this->db->update('condominios', $data, [
				'id_condominio' => $idCondominio,
			]);

			if ($respuesta['err']) {
				unlink($cargar_archivo['ruta_archivo'] . $cargar_archivo['archivo_servidor']);
				$respuesta['msg'] = 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message'];
			} else {
				if ($archivo_cargado && !empty($imagen_actual)) {
					unlink($ruta_cargar_archivos . $imagen_actual);
				}
				$respuesta['condominio'] = $this->listar($idCondominio);
				$respuesta['msg'] = 'Información actualizada con éxito.';
			}
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

			$idCondominio = $data['id_condominio'];

			// Verificar cuantos registros serán actualizados
			$condominio = $this->db->get_where('condominios', [
				'id_condominio' => $idCondominio,
			]);

			if ($condominio->num_rows() != 1) {
				if ($condominio->num_rows() == 0) {
					$respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
				} elseif ($condominio->num_rows() > 1) {
					$respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
				}
				return $respuesta;
			}

			$estatus = 0;
			// Si no se especifica valor para estatus, se alterna el valor
			if (!empty($data['estatus']) && intval($data['estatus'])) {
				$estatus = intval($data['estatus']) != 0 ? 1 : 0;
			} else {
				$estatus = !$condominio->row()->estatus;
			}

			$data = [
				'estatus' => $estatus,
				'fk_id_usuario_modifico' => $data['fk_id_usuario_modifico'],
				'fecha_modificacion' => date('Y-m-d H:i:s'),
			];

			// Almacenar información en BD
			$respuesta['err'] = !$this->db->update('condominios', $data, [
				'id_condominio' => $idCondominio,
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
}

/* End of file Condominio_model.php */
/* Location: ./application/models/Condominio_model.php */
