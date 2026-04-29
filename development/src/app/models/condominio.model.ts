import { isDevMode } from '@angular/core';
import { reglamento } from './test.model';

export class CondominioResumenModel {
	id_condominio: number;
	condominio: string;
	email: string;
	telefono: string;
	domicilio: string;
	telefono_guardia: string;
	telefono_secretaria: string;
	telefono_moderador: string;
	anio_construccion: string;
	imagen: string;
	estatus: number;

	constructor() {
		return {
			id_condominio: 0,
			condominio: null,
			email: null,
			telefono: null,
			domicilio: null,
			telefono_guardia: null,
			telefono_secretaria: null,
			telefono_moderador: null,
			anio_construccion: null,
			imagen: null,
			estatus: 1,
		};
	}
}

export class CondominioModel {
	id_condominio: number;
	condominio: string;
	email: string;
	telefono: string;
	domicilio: string;
	telefono_guardia: string;
	telefono_secretaria: string;
	telefono_moderador: string;
	anio_construccion: string;
	imagen: string;
	constructora: string;
	constructora_telefono: string;
	constructora_domicilio: string;
	reglamento: string;
	estatus: number;

	constructor() {
		return {
			id_condominio: 0,
			condominio: isDevMode() ? 'condominio de prueba #3' : null,
			email: isDevMode() ? 'abc@abc.ab' : null,
			telefono: isDevMode() ? '2281234561' : null,
			domicilio: isDevMode() ? 'domicilio de prueba' : null,
			telefono_guardia: isDevMode() ? '2281234562' : null,
			telefono_secretaria: isDevMode() ? '2281234563' : null,
			telefono_moderador: isDevMode() ? '2281234564' : null,
			anio_construccion: isDevMode() ? '2019' : null,
			imagen: null,
			constructora: isDevMode() ? 'constructora de prueba' : null,
			constructora_telefono: isDevMode() ? '2281234565' : null,
			constructora_domicilio: isDevMode() ? 'domicilio de la constructora de prueba' : null,
			reglamento: isDevMode() ? reglamento : null,
			estatus: 1,
		};
	}
}
