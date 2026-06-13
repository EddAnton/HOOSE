import { isDevMode } from '@angular/core';

export class AdministradorResumenModel {
  id_usuario: number;
  nombre: string;
  usuario: string;
  email: string;
  telefono: string;
  domicilio: string;
  imagen: string;
  fk_id_condominio: number;
  condominio_nombre: string;
  tipo_administrador: string;
  estructura_administracion: string;
  tipo_persona: string;
  id_administrador_interno: number;
  cargo: string;
  estatus: number;

  constructor() {
    return {
      id_usuario: 0,
      nombre: null,
      usuario: null,
      email: null,
      telefono: null,
      domicilio: null,
      imagen: null,
      fk_id_condominio: null,
      condominio_nombre: null,
      tipo_administrador: null,
      estructura_administracion: null,
      tipo_persona: null,
      id_administrador_interno: null,
      cargo: null,
      estatus: 0,
    };
  }
}

export class AdministradorModel {
  id_usuario: number;
  usuario: string;
  nombre: string;
  email: string;
  telefono: string;
  domicilio: string;
  identificacion_folio: string;
  identificacion_domicilio: string;
  imagen: string;
  identificacion_anverso: string;
  identificacion_reverso: string;
  fk_id_condominio: number;
  tipo_administrador: string;
  estructura_administracion: string;
  tipo_persona: string;
  razon_social: string;
  rfc: string;
  domicilio_fiscal: string;
  sitio_web: string;
  fecha_inicio_mandato: any;
  fecha_fin_mandato: any;
  estatus: number;

  constructor() {
    return {
      id_usuario: 0,
      usuario: isDevMode() ? 'administrador0' : null,
      nombre: isDevMode() ? 'administrador 0' : null,
      email: isDevMode() ? 'administrador0@pontevedra.com' : null,
      telefono: isDevMode() ? '2281505214' : null,
      domicilio: null,
      identificacion_folio: null,
      identificacion_domicilio: null,
      imagen: null,
      identificacion_anverso: null,
      identificacion_reverso: null,
      fk_id_condominio: null,
      tipo_administrador: null,
      estructura_administracion: null,
      tipo_persona: 'FISICA',
      razon_social: null,
      rfc: null,
      domicilio_fiscal: null,
      sitio_web: null,
      fecha_inicio_mandato: null,
      fecha_fin_mandato: null,
      estatus: 0,
    };
  }
}
