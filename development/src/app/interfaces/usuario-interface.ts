export interface UsuarioSesionInterface {
	id_usuario: number;
	usuario: string;
	nombre: string;
	email: string;
	id_perfil_usuario: number;
	perfil_usuario: string;
	id_condominio_usuario: number;
	tiene_tablero_avisos: boolean;
	imagen_archivo: string;
	debe_cambiar_contrasenia: boolean;
	id_tipo_miembro: number;
	tipo_miembro: string;
	es_colaborador: boolean;
	fecha_inicio: Date;
	fecha_fin: Date;
	token: string;
	tokenExpire: number;
}
