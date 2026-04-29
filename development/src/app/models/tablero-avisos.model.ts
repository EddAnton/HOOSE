export class AvisoModel {
	id_aviso: number;
	titulo: string;
	descripcion: string;
	id_perfil_usuario_destino: number;
	publicado: number;
	fecha_publicacion: Date;
	estatus: number;

	constructor() {
		return {
			id_aviso: 0,
			titulo: null,
			descripcion: null,
			id_perfil_usuario_destino: 0,
			publicado: 1,
			fecha_publicacion: new Date(),
			estatus: 0,
		};
	}
}
