import { isDevMode } from '@angular/core';

export class CondominoResumenModel {
	id_usuario: number;
	nombre: string;
	usuario: string;
	email: string;
	telefono: string;
	domicilio: string;
	imagen: string;
	estatus: number;
	unidad_edificio: string;
	id_condomino_contrato: number;
	deposito: number;
	renta: number;
	contrato_activo: number;

	constructor() {
		return {
			id_usuario: 0,
			nombre: null,
			usuario: null,
			email: null,
			telefono: null,
			domicilio: null,
			imagen: null,
			estatus: 0,
			unidad_edificio: null,
			id_condomino_contrato: 0,
			deposito: 0,
			renta: 0,
			contrato_activo: 0,
		};
	}
}

export class CondominoModel {
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
	contrato: string;
	estatus: number;
	edificio: string;
	id_unidad: number;
	unidad: string;
	unidad_edificio: string;
	deposito: number;
	renta: number;
	fecha_inicio: Date;
	fecha_fin: Date;
	contrato_activo: number;

	constructor() {
		return {
			id_usuario: 0,
			usuario: isDevMode() ? 'condomino0' : null,
			nombre: isDevMode() ? 'condomino 0' : null,
			email: isDevMode() ? 'condomino0@pontevedra.com' : null,
			telefono: isDevMode() ? '2281505214' : null,
			domicilio: isDevMode() ? 'agustin serdán 6\ncol. libertad\nc.p. 91080\nxalapa, ver.' : null,
			identificacion_folio: isDevMode() ? '2080061241653' : null,
			identificacion_domicilio: isDevMode() ? 'agustin serdán 6 a\ncol. libertad\nc.p. 91075\nxalapa, ver.' : null,
			imagen: null,
			identificacion_anverso: null,
			identificacion_reverso: null,
			contrato: null,
			estatus: 0,
			edificio: null,
			id_unidad: 0,
			unidad: null,
			unidad_edificio: null,
			deposito: 0,
			renta: 0,
			fecha_inicio: new Date(),
			fecha_fin: new Date(),
			contrato_activo: 0,
		};
	}
}
