import { Component, OnInit, isDevMode } from '@angular/core';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';

import { environment } from '../../../environments/environment';
import * as hlpApp from '../../helpers/app-helper';
import * as hlpSwal from '../../helpers/sweetalert2-helper';
import * as hlpPrimeNGTable from '../../helpers/primeng-table-helper';
import { VisitaModel, VisitaRegistrarSalidaModel } from '../../models/visita.model';
import { UnidadesEdificioModel } from '../../models/unidad.model';
import { UnidadesService } from '../../services/unidades.service';
import { VisitasService } from '../../services/visitas.service';
import { SesionUsuarioService } from '../../services/sesion-usuario.service';

@Component({
	selector: 'app-visitas',
	templateUrl: './visitas.component.html',
	styleUrls: ['./visitas.component.css'],
})
export class VisitasComponent implements OnInit {
	appData = environment;
	hlpApp = hlpApp;
	hlpPrimeNGTable = hlpPrimeNGTable;
	isDevelopment = isDevMode;

	// Tabla Visitas
	// Columnas de la tabla
	VisitasCols: any[] = [
		{ header: 'Visitante' },
		{ header: 'Contacto' },
		{ header: 'Unidad' },
		{ header: 'Entrada', width: '120px' },
		{ header: 'Salida', width: '120px' },
		// Botones de acción
		{ textAlign: 'center', width: '130px' },
	];
	VisitasFilter: any[] = ['visitante', 'telefono', 'unidad', 'entrada', 'salida'];

	Visitas: VisitaModel[] = [];
	Visita: VisitaModel;
	Unidades: UnidadesEdificioModel[] = [];
	fechaLimite: Date;

	frmVisita: FormGroup;
	frmRegistrarSalida: FormGroup;
	mostrarDialogoEdicion: boolean = false;
	mostrarDialogoRegistrarSalida: boolean = false;
	permitirEliminar: boolean = false;

	constructor(
		private sesionUsuarioService: SesionUsuarioService,
		private formBuilder: FormBuilder,
		private visitasService: VisitasService,
		private unidadesService: UnidadesService,
	) {}

	ngOnInit(): void {
		this.permitirEliminar = [1, 2].includes(this.sesionUsuarioService.obtenerIDPerfilUsuario());
		this.onActualizarInformacion();
	}

	private OrdenarVisitas(visitas: VisitaModel[]) {
		return visitas.sort((a, b) =>
			a.fecha_hora_entrada.toString() + a.visitante > b.fecha_hora_entrada.toString() + b.visitante ? 1 : -1,
		);
	}

	public onActualizarInformacion() {
		this.Visitas = [];
		this.Unidades = [];

		hlpSwal.Cargando();

		(this.permitirEliminar ? this.visitasService.Listar() : this.visitasService.ListarActivos())
			.toPromise()
			.then((r) => {
				this.Visitas = this.OrdenarVisitas(r['visitas']);
			})
			.catch(async (e) => {
				await hlpSwal.Error(e);
			});

		this.unidadesService
			.ListarParaVisita()
			.toPromise()
			.then((r) => {
				this.Unidades = r['unidades'];
			})
			.catch(async (e) => {
				await hlpSwal.Error(e);
			})
			.finally(() => {
				hlpSwal.Cerrar();
			});
	}

	async onVisitaEditar(idVisita: number = 0) {
		hlpSwal.Cargando();

		if (idVisita > 0) {
			this.Visita = await this.visitasService
				.ListarVisita(idVisita)
				.toPromise()
				.then((r) => r['visita'])
				.catch(async (e) => {
					await hlpSwal.Error(e).then(() => null);
				});
			if (this.Visita == null) return;
		} else {
			this.Visita = new VisitaModel();
		}

		hlpSwal.Cerrar();

		try {
			this.fechaLimite = new Date();
			this.Visita.fecha_hora_entrada =
				idVisita > 0 ? new Date(this.Visita.fecha_hora_entrada) : this.Visita.fecha_hora_entrada;
			this.Visita.fecha_hora_salida = this.Visita.fecha_hora_salida
				? new Date(this.Visita.fecha_hora_salida)
				: this.Visita.fecha_hora_salida;

			this.frmVisita = this.formBuilder.group(this.Visita);
			this.frmVisita
				.get('visitante')
				.setValidators([Validators.required, Validators.minLength(3), Validators.maxLength(255)]);
			this.frmVisita
				.get('telefono')
				.setValidators([Validators.required, Validators.minLength(10), Validators.maxLength(12)]);
			this.frmVisita.get('domicilio').setValidators([Validators.maxLength(255)]);
			this.frmVisita.get('identificacion_folio').setValidators([Validators.maxLength(50)]);
			this.frmVisita.get('id_unidad').setValidators([Validators.required, Validators.min(1)]);
			this.frmVisita.get('fecha_hora_entrada').setValidators([Validators.required]);
			this.frmVisita.updateValueAndValidity();

			this.mostrarDialogoEdicion = true;
		} catch (e) {
			hlpSwal.Error(e);
		}
	}

