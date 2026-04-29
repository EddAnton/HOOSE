import { isDevMode } from '@angular/core';
import { asamblea } from './test.model';

export class ConvocatoriaResumenModel {
  id_asamblea: number;
  tipo_asamblea: number;
  fecha_hora: Date;
  lugar: string;
  convocatoria_quien_emite: string;
  convocatoria_vencida: number;
  tiene_acta: number;
  id_acta: number;
  acta_finalizada: number;
  estatus: number;
}

export class ConvocatoriaModel {
  id_asamblea: number;
  id_tipo_asamblea: number;
  fecha_hora: Date;
  lugar: string;
  fundamento_legal: string;
  convocatoria_cierre: string;
  convocatoria_fecha: Date;
  convocatoria_ciudad: string;
  convocatoria_quien_emite: string;
  orden_dia: any[];
  estatus: number;

  constructor() {
    return {
      id_asamblea: 0,
      id_tipo_asamblea: 0,
      fecha_hora: null,
      lugar: null,
      fundamento_legal: isDevMode() ? asamblea : null,
      convocatoria_cierre: isDevMode() ? asamblea : null,
      convocatoria_fecha: null,
      convocatoria_ciudad: null,
      convocatoria_quien_emite: null,
      orden_dia: [],
      estatus: 0,
    };
  }
}

export class OrdenDelDiaModel {
  id_asamblea_orden_dia: number;
  orden_dia: string;
  requiere_votacion: boolean

  constructor() {
    return {
      id_asamblea_orden_dia: 0,
      orden_dia: null,
      requiere_votacion: false
    }
  }
}

export class ActaPaseListaModel {
  id_usuario: number;
  usuario: string;
  perfil: string;
  unidad: string;
  asistencia: boolean;

  constructor() {
    return {
      id_usuario: 0,
      usuario: null,
      perfil: null,
      unidad: null,
      asistencia: false,
    };
  }
}

/* export class ActaOrdenDiaVotacionModel {
  id_asamblea_orden_dia: number;
  id_unidad: number;
  unidad: string;
  id_usuario: number;
  usuario: string;
  // perfil_usuario: string;
  id_sentido_votacion: number;

  constructor() {
    return {
      id_asamblea_orden_dia: 0,
      id_unidad: 0,
      unidad: null,
      id_usuario: 0,
      usuario: null,
      // perfil_usuario: null,
      id_sentido_votacion: 0,
    };
  }
} */
/* export class ActaOrdenDiaSentidoVotacionModel {
  id_asamblea_orden_dia: number;
  id_sentido_votacion: number;
  total: number;

  constructor() {
    return {
      id_asamblea_orden_dia: 0,
      id_sentido_votacion: 0,
      total: 0,
    };
  }
} */
/* export class ActaOrdenDiaVotacionModel {
  id_asamblea_orden_dia: number;
  id_usuario: number;
  usuario: string;
  perfil: string;
  unidad: string;
  puede_votar: boolean;
  votacion: number;

  constructor() {
    return {
      id_asamblea_orden_dia: 0,
      id_usuario: 0,
      usuario: null,
      perfil: null,
      unidad: null,
      puede_votar: false,
      votacion: 0,
    };
  }
} */

/* export class ActaOrdenDiaModel {
  id_asamblea_orden_dia: number;
  orden_dia: string;
  requiere_votacion: boolean;
  apertura: string;
  cierre: string;
  votacion: ActaOrdenDiaVotacionModel[];

  constructor() {
    return {
      id_asamblea_orden_dia: 0,
      orden_dia: null,
      requiere_votacion: false,
      apertura: null,
      cierre: null,
      votacion: [],
    };
  }
} */

export class ActaModel {
  // id_asamblea: number;
  id_acta: number;
  fecha_hora: Date;
  lugar: string;
  apertura: string;
  cierre: string;
  quien_emite: string;
  total_unidades: number;
  estatus: number;
  /* existe_quorum: boolean;
  votos_pendientes: boolean; */
  /*
  /*
  pase_lista: ActaPaseListaModel[];
  orden_dia: ActaOrdenDiaModel[];
  */

  constructor() {
    return {
      // id_asamblea: 0,
      id_acta: 0,
      fecha_hora: new Date(),
      lugar: null,
      apertura: null,
      cierre: null,
      quien_emite: null,
      total_unidades: 0,
      /* existe_quorum: false,
      votos_pendientes: true, */
      estatus: 0,
      /*
      pase_lista: [],
      orden_dia: [],
      */
    }
  }
}