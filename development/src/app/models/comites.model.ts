export class TipoComiteModel {
  id_tipo_comite: number;
  tipo_comite: string;
}

export class MiembroComiteResumenModel {
  id_miembro: number;
  id_tipo_comite: number;
  id_tipo_miembro: number;
  tipo_miembro: string;
  id_usuario: number;
  usuario: string;
  email: string;
  telefono: string;
  id_perfil_usuario: number;
  perfil_usuario: string;
  fecha_inicio: Date;

  /* constructor() {
    return {
      id_miembro_comite: 0,
      id_tipo_comite: 0,
      id_usuario: 0,
      usuario: null,
      id_perfil_usuario: 0,
      perfil_usuario: null,
      fecha_inicio: new Date(),
    };
  } */
}

export class MiembroComiteModel {
  id_tipo_comite: number;
  // tipo_comite: string;
  id_usuario: number;
  // usuario: string;
  id_tipo_miembro: number;
  // tipo_miembro: string;
  fecha_inicio: Date;

  constructor() {
    return {
      id_tipo_comite: 0,
      // tipo_comite: null,
      id_usuario: 0,
      // usuario: null,
      id_tipo_miembro: 0,
      // tipo_miembro: null,
      fecha_inicio: new Date(),
    };
  }
}

