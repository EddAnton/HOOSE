import { Component, OnInit, ViewChild, isDevMode } from '@angular/core';
import { FormArray, FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';

import { environment } from '../../../environments/environment';
import * as hlpApp from '../../helpers/app-helper';
import * as hlpSwal from '../../helpers/sweetalert2-helper';
import * as hlpPrimeNGTable from '../../helpers/primeng-table-helper';
import { ProyectoImagenModel, ProyectoModel, ProyectoResumenModel } from '../../models/proyecto.model';
import { SesionUsuarioService } from 'src/app/services/sesion-usuario.service';
import { ProyectosService } from '../../services/proyectos.service';
import { FileUpload } from 'primeng/fileupload';

@Component({
	selector: 'app-proyectos',
	templateUrl: './proyectos.component.html',
	styleUrls: ['./proyectos.component.css'],
})
export class ProyectosComponent implements OnInit {
	appData = environment;
	hlpApp = hlpApp;
	hlpPrimeNGTable = hlpPrimeNGTable;
	environment = environment;
	isDevelopment = isDevMode;

	@ViewChild('txtArchivosImagenes') txtArchivosImagenes: FileUpload;

	// Tabla Proyectos
	// Columnas de la tabla
	ProyectosCols: any[] = [
		{ header: 'Titulo' },
		{ header: 'Presupuesto', width: '90px' },
		{ header: 'Fecha Inicio', width: '130px' },
		{ header: 'Fecha Fin', width: '130px' },
		{ header: 'Porcentaje', width: '80px' },
		// Botones de acción
		{ textAlign: 'center', width: '130px' },
	];
	ProyectosFilter: any[] = ['titulo', 'presupuesto', 'fecha_inicio', 'fecha_fin'];

	Proyectos: ProyectoResumenModel[] = [];
	Proyecto: ProyectoModel;

	frmProyecto: FormGroup;
	mostrarDialogoEdicionProyecto: boolean = false;
	srcImagen: string = null;
	mostrarDialogoImagen: boolean = false;

	mostrarDialogoDetallesProyecto: boolean = false;
	mostrarDialogoGaleriaImagenes: boolean = false;
	galeriaIndiceActivo: number = 0;
	mostrarGaleria: boolean = false;

	permitirAgregarEditar: boolean = false;

	constructor(
		private formBuilder: FormBuilder,
		private sesionUsuarioService: SesionUsuarioService,
		private proyectosService: ProyectosService,
	) {}

	ngOnInit() {
		this.permitirAgregarEditar = [1, 2, 3].includes(this.sesionUsuarioService.obtenerIDPerfilUsuario());
		this.onActualizarInformacion();
	}

	private OrdenarProyectos(proyectos: ProyectoResumenModel[]) {
		return proyectos.sort((a, b) => (a.titulo < b.titulo ? 1 : -1));
	}

	private agregarURLImagenes() {
		if (this.Proyecto.imagenes.length < 1) {
			return;
		}

		this.Proyecto.imagenes.forEach((i) => {
			i.imagen = environment.urlBackendProyectosFiles + this.Proyecto.id_proyecto + '/' + i.imagen;
		});
	}

	public onActualizarInformacion() {
		this.Proyectos = [];

		hlpSwal.Cargando();

		this.proyectosService
			.ListarActivos()
			.toPromise()
			.then((r) => {
				this.Proyectos = this.OrdenarProyectos(r['proyectos']);
			})
			.catch(async (e) => {
				await hlpSwal.Error(e);
			})
			.finally(() => {
				hlpSwal.Cerrar();
			});
	}

	async onProyectoEditar(idProyecto: number = 0) {
		if (idProyecto > 0) {
			hlpSwal.Cargando();

			this.Proyecto = await this.proyectosService
				.ListarProyecto(idProyecto)
				.toPromise()
				.then((r) => {
					return r['proyecto'];
				})
				.catch(async (e) => {
					await hlpSwal.Error(e).then(() => null);
				})
				.finally(() => hlpSwal.Cerrar());

			if (this.Proyecto == null) return;

			this.agregarURLImagenes();
			this.Proyecto.imagenes_borrar = [];
			// this.Proyecto.archivos_imagenes = [];
		} else {
			this.Proyecto = new ProyectoModel();
		}

		try {
			this.Proyecto.fecha_inicio = idProyecto > 0 ? new Date(this.Proyecto.fecha_inicio + 'T00:00:00') : new Date();
			this.Proyecto.fecha_fin = idProyecto > 0 ? new Date(this.Proyecto.fecha_fin + 'T00:00:00') : new Date();

			this.frmProyecto = this.formBuilder.group(this.Proyecto);
			this.frmProyecto
				.get('titulo')
				.setValidators([Validators.required, Validators.minLength(3), Validators.maxLength(150)]);
			this.frmProyecto
				.get('descripcion')
				.setValidators([Validators.required, Validators.minLength(0), Validators.maxLength(65500)]);
			this.frmProyecto.get('presupuesto').setValidators([Validators.required, Validators.min(0.1)]);
			this.frmProyecto.get('fecha_inicio').setValidators([Validators.required]);
			this.frmProyecto.get('porcentaje_avance').setValidators([Validators.min(0), Validators.max(100)]);
			this.frmProyecto.addControl('archivos_imagenes', this.formBuilder.array([]));

			this.frmProyecto.updateValueAndValidity();

			this.mostrarDialogoEdicionProyecto = true;
		} catch (e) {
			hlpSwal.Error(e);
		}
	}

	onImagenMostrar(srcImagen: string = null) {
		this.srcImagen = srcImagen;
		this.mostrarDialogoImagen = this.srcImagen != null;
	}

	onEliminarImagen(idProyectoImagen: number = 0) {
		if (idProyectoImagen == 0) return;

		hlpSwal
			.Pregunta({
				html: '¿Deseas eliminar la imagen al guardar cambios?',
			})
			.then((r) => {
				if (r.isConfirmed) {
					this.Proyecto.imagenes_borrar.push(idProyectoImagen);
					this.Proyecto.imagenes = this.Proyecto.imagenes.filter((i) => i.id_proyecto_imagen != idProyectoImagen);
					hlpSwal.ExitoToast('Imagen marcada para ser eliminada.');
				}
			});
	}

	onProyectoGuardar() {
		if (!this.frmProyecto.valid) {
			this.frmProyecto.markAllAsTouched();
			hlpSwal.Error('Se detectaron errores en la información solicitada.');
			return;
		}

		let fechaInicio = new Date(this.frmProyecto.get('fecha_inicio').value).getTime();
		let fechaFin = new Date(this.frmProyecto.get('fecha_fin').value).getTime();

		if (fechaInicio > fechaFin) {
			hlpSwal.Error('La fecha final no puede ser menor a la de inicio.');
			return;
		}

		let proyecto = this.frmProyecto.value;
		proyecto.archivos_imagenes = this.txtArchivosImagenes.files;
		proyecto.imagenes_borrar = this.Proyecto.imagenes_borrar;
		proyecto.fecha_inicio = hlpApp.formatDateToMySQL(proyecto.fecha_inicio);
		proyecto.fecha_fin = hlpApp.formatDateToMySQL(proyecto.fecha_fin);
		delete proyecto.imagenes;

		hlpSwal
			.Pregunta({
				html: '¿Deseas guardar la información?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.proyectosService.Guardar(proyecto).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err && r.value.proyecto) {
					const re = r.value.proyecto;
					if (proyecto.id_proyecto == 0) {
						this.Proyectos.push(re);
					} else {
						this.Proyectos = this.Proyectos.map((C) => (C.id_proyecto === re.id_proyecto ? re : C));
					}
					this.Proyectos = this.OrdenarProyectos(this.Proyectos);
					hlpSwal.ExitoToast(r.value.msg);
					this.mostrarDialogoEdicionProyecto = false;
				}
			});
	}

	onProyectoCancelar() {
		this.mostrarDialogoEdicionProyecto = false;
	}

	onMostrarGaleria() {
		this.galeriaIndiceActivo = 0;
		this.mostrarGaleria = true;
	}

	async onProyectoDetalles(idProyecto: number = 0) {
		if (idProyecto == 0) {
			return;
		}

		hlpSwal.Cargando();

		this.Proyecto = await this.proyectosService
			.ListarProyecto(idProyecto)
			.toPromise()
			.then((r) => {
				return r['proyecto'];
			})
			.catch(async (e) => {
				await hlpSwal.Error(e).then(() => null);
			})
			.finally(() => hlpSwal.Cerrar());
		this.agregarURLImagenes();

		this.mostrarDialogoDetallesProyecto = this.Proyecto != null;
	}

	onProyectoEliminar(Proyecto: ProyectoResumenModel = null) {
		if (Proyecto == null) {
			return;
		}

		hlpSwal
			.Pregunta({
				html: '¿Deseas eliminar el proyecto?<br><p class="pt-2 text-danger fw-bold">ESTE PROCESO ES IRREVERSIBLE.</p>',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.proyectosService.Eliminar(Proyecto.id_proyecto).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err) {
					this.Proyectos = this.Proyectos.filter((r: ProyectoResumenModel) => r.id_proyecto != Proyecto.id_proyecto);
					hlpSwal.ExitoToast(r.value.msg);
				}
			});
	}
}
