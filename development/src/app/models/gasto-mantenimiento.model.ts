export class GastoMantenimientoResumenModel {
	id_gasto_mantenimiento: number;
	concepto: string;
	fecha: Date;
	importe: number;
	es_deducible: number;
	estatus: number;

	constructor() {
		return {
			id_gasto_mantenimiento: 0,
			concepto: null,
			fecha: new Date(),
			importe: 0,
			es_deducible: 0,
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

export class GastoMantenimientoModel {
	id_gasto_mantenimiento: number;
	id_gasto_fijo: number;
	concepto: string;
	fecha: Date;
	importe: number;
	descripcion: string;
	es_deducible: any;
	comprobante: string;
	id_fondo_monetario: number;
	estatus: number;

	constructor() {
		return {
			id_gasto_mantenimiento: 0,
			id_gasto_fijo: 0,
			concepto: null,
			fecha: new Date(),
			importe: 0,
			descripcion: null,
			es_deducible: 0,
			comprobante: null,
			id_fondo_monetario: 0,
			estatus: 0,
		};
	}
}
