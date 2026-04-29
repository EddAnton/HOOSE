<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Modelo: Usuario
 *
 * Este modelo realiza las operaciones necesarias para el almacenamiento de la información de
 * los Usuarios
 *
 * @package   CodeIgniter
 * @author    Carlos Alberto Malpica Gómez <cmalpicag@gmail.com>
 *
 */

class Usuario_model extends CI_Model
{
  public function __construct()
  {
    parent::__construct();
    $this->load->helper('File_Upload');
  }

  /*
     Valida que no exista un usuario registrado según los criterios que apliquen
       $email => Correo electrónico
       $idCondominio => ID del condominio
   */
  public function validar_existe_usuario($data)
  {
    $response = [
      'err' => true,
      'msg' => null,
    ];

    // Error si $data es vacía
    if (empty($data)) {
      $response['msg'] = 'Debe especificar información requerida.';
      return $response;
    }

    // Error si no se especifica usuario
    if (empty($data['usuario'])) {
      $response['msg'] = 'No se ha especificado el usuario.';
      return $response;
    }
    // Error si no se especifica email
    if (empty($data['email']) || !emailValido($data['email'])) {
      $response['msg'] = 'No se ha especificado el email o éste no es válido.';
      return $response;
    }
    // Error si no se especifica id_condominio
    if (empty($data['id_condominio']) || !intval($data['id_condominio']) || intval($data['id_condominio']) < 0) {
      $response['msg'] = 'No se ha especificado el email o éste no es válido.';
      return $response;
    }

    try {
      // Obtiene registro del usuario
      $registrosEncontrados = $this->db
        ->group_start()
        ->where(['usuario' => $data['usuario']])
        ->or_group_start()
        ->where(['email' => $data['email'], 'fk_id_condominio' => $data['id_condominio']])
        ->group_end()
        ->group_end()
        // ->get_where('usuarios u', ['estatus' => 1])
        ->get('usuarios u')
        ->num_rows();

      // No se encontraron coincidencias
      if ($registrosEncontrados > 0) {
        $response['msg'] = 'Ya existe un registro para el usuario o email especificados.';
        return $response;
      }

      $response['err'] = false;
    } catch (Exception $e) {
      throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
      die();
    }
    return $response;
  }

