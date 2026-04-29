import { Component, OnInit, isDevMode } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';

import { environment } from '../../../environments/environment';
import * as hlpApp from '../../helpers/app-helper';
import * as hlpSwal from '../../helpers/sweetalert2-helper';
import * as hlpPrimeNGTable from '../../helpers/primeng-table-helper';
import { PonteCloudCarpetaModel, PonteCloudContenidoModel } from 'src/app/models/pontecloud-carpeta.model';
import { SesionUsuarioService } from '../../services/sesion-usuario.service';
import { PonteCloudService } from '../../services/ponte-cloud.service';

@Component({
	selector: 'app-ponte-cloud',
	templateUrl: './ponte-cloud.component.html',
	styleUrls: ['./ponte-cloud.component.css'],
})
export class PonteCloudComponent implements OnInit {
	appData = environment;
	hlpApp = hlpApp;
	hlpPrimeNGTable = hlpPrimeNGTable;
	isDevelopment = isDevMode;

	// Tabla Contenido
	// Columnas de la tabla
	ContenidoCols: any[] = [
		{ textAlign: 'center', width: '40px' },
		{ header: 'Nombre' },
		{ header: 'Tamaño', width: '100px' },
		{ header: 'Fecha creación', width: '140px' },
		// Botones de acción
		{ textAlign: 'center', width: '90px' },
	];
	ContenidoFilter: any[] = ['nombre', 'fecha_registro'];
	Carpeta: PonteCloudCarpetaModel = null;
	Contenido: PonteCloudContenidoModel[] = [];

	permitirAgregarEditar: boolean = false;

	frmSubirArchivo: FormGroup;
	mostrarDlgSubirArchivo: boolean = false;

	ElementoRenombrar: PonteCloudContenidoModel = null;
	frmRenombrar: FormGroup;
	mostrarDlgRenombrar: boolean = false;

	constructor(
		private sesionUsuarioService: SesionUsuarioService,
		private formBuilder: FormBuilder,
		private ponteCloudService: PonteCloudService,
	) {}

	ngOnInit() {
		this.permitirAgregarEditar = [1, 2].includes(this.sesionUsuarioService.obtenerIDPerfilUsuario());
		this.onActualizarInformacion();
	}

	private OrdenarContenido(contenido: PonteCloudContenidoModel[]) {
		return contenido.sort((a, b) => (a.tipo + a.nombre.toLowerCase() > b.tipo + b.nombre.toLowerCase() ? 1 : -1));
	}

	public onActualizarInformacion(idCarpeta: number = 0) {
		idCarpeta = idCarpeta > 0 ? idCarpeta : this.Carpeta?.id;
		this.Contenido = [];

		hlpSwal.Cargando();

		this.ponteCloudService
			.Listar(idCarpeta)
			.toPromise()
			.then((r) => {
				this.Carpeta = r['data']['carpeta'];
				this.Contenido = r['data']['contenido'];
				if (this.Carpeta.id_cloud_carpeta_padre != null) {
					this.Contenido.push({
						tipo: 1,
						id: this.Carpeta.id_cloud_carpeta_padre,
						archivo: null,
						nombre: '/',
						tamanio: null,
						unidad_medida: null,
						archivo_interno: null,
						fecha_registro: null,
						fecha_modificacion: null,
					});
				}
				this.Contenido = this.OrdenarContenido(r['data']['contenido']);
			})
			.catch(async (e) => {
				await hlpSwal.Error(e);
			})
			.finally(() => hlpSwal.Cerrar());
	}

	async onAbrirArchivo(srcDocumento: string = null) {
		if (srcDocumento == null) {
			return;
		}
		srcDocumento =
			environment.urlBackendCloudFiles + this.sesionUsuarioService.obtenerIDCondominioUsuario() + '/' + srcDocumento;
		window.open(srcDocumento, '_blank');
	}

	onMostrarCrearCarpeta() {
		this.ElementoRenombrar = null;
		this.frmRenombrar = this.formBuilder.group({
			nombre: ['', [Validators.required, Validators.minLength(1), Validators.maxLength(255)]],
		});
		this.frmRenombrar.updateValueAndValidity();
		this.mostrarDlgRenombrar = true;
	}

