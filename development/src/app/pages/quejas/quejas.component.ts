import { Component, OnInit, ViewChild, isDevMode } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';

import { environment } from '../../../environments/environment';
import * as hlpApp from '../../helpers/app-helper';
import * as hlpSwal from '../../helpers/sweetalert2-helper';
import * as hlpPrimeNGTable from '../../helpers/primeng-table-helper';
import {
	ColaboradorDisponibleAsignarModel,
	QuejaActualizarEstatusModel,
	QuejaAsignarColaboradorModel,
	QuejaDetalleModel,
	QuejaModel,
	QuejaResumenModel,
	QuejaSeguimientoModel,
} from '../../models/queja.model';
import { QuejasService } from '../../services/quejas.service';
import { UsuariosColaboradoresService } from '../../services/usuarios-colaboradores.service';
import { EstatusQuejaModel } from 'src/app/models/estatus-queja.model';
import { EstatusQuejasService } from '../../services/estatus-quejas.service';
import { SesionUsuarioService } from 'src/app/services/sesion-usuario.service';
import { FileUpload } from 'primeng/fileupload';
// import { FormsValidator } from '../../validators/forms.validator';

@Component({
	selector: 'app-quejas',
	templateUrl: './quejas.component.html',
	styleUrls: ['./quejas.component.css'],
})
export class QuejasComponent implements OnInit {
	appData = environment;
	hlpApp = hlpApp;
	hlpPrimeNGTable = hlpPrimeNGTable;
	isDevelopment = isDevMode;

	@ViewChild('txtEvidenciaArchivos') txtEvidenciaArchivos: FileUpload;

	// Tabla Quejas
	// Columnas de la tabla
	QuejasCols: any[] = [
		{ header: 'Título' },
		{ header: 'Fecha' },
		{ header: 'Registró' },
		{ header: 'Asignada a' },
		{ header: 'Estatus' },
		// Botones de acción
		{ textAlign: 'center', width: '170px' },
	];
	QuejasFilter: any[] = ['nombre'];

	// Tabla Seguimiento
	// Columnas de la tabla
	QuejaSeguimientoCols: any[] = [
		{ header: 'Fecha', width: '190px' },
		{ header: 'Descripción' },
		{ header: 'Registró' },
		// Botones de acción
		{ textAlign: 'center', width: '90px' },
	];
	// Tabla Seguimiento Detalle
	// Columnas de la tabla
	QuejaSeguimientoDetalleCols: any[] = [
		{ header: 'Fecha', width: '190px' },
		{ header: 'Descripción' },
		{ header: 'Registró' },
	];

	Quejas: QuejaResumenModel[] = [];
	QuejaResumen: QuejaResumenModel;
	// idQueja: number;
	Queja: QuejaModel;
	QuejaDetalle: QuejaDetalleModel;
	ColaboradoresDisponiblesAsignar: ColaboradorDisponibleAsignarModel[] = [];
	QuejaAsignarColaborador: QuejaAsignarColaboradorModel;
	EstatusQuejas: EstatusQuejaModel[] = [];
	EstatusQuejaSeleccionado: EstatusQuejaModel;
	QuejaActualizarEstatus: QuejaActualizarEstatusModel;
	QuejaSeguimiento: QuejaSeguimientoModel[] = [];
	Seguimiento: QuejaSeguimientoModel;

	permitirAgregar: boolean = false;
	permitirEditar: boolean = false;
	// idPerfilUsuario: number = 0;
	idUsuario: number = 0;
	fechaRegistroLimite: Date = new Date();

	frmQueja: FormGroup;
	mostrarDialogoEdicionQueja: boolean = false;
	srcArchivoQueja: string = null;
	mostrarDialogoArchivoQueja: boolean = false;
	mostrarDialogoDetalleQueja: boolean = false;
	frmQuejaAsignarColaborador: FormGroup;
	mostrarDialogoQuejaAsignarColaborador: boolean = false;
	frmQuejaActualizarEstatus: FormGroup;
	mostrarDialogoQuejaActualizarEstatus: boolean = false;
	mostrarDialogoQuejaSeguimiento: boolean = false;
	mostrarDialogoEdicionSeguimiento: boolean = false;
	frmSeguimiento: FormGroup;

	responsiveOptions: any = [
		/* {
			breakpoint: '1450px',
			numVisible: 4,
			numScroll: 4,
		}, */
		{
			breakpoint: '1450px',
			numVisible: 2,
			numScroll: 2,
		},
		/* 		{
			breakpoint: '992px',
			numVisible: 2,
			numScroll: 2,
		}, */
		{
			breakpoint: '860px',
			numVisible: 1,
			numScroll: 1,
		},
	];

