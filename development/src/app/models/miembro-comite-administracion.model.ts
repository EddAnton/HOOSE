import { isDevMode } from '@angular/core';

export class MiembroResumenModel {
	id_miembro: number;
	nombre: string;
	email: string;
	telefono: string;
	domicilio: string;
	imagen: string;
	tipo_miembro: string;
	fecha_inicio: Date;
	fecha_fin: Date;
	estatus: number;

	constructor() {
		return {
			id_miembro: 0,
			nombre: null,
			email: null,
			telefono: null,
			domicilio: null,
			imagen: null,
			tipo_miembro: null,
			fecha_inicio: new Date(),
			fecha_fin: new Date(),
			estatus: 0,
		};
	}
}

export class MiembroModel {
	id_miembro: number;
	nombre: string;
	email: string;
	telefono: string;
	domicilio: string;
	identificacion_folio: string;
	identificacion_domicilio: string;
	imagen: string;
	identificacion_anverso: string;
	identificacion_reverso: string;
	id_tipo_miembro: number;
	fecha_inicio: Date;
	estatus: number;

	constructor() {
		return {
			id_miembro: 0,
			nombre: isDevMode() ? 'miembro comite administracion 0' : null,
			email: isDevMode() ? 'miembro-ca.condominio010@pontevedra.com' : null,
			telefono: isDevMode() ? '2281111111' : null,
			domicilio: isDevMode() ? 'agustin serdán 6\ncol. libertad\nc.p. 91080\nxalapa, ver.' : null,
			identificacion_folio: isDevMode() ? '2080061241653' : null,
			identificacion_domicilio: isDevMode() ? 'agustin serdán 6 a\ncol. libertad\nc.p. 91075\nxalapa, ver.' : null,
			imagen: null,
			identificacion_anverso: null,
			identificacion_reverso: null,
			id_tipo_miembro: 0,
			fecha_inicio: new Date(),
			estatus: 0,
		};
	}
}
