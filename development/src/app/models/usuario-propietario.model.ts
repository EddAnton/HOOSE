import { isDevMode } from '@angular/core';
import { UnidadesEdificioModel, UnidadesPropietarioResumenModel } from './unidad.model';

export class PropietarioResumenModel {
	id_usuario: number;
	nombre: string;
	usuario: string;
	email: string;
	telefono: string;
	domicilio: string;
	imagen: string;
	estatus: number;
	unidades: UnidadesPropietarioResumenModel[];

	constructor() {
		return {
			id_usuario: 0,
			usuario: null,
			nombre: null,
			email: null,
			telefono: null,
			domicilio: null,
			imagen: null,
			estatus: 0,
			unidades: [],
		};
	}
}

export class PropietarioModel {
	id_usuario: number;
	usuario: string;
	nombre: string;
	email: string;
	telefono: string;
	domicilio: string;
	identificacion_folio: string;
	identificacion_domicilio: string;
	identificacion_anverso: string;
	identificacion_reverso: string;
	imagen: string;
	estatus: number;
	unidades: UnidadesEdificioModel[];

	constructor() {
		return {
			id_usuario: 0,
			usuario: isDevMode() ? 'propietario02.c01' : null,
			nombre: isDevMode() ? 'propietario 02' : null,
			email: isDevMode() ? 'propietario02.condominio01@pontevedra.com.mx' : null,
			telefono: isDevMode() ? '2281505214' : null,
			domicilio: isDevMode() ? 'agustin serdán 6\ncol. libertad\nc.p. 91080\nxalapa, ver.' : null,
			identificacion_folio: isDevMode() ? '2080061241653' : null,
			identificacion_domicilio: isDevMode() ? 'agustin serdán 6 a\ncol. libertad\nc.p. 91075\nxalapa, ver.' : null,
			identificacion_anverso: null,
			identificacion_reverso: null,
			imagen: null,
			estatus: 0,
			unidades: [],
		};
	}
}
