import { Component, OnInit, isDevMode, ViewChild } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';

import { environment } from '../../../environments/environment';
import * as hlpApp from '../../helpers/app-helper';
import * as hlpSwal from '../../helpers/sweetalert2-helper';

import { EventApi, FullCalendarComponent } from '@fullcalendar/angular';
import { CalendarOptions } from '@fullcalendar/core';

import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import esLocale from '@fullcalendar/core/locales/es';
import {
	AreaComunListarReservacionesModel,
	AreaComunReservacionModel,
	AreaComunReservacionesModel,
	AreaComunResumenModel,
} from '../../models/area-comun.model';
import { AreasComunesService } from '../../services/areas-comunes.service';
import { SesionUsuarioService } from 'src/app/services/sesion-usuario.service';
import { UsuarioPropietarioYCondomino } from 'src/app/models/usuario.model';
import { UsuariosService } from '../../services/usuarios.service';
import { FormsValidator } from '../../validators/forms.validator';

@Component({
	selector: 'app-reservar-areas-comunes',
	templateUrl: './reservar-areas-comunes.component.html',
	styleUrls: ['./reservar-areas-comunes.component.css'],
})
export class ReservarAreasComunesComponent implements OnInit {
	appData = environment;
	hlpApp = hlpApp;
	isDevelopment = isDevMode;

	@ViewChild('calendar') calendarComponent: FullCalendarComponent;
	calendarOptions: CalendarOptions = {
		plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
		locale: esLocale,
		themeSystem: 'bootstrap5',
		editable: true,
		initialView: 'dayGridMonth',
		headerToolbar: {
			start: 'prev,next,today,onButtonAgregarEvento',
			center: 'title',
			end: 'onActualizarInformacion,dayGridMonth,timeGridWeek',
		},
		initialDate: new Date(),
		eventTimeFormat: {
			hour: '2-digit',
			minute: '2-digit',
			hour12: true,
		},
		slotLabelFormat: {
			hour: '2-digit',
			minute: '2-digit',
		},
		datesSet: this.handleEvents.bind(this),
		customButtons: {
			onButtonAgregarEvento: {
				text: 'Agregar evento',
				icon: 'bi bi-calendar-plus',
				click: () => {
					this.onReservacionEditar();
				},
			},
			onActualizarInformacion: {
				text: 'Actualizar información',
				icon: 'bi bi-arrow-clockwise',
				click: () => {
					this.onActualizarInformacion();
				},
			},
		},
		dateClick: (info) => {
			this.onCambiarVistaFecha(info.dateStr);
		},
		eventClick: (info) => {
			this.onReservacionMostrarDetalle(Number(info.event._def.publicId));
		},
	};

	Reservaciones: AreaComunReservacionesModel[] = [];
	Reservacion: AreaComunReservacionModel;
	dataConsultaReservaciones: AreaComunListarReservacionesModel = new AreaComunListarReservacionesModel();
	cambiandoVista: boolean = false;

	AreasComunes: AreaComunResumenModel[] = [];
	Usuarios: UsuarioPropietarioYCondomino[] = [];

	mostrarDialogoDetallesReservacion: boolean = false;

	frmReservacion: FormGroup;
	fechaRegistroLimite: Date = new Date();
	mostrarDialogoEdicionReservacion: boolean = false;
	permitirAgregarEditar: boolean = false;
	permitirEditarReservacion: boolean = false;
	permitirPagoReservacion: boolean = false;

	constructor(
		private formBuilder: FormBuilder,
		private sesionUsuarioService: SesionUsuarioService,
		private areasComunesService: AreasComunesService,
		private usuariosService: UsuariosService,
	) {}

	ngOnInit() {
		this.permitirAgregarEditar = [1, 2].includes(this.sesionUsuarioService.obtenerIDPerfilUsuario());
		this.cambiandoVista = false;
	}

