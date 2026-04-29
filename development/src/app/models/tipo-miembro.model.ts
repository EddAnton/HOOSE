export class TipoMiembroModel {
	id_tipo_miembro: number;
	tipo_miembro: string;
	es_colaborador: any;
	estatus: number;

	constructor() {
		return {
			id_tipo_miembro: 0,
			tipo_miembro: null,
			es_colaborador: 0,
			estatus: 0,
		};
	}
}
