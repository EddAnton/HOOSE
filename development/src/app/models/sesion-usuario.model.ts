export class LoginModel {
	usuario: string;
	contrasenia: string;

	constructor() {
		return {
			usuario: null,
			contrasenia: null,
		};
	}
}

export class SesionUsuarioModel {
	id_usuario: number;
	usuario: string;
	nombre: string;
	email: string;
	telefono: string;
	id_perfil_usuario: number;
	perfil_usuario: string;
	id_condominio_usuario: number;
	condominio_usuario: string;
	tiene_tablero_avisos: number;
	imagen_archivo: string;
	debe_cambiar_contrasenia: number;
	id_tipo_miembro: number;
	tipo_miembro: string;
	/* es_colaborador: number;
	fecha_inicio: Date;
	fecha_fin: Date; */
	token: string;
	tokenExpire: number;

	constructor() {
		return {
			id_usuario: 0,
			usuario: null,
			nombre: null,
			email: null,
			telefono: null,
			id_perfil_usuario: 0,
			perfil_usuario: null,
			id_condominio_usuario: 0,
			condominio_usuario: null,
			tiene_tablero_avisos: 0,
			imagen_archivo: null,
			debe_cambiar_contrasenia: 0,
			id_tipo_miembro: 0,
			tipo_miembro: null,
			token: null,
			tokenExpire: 0,
		};
	}
}
