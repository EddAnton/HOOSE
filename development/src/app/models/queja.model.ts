export class QuejaResumenModel {
	id_queja: number;
	titulo: string;
	fecha: Date;
	id_estatus_queja: number;
	estatus_queja: string;
	id_usuario_asignado: number;
	usuario_asignado: string;
	id_usuario_registro: number;
	usuario_registro: string;
	estatus: number;
}

export class QuejaArchivoModel {
	id_queja_archivo: number;
	archivo: string;
}

export class QuejaModel {
	id_queja: number;
	titulo: string;
	descripcion: string;
	evidencia: QuejaArchivoModel[];
	archivos: any[];
	archivos_borrar: number[];
	seguimiento: QuejaSeguimientoModel[];

	constructor() {
		return {
			id_queja: 0,
			titulo: null,
			descripcion: null,
			evidencia: [],
			archivos: [],
			archivos_borrar: [],
			seguimiento: [],
		};
	}
}

export class ColaboradorDisponibleAsignarModel {
	id_usuario_asignado: number;
	nombre: string;
}

export class QuejaAsignarColaboradorModel {
	id_queja: number;
	id_usuario_asignado: number;

	constructor() {
		return {
			id_queja: 0,
			id_usuario_asignado: 0,
		};
	}
}

export class QuejaActualizarEstatusModel {
	id_queja: number;
	id_estatus_queja: number;
	solucion: string;

	constructor() {
		return {
			id_queja: 0,
			id_estatus_queja: 0,
			solucion: null,
		};
	}
}

export class QuejaDetalleModel {
	id_queja: number;
	titulo: string;
	descripcion: string;
	fecha: Date;
	id_estatus_queja: number;
	estatus_queja: string;
	id_usuario_asignado: number;
	usuario_asignado: string;
	solucion: string;
	id_usuario_registro: number;
	usuario_registro: string;
	estatus: number;
}

export class QuejaSeguimientoModel {
	id_queja_seguimiento: number;
	id_queja: number;
	fecha: Date;
	seguimiento: string;
	bloqueado: number;
	estatus: number;

	constructor() {
		return {
			id_queja_seguimiento: 0,
			id_queja: 0,
			fecha: new Date(),
			seguimiento: null,
			bloqueado: 0,
			estatus: 0,
		};
	}
}
