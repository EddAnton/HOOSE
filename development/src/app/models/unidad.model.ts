export class UnidadModel {
	id_unidad: number;
	unidad: string;
	id_edificio: number;
	edificio: string;
	escrituras_archivo: string;
	estatus: number;
	/* ocupada: number;
	condomino: string;
	id_condomino_contrato: number;
	renta: number; */

	constructor() {
		return {
			id_unidad: 0,
			unidad: null,
			id_edificio: 0,
			edificio: null,
			escrituras_archivo: null,
			estatus: 0,
			/* ocupada: 0,
			condomino: null,
			id_condomino_contrato: 0,
			renta: 0, */
		};
	}
}

export class UnidadesPropietarioResumenModel {
	unidad: string;

	constructor() {
		return {
			unidad: null,
		};
	}
}

export class UnidadesEdificioModel {
	id_unidad: number;
	unidad: string;

	constructor() {
		return {
			id_unidad: 0,
			unidad: null,
		};
	}
}

export class UnidadParaRecaudacionesModel {
	id_unidad: number;
	unidad: string;
	id_edificio: number;
	edificio: string;
	ocupada: number;
	id_perfil_usuario_paga: number;
	perfil_usuario_paga: string;
	id_usuario_paga: number;
	usuario_paga: string;
	renta: number;

	constructor() {
		return {
			id_unidad: 0,
			unidad: null,
			id_edificio: 0,
			edificio: null,
			ocupada: 0,
			id_perfil_usuario_paga: 0,
			perfil_usuario_paga: null,
			id_usuario_paga: 0,
			usuario_paga: null,
			renta: 0,
		};
	}
}
