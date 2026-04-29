import { isDevMode } from '@angular/core';

export class ColaboradorResumenModel {
	id_usuario: number;
	nombre: string;
	usuario: string;
	email: string;
	telefono: string;
	domicilio: string;
	imagen: string;
	fecha_inicio: Date;
	salario: number;
	tipo_miembro: string;
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
			fecha_inicio: new Date(),
			salario: 0,
			tipo_miembro: null,
			estatus: 0,
		};
	}
}

export class ColaboradorModel {
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
	fecha_inicio: Date;
	fecha_fin: Date;
	salario: number;
	contrato: string;
	id_tipo_miembro: number;
	tipo_miembro: string;
	estatus: number;

	constructor() {
		return {
			id_usuario: 0,
			usuario: isDevMode() ? 'colaborador0' : null,
			nombre: isDevMode() ? 'colaborador 0' : null,
			email: isDevMode() ? 'colaborador0@pontevedra.com' : null,
			telefono: isDevMode() ? '2281505214' : null,
			domicilio: isDevMode() ? 'agustin serdán 6\ncol. libertad\nc.p. 91080\nxalapa, ver.' : null,
			identificacion_folio: isDevMode() ? '2080061241653' : null,
			identificacion_domicilio: isDevMode() ? 'agustin serdán 6 a\ncol. libertad\nc.p. 91075\nxalapa, ver.' : null,
			imagen: null,
			identificacion_anverso: null,
			identificacion_reverso: null,
			fecha_inicio: new Date(),
			fecha_fin: new Date(),
			salario: 0,
			contrato: null,
			id_tipo_miembro: 0,
			tipo_miembro: null,
			estatus: 0,
		};
	}
}
