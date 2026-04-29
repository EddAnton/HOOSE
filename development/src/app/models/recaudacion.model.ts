export class RecaudacionResumenModel {
	id_recaudacion: number;
	usuario_paga: string;
	perfil_usuario_paga: string;
	id_edificio: number;
	edificio: string;
	id_unidad: number;
	unidad: string;
	anio: number;
	mes: number;
	id_estatus_recaudacion: number;
	estatus_recaudacion: string;
	total: number;
	estatus: number;

	constructor() {
		return {
			id_recaudacion: 0,
			usuario_paga: null,
			perfil_usuario_paga: null,
			id_edificio: 0,
			edificio: null,
			id_unidad: 0,
			unidad: null,
			anio: 0,
			mes: 0,
			id_estatus_recaudacion: 0,
			estatus_recaudacion: null,
			total: 0,
			estatus: 0,
		};
	}
}

export class FilaTotalesModel {
	total: number;

	constructor() {
		return {
			total: 0,
		};
	}
}

export class RecaudacionModel {
	id_recaudacion: number;
	id_edificio: number;
	id_unidad: number;
	id_perfil_usuario_paga: number;
	perfil_usuario_paga: string;
	id_usuario_paga: number;
	usuario_paga: string;
	anio: number;
	mes: number;
	renta: number;
	agua: number;
	energia_electrica: number;
	gas: number;
	seguridad: number;
	servicios_publicos: number;
	otros_servicios: number;
	fecha_limite_pago: Date;
	id_estatus_recaudacion: number;
	fecha_pago: Date;
	id_forma_pago: number;
	numero_referencia: string;
	notas: string;
	total: number;
	estatus: number;

	constructor() {
		return {
			id_recaudacion: 0,
			id_edificio: 0,
			id_unidad: 0,
			id_perfil_usuario_paga: 0,
			perfil_usuario_paga: null,
			id_usuario_paga: 0,
			usuario_paga: null,
			anio: 0,
			mes: 0,
			renta: 0,
			agua: 0,
			energia_electrica: 0,
			gas: 0,
			seguridad: 0,
			servicios_publicos: 0,
			otros_servicios: 0,
			fecha_limite_pago: new Date(),
			id_estatus_recaudacion: 0,
			fecha_pago: new Date(),
			id_forma_pago: 0,
			numero_referencia: null,
			notas: null,
			total: 0,
			estatus: 0,
		};
	}
}

export class RecaudacionRegistrarPagoModel {
	id_recaudacion: number;
	fecha_pago: Date;
	id_forma_pago: number;
	numero_referencia: string;

	constructor() {
		return {
			id_recaudacion: 0,
			fecha_pago: new Date(),
			id_forma_pago: 0,
			numero_referencia: null,
		};
	}
}
