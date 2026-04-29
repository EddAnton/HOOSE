export class FondoMonetarioResumenModel {
	id_fondo_monetario: number;
	fondo_monetario: string;
	tipo_fondo: string;
	banco: string;
	numero_cuenta: string;
	saldo: number;
	clabe: string;
	estatus: number;

	constructor() {
		return {
			id_fondo_monetario: 0,
			fondo_monetario: null,
			tipo_fondo: null,
			banco: null,
			numero_cuenta: null,
			saldo: 0,
			clabe: null,
			estatus: 0,
		};
	}
}

export class FondoMonetarioModel {
	id_fondo_monetario: number;
	fondo_monetario: string;
	id_tipo_fondo_monetario: number;
	requiere_datos_bancarios: number;
	banco: string;
	numero_cuenta: string;
	clabe: string;
	saldo: number;
	estatus: number;

	constructor() {
		return {
			id_fondo_monetario: 0,
			fondo_monetario: null,
			id_tipo_fondo_monetario: 0,
			requiere_datos_bancarios: 0,
			banco: null,
			numero_cuenta: null,
			clabe: null,
			saldo: 0,
			estatus: 0,
		};
	}
}

export class FondoMonetarioTraspasoModel {
	id_fondo_monetario_destino: number;
	fecha: Date;
	importe: number;

	constructor() {
		return {
			id_fondo_monetario_destino: 0,
			fecha: new Date(),
			importe: 0,
		};
	}
}

/* export class FondoMonetarioMovimientosModel {
	id_fondo_monetario: number;
	id_tipo_fondo_monetario: number;
	banco: string;
	numero_cuenta: string;
	clabe: string;
	saldo: number;
	movimientos: FondoMonetarioMovimientoModel[];

	constructor() {
		return {
			id_fondo_monetario: 0,
			id_tipo_fondo_monetario: 0,
			banco: null,
			numero_cuenta: null,
			clabe: null,
			saldo: 0,
			movimientos: [],
		};
	}
} */

export class FondoMonetarioMovimientoModel {
	id_fondo_monetario_movimiento: number;
	id_fondo_monetario: number;
	id_tipo_movimiento: number;
	tipo_movimiento: string;
	fecha: Date;
	concepto: string;
	importe: number;
	saldo_anterior: number;
	saldo_nuevo: number;
	comprobante_archivo: string;
	es_externo: number;
	estatus: number;
	cancelado: number;
	fecha_registro: Date;

	constructor() {
		return {
			id_fondo_monetario_movimiento: 0,
			id_fondo_monetario: 0,
			id_tipo_movimiento: 0,
			tipo_movimiento: null,
			fecha: new Date(),
			concepto: null,
			importe: 0,
			saldo_anterior: 0,
			saldo_nuevo: 0,
			comprobante_archivo: null,
			es_externo: 0,
			estatus: 0,
			cancelado: 0,
			fecha_registro: new Date(),
		};
	}
}
