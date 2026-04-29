import { Component, OnInit, ViewChild, isDevMode } from '@angular/core';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';
import { DomSanitizer } from '@angular/platform-browser';

import { environment } from '../../../../environments/environment';
import * as hlpApp from '../../../helpers/app-helper';
import * as hlpSwal from '../../../helpers/sweetalert2-helper';
import * as hlpPrimeNGTable from '../../../helpers/primeng-table-helper';

import { FileUpload } from 'primeng/fileupload';
import { UnidadesService } from '../../../services/unidades.service';
import { UnidadModel } from '../../../models/unidad.model';
import { EdificiosService } from '../../../services/edificios.service';
import { EdificioModel } from '../../../models/edificio.model';
import { SesionUsuarioService } from '../../../services/sesion-usuario.service';

@Component({
	selector: 'app-catalogo-unidades',
	templateUrl: './catalogo-unidades.component.html',
	styleUrls: ['./catalogo-unidades.component.css'],
})
export class CatalogoUnidadesComponent implements OnInit {
	hlpApp = hlpApp;
	hlpPrimeNGTable = hlpPrimeNGTable;
	isDevelopment = isDevMode;

	@ViewChild('fileEscrituras') fileEscrituras: FileUpload;

	// Tabla Unidade
	// Columnas de la tabla
	UnidadesCols: any[] = [
		{ header: 'Unidad' },
		{ header: 'Edificio' },
		{ header: 'Escrituras', width: '70px' },
		{ header: 'Estatus', width: '70px' },
		// Botones de acción
		{ textAlign: 'center', width: '50px' },
	];
	UnidadesFilter: any[] = ['unidad', 'edificio'];

	Unidades: UnidadModel[] = [];
	Unidad: UnidadModel;
	Edificios: EdificioModel[] = [];

	frmUnidad: FormGroup;
	mostrarDialogoEdicionUnidad: boolean = false;
	mostrarDialogoImagenUnidad: boolean = false;
	mostrarDialogoEscriturasUnidad: boolean = false;
	srcEscrituras: string = null;
	bEscriturasBorrar: boolean = false;
	permitirAgregarEditar: boolean = false;

	constructor(
		private sesionUsuarioService: SesionUsuarioService,
		private unidadesService: UnidadesService,
		private edificiosService: EdificiosService,
		private formBuilder: FormBuilder,
		private sanitizer: DomSanitizer,
	) {}

	ngOnInit(): void {
		this.permitirAgregarEditar = [1, 2].includes(this.sesionUsuarioService.obtenerIDPerfilUsuario());
		this.onActualizarInformacion();
		// this.disableContextMenu();
	}

	private OrdenarUnidades(unidades: UnidadModel[]) {
		return unidades.sort((a, b) => (a.edificio + a.unidad > b.edificio + b.unidad ? 1 : -1));
	}

	public onActualizarInformacion() {
		this.Unidades = [];
		this.Edificios = [];

		hlpSwal.Cargando();

		this.unidadesService
			.Listar()
			.toPromise()
			.then((r) => {
				this.Unidades = this.OrdenarUnidades(r['unidades']);
			})
			.catch(async (e) => {
				await hlpSwal.Error(e);
			})
			.finally(() => {
				this.edificiosService
					.ListarActivos()
					.toPromise()
					.then((r) => {
						this.Edificios = r['edificios'];
					})
					.catch(async (e) => {
						await hlpSwal.Error(e);
					});
				hlpSwal.Cerrar();
			});
	}

	async onUnidadEditar(unidad: UnidadModel = null) {
		if (unidad != null) {
			this.Unidad = unidad;
		} else {
			this.Unidad = new UnidadModel();
		}

		try {
			this.srcEscrituras = this.Unidad.escrituras_archivo
				? environment.urlBackendUnidadesFiles + this.Unidad.id_unidad + '/' + this.Unidad.escrituras_archivo
				: null;
			this.frmUnidad = this.formBuilder.group(this.Unidad);
			this.frmUnidad
				.get('unidad')
				.setValidators([Validators.required, Validators.minLength(3), Validators.maxLength(150)]);
			this.frmUnidad.get('id_edificio').setValidators([Validators.required, Validators.min(1)]);
			this.frmUnidad.addControl('archivo_escrituras', new FormControl());
			this.frmUnidad.updateValueAndValidity();
			this.bEscriturasBorrar = false;

			this.mostrarDialogoEdicionUnidad = true;
		} catch (e) {
			hlpSwal.Error(e);
		}
	}

