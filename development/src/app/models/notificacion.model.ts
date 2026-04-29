export class NotificacionResumenModel {
	id_notificacion: number;
	fecha: Date;
	asunto: string;
	enviado: number;
	fecha_enviado: Date;
	usuario_envio: string;
	estatus: number;
}

export class DestinatarioModel {
	id_usuario: number;
	nombre: string;
	email: string;
}

export class NotificacionDestinatarioModel {
	id_usuario: number;
	nombre: string;
	email: string;
	id_perfil_usuario: number;
	perfil_usuario: string;
}

export class NotificacionDetalleModel {
	id_notificacion: number;
	fecha: Date;
	asunto: string;
	mensaje: string;
	enviado: number;
	fecha_enviado: Date;
	usuario_envio: string;
	estatus: number;
	destinatarios: NotificacionDestinatarioModel[];

	constructor() {
		return {
			id_notificacion: 0,
			fecha: new Date(),
			asunto: null,
			mensaje: null,
			enviado: 0,
			fecha_enviado: new Date(),
			usuario_envio: null,
			estatus: 0,
			destinatarios: [],
		};
	}
}

export class NotificacionModel {
	id_notificacion: number;
	asunto: string;
	mensaje: string;
	enviar: number;
	destinatarios: number[];

	constructor() {
		return {
			id_notificacion: 0,
			asunto: null,
			mensaje: null,
			enviar: 0,
			destinatarios: [],
		};
	}
}