	constructor(
		private formBuilder: FormBuilder,
		private sesionUsuarioService: SesionUsuarioService,
		private quejasService: QuejasService,
		private usuariosColaboradoresService: UsuariosColaboradoresService,
		private estatusQuejasService: EstatusQuejasService,
	) {}

	ngOnInit(): void {
		// this.idPerfilUsuario = this.sesionUsuarioService.obtenerIDPerfilUsuario();
		this.permitirAgregar = this.sesionUsuarioService.obtenerIDPerfilUsuario() != 3;
		this.permitirEditar = [1, 2].includes(this.sesionUsuarioService.obtenerIDPerfilUsuario());
		this.idUsuario = this.sesionUsuarioService.obtenerIDUsuario();
		this.onActualizarInformacion();
	}

	private OrdenarQuejas(quejas: QuejaResumenModel[]) {
		return quejas.sort((a, b) =>
			a.id_estatus_queja + new Date(a.fecha).toString() > b.id_estatus_queja + new Date(b.fecha).toString() ? 1 : -1,
		);
	}

	private agregarURLArchivosQueja() {
		if (this.Queja.archivos.length < 1) {
			return;
		}

		this.Queja.archivos.forEach((i) => {
			i.archivo = environment.urlBackendQuejasFiles + this.Queja.id_queja + '/' + i.archivo;
		});
	}

	private OrdenarSeguimiento(seguimiento: QuejaSeguimientoModel[]) {
		return seguimiento.sort((a, b) => (new Date(a.fecha).toString() < new Date(b.fecha).toString() ? 1 : -1));
	}

	public onActualizarInformacion() {
		this.Quejas = [];

		hlpSwal.Cargando();

		this.usuariosColaboradoresService
			.ListarActivos()
			.toPromise()
			.then((r) => {
				this.ColaboradoresDisponiblesAsignar = r['colaboradores']
					.map((c) => {
						return { id_usuario_asignado: c.id_usuario, nombre: c.nombre };
					})
					.sort((a, b) => (a.nombre > b.nombre ? 1 : -1));
			})
			.catch(async (e) => {
				await hlpSwal.Error(e);
			});

		this.estatusQuejasService
			.ListarActivos()
			.toPromise()
			.then((r) => {
				this.EstatusQuejas = r['estatus_quejas'];
			})
			.catch(async (e) => {
				await hlpSwal.Error(e);
			});

		this.quejasService
			.ListarActivos()
			.toPromise()
			.then((r) => {
				this.Quejas = this.OrdenarQuejas(r['quejas']);
			})
			.catch(async (e) => {
				await hlpSwal.Error(e);
			})
			.finally(() => {
				hlpSwal.Cerrar();
			});
	}

	async onQuejaEditar(idQueja: number = 0) {
		try {
			if (idQueja > 0) {
				hlpSwal.Cargando();
				this.Queja = await this.quejasService
					.ListarQueja(idQueja)
					.toPromise()
					.then((r) => r['queja'])
					.catch(async (e) => {
						await hlpSwal.Error(e).then(() => null);
					})
					.finally(() => hlpSwal.Cerrar());

				if (this.Queja == null) return;

				this.agregarURLArchivosQueja();
				this.Queja.archivos_borrar = [];
			} else {
				this.Queja = new QuejaModel();
			}

			this.frmQueja = this.formBuilder.group(this.Queja);
			this.frmQueja
				.get('titulo')
				.setValidators([Validators.required, Validators.minLength(3), Validators.maxLength(150)]);
			this.frmQueja
				.get('descripcion')
				.setValidators([Validators.required, Validators.minLength(3), Validators.maxLength(1000)]);
			this.frmQueja.addControl('archivos', this.formBuilder.array([]));

			this.frmQueja.updateValueAndValidity();

			this.mostrarDialogoEdicionQueja = true;
		} catch (e) {
			hlpSwal.Error(e);
		}
	}

	onMostrarArchivoQueja(srcArchivoQueja: string = null) {
		this.srcArchivoQueja = srcArchivoQueja;
		this.mostrarDialogoArchivoQueja = this.srcArchivoQueja != null;
	}