	onMostrarRenombrarItem(elementoRenombrar: PonteCloudContenidoModel = null) {
		if (elementoRenombrar == null) {
			return;
		}

		this.ElementoRenombrar = elementoRenombrar;
		this.frmRenombrar = this.formBuilder.group({
			nombre: [
				this.ElementoRenombrar.tipo == 1 ? this.ElementoRenombrar.nombre : this.ElementoRenombrar.archivo,
				[Validators.required, Validators.minLength(1), Validators.maxLength(255)],
			],
		});
		this.frmRenombrar.updateValueAndValidity();
		this.mostrarDlgRenombrar = true;
	}

	onRenombrarGuardar() {
		if (!this.frmRenombrar.valid) {
			this.frmRenombrar.markAllAsTouched();
			hlpSwal.Error('Se detectaron errores en la información solicitada.');
			return;
		}
		let data = this.frmRenombrar.value;

		const msg =
			this.ElementoRenombrar == null
				? '¿Deseas crear la carpeta?'
				: '¿Deseas renombrar ' + (this.ElementoRenombrar.tipo == 1 ? 'la carpeta' : 'el archivo') + '?';

		hlpSwal
			.Pregunta({
				html: msg,
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						const response =
							this.ElementoRenombrar == null
								? await this.ponteCloudService.CarpetaCrear(this.Carpeta.id, data).toPromise()
								: this.ElementoRenombrar.tipo == 1
								? await this.ponteCloudService.CarpetaRenombrar(this.ElementoRenombrar.id, data).toPromise()
								: await this.ponteCloudService.ArchivoRenombrar(this.ElementoRenombrar.id, data).toPromise();
						return response;
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value?.err == false) {
					if (this.ElementoRenombrar == null) {
						this.Contenido.push(r.value.carpeta);
					} else {
						const item = this.ElementoRenombrar.tipo == 1 ? r.value.carpeta : r.value.archivo;
						let indexToUpdate = this.Contenido.findIndex((c) => c.id === item.id && c.tipo == item.tipo);
						this.Contenido[indexToUpdate] = item;
					}
					this.Contenido = this.OrdenarContenido(this.Contenido);

					hlpSwal.Exito(r.value.msg);
					this.mostrarDlgRenombrar = false;
				}
			});
	}

	onMostrarSubirArchivo() {
		this.frmSubirArchivo = this.formBuilder.group({
			archivo: ['', Validators.required],
		});
		this.frmSubirArchivo.updateValueAndValidity();
		this.mostrarDlgSubirArchivo = true;
	}

	onArchivoSeleccionado(event) {
		if (event.target.files.length != 1) return;
		let file: any;
		file = event.target.files[0];

		this.frmSubirArchivo.patchValue({ archivo: file });
		this.frmSubirArchivo.get('archivo').updateValueAndValidity();
	}

	onArchivoSeleccionadoCancelar() {
		(<HTMLInputElement>document.getElementById('txtArchivo')).value = '';
		this.frmSubirArchivo.get('archivo').setValue(null);
	}

	onSubirArchivoGuardar() {
		if (!this.frmSubirArchivo.valid) {
			this.frmSubirArchivo.markAllAsTouched();
			hlpSwal.Error('Se detectaron errores en la información solicitada.');
			return;
		}

		let archivo = this.frmSubirArchivo.getRawValue();

		hlpSwal
			.Pregunta({
				html: '¿Deseas subir el archivo seleccionado?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.ponteCloudService.ArchivoSubir(this.Carpeta.id, archivo).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value?.err == false) {
					this.Contenido.push(r.value.archivo);
					this.Contenido = this.OrdenarContenido(this.Contenido);
					hlpSwal.Exito(r.value.msg);
					this.mostrarDlgSubirArchivo = false;
				}
			});
	}

	onEliminarItem(item: PonteCloudContenidoModel = null) {
		if (item == null) {
			return;
		}
		hlpSwal
			.Pregunta({
				html:
					'¿Deseas eliminar ' +
					(item.tipo == 1 ? 'la carpeta' : 'el archivo') +
					'?<p class="text-start pt-3">Nombre: <b>' +
					item.nombre +
					'<b></p>',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						const response =
							item.tipo == 1
								? await this.ponteCloudService.CarpetaAlternarEstatus(item.id).toPromise()
								: await this.ponteCloudService.ArchivoAlternarEstatus(item.id).toPromise();
						return response;
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value?.err == false) {
					this.Contenido = this.Contenido.filter((c) => c.id != item.id);
					hlpSwal.Exito(r.value.msg);
				}
			});
	}
}