	onEscriturasSeleccionadas(event) {
		if (event.target.files.length != 1) return;
		let file: any;
		file = event.target.files[0];

		this.frmUnidad.patchValue({ archivo_escrituras: file });
		this.frmUnidad.get('archivo_escrituras').updateValueAndValidity();

		let reader = new FileReader();

		reader.onload = (e) => {
			file.src = reader.result;
			this.srcEscrituras = file.src;
			this.bEscriturasBorrar = false;
		};
		reader.readAsDataURL(file);
	}

	onEscriturasSeleccionadasCancelar() {
		(<HTMLInputElement>document.getElementById('txtEscriturasArchivo')).value = '';
		this.frmUnidad.get('archivo_escrituras').setValue(null);

		this.srcEscrituras = this.frmUnidad.get('archivo_escrituras').value
			? environment.urlBackendUnidadesFiles + this.Unidad.id_unidad + '/' + this.Unidad.escrituras_archivo
			: null;
		this.bEscriturasBorrar = !this.srcEscrituras;
	}

	onEscriturasEliminadas() {
		this.frmUnidad.get('escrituras_archivo').setValue(null);
		this.onEscriturasSeleccionadasCancelar();
	}

	onUnidadGuardar() {
		if (!this.frmUnidad.valid) {
			this.frmUnidad.markAllAsTouched();
			hlpSwal.Error('Se detectaron errores en la información solicitada.');
			return;
		}

		let unidad = this.frmUnidad.value;
		unidad.borrar_escrituras = this.bEscriturasBorrar ? 1 : 0;
		delete unidad.escrituras_archivo;

		hlpSwal
			.Pregunta({
				html: '¿Deseas guardar la información?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.unidadesService.Guardar(unidad).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err && r.value.unidad) {
					const c = r.value.unidad;
					if (unidad.id_unidad == 0) {
						this.Unidades.push(c);
					} else {
						this.Unidades = this.Unidades.map((C) => (C.id_unidad === c.id_unidad ? c : C));
					}
					this.Unidades = this.OrdenarUnidades(this.Unidades);
					hlpSwal.ExitoToast(r.value.msg);
					this.mostrarDialogoEdicionUnidad = false;
				}
			});
	}

	onUnidadCancelar() {
		this.srcEscrituras = null;
		this.mostrarDialogoEdicionUnidad = false;
	}

	escriturasURL() {
		return this.sanitizer.bypassSecurityTrustResourceUrl(this.srcEscrituras + '#toolbar=0&view=fitH');
	}

	async onUnidadEscriturasMostrar(unidad: UnidadModel = null) {
		if (unidad) {
			this.srcEscrituras = unidad.escrituras_archivo
				? environment.urlBackendUnidadesFiles + unidad.id_unidad + '/' + unidad.escrituras_archivo
				: null;
		}
		this.mostrarDialogoEscriturasUnidad = this.srcEscrituras != null;
		if (this.mostrarDialogoEscriturasUnidad) {
			hlpSwal.Cargando();
		}
	}

	onUnidadEscriturasMostradas() {
		hlpSwal.Cerrar();
	}

	onUnidadAlternarEstatus(unidad: UnidadModel) {
		hlpSwal
			.Pregunta({
				html: '¿Deseas ' + (unidad.estatus == 1 ? 'des' : '') + 'habilitar la Unidad?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.unidadesService.AlternarEstatus(unidad.id_unidad).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err) {
					unidad.estatus = unidad.estatus == 1 ? 0 : 1;
					hlpSwal.ExitoToast(r.value.msg);
				}
			});
	}

	/* onUnidadDeshabilitar(unidad: UnidadModel) {
		if (unidad.estatus == 0) {
			return;
		}

		hlpSwal
			.Pregunta({
				html: '¿Deseas eliminar el Unidad?<br /><p class="text-danger"><b>ESTE PROCESO ES IRREVERSIBLE</b></p>',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.unidadesService.Deshabilitar(unidad.id_unidad).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err) {
					this.Unidades = this.Unidades.filter(function (e) {
						return e.id_unidad !== unidad.id_unidad;
					});
					hlpSwal.ExitoToast(r.value.msg);
				}
			});
	} */
}
