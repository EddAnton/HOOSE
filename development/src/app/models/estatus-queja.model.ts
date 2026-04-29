export class EstatusQuejaModel {
	id_estatus_queja: number;
	estatus_queja: string;
	debe_especificar_solucion: number;
	estatus: number;

	constructor() {
		return {
			id_estatus_queja: 0,
			estatus_queja: null,
			debe_especificar_solucion: 0,
			estatus: 0,
		};
	}
}
