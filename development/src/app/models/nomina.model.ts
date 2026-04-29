export class NominaResumenModel {
	id_colaborador_nomina: number;
	colaborador: string;
	puesto: string;
	anio: number;
	mes: number;
	importe: number;
	fecha_pago: Date;
	estatus: number;
}

export class FilaTotalesModel {
	total: number;

	constructor() {
		return {
			total: 0,
		};
	}
}

export class NominaPagoModel {
	id_colaborador_nomina: number;
	id_colaborador: number;
	colaborador: string;
	puesto: string;
	anio: number;
	mes: number;
	importe: number;
	fecha_pago: Date;
	id_fondo_monetario: number;

	constructor() {
		return {
			id_colaborador_nomina: 0,
			id_colaborador: 0,
			colaborador: null,
			puesto: null,
			anio: 0,
			mes: 0,
			importe: 0,
			fecha_pago: new Date(),
			id_fondo_monetario: 0,
		};
	}
}

export class NominaPagoDetalleModel {
	id_colaborador_nomina: number;
	imagen_archivo: string;
	colaborador: string;
	puesto: string;
	anio: number;
	mes: number;
	importe: number;
	fecha_pago: Date;
	estatus: number;
	email: string;
	telefono: string;
	fondo_monetario: string;

	/* 	constructor() {
		return {
			id_colaborador_nomina: 0,
			colaborador: null,
			puesto: null,
			anio: 0,
			mes: 0,
			importe: 0,
			fecha_pago: new Date(),
			estatus: 0,
			email: null,
			telefono: null,
		};
	} */
}