	onVisitaEditarGuardar() {
		if (!this.frmVisita.valid) {
			this.frmVisita.markAllAsTouched();
			hlpSwal.Error('Se detectaron errores en la información solicitada.');
			return;
		}

		let visita = this.frmVisita.getRawValue();
		visita.fecha_hora_entrada = hlpApp.formatDateToMySQL(visita.fecha_hora_entrada);
		if (visita.fecha_hora_salida) {
			visita.fecha_hora_salida = hlpApp.formatDateToMySQL(visita.fecha_hora_salida);
		}

		hlpSwal
			.Pregunta({
				html: '¿Deseas guardar la información?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.visitasService.Guardar(visita).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err && r.value.visita) {
					const re = r.value.visita;
					if (visita.id_visita == 0) {
						this.Visitas.push(re);
					} else {
						this.Visitas = this.Visitas.map((C) => (C.id_visita === re.id_visita ? re : C));
					}
					this.Visitas = this.OrdenarVisitas(this.Visitas);
					hlpSwal.ExitoToast(r.value.msg);
					this.mostrarDialogoEdicion = false;
				}
			});
	}

	onVisitaEditarCancelar() {
		this.mostrarDialogoEdicion = false;
	}

	async onVisitaRegistrarSalida(Visita: VisitaModel) {
		try {
			this.fechaLimite = new Date();
			this.Visita = Visita;
			this.Visita.fecha_hora_entrada = new Date(this.Visita.fecha_hora_entrada);
			this.frmRegistrarSalida = this.formBuilder.group(new VisitaRegistrarSalidaModel());
			// this.frmRegistrarSalida.get('id_visita').setValue(Visita.id_visita);
			this.frmRegistrarSalida.get('fecha_hora_salida').setValidators([Validators.required]);

			this.mostrarDialogoRegistrarSalida = true;
		} catch (e) {
			hlpSwal.Error(e);
		}
	}

	onVisitaRegistrarSalidaGuardar() {
		// let id_visita = this.frmRegistrarSalida.get('id_visita').value;
		let idVisita = this.Visita.id_visita;
		let visita = this.frmRegistrarSalida.value;
		visita.fecha_hora_salida = hlpApp.formatDateToMySQL(visita.fecha_hora_salida);

		hlpSwal
			.Pregunta({
				html: '¿Deseas registrar la salida de la visita?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.visitasService.RegistrarSalida(idVisita, visita).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err) {
					this.mostrarDialogoRegistrarSalida = false;
					this.Visitas = this.OrdenarVisitas(
						this.Visitas.map((re) => (re.id_visita === idVisita ? r.value.visita : re)),
					);
					hlpSwal.ExitoToast(r.value.msg);
				}
			});
	}

	onVisitaRegistrarSalidaCancelar() {
		this.mostrarDialogoRegistrarSalida = false;
	}

	onVisitaEliminar(idVisita: number = 0) {
		hlpSwal
			.Pregunta({
				html: '¿Deseas eliminar la visita?<br><p class="pt-2 text-danger fw-bold">ESTE PROCESO ES IRREVERSIBLE.</p>',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.visitasService.Eliminar(idVisita).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err) {
					this.Visitas = this.Visitas.filter((r: VisitaModel) => r.id_visita != idVisita);
					hlpSwal.ExitoToast(r.value.msg);
				}
			});
	}
}
