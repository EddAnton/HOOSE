export class EstatusRecaudacionModel {
	id_estatus_recaudacion: number;
	estatus_recaudacion: string;
	estatus: number;

	constructor() {
		return {
			id_estatus_recaudacion: 0,
			estatus_recaudacion: null,
			estatus: 0,
		};
	}
}