  /*
     Valida el inicio de sesión
     $datos =>
       Información para el inicio de sesión
   */
  public function iniciar_sesion($data)
  {
    $response = [
      'err' => true,
      'msg' => null,
    ];

    // Validar que sea proporcionada la información requerida el inicio de sesión
    if (empty($data)) {
      $response['msg'] = 'Debe especificar información requerida.';
      return $response;
    }

    try {
      // Obtiene registro del usuario
      $usuario = $this->db
        ->select(
          'u.id_usuario,
            u.usuario,
            u.nombre,
            u.email,
            u.telefono,
            pu.id_perfil_usuario,
            pu.perfil_usuario,
            IFNULL(c.id_condominio, 0) id_condominio_usuario,
            c.condominio condominio_usuario,
            pu.tiene_tablero_avisos,
            IF(u.imagen_archivo IS NOT NULL,
            CONCAT("' .
          PATH_ARCHIVOS_USUARIOS .
          '/", u.id_usuario, "/", u.imagen_archivo),
            NULL) imagen_archivo,
            u.debe_cambiar_contrasenia,
            u.estatus'
        )
        ->join('cat_perfiles_usuarios pu', 'pu.id_perfil_usuario = u.fk_id_perfil_usuario')
        ->join('condominios c', 'c.id_condominio = u.fk_id_condominio', 'left')
        ->where([
          'u.usuario' => $data['usuario'],
          'u.contrasenia' => $data['contrasenia'],
        ])
        ->get('usuarios u')
        ->row();

      // No se encontraron coincidencias
      if ($usuario == null) {
        $response['msg'] = 'Usuario y/o contraseña no válidos.';
        return $response;
      }
      // Usuario no activo
      if ($usuario->estatus != 1) {
        $response['msg'] = 'Usuario no activo. Solicita soporte.';
        return $response;
      }
      unset($usuario->estatus);
      // Obtener datos específicos del tipo de miembro
      $dataPerfil = null;
      switch ($usuario->id_perfil_usuario) {
        case 3:
          $dataPerfil = $this->db
            ->select(
              'tm.id_tipo_miembro,
                tm.tipo_miembro,
                tm.es_colaborador,
                cc.fecha_inicio,
                cc.fecha_fin'
            )
            ->join('cat_tipos_miembros tm', 'tm.id_tipo_miembro = cc.fk_id_tipo_miembro')
            ->get_where('colaboradores_contratos cc', ['cc.fk_id_usuario' => $usuario->id_usuario])
            ->row();
          // No se encontró tipo de miembro o el contrato no se encuentra vigente
          if (
            $dataPerfil == null ||
            date('Y-m-d', strtotime($dataPerfil->fecha_inicio)) > date('Y-m-d') ||
            (!empty($dataPerfil->fecha_fin) && date('Y-m-d', strtotime($dataPerfil->fecha_fin)) < date('Y-m-d'))
          ) {
            $response['msg'] = 'Colaborador sin contrato o contrato sin vigencia.';
            return $response;
          }
          break;
          unset($dataPerfil->fecha_inicio);
          unset($dataPerfil->fecha_fin);
          unset($dataPerfil->estatus);
      }

      $response['usuario'] = (object) array_merge((array) $usuario, !empty($dataPerfil) ? (array) $dataPerfil : []);
      $response['err'] = false;
      $response['msg'] = 'Inicio de sesión válido.';
    } catch (Exception $e) {
      throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
      die();
    }
    return $response;
  }

  /*
     Obtener listado de registros según el ID del perfil de usuario
       $idPerfilUsuario => ID del perfil del usuario
       $idCondominio => ID del condominio
       $soloActivos => Determinar si se requieren todos los registros o sólo los activos
   */
  public function listar_perfil(
    $idPerfilUsuario = 0,
    $idCondominio = 0,
    $soloActivos = false,
    $idUsuarioPropietario = 0
  ) {
    try {
      // Si no se proporciona ID del perfil ni ID condominio, aborta el proceso
      if (empty($idPerfilUsuario) || empty($idCondominio)) {
        return false;
      }

      $this->db
        ->select(
          'u.id_usuario,
            u.nombre,
            u.usuario,
            u.email,
            u.telefono,
            u.domicilio,
            u.imagen_archivo imagen,
            pu.id_perfil_usuario,
            pu.perfil_usuario,
            u.estatus'
        )
        ->join('cat_perfiles_usuarios pu', 'pu.id_perfil_usuario = u.fk_id_perfil_usuario')
        ->where(['u.fk_id_perfil_usuario' => $idPerfilUsuario, 'u.fk_id_condominio' => $idCondominio]);

      switch ($idPerfilUsuario) {
        /* case PERFIL_USUARIO_PROPIETARIO:
              $this->db->select(
                'u.usuario,
                  u.identificacion_folio,
                  u.identificacion_domicilio,
                  u.identificacion_anverso_archivo,
                  u.identificacion_reverso_archivo'
              );
              break; */

        /* case PERFIL_USUARIO_CONDOMINO:
              $this->db
                ->select(
                  'CONCAT(un.unidad, " (", e.edificio, ")") unidad_edificio,
                    cc.id_condomino_contrato,
                    cc.deposito,
                    cc.renta,
                    cc.estatus contrato_activo'
                  // CASE WHEN cc.fecha_fin IS NULL OR cc.fecha_fin < NOW() THEN 1 ELSE 0 END renta_activa'
                )
                ->join('condominos_contratos cc', 'cc.fk_id_usuario = u.id_usuario')
                ->join('unidades un', 'un.id_unidad = cc.fk_id_unidad')
                ->join('edificios e', 'e.id_edificio = un.fk_id_edificio');
              break; */

        case PERFIL_USUARIO_COLABORADOR:
          /*
               IF(cc.contrato_archivo IS NOT NULL,
                 CONCAT("' .
                 PATH_ARCHIVOS_USUARIOS .
                 '/", u.id_usuario, "/", cc.contrato_archivo),
                 NULL) contrato_archivo,
               */
          /*
                 cc.contrato_archivo,
                 tm.id_tipo_miembro,
                     cc.fecha_fin,
               */
          $this->db
            ->select(
              'tm.tipo_miembro,
                cc.fecha_inicio,
                cc.salario'
            )
            ->join('colaboradores_contratos cc', 'cc.fk_id_usuario = u.id_usuario')
            ->join('cat_tipos_miembros tm', 'tm.id_tipo_miembro = cc.fk_id_tipo_miembro');
          break;
      }

      if ($idUsuarioPropietario > 0) {
        $this->db
          ->join('condominos_contratos cc', 'cc.fk_id_usuario = u.id_usuario AND cc.estatus = 1')
          ->join(
            'unidades_propietarios up',
            'up.fk_id_unidad = cc.fk_id_unidad AND up.fk_id_usuario = ' . $idUsuarioPropietario . ' AND up.estatus = 1'
          );
      }

      if ($soloActivos || $idUsuarioPropietario > 0) {
        $this->db->where(['u.estatus' => 1]);
      }

      $response = $this->db
        ->order_by('u.nombre')
        ->get('usuarios u')
        ->result_array();
      /* ->get_compiled_select('usuarios u');
         echo $response;
         exit(); */

      if (!empty($response)) {
        switch ($idPerfilUsuario) {
          case PERFIL_USUARIO_PROPIETARIO:
            foreach ($response as &$propietario) {
              /*
                     IF(u.escrituras_archivo IS NOT NULL,
                     CONCAT("' .
                     PATH_ARCHIVOS_UNIDADES .
                     '/", u.id_unidad, "/", u.escrituras_archivo),
                     NULL) escrituras_archivo'
                     */
              $propietario['unidades'] = $this->db
                ->select('CONCAT("- ", u.unidad, " (", e.edificio, ")") unidad')
                ->join(
                  'unidades_propietarios up',
                  'up.fk_id_usuario = ' . $propietario['id_usuario'] . ' AND up.fk_id_unidad = u.id_unidad'
                )
                ->join('edificios e', 'e.id_edificio = u.fk_id_edificio')
                ->where([
                  'up.fk_id_usuario' => $propietario['id_usuario'],
                ])
                ->order_by('e.edificio, u.unidad')
                ->get('unidades u')
                ->result_array();
            }
            break;
          case PERFIL_USUARIO_CONDOMINO:
            foreach ($response as &$condomino) {
              /* $condomino['unidad'] = $this->db
                       ->select(
                         'u.id_unidad,
                         CONCAT(u.unidad, " (", e.edificio, ")") unidad_edificio,
                         uc.deposito,
                         uc.renta,
                         uc.fecha_inicio,
                         uc.fecha_fin,
                         IF(u.escrituras_archivo IS NOT NULL,
                         CONCAT("' .
                           PATH_ARCHIVOS_UNIDADES .
                           '/", u.id_unidad, "/", u.escrituras_archivo),
                         NULL) escrituras_archivo,
                         IF(uc.contrato_archivo IS NOT NULL,
                         CONCAT("' .
                           PATH_ARCHIVOS_USUARIOS .
                           '/' .
                           $condomino['id_usuario'] .
                           '/unidad_", u.id_unidad, "/", uc.contrato_archivo),
                         NULL) contrato_archivo,
                         uc.estatus'
                       )
                       ->join('unidades u', 'u.id_unidad = uc.fk_id_unidad')
                       ->join('edificios e', 'e.id_edificio = u.fk_id_edificio')
                       ->get('unidades_condominos uc', ['uc.fk_id_usuario' => $condomino['id_usuario']])
                       ->result_array(); */
              $unidad = $this->db
                ->select(
                  'CONCAT(un.unidad, " (", e.edificio, ")") unidad_edificio,
                    cc.id_condomino_contrato,
                    cc.deposito,
                    cc.renta,
                    cc.estatus contrato_activo'
                )
                ->join('unidades un', 'un.id_unidad = cc.fk_id_unidad')
                ->join('edificios e', 'e.id_edificio = un.fk_id_edificio')
                ->order_by('cc.id_condomino_contrato DESC')
                ->get_where('condominos_contratos cc', ['cc.fk_id_usuario' => $condomino['id_usuario']])
                ->row_array();
              $condomino = array_merge($condomino, $unidad);
            }
            break;
        }
      }

      return $response;
    } catch (Exception $e) {
      throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
      die();
    }
  }

  /*
     Obtener listado de registros según el ID del perfil de usuario
       $idPerfilUsuario => ID del perfil del usuario
       $idCondominio => ID del condominio
       $soloActivos => Determinar si se requieren todos los registros o sólo los activos
   */
  public function listar_perfiles_usuarios_tablero_avisos()
  {
    try {
      return $this->db
        ->select(
          'id_perfil_usuario,
            perfil_usuario'
        )
        ->order_by('perfil_usuario')
        ->get_where('cat_perfiles_usuarios', ['tiene_tablero_avisos' => 1, 'estatus' => 1])
        ->result_array();
    } catch (Exception $e) {
      throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
      die();
    }
  }

  /*
    Obtener información del identificador especificado o todos los registros.
      $id => ID especifico a obtener, si se requiere
      $soloActivos => Determinar si se requieren todos los registros o sólo los activos
  */
  public function listar_usuarios_acta_asamblea($idCondominio = 0)
  {
    try {
      // Si no se proporciona ID condominio, aborta el proceso
      if (empty($idCondominio)) {
        return false;
      }

      return $this->db
        ->select(
          'IFNULL(usc.id_usuario, usp.id_usuario) id_usuario,
            IFNULL(usc.nombre, usp.nombre) usuario,
            pu.perfil_usuario perfil,
            CONCAT(e.edificio, " · ", u.unidad) unidad'
        )
        ->join('edificios e', 'e.id_edificio = u.fk_id_edificio AND e.estatus = 1')
        ->join('unidades_propietarios up', 'up.fk_id_unidad = u.id_unidad')
        ->join('usuarios usp', 'usp.id_usuario = up.fk_id_usuario AND usp.estatus = 1')
        ->join('condominos_contratos cc', 'cc.fk_id_unidad = u.id_unidad AND cc.estatus = 1', 'left')
        ->join('usuarios usc', 'usc.id_usuario = cc.fk_id_usuario AND usc.estatus = 1', 'left')
        ->join('cat_perfiles_usuarios pu', 'pu.id_perfil_usuario = IFNULL(usc.fk_id_perfil_usuario, usp.fk_id_perfil_usuario)')
        ->where(['e.fk_id_condominio' => $idCondominio, 'u.estatus' => 1])
        ->order_by('pu.id_perfil_usuario, usuario')
        ->get('unidades u')
        ->result_array();
    } catch (Exception $e) {
      throw new Exception(extraerErrorDesdeJSON($e->getMessage()));
      die();
    }
  }

  /* 
   Obtener el expediente con los documentos del personal
   */
  public function obtener_imagen($idUsuario = 0, $campo = null)
  {
    $respuesta = [
      'err' => true,
      'msg' => 'Ocurrió un error al obtener el archivo con la imagen.',
    ];

    try {
      // Validar que la información a insertar sea proporcionada
      if (empty($idUsuario) || empty($campo)) {
        $respuesta['msg'] = 'Debe especificar un identificador válido.';
        return $respuesta;
      }

      // Verificar cuantos registros serán actualizados
      $respuesta['archivo'] = $this->db
        ->select($campo . '_archivo archivo')
        ->get_where('usuarios', ['id_usuario' => $idUsuario])
        ->row_array()['archivo'];
      $respuesta['err'] = false;
      $respuesta['msg'] = 'Archivo obtenido con éxito.';
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

      $idUsuario = $data['id_usuario'];

      // Verificar cuantos registros serán actualizados
      $usuario = $this->db->get_where('usuarios', [
        'id_usuario' => $idUsuario,
      ]);

      if ($usuario->num_rows() != 1) {
        if ($usuario->num_rows() == 0) {
          $respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
        } elseif ($usuario->num_rows() > 1) {
          $respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
        }
        return $respuesta;
      }

      $estatus = 0;
      // Si no se especifica valor para estatus, se alterna el valor
      if (!empty($data['estatus']) && intval($data['estatus'])) {
        $estatus = intval($data['estatus']) != 0 ? 1 : 0;
      } else {
        $estatus = !$usuario->row()->estatus;
      }

      $data = [
        'estatus' => $estatus,
        'fk_id_usuario_modifico' => $data['fk_id_usuario_modifico'],
        'fecha_modificacion' => date('Y-m-d H:i:s'),
      ];

      // Almacenar información en BD
      $respuesta['err'] = !$this->db->update('usuarios', $data, [
        'id_usuario' => $idUsuario,
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

  /*
     Modifica la contraseña del usuario
     $data => Información del registro a actualizar
   */
  public function cambiar_contrasenia($data)
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

      $idUsuario = $data['id_usuario'];

      //Obtener la contraseña actual
      $this->db->select('usuario, contrasenia')->where('id_usuario', $idUsuario);
      if (empty($data['contrasenia_nueva'])) {
        $this->db->where(['fk_id_perfil_usuario !=' => PERFIL_USUARIO_SUPER_ADMINISTRADOR]);
      }
      $usuario = $this->db->get('usuarios');
      if ($usuario->num_rows() != 1) {
        if ($usuario->num_rows() == 0) {
          $respuesta['msg'] = 'No se encontraron coincidencias para actualizar.';
        } elseif ($usuario->num_rows() > 1) {
          $respuesta['msg'] = 'Se detectó más de una coincidencia. Contactar al Administrador.';
        }
        return $respuesta;
      }

      // Verificar si incluye la nueva contraseña. Si no se incluye, se trata de un reinicio
      $debe_cambiar_contrasenia = 0;
      if (empty($data['contrasenia_nueva'])) {
        $data['contrasenia'] = md5($usuario->row()->usuario);
        $debe_cambiar_contrasenia = 1;
      } else {
        // Validar que la contraseña actual sea igual a la almacenada
        if ($data['contrasenia_actual'] != $usuario->row()->contrasenia) {
          $respuesta['msg'] = 'La contraseña actual no corresponde a la registrada.';
          return $respuesta;
        } else {
          $data['contrasenia'] = $data['contrasenia_nueva'];
        }
      }

      $data = [
        'contrasenia' => $data['contrasenia'],
        'debe_cambiar_contrasenia' => $debe_cambiar_contrasenia,
        'fk_id_usuario_modifico' => $idUsuario,
        'fecha_modificacion' => date('Y-m-d H:i:s'),
      ];

      // Almacenar información en BD
      $respuesta['err'] = !$this->db->update('usuarios', $data, [
        'id_usuario' => $idUsuario,
      ]);

      $respuesta['msg'] = $respuesta['err']
        ? 'Código: ' . $this->db->error()['code'] . ' - ' . $this->db->error()['message']
        : 'Contraseña modificada con éxito.';
    } catch (Exception $e) {
      throw new Exception($e->getMessage());
      die();
    }
    return $respuesta;
  }
}

/* End of file Usuario_model.php */
/* Location: ./application/models/Usuario_model.php */