	async onActualizarInformacion() {
		console.log('Entró: onActualizarInformacion()');

		hlpSwal.Cargando();

		this.Reservaciones = [];
		this.areasComunesService
			.ListarParaReservaciones()
			.toPromise()
			.then((r) => {
				this.AreasComunes = r['areas_comunes'];
			})
			.catch(async (e) => {
				await hlpSwal.Error(e).then(() => null);
			});

		this.usuariosService
			.ListarPropietariosYCondominos()
			.toPromise()
			.then((r) => {
				this.Usuarios = r['usuarios'];
			})
			.catch(async (e) => {
				await hlpSwal.Error(e).then(() => null);
			});

		this.areasComunesService
			.ListarReservaciones(this.dataConsultaReservaciones)
			.toPromise()
			.then((r) => {
				this.Reservaciones = r['reservaciones'].map((r) => {
					return {
						groupId: r.id_area_comun_reservacion,
						id: r.id_area_comun_reservacion,
						title: r.area_comun + ' - ' + r.usuario,
						start: r.fecha_inicio.replace(' ', 'T'),
						end: r.fecha_fin.replace(' ', 'T'),
						backgroundColor: r.pagado == 0 ? 'red' : '',
					};
				});
				this.calendarOptions.events = this.Reservaciones;
			})
			.catch(async (e) => {
				await hlpSwal.Error(e);
			})
			.finally(() => {
				hlpSwal.Cerrar();
			});
	}

	private handleEvents(event: EventApi | any): void {
		console.log('Entró: handleEvents()');
		if (this.cambiandoVista) {
			console.log('Saliendo. onCambiarVistaFecha() en ejecución');
			return;
		}

		const calendarApi = this.calendarComponent.getApi();
		let fecha = this.calendarComponent.getApi().getDate();
		this.dataConsultaReservaciones.anio = fecha.getFullYear();
		this.dataConsultaReservaciones.mes = fecha.getMonth() + 1;
		switch (calendarApi.currentData.currentViewType) {
			case 'dayGridMonth':
				this.onActualizarInformacion();
				break;
			/* case 'dayGridDay':
					this.dataConsultaReservaciones.dia = fecha.getDate();
					break; */
		}
		// this.onActualizarInformacion();
	}

	onCambiarVistaFecha(fechaString: string = null) {
		console.log('Entró: onCambiarVistaFecha()');
		this.cambiandoVista = true;
		if (this.calendarComponent.getApi().view.type == 'timeGridWeek') {
			console.log('Vista actual ya era timeGridWeek');
			this.cambiandoVista = false;
			return;
		}

		if (fechaString === null || isNaN(Date.parse(fechaString))) {
			console.error('Fecha no válida.');
			return;
		}

		let fecha = new Date(fechaString);
		fecha.setDate(fecha.getDate() + 1);
		this.calendarComponent.getApi().gotoDate(fecha);
		this.calendarComponent.getApi().changeView('timeGridWeek');
		// this.calendarComponent.getApi().changeView('dayGridDay');

		this.cambiandoVista = false;
	}

	/* onMostrarAgregarEvento() {
		console.log('Entró: onMostrarAgregarEvento()');
	} */

	async onReservacionMostrarDetalle(idReservacion: number = 0) {
		if (idReservacion < 1) {
			return;
		}
		try {
			hlpSwal.Cargando();
			this.Reservacion = await this.areasComunesService
				.ListarReservacion(idReservacion)
				.toPromise()
				.then((r) => r['reservacion'])
				.catch(async (e) => {
					await hlpSwal.Error(e).then(() => null);
				})
				.finally(() => hlpSwal.Cerrar());
			this.permitirEditarReservacion =
				this.permitirAgregarEditar && this.fechaRegistroLimite < new Date(this.Reservacion.fecha_inicio);
			this.permitirPagoReservacion =
				this.permitirAgregarEditar &&
				!this.permitirEditarReservacion &&
				(this.fechaRegistroLimite < new Date(this.Reservacion.fecha_inicio) || this.Reservacion.pagado == 0);
			this.mostrarDialogoDetallesReservacion = true;
		} catch (e) {
			hlpSwal.Error(e);
		}
	}

