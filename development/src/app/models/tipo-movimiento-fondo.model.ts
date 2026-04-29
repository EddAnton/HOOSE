export class TipoMovimientoFondoModel {
	id_tipo_movimiento: number;
	tipo_movimiento: string;
	estatus: number;

	constructor() {
		return {
			id_tipo_movimiento: 0,
			tipo_movimiento: null,
			estatus: 0,
		};
	}
}
