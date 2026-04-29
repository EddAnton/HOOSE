export class CuotaMantenimientoResumenModel {
	id_cuota_mantenimiento: number;
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
	pagado: number;
	saldo: number;
	total: number;
	estatus: number;

	constructor() {
		return {
			id_cuota_mantenimiento: 0,
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
			pagado: 0,
			total: 0,
			saldo: 0,
			estatus: 0,
		};
	}
}

export class FilaTotalesModel {
	pagado: number;
	saldo: number;
	total: number;

	constructor() {
		return {
			pagado: 0,
			saldo: 0,
			total: 0,
		};
	}
}

export class CuotaMantenimientoModel {
	id_cuota_mantenimiento: number;
	id_edificio: number;
	id_unidad: number;
	id_perfil_usuario_paga: number;
	perfil_usuario_paga: string;
	id_usuario_paga: number;
	usuario_paga: string;
	anio: number;
	mes: number;
	ordinaria: number;
	extraordinaria: number;
	otros_servicios: number;
	descuento_pronto_pago: number;
	total: number;
	saldo: number;
	pagado: number;
	fecha_limite_pago: Date;
	id_fondo_monetario: number;
	importe: number;
	fecha_pago: Date;
	id_forma_pago: number;
	numero_referencia: string;
	notas: string;

	constructor() {
		return {
			id_cuota_mantenimiento: 0,
			id_edificio: 0,
			id_unidad: 0,
			id_perfil_usuario_paga: 0,
			perfil_usuario_paga: null,
			id_usuario_paga: 0,
			usuario_paga: null,
			anio: 0,
			mes: 0,
			ordinaria: 0,
			extraordinaria: 0,
			otros_servicios: 0,
			descuento_pronto_pago: 0,
			total: 0,
			saldo: 0,
			pagado: 0,
			fecha_limite_pago: new Date(),
			id_fondo_monetario: 0,
			importe: 0,
			fecha_pago: new Date(),
			id_forma_pago: 0,
			numero_referencia: null,
			notas: null,
		};
	}
}

export class CuotaMantenimientoMasivaModel {
	anio: number;
	mes: number;

	constructor() {
		return {
			anio: 0,
			mes: 0,
		};
	}
}

export class CuotaMantenimientoRegistrarPagoModel {
	// id_cuota_mantenimiento: number;
	total: number;
	pagado: number;
	saldo: number;
	saldo_nuevo: number;
	id_fondo_monetario: number;
	importe: number;
	fecha_pago: Date;
	id_forma_pago: number;
	numero_referencia: string;
	notas: string;

	constructor() {
		return {
			// id_cuota_mantenimiento: 0,
			total: 0,
			pagado: 0,
			saldo: 0,
			saldo_nuevo: 0,
			id_fondo_monetario: 0,
			importe: 0,
			fecha_pago: new Date(),
			id_forma_pago: 0,
			numero_referencia: null,
			notas: null,
		};
	}
}
