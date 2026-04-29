export class ProyectoImagenModel {
	id_proyecto_imagen: number;
	imagen: string;
}

export class ProyectoResumenModel {
	id_proyecto: number;
	titulo: string;
	presupuesto: number;
	fecha_inicio: Date;
	fecha_fin: Date;
	porcentaje_avance: number;
	estatus: number;
}

export class ProyectoModel {
	id_proyecto: number;
	titulo: string;
	descripcion: string;
	presupuesto: number;
	fecha_inicio: Date;
	fecha_fin: Date;
	porcentaje_avance: number;
	estatus: number;
	imagenes: ProyectoImagenModel[];
	archivos_imagenes: any[];
	imagenes_borrar: number[];

	constructor() {
		return {
			id_proyecto: 0,
			titulo: null,
			descripcion: null,
			presupuesto: 0,
			fecha_inicio: new Date(),
			fecha_fin: new Date(),
			porcentaje_avance: 0,
			estatus: 0,
			imagenes: [],
			archivos_imagenes: [],
			imagenes_borrar: [],
		};
	}
}
