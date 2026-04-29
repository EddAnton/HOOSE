import { Component, OnInit, isDevMode } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import alasql from 'alasql';

import { environment } from '../../../environments/environment';
import * as hlpApp from '../../helpers/app-helper';
import * as hlpSwal from '../../helpers/sweetalert2-helper';
import * as hlpPrimeNGTable from '../../helpers/primeng-table-helper';
import {
	DestinatarioModel,
	NotificacionDestinatarioModel,
	NotificacionDetalleModel,
	NotificacionModel,
	NotificacionResumenModel,
} from '../../models/notificacion.model';
import { NotificacionesService } from '../../services/notificaciones.service';
import { SesionUsuarioService } from '../../services/sesion-usuario.service';
import { UsuariosService } from '../../services/usuarios.service';
import { filter } from 'rxjs/operators';

@Component({
	selector: 'app-notificaciones',
	templateUrl: './notificaciones.component.html',
	styleUrls: ['./notificaciones.component.css'],
})
export class NotificacionesComponent implements OnInit {
	appData = environment;
	hlpApp = hlpApp;
	hlpPrimeNGTable = hlpPrimeNGTable;
	isDevelopment = isDevMode;

	// Tabla Áreas comunes
	// Columnas de la tabla
	NotificacionesCols: any[] = [
		{ header: 'Fecha', width: '160px' },
		{ header: 'Asunto' },
		{ header: 'Enviado', width: '70px' },
		{ header: 'Fecha envío', width: '160px' },
		{ header: 'Usuario envío' },
		// Botones de acción
		{ textAlign: 'center', width: '130px' },
	];
	NotificacionesFilter: any[] = ['fecha', 'asunto', 'fecha_enviado', 'usuario_envio'];

	Propietarios: DestinatarioModel[];
	PropietariosDisponibles: DestinatarioModel[];
	PropietariosSeleccionados: DestinatarioModel[];
	Condominos: DestinatarioModel[];
	CondominosDisponibles: DestinatarioModel[];
	CondominosSeleccionados: DestinatarioModel[];
	Colaboradores: DestinatarioModel[];
	ColaboradoresDisponibles: DestinatarioModel[];
	ColaboradoresSeleccionados: DestinatarioModel[];

	Notificaciones: NotificacionResumenModel[] = [];
	Notificacion: NotificacionModel;
	NotificacionDetalle: NotificacionDetalleModel;

	frmNotificacion: FormGroup;
	ModulosEditorMensaje = {
		imageResize: {
			handleStyles: {
				backgroundColor: 'black',
				border: 'none',
				color: 'white',
			},
			modules: ['DisplaySize', 'Resize'],
		},
	};
	mostrarDialogoEdicionNotificacion: boolean = false;
	mostrarDialogoDetalleNotificacion: boolean = false;

	constructor(
		private formBuilder: FormBuilder,
		private sesionUsuarioService: SesionUsuarioService,
		private usuariosService: UsuariosService,
		private notificacionesService: NotificacionesService,
	) {}

	ngOnInit(): void {
		this.onActualizarInformacion();
	}

	private OrdenarNotificaciones(notificaciones: NotificacionResumenModel[]) {
		return notificaciones.sort((a, b) => {
			if (a.enviado < b.enviado) {
				return -1;
			} else {
				if (new Date(a.fecha).valueOf() > new Date(b.fecha).valueOf()) {
					return -1;
				} else {
					return 1;
				}
			}
		});
	}

	onOrdenarDestinatarios(destinatarios: DestinatarioModel[] = []) {
		destinatarios = destinatarios.sort((a, b) => (a.nombre > b.nombre ? 1 : -1));
	}

	public onActualizarInformacion() {
		this.Propietarios = [];
		this.Condominos = [];
		this.Colaboradores = [];

		this.Notificaciones = [];

		hlpSwal.Cargando();

		this.usuariosService
			.ListarUsuariosParaNotificaciones()
			.toPromise()
			.then((r) => {
				const d = r['usuarios'];

				/* 				this.Propietarios = d
					.filter((u) => u.id_perfil_usuario == 3)
					.map((d) => {
						return {
							id_usuario: d.id_usuario,
							nombre: d.nombre,
							email: d.email,
						};
					});
				this.Condominos = d
					.filter((u) => u.id_perfil_usuario == 4)
					.map((d) => {
						return {
							id_usuario: d.id_usuario,
							nombre: d.nombre,
							email: d.email,
						};
					});
				this.Colaboradores = d
					.filter((u) => u.id_perfil_usuario == 5)
					.map((d) => {
						return {
							id_usuario: d.id_usuario,
							nombre: d.nombre,
							email: d.email,
						};
					}); */
				this.Propietarios = alasql('SELECT id_usuario, nombre, email FROM ? WHERE id_perfil_usuario = "4"', [d]);
				this.Condominos = alasql('SELECT id_usuario, nombre, email FROM ? WHERE id_perfil_usuario = "5"', [d]);
				this.Colaboradores = alasql('SELECT id_usuario, nombre, email FROM ? WHERE id_perfil_usuario = "3"', [d]);
			})
			.catch(async (e) => {
				await hlpSwal.Error(e);
			});

		this.notificacionesService
			.ListarActivos()
			.toPromise()
			.then((r) => {
				this.Notificaciones = this.OrdenarNotificaciones(r['notificaciones']);
			})
			.catch(async (e) => {
				await hlpSwal.Error(e);
			})
			.finally(() => {
				hlpSwal.Cerrar();
			});
	}

