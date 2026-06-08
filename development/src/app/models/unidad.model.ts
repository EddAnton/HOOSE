export class UnidadModel {
  id_unidad: number;
  unidad: string;
  nivel: number;
  tipo_unidad: string;
  numero_interior: string;
  porcentaje_indiviso: number;
  metros_cuadrados: number;
  cuota_mantenimiento: number;
  estacionamiento: string;
  bodega: string;
  id_edificio: number;
  edificio: string;
  escrituras_archivo: string;
  plano_archivo: string;
  fk_id_condominio: number;
  archivo_escrituras: any;
  archivo_plano: any;
  estatus: number;
  condominio_nombre: string;

  constructor() {
    return {
      id_unidad: 0,
      unidad: null,
      nivel: 0,
      tipo_unidad: 'Departamento',
      numero_interior: null,
      porcentaje_indiviso: 0,
      metros_cuadrados: 0,
      cuota_mantenimiento: 0,
      estacionamiento: null,
      bodega: null,
      id_edificio: 0,
      edificio: null,
      escrituras_archivo: null,
      plano_archivo: null,
      fk_id_condominio: null,
      archivo_escrituras: null,
      archivo_plano: null,
      estatus: 0,
      condominio_nombre: null,
    };
  }
}

export class UnidadesPropietarioResumenModel {
	unidad: string;

	constructor() {
		return {
			unidad: null,
		};
	}
}

export class UnidadesEdificioModel {
	id_unidad: number;
	unidad: string;

	constructor() {
		return {
			id_unidad: 0,
			unidad: null,
		};
	}
}

export class UnidadParaRecaudacionesModel {
	id_unidad: number;
	unidad: string;
	id_edificio: number;
	edificio: string;
	ocupada: number;
	id_perfil_usuario_paga: number;
	perfil_usuario_paga: string;
	id_usuario_paga: number;
	usuario_paga: string;
	renta: number;

	constructor() {
		return {
			id_unidad: 0,
			unidad: null,
			id_edificio: 0,
			edificio: null,
			ocupada: 0,
			id_perfil_usuario_paga: 0,
			perfil_usuario_paga: null,
			id_usuario_paga: 0,
			usuario_paga: null,
			renta: 0,
		};
	}
}