	onArchivoEliminar(idQuejaArchivo: number = 0) {
		if (idQuejaArchivo == 0) return;

		hlpSwal
			.Pregunta({
				html: '¿Deseas eliminar el archivo al guardar cambios?',
			})
			.then((r) => {
				if (r.isConfirmed) {
					this.Queja.archivos_borrar.push(idQuejaArchivo);
					this.Queja.archivos = this.Queja.archivos.filter((i) => i.id_queja_archivo != idQuejaArchivo);
					hlpSwal.ExitoToast('Archivo marcado para eliminación al guardar la información.');
				}
			});
	}

	onQuejaGuardar() {
		if (!this.frmQueja.valid) {
			this.frmQueja.markAllAsTouched();
			hlpSwal.Error('Se detectaron errores en la información solicitada.');
			return;
		}

		let queja = this.frmQueja.value;
		queja.archivos = this.txtEvidenciaArchivos.files;
		queja.archivos_borrar = this.Queja.archivos_borrar;

		hlpSwal
			.Pregunta({
				html: '¿Deseas guardar la información?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.quejasService.Guardar(queja).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err && r.value.queja) {
					const c = {
						id_queja: r.value.queja.id_queja,
						titulo: r.value.queja.titulo,
						fecha: r.value.queja.fecha,
						id_estatus_queja: r.value.queja.id_estatus_queja,
						estatus_queja: r.value.queja.estatus_queja,
						id_usuario_asignado: r.value.queja.id_usuario_asignado,
						usuario_asignado: r.value.queja.usuario_asignado,
						id_usuario_registro: r.value.queja.id_usuario_registro,
						usuario_registro: r.value.queja.usuario_registro,
						estatus: r.value.queja.estatus,
					};
					if (queja.id_queja == 0) {
						this.Quejas.push(c);
					} else {
						this.Quejas = this.Quejas.map((C) => (C.id_queja === c.id_queja ? c : C));
					}
					this.Quejas = this.OrdenarQuejas(this.Quejas);
					hlpSwal.Cerrar();
					hlpSwal.ExitoToast(r.value.msg);
					this.mostrarDialogoEdicionQueja = false;
				}
			});
	}

	onQuejaCancelar() {
		this.mostrarDialogoEdicionQueja = false;
	}

	async onQuejaDetalle(idQueja: number = 0) {
		hlpSwal.Cargando();

		if (idQueja < 1) {
			return;
		}
		try {
			this.Queja = await this.quejasService
				.ListarQueja(idQueja)
				.toPromise()
				.then((r) => r['queja'])
				.catch(async (e) => {
					await hlpSwal.Error(e).then(() => null);
				})
				.finally(() => hlpSwal.Cerrar());

			// this.QuejaSeguimiento = this.Queja.seguimiento;
			this.Queja.seguimiento = this.OrdenarSeguimiento(this.Queja.seguimiento);
			this.agregarURLArchivosQueja();
			this.mostrarDialogoDetalleQueja = true;
		} catch (e) {
			hlpSwal.Error(e);
		}
	}

	onQuejaEliminar(idQueja: number = 0) {
		hlpSwal
			.Pregunta({
				html: '¿Deseas eliminar la queja?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.quejasService.Eliminar(idQueja).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err) {
					this.Quejas = this.Quejas.filter((q: QuejaResumenModel) => q.id_queja != idQueja);
					hlpSwal.ExitoToast(r.value.msg);
				}
			});
	}

	onQuejaAsignarColaboradorEditar(queja: QuejaResumenModel = null) {
		if (queja == null) {
			return;
		}

		try {
			this.QuejaAsignarColaborador = new QuejaAsignarColaboradorModel();
			this.QuejaAsignarColaborador.id_queja = queja.id_queja;
			this.QuejaAsignarColaborador.id_usuario_asignado =
				queja.id_usuario_asignado != null ? queja.id_usuario_asignado : 0;

			this.frmQuejaAsignarColaborador = this.formBuilder.group(this.QuejaAsignarColaborador);
			this.frmQuejaAsignarColaborador
				.get('id_usuario_asignado')
				.setValidators([Validators.required, Validators.min(1)]);
			this.frmQuejaAsignarColaborador.updateValueAndValidity();

			this.mostrarDialogoQuejaAsignarColaborador = true;
		} catch (e) {
			hlpSwal.Error(e);
		}
	}

	onQuejaAsignarColaboradorGuardar() {
		if (!this.frmQuejaAsignarColaborador.valid) {
			this.frmQuejaAsignarColaborador.markAllAsTouched();
			hlpSwal.Error('Se detectaron errores en la información solicitada.');
			return;
		}

		let quejaAsignarColaborador = this.frmQuejaAsignarColaborador.value;

		hlpSwal
			.Pregunta({
				html: '¿Deseas guardar la información?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.quejasService.AsignarColaborador(quejaAsignarColaborador).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err && r.value.queja) {
					const q = r.value.queja;

					this.Quejas = this.Quejas.map((Q) => (Q.id_queja === q.id_queja ? q : Q));
					this.Quejas = this.OrdenarQuejas(this.Quejas);

					/* const Q = this.Quejas.find((q) => q.id_queja == q.id_queja);
					Q.id_usuario_asignado = q.id_usuario_asignado;
					Q.usuario_asignado = q.usuario_asignado; */

					hlpSwal.ExitoToast(r.value.msg);
					this.mostrarDialogoQuejaAsignarColaborador = false;
				}
			});
	}

	onQuejaAsignarColaboradorCancelar() {
		this.mostrarDialogoQuejaAsignarColaborador = false;
	}

	onQuejaActualizarEstatusEditar(queja: QuejaResumenModel = null) {
		if (queja == null) {
			return;
		}

		try {
			this.EstatusQuejaSeleccionado = new EstatusQuejaModel();
			this.QuejaActualizarEstatus = new QuejaActualizarEstatusModel();
			this.QuejaActualizarEstatus.id_queja = queja.id_queja;
			this.QuejaActualizarEstatus.id_estatus_queja = queja.id_estatus_queja != null ? queja.id_estatus_queja : 0;

			this.frmQuejaActualizarEstatus = this.formBuilder.group(this.QuejaActualizarEstatus);
			this.frmQuejaActualizarEstatus.get('id_estatus_queja').setValidators([Validators.required, Validators.min(1)]);
			// this.frmQuejaActualizarEstatus.get('solucion').setValidators([Validators.maxLength(500)]);
			// this.frmQuejaActualizarEstatus.updateValueAndValidity();
			this.onEstatusQuejaSeleccionado(this.QuejaActualizarEstatus.id_estatus_queja);

			this.mostrarDialogoQuejaActualizarEstatus = true;
		} catch (e) {
			hlpSwal.Error(e);
		}
	}

	onEstatusQuejaSeleccionado(idEstatusQueja: number = 0) {
		this.EstatusQuejaSeleccionado = this.EstatusQuejas.find((e) => e.id_estatus_queja == idEstatusQueja);
		if (!this.EstatusQuejaSeleccionado) {
			return;
		}
		if (this.EstatusQuejaSeleccionado.debe_especificar_solucion == 1) {
			this.frmQuejaActualizarEstatus.get('solucion').setValidators([Validators.required, Validators.maxLength(500)]);
			this.frmQuejaActualizarEstatus.get('solucion').enable();
		} else {
			this.frmQuejaActualizarEstatus.get('solucion').setValidators([Validators.maxLength(500)]);
			this.frmQuejaActualizarEstatus.get('solucion').setValue(null);
			this.frmQuejaActualizarEstatus.get('solucion').disable();
		}
		this.frmQuejaActualizarEstatus.updateValueAndValidity();
	}

	onQuejaActualizarEstatusGuardar() {
		if (!this.frmQuejaActualizarEstatus.valid) {
			this.frmQuejaActualizarEstatus.markAllAsTouched();
			hlpSwal.Error('Se detectaron errores en la información solicitada.');
			return;
		}

		let quejaActualizarEstatus = this.frmQuejaActualizarEstatus.value;

		hlpSwal
			.Pregunta({
				html: '¿Deseas guardar la información?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.quejasService.ActualizarEstatus(quejaActualizarEstatus).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err && r.value.queja) {
					const q = r.value.queja;
					const Q = this.Quejas.find((q) => q.id_queja == q.id_queja);
					Q.id_estatus_queja = q.id_estatus_queja;
					Q.estatus_queja = q.estatus_queja;
					this.Quejas = this.OrdenarQuejas(this.Quejas);

					hlpSwal.ExitoToast(r.value.msg);

					this.mostrarDialogoQuejaActualizarEstatus = false;
				}
			});
	}

	onQuejaActualizarEstatusCancelar() {
		this.mostrarDialogoQuejaActualizarEstatus = false;
	}

	async onMostrarQuejaSeguimiento(idQueja: number = 0) {
		// this.idQueja = 0;
		this.QuejaResumen = null;

		if (idQueja < 1) {
			return;
		}

		try {
			hlpSwal.Cargando();
			// this.idQueja = idQueja;
			this.QuejaResumen = this.Quejas.filter((Q) => Q.id_queja == idQueja)[0];
			this.QuejaSeguimiento = [];
			this.QuejaSeguimiento = await this.quejasService
				.ListarSeguimiento(this.QuejaResumen.id_queja)
				.toPromise()
				.then((r) => r['seguimiento'])
				.catch(async (e) => {
					await hlpSwal.Error(e).then(() => null);
				})
				.finally(() => hlpSwal.Cerrar());

			if (this.QuejaSeguimiento == null) return;

			this.QuejaSeguimiento = this.OrdenarSeguimiento(this.QuejaSeguimiento);

			this.mostrarDialogoQuejaSeguimiento = true;
		} catch (e) {
			hlpSwal.Error(e);
		}
	}

	async onSeguimientoEditar(seguimiento: QuejaSeguimientoModel = null) {
		try {
			if (!seguimiento) {
				seguimiento = new QuejaSeguimientoModel();
				// seguimiento.id_queja = this.idQueja;
				seguimiento.id_queja = this.QuejaResumen.id_queja;
			}
			this.fechaRegistroLimite = new Date();
			this.Seguimiento = seguimiento;

			this.Seguimiento.fecha =
				this.Seguimiento.id_queja_seguimiento > 0 ? new Date(this.Seguimiento.fecha) : new Date();

			this.frmSeguimiento = this.formBuilder.group(this.Seguimiento);
			/* this.frmSeguimiento = this.formBuilder.group(this.Seguimiento, {
				validators: FormsValidator.fechaMenorQue('fecha_inicio', 'fecha_fin'),
			}); */
			this.frmSeguimiento.get('fecha').setValidators([Validators.required]);
			this.frmSeguimiento
				.get('seguimiento')
				.setValidators([Validators.required, Validators.minLength(3), Validators.maxLength(1000)]);

			this.frmSeguimiento.updateValueAndValidity();

			this.mostrarDialogoEdicionSeguimiento = true;
		} catch (e) {
			hlpSwal.Error(e);
		}
	}

	public onSeguimientoGuardar() {
		if (!this.frmSeguimiento.valid) {
			this.frmSeguimiento.markAllAsTouched();
			hlpSwal.Error('Se detectaron errores en la información solicitada.');
			return;
		}

		let fechaRegistroLimite = new Date().getTime();
		let fecha = new Date(this.frmSeguimiento.get('fecha').value).getTime();

		if (fecha > fechaRegistroLimite) {
			hlpSwal.Error('La fecha no puede ser mayor a la actual.');
			return;
		}

		let seguimiento = this.frmSeguimiento.value;
		seguimiento.fecha = hlpApp.formatDateToMySQL(seguimiento.fecha);

		hlpSwal
			.Pregunta({
				html: '¿Deseas guardar la información?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.quejasService.GuardarSeguimiento(seguimiento).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err && r.value.seguimiento) {
					const s = r.value.seguimiento;
					if (seguimiento.id_queja_seguimiento == 0) {
						this.QuejaSeguimiento.push(s);
					} else {
						this.QuejaSeguimiento = this.QuejaSeguimiento.map((S) =>
							S.id_queja_seguimiento === s.id_queja_seguimiento ? s : S,
						);
					}

					this.QuejaSeguimiento = this.OrdenarSeguimiento(this.QuejaSeguimiento);
					hlpSwal.Cerrar();
					hlpSwal.ExitoToast(r.value.msg);
					this.mostrarDialogoEdicionSeguimiento = false;
				}
			});
	}

	public onSeguimientoCancelar() {
		this.mostrarDialogoEdicionSeguimiento = false;
	}

	onSeguimientoEliminar(idSeguimiento: number = 0) {
		if (idSeguimiento < 1) {
			return;
		}

		hlpSwal
			.Pregunta({
				html: '¿Deseas eliminar el seguimiento?<br><p class="pt-2 text-danger fw-bold">ESTE PROCESO ES IRREVERSIBLE.</p>',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.quejasService.EliminarSeguimiento(idSeguimiento).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err) {
					this.QuejaSeguimiento = this.QuejaSeguimiento.filter(
						(s: QuejaSeguimientoModel) => s.id_queja_seguimiento != idSeguimiento,
					);

					hlpSwal.ExitoToast(r.value.msg);
				}
			});
	}
}