	async onReservacionEditar(idReservacion: number = 0) {
		try {
			if (idReservacion > 0) {
				if (this.Reservacion?.id_area_comun_reservacion != idReservacion) {
					hlpSwal.Cargando();
					this.Reservacion = await this.areasComunesService
						.ListarReservacion(idReservacion)
						.toPromise()
						.then((r) => r['reservacion'])
						.catch(async (e) => {
							await hlpSwal.Error(e).then(() => null);
						})
						.finally(() => hlpSwal.Cerrar());

					if (this.Reservacion == null) return;
				}
				this.Reservacion.importe_sugerido = 0;
			} else {
				this.Reservacion = new AreaComunReservacionModel();
			}

			this.fechaRegistroLimite = new Date();
			this.Reservacion.fecha_inicio = idReservacion > 0 ? new Date(this.Reservacion.fecha_inicio) : new Date();
			this.Reservacion.fecha_fin = idReservacion > 0 ? new Date(this.Reservacion.fecha_fin) : new Date();
			this.Reservacion.pagado = this.Reservacion.pagado == 1 ? true : false;
			this.Reservacion.fecha_pago =
				idReservacion > 0 && this.Reservacion.fecha_pago != null
					? new Date(this.Reservacion.fecha_pago + 'T00:00:00')
					: new Date();

			this.frmReservacion = this.formBuilder.group(this.Reservacion, {
				validators: FormsValidator.fechaMenorQue('fecha_inicio', 'fecha_fin'),
			});
			this.frmReservacion.get('id_area_comun').setValidators([Validators.required, Validators.min(1)]);
			this.frmReservacion.get('id_usuario').setValidators([Validators.required, Validators.min(1)]);
			this.frmReservacion.get('fecha_inicio').setValidators([Validators.required]);
			this.frmReservacion.get('fecha_fin').setValidators([Validators.required]);
			this.frmReservacion.get('importe_total').setValidators([Validators.required, Validators.min(0)]);
			this.frmReservacion.get('pagado').setValidators([Validators.required]);

			this.frmReservacion.get('importe_hora').disable();
			this.frmReservacion.get('importe_sugerido').disable();

			if (this.Reservacion.fecha_inicio < this.fechaRegistroLimite) {
				this.frmReservacion.get('id_area_comun').disable();
				this.frmReservacion.get('id_usuario').disable();
				this.frmReservacion.get('fecha_inicio').disable();
				this.frmReservacion.get('fecha_fin').disable();
				this.frmReservacion.get('importe_total').disable();
			}

			this.onCalcularImporteSugerido();
			this.onPagadoChange(this.frmReservacion.get('pagado').value);
			if (this.mostrarDialogoDetallesReservacion) {
				this.mostrarDialogoDetallesReservacion = false;
			}

			this.mostrarDialogoEdicionReservacion = true;
		} catch (e) {
			hlpSwal.Error(e);
		}
	}

	onAreaComunSeleccionada(idAreaComun: number = 0) {
		this.frmReservacion.get('importe_hora').setValue(0);
		const ac = this.AreasComunes.filter((ac) => (ac.id_area_comun = idAreaComun))[0];
		if (!ac) {
			return;
		}
		this.frmReservacion.get('importe_hora').setValue(ac.importe_hora);
	}

	onCalcularImporteSugerido() {
		let fechaInicio = this.frmReservacion.get('fecha_inicio').value;
		let fechaFin = this.frmReservacion.get('fecha_fin').value;

		if (
			Object.prototype.toString.call(fechaInicio) !== '[object Date]' ||
			isNaN(fechaInicio) ||
			Object.prototype.toString.call(fechaFin) !== '[object Date]' ||
			isNaN(fechaFin) ||
			fechaFin < fechaInicio
		) {
			this.frmReservacion.get('importe_sugerido').setValue(0);
			return;
		}
		let diferenciaHoras = Math.abs(fechaInicio - fechaFin) / 36e5;
		diferenciaHoras = Math.trunc(diferenciaHoras) + (diferenciaHoras % 1 != 0 ? 1 : 0);

		this.frmReservacion
			.get('importe_sugerido')
			.setValue(diferenciaHoras * this.frmReservacion.get('importe_hora').value);
	}

