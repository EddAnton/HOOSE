export class UsuarioSesionModel {
  id_usuario: number;
  usuario: string;
  nombre: string;
  email: string;
  id_perfil_usuario: number;
  perfil_usuario: string;
  id_condominio_usuario: number;
  tiene_tablero_avisos: boolean;
  imagen_archivo: string;
  debe_cambiar_contrasenia: boolean;
  id_tipo_miembro: number;
  tipo_miembro: string;
  es_colaborador: boolean;
  fecha_inicio: Date;
  fecha_fin: Date;
  token: string;
  tokenExpire: number;

  constructor() {
    return {
      id_usuario: 0,
      usuario: null,
      nombre: null,
      email: null,
      id_perfil_usuario: 0,
      perfil_usuario: null,
      id_condominio_usuario: 0,
      tiene_tablero_avisos: false,
      imagen_archivo: null,
      debe_cambiar_contrasenia: false,
      id_tipo_miembro: 0,
      tipo_miembro: null,
      es_colaborador: false,
      fecha_inicio: null,
      fecha_fin: null,
      token: null,
      tokenExpire: 0,
    };
  }
}

export class UsuarioCambiarContraseniaModel {
  contrasenia_actual: string;
  contrasenia_nueva: string;
  contrasenia_nueva_confirmada: string;

  constructor() {
    return {
      contrasenia_actual: null,
      contrasenia_nueva: null,
      contrasenia_nueva_confirmada: null,
    };
  }
}

export class UsuarioPropietarioYCondomino {
  id_usuario: number;
  nombre: string;
}

/* export class UsuarioActaAsamblea {
  id_usuario: number;
  nombre: string;
  perfil: string;
} */
