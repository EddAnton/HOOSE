export class AreaComunResumenModel {
	id_area_comun: number;
	nombre: string;
	descripcion: string;
	importe_hora: number;
	estatus: number;
}

export class AreaComunModel {
	id_area_comun: number;
	nombre: string;
	descripcion: string;
	importe_hora: number;
	estatus: number;

	constructor() {
		return {
			id_area_comun: 0,
			nombre: null,
			descripcion: null,
			importe_hora: 0,
			estatus: 0,
		};
	}
}

export class AreaComunParaReservacionModel {
	id_area_comun: number;
	nombre: string;
	importe_hora: number;

	/* 	constructor() {
		return {
			id_area_comun: 0,
			nombre: null,
			importe_hora: 0,
		};
	} */
}

export class AreaComunListarReservacionesModel {
	anio: number;
	mes: number;
	dia: number;

	constructor() {
		return {
			anio: 0,
			mes: 0,
			dia: 0,
		};
	}
}

export class AreaComunReservacionesModel {
	id: string;
	title: string;
	start: string;
	end: string;
}

export class AreaComunReservacionModel {
	id_area_comun_reservacion: number;
	id_area_comun: number;
	area_comun: string;
	importe_hora: number;
	id_usuario: number;
	usuario: string;
	fecha_inicio: Date;
	fecha_fin: Date;
	importe_sugerido: number;
	importe_total: number;
	pagado: any;
	fecha_pago: Date;

	constructor() {
		return {
			id_area_comun_reservacion: 0,
			id_area_comun: 0,
			area_comun: null,
			importe_hora: 0,
			id_usuario: 0,
			usuario: null,
			fecha_inicio: new Date(),
			fecha_fin: new Date(),
			importe_sugerido: 0,
			importe_total: 0,
			pagado: 0,
			fecha_pago: new Date(),
		};
	}
}

export class AreaComunReservacionRegistrarPagoModel {
	id_area_comun_reservacion: number;
	fecha_pago: Date;

	constructor() {
		return {
			id_area_comun_reservacion: 0,
			fecha_pago: new Date(),
		};
	}
}