	async onNotificacionEditar(idNotificacion: number = 0) {
		this.PropietariosDisponibles = [];
		this.PropietariosSeleccionados = [];
		this.CondominosDisponibles = [];
		this.CondominosSeleccionados = [];
		this.ColaboradoresDisponibles = [];
		this.ColaboradoresSeleccionados = [];

		this.Notificacion = new NotificacionModel();

		if (idNotificacion > 0) {
			hlpSwal.Cargando();
			this.NotificacionDetalle = await this.notificacionesService
				.ListarNotificacionDetalle(idNotificacion)
				.toPromise()
				.then((r) => r['notificacion'])
				.catch(async (e) => {
					await hlpSwal.Error(e).then(() => null);
				});

			if (this.NotificacionDetalle == null) return;

			// Inicializar Propietarios seleccionados y determinar los disponibles
			this.PropietariosSeleccionados = alasql(
				'SELECT id_usuario, nombre, email FROM ? WHERE id_perfil_usuario = "4" ORDER BY nombre',
				[this.NotificacionDetalle.destinatarios],
			);
			this.PropietariosDisponibles = alasql(
				'SELECT * FROM ? AS p LEFT JOIN ? AS s ON s.id_usuario = p.id_usuario WHERE s.id_usuario IS NULL ORDER BY nombre',
				[this.Propietarios, this.PropietariosSeleccionados],
			);
			// Inicializar Condominos seleccionados y determinar los disponibles
			this.CondominosSeleccionados = alasql(
				'SELECT id_usuario, nombre, email FROM ? WHERE id_perfil_usuario = "5" ORDER BY nombre',
				[this.NotificacionDetalle.destinatarios],
			);
			this.CondominosDisponibles = alasql(
				'SELECT * FROM ? AS p LEFT JOIN ? AS s ON s.id_usuario = p.id_usuario WHERE s.id_usuario IS NULL ORDER BY nombre',
				[this.Condominos, this.CondominosSeleccionados],
			);
			// Inicializar Colaboradores seleccionados y determinar los disponibles
			this.ColaboradoresSeleccionados = alasql(
				'SELECT id_usuario, nombre, email FROM ? WHERE id_perfil_usuario = "3" ORDER BY nombre',
				[this.NotificacionDetalle.destinatarios],
			);
			this.ColaboradoresDisponibles = alasql(
				'SELECT * FROM ? AS p LEFT JOIN ? AS s ON s.id_usuario = p.id_usuario WHERE s.id_usuario IS NULL ORDER BY nombre',
				[this.Colaboradores, this.ColaboradoresSeleccionados],
			);
			hlpSwal.Cerrar();
		} else {
			this.NotificacionDetalle = new NotificacionDetalleModel();
			this.PropietariosDisponibles = [...this.Propietarios];
			this.CondominosDisponibles = [...this.Condominos];
			this.ColaboradoresDisponibles = [...this.Colaboradores];
		}
		this.Notificacion.id_notificacion = this.NotificacionDetalle.id_notificacion;
		this.Notificacion.asunto = this.NotificacionDetalle.asunto;
		this.Notificacion.mensaje = this.NotificacionDetalle.mensaje;
		this.Notificacion.enviar = 0;

		try {
			this.frmNotificacion = this.formBuilder.group(this.Notificacion);
			this.frmNotificacion
				.get('asunto')
				.setValidators([Validators.required, Validators.minLength(3), Validators.maxLength(150)]);

			this.mostrarDialogoEdicionNotificacion = true;
		} catch (e) {
			hlpSwal.Error(e);
		}
	}

