export class FormaPagoModel {
	id_forma_pago: number;
	forma_pago: string;
	requiere_numero_referencia: number;
	estatus: number;

	constructor() {
		return {
			id_forma_pago: 0,
			forma_pago: null,
			requiere_numero_referencia: 0,
			estatus: 0,
		};
	}
}
