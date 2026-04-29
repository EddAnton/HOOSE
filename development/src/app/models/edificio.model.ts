import { isDevMode } from '@angular/core';

export class EdificioModel {
	id_edificio: number;
	edificio: string;
	estatus: number;

	constructor() {
		return {
			id_edificio: 0,
			edificio: isDevMode ? 'edificio de prueba #' : null,
			estatus: 0,
		};
	}
}