	onPagadoChange(valor: boolean = false) {
		if (!valor) {
			this.frmReservacion.get('fecha_pago').setValue(null);
			this.frmReservacion.get('fecha_pago').disable();
		} else {
			this.frmReservacion.get('fecha_pago').setValue(this.Reservacion.fecha_pago);
			this.frmReservacion.get('fecha_pago').enable();
		}
		this.frmReservacion.updateValueAndValidity();
	}

	onReservacionEditarGuardar() {
		if (!this.frmReservacion.valid) {
			this.frmReservacion.markAllAsTouched();
			hlpSwal.Error('Se detectaron errores en la información solicitada.');
			return;
		}

		let fechaInicio = new Date(this.frmReservacion.get('fecha_inicio').value).getTime();
		let fechaFin = new Date(this.frmReservacion.get('fecha_fin').value).getTime();

		if (fechaFin <= fechaInicio) {
			hlpSwal.Error('La fecha final no puede ser menor a la de inicio.');
			return;
		}

		let reservacion = this.frmReservacion.value;
		/* reservacion.fecha_inicio = hlpApp.formatDateToMySQL(reservacion.fecha_inicio);
		reservacion.fecha_fin = hlpApp.formatDateToMySQL(reservacion.fecha_fin); */
		let data = {
			id_area_comun_reservacion: reservacion.id_area_comun_reservacion,
			id_area_comun: reservacion.id_area_comun,
			id_usuario: reservacion.id_usuario,
			fecha_inicio: hlpApp.formatDateToMySQL(reservacion.fecha_inicio),
			fecha_fin: hlpApp.formatDateToMySQL(reservacion.fecha_fin),
			importe_total: reservacion.importe_total,
			fecha_pago: reservacion.pagado == 1 ? hlpApp.formatDateToMySQL(reservacion.fecha_pago).substring(0, 10) : null,
		};
		console.log('reservacion :>> ', data);

		hlpSwal
			.Pregunta({
				html: '¿Deseas guardar la información?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.areasComunesService.GuardarReservacion(data).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err && r.value.reservacion) {
					const re = {
						groupId: r.value.reservacion.id_area_comun_reservacion,
						id: r.value.reservacion.id_area_comun_reservacion,
						title: r.value.reservacion.area_comun + ' - ' + r.value.reservacion.usuario,
						start: r.value.reservacion.fecha_inicio.replace(' ', 'T'),
						end: r.value.reservacion.fecha_fin.replace(' ', 'T'),
						backgroundColor: r.value.reservacion.pagado == 0 ? 'red' : '',
					};
					if (reservacion.id_area_comun_reservacion == 0) {
						this.Reservaciones.push(re);
					} else {
						this.Reservaciones = this.Reservaciones.map((C) => (C.id === re.id ? re : C));
					}
					this.calendarComponent.getApi().removeAllEvents();
					this.calendarOptions.events = this.Reservaciones;
					hlpSwal.ExitoToast(r.value.msg);
					this.mostrarDialogoEdicionReservacion = false;
				}
			});
	}

	onReservacionEditarCancelar() {
		this.mostrarDialogoEdicionReservacion = false;
	}

	onReservacionCancelar(idReservacion: number = 0) {
		if (idReservacion < 1) {
			return;
		}

		hlpSwal
			.Pregunta({
				html: '¿Deseas cancelar la reservacion?<br><p class="pt-2 text-danger fw-bold">ESTE PROCESO ES IRREVERSIBLE.</p>',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.areasComunesService.CancelarReservacion(idReservacion).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err) {
					this.Reservaciones = this.Reservaciones.filter((r) => Number(r.id) != idReservacion);
					this.calendarComponent.getApi().removeAllEvents();
					this.calendarOptions.events = this.Reservaciones;
					this.mostrarDialogoDetallesReservacion = false;
					hlpSwal.ExitoToast(r.value.msg);
				}
			});
	}
}
