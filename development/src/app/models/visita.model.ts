import { isDevMode } from '@angular/core';
/* export class VisitaResumenModel {
	id_visita: string;
	visitante: string;
	telefono: string;
  id_unidad: number;
  unidad: string;
	fecha_hora_entrada: Date;
	fecha_hora_salida: Date;
	estatus: string;

	constructor() {
		return {
			id_visita: null,
			visitante: null,
			telefono: null,
      id_unidad: 0,
      unidad: null,
			fecha_hora_entrada: new Date(),
			fecha_hora_salida: new Date(),
			estatus: null,
		};
	}
} */

export class VisitaModel {
	id_visita: number;
	visitante: string;
	telefono: string;
	domicilio: string;
	identificacion_folio: string;
	id_unidad: number;
	unidad: string;
	fecha_hora_entrada: Date;
	fecha_hora_salida: Date;
	estatus: string;

	constructor() {
		return {
			id_visita: 0,
			visitante: isDevMode() ? 'visitante 0' : null,
			telefono: isDevMode() ? '2281505214' : null,
			domicilio: null,
			identificacion_folio: isDevMode() ? '2080061241653' : null,
			id_unidad: 1,
			unidad: null,
			fecha_hora_entrada: new Date(),
			fecha_hora_salida: null,
			estatus: null,
		};
	}
}
export class VisitaRegistrarSalidaModel {
	// id_visita: number;
	fecha_hora_salida: Date;

	constructor() {
		return {
			// id_visita: 0,
			fecha_hora_salida: new Date(),
		};
	}
}