	onNotificacionGuardar() {
		if (!this.frmNotificacion.valid) {
			this.frmNotificacion.markAllAsTouched();
			hlpSwal.Error('Se detectaron errores en la información solicitada.');
			return;
		}

		let notificacion = this.frmNotificacion.value;
		notificacion.enviar = notificacion.enviar ? 1 : 0;
		const propietarios = alasql('SELECT id_usuario FROM ?', [this.PropietariosSeleccionados]).map((d) => d.id_usuario);
		const condominos = alasql('SELECT id_usuario FROM ?', [this.CondominosSeleccionados]).map((d) => d.id_usuario);
		const colaboradores = alasql('SELECT id_usuario FROM ?', [this.ColaboradoresSeleccionados]).map(
			(d) => d.id_usuario,
		);
		const destinatarios = propietarios.concat(condominos.concat(colaboradores));

		if (destinatarios.length < 1) {
			hlpSwal.Error('Se debe especificar al menos un destinatario.');
			return;
		}
		notificacion.destinatarios = destinatarios;

		hlpSwal
			.Pregunta({
				html: '¿Deseas guardar la información' + (notificacion.enviar == 1 ? ' y enviar la notificación ' : '') + '?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.notificacionesService.Guardar(notificacion).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: 1 }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && r.value.err != 1) {
					if (r.value.notificacion) {
						const c = r.value.notificacion;
						if (notificacion.id_notificacion == 0) {
							this.Notificaciones.push(c);
						} else {
							this.Notificaciones = this.Notificaciones.map((C) => (C.id_notificacion === c.id_notificacion ? c : C));
						}
						this.Notificaciones = this.OrdenarNotificaciones(this.Notificaciones);
					}
					switch (r.value.err) {
						case 0:
							hlpSwal.Exito(r.value.msg);
							break;
						default:
							hlpSwal.Advertencia(r.value.msg);
							break;
					}
					this.mostrarDialogoEdicionNotificacion = false;
				}
			});
	}

	onNotificacionCancelar() {
		this.mostrarDialogoEdicionNotificacion = false;
	}

	async onNotificacionDetalle(idNotificacion: number = 0) {
		if (idNotificacion < 1) {
			return;
		}

		hlpSwal.Cargando();

		this.NotificacionDetalle = await this.notificacionesService
			.ListarNotificacionDetalle(idNotificacion)
			.toPromise()
			.then((r) => r['notificacion'])
			.catch(async (e) => {
				await hlpSwal.Error(e).then(() => null);
			})
			.finally(() => hlpSwal.Cerrar());

		if (this.NotificacionDetalle == null) return;

		// Inicializar Destinatarios
		this.PropietariosSeleccionados = [];
		this.CondominosSeleccionados = [];
		this.ColaboradoresSeleccionados = [];

		// Determinar Destinatarios
		this.PropietariosSeleccionados = alasql(
			'SELECT id_usuario, nombre, email FROM ? WHERE id_perfil_usuario = "4" ORDER BY nombre',
			[this.NotificacionDetalle.destinatarios],
		);
		this.CondominosSeleccionados = alasql(
			'SELECT id_usuario, nombre, email FROM ? WHERE id_perfil_usuario = "5" ORDER BY nombre',
			[this.NotificacionDetalle.destinatarios],
		);
		this.ColaboradoresSeleccionados = alasql(
			'SELECT id_usuario, nombre, email FROM ? WHERE id_perfil_usuario = "3" ORDER BY nombre',
			[this.NotificacionDetalle.destinatarios],
		);

		this.mostrarDialogoDetalleNotificacion = true;
	}

	onNotificacionEliminar(idNotificacion: number = 0) {
		if (idNotificacion < 1) {
			return;
		}

		hlpSwal
			.Pregunta({
				html: '¿Deseas eliminar la notificación?<br><p class="pt-2 text-danger fw-bold">ESTE PROCESO ES IRREVERSIBLE.</p>',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.notificacionesService.Eliminar(idNotificacion).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err) {
					this.Notificaciones = this.Notificaciones.filter(
						(r: NotificacionResumenModel) => r.id_notificacion != idNotificacion,
					);
					hlpSwal.ExitoToast(r.value.msg);
				}
			});
	}

	onNotificacionEnviar(Notificacion: NotificacionResumenModel = null) {
		if (Notificacion == null) {
			return;
		}

		hlpSwal
			.Pregunta({
				html: '¿Deseas ' + (Notificacion.enviado == 1 ? 're' : '') + 'enviar la notificación?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.notificacionesService.Enviar(Notificacion.id_notificacion).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err && r.value.notificacion) {
					const c = r.value.notificacion;
					this.Notificaciones = this.Notificaciones.map((C) => (C.id_notificacion === c.id_notificacion ? c : C));
					this.Notificaciones = this.OrdenarNotificaciones(this.Notificaciones);

					hlpSwal.Exito(r.value.msg);
				}
			});
	}
}
