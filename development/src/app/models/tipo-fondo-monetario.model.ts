export class TipoFondoMonetarioModel {
	id_tipo_fondo_monetario: number;
	tipo_fondo: string;
	requiere_datos_bancarios: number;
	estatus: number;

	constructor() {
		return {
			id_tipo_fondo_monetario: 0,
			tipo_fondo: null,
			requiere_datos_bancarios: 0,
			estatus: 0,
		};
	}
}
