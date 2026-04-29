export class PonteCloudCarpetaModel {
	id: number;
	nombre: string;
	nivel: number;
	id_cloud_carpeta_padre: number;
}

export class PonteCloudContenidoModel {
	tipo: number;
	id: number;
	archivo: string;
	nombre: string;
	tamanio: number;
	unidad_medida: string;
	archivo_interno: string;
	fecha_registro: string;
	fecha_modificacion: string;
}

export class PonteCloudNombreItemModel {
	nombre: string;

	constructor() {
		return {
			nombre: null,
		};
	}
}
