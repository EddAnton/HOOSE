import { isDevMode } from '@angular/core';
import { reglamento } from './test.model';

export class CondominioResumenModel {
	id_condominio: number;
	condominio: string;
	email: string;
	telefono: string;
	domicilio: string;
	tipo: string;
	metros_cuadrados: number;
	tipo_administracion: string;
	fk_id_administrador: number;
	administrador_nombre: string;
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
			tipo: null,
			metros_cuadrados: null,
			tipo_administracion: null,
			fk_id_administrador: null,
			administrador_nombre: null,
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
	tipo: string;
	metros_cuadrados: number;
	tipo_administracion: string;
	fk_id_administrador: number;
	administrador_nombre: string;
	telefono_guardia: string;
	telefono_secretaria: string;
	telefono_moderador: string;
	anio_construccion: string;
	imagen: string;
	constructora: string;
	constructora_telefono: string;
	constructora_domicilio: string;
	reglamento: string;
	archivo_acta_constitutiva: string;
	archivo_reglamento_interno: string;
	archivo_poliza_seguro: string;
	archivo_planos: string;
	estatus: number;

	constructor() {
		return {
			id_condominio: 0,
			condominio: null,
			email: null,
			telefono: null,
			domicilio: null,
			tipo: null,
			metros_cuadrados: null,
			tipo_administracion: null,
			fk_id_administrador: null,
			administrador_nombre: null,
			telefono_guardia: null,
			telefono_secretaria: null,
			telefono_moderador: null,
			anio_construccion: null,
			imagen: null,
			constructora: null,
			constructora_telefono: null,
			constructora_domicilio: null,
			reglamento: null,
			archivo_acta_constitutiva: null,
			archivo_reglamento_interno: null,
			archivo_poliza_seguro: null,
			archivo_planos: null,
			estatus: 1,
		};
	}
}
