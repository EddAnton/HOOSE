import { isDevMode } from '@angular/core';

export class EdificioModel {
  id_edificio: number;
  edificio: string;
  tipo: string;
  numero_niveles: number;
  numero_unidades: number;
  direccion: string;
  descripcion: string;
  plano_archivo: string;
  fk_id_condominio: number;
  archivo_plano: any;
  estatus: number;
  condominio_nombre: string;

  constructor() {
    return {
      id_edificio: 0,
      edificio: null,
      tipo: 'Edificio',
      numero_niveles: 0,
      numero_unidades: 0,
      direccion: null,
      descripcion: null,
      plano_archivo: null,
      fk_id_condominio: null,
      archivo_plano: null,
      estatus: 0,
      condominio_nombre: null,
    };
  }
}
