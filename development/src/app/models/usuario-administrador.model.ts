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
      estatus: 0,
    };
  }
}
