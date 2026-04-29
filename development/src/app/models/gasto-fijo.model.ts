export class GastoFijoModel {
	id_gasto_fijo: number;
	gasto_fijo: string;
	estatus: number;

	constructor() {
		return {
			id_gasto_fijo: 0,
			gasto_fijo: null,
			estatus: 0,
		};
	}
}
