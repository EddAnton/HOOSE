import { Component, OnInit, isDevMode } from '@angular/core';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';

import { environment } from '../../../../environments/environment';
import * as hlpApp from '../../../helpers/app-helper';
import * as hlpSwal from '../../../helpers/sweetalert2-helper';
import * as hlpPrimeNGTable from '../../../helpers/primeng-table-helper';

import { CondominiosService } from '../../../services/condominios.service';
import { CondominioResumenModel, CondominioModel } from '../../../models/condominio.model';

@Component({
	selector: 'app-catalogo-condominios',
	templateUrl: './catalogo-condominios.component.html',
	styleUrls: ['./catalogo-condominios.component.css'],
})
export class CatalogoCondominiosComponent implements OnInit {
	appData = environment;
	hlpApp = hlpApp;
	hlpPrimeNGTable = hlpPrimeNGTable;
	// isDevelopment = isDevMode;

	// Tabla Condominio
	// Columnas de la tabla
	/* CondominiosCols: any[] = [
		{ header: '', width: '80px' },
		{ header: 'Condominio' },
		{ header: 'Email', width: '250px' },
		{ header: 'Contacto', width: '120px' },
		{ header: 'Domicilio' },
		{ header: 'Estatus', width: '70px' },
		// Botones de acción
		{ textAlign: 'center', width: '90px' },
	]; */
	CondominiosCols: any[] = [
		{ header: '', width: '80px' },
		{ header: 'Condominio' },
		{ header: 'Email' },
		{ header: 'Contacto', width: '120px' },
		{ header: 'Domicilio' },
		{ header: 'Estatus', width: '70px' },
		// Botones de acción
		{ textAlign: 'center', width: '90px' },
	];
	CondominiosFilter: any[] = ['condominio', 'domicilio'];

	Condominios: CondominioResumenModel[] = [];
	Condominio: CondominioModel;

	frmCondominio: FormGroup;
	mostrarDialogoEdicionCondominio: boolean = false;
	mostrarDialogoImagenCondominio: boolean = false;
	mostrarDialogoDetallesCondominio: boolean = false;
	srcImagen: string = null;
	ModulosEditorReglamento = {
		imageResize: {
			handleStyles: {
				backgroundColor: 'black',
				border: 'none',
				color: 'white',
			},
			modules: ['DisplaySize', 'Resize'],
		},
	};

	constructor(private condominiosService: CondominiosService, private formBuilder: FormBuilder) {}

	ngOnInit(): void {
		this.onActualizarInformacion();
	}

	private OrdenarCondominios(condominios: CondominioResumenModel[]) {
		return condominios.sort((a, b) => (a.condominio > b.condominio ? 1 : -1));
	}

	public onActualizarInformacion() {
		this.Condominios = [];

		hlpSwal.Cargando();

		this.condominiosService
			.Listar()
			.toPromise()
			.then((r) => {
				this.Condominios = this.OrdenarCondominios(r['condominios']);
			})
			.catch(async (e) => {
				await hlpSwal.Error(e);
			})
			.finally(() => {
				hlpSwal.Cerrar();
			});
	}

	async onCondominioEditar(idCondominio: number = 0) {
		if (idCondominio > 0) {
			hlpSwal.Cargando();
			this.Condominio = await this.condominiosService
				.ListarCondominio(idCondominio)
				.toPromise()
				.then((r) => r['condominios'])
				.catch(async (e) => {
					await hlpSwal.Error(e).then(() => null);
				})
				.finally(() => {
					hlpSwal.Cerrar();
				});
			if (this.Condominio == null) return;
		} else {
			this.Condominio = new CondominioModel();
		}

		try {
			this.srcImagen = this.Condominio.imagen
				? environment.urlBackendCondominiosFiles + this.Condominio.id_condominio + '/' + this.Condominio.imagen
				: null;
			this.frmCondominio = this.formBuilder.group(this.Condominio);
			this.frmCondominio
				.get('condominio')
				.setValidators([Validators.required, Validators.minLength(3), Validators.maxLength(255)]);
			this.frmCondominio.get('email').setValidators([Validators.pattern('^[a-z0-9._%+-]+@[a-z0-9.-]+\\.[a-z]{2,4}$')]);
			this.frmCondominio.get('telefono').setValidators([Validators.minLength(10), Validators.maxLength(12)]);
			this.frmCondominio.get('domicilio').setValidators([Validators.required, Validators.maxLength(255)]);
			this.frmCondominio.get('telefono_guardia').setValidators([Validators.minLength(10), Validators.maxLength(12)]);
			this.frmCondominio.get('telefono_moderador').setValidators([Validators.minLength(10), Validators.maxLength(12)]);
			this.frmCondominio.get('telefono_secretaria').setValidators([Validators.minLength(10), Validators.maxLength(12)]);
			this.frmCondominio.get('anio_construccion').setValidators([Validators.pattern('^[0-9]{4}$')]);
			this.frmCondominio.get('constructora').setValidators([Validators.minLength(3), Validators.maxLength(255)]);
			this.frmCondominio
				.get('constructora_telefono')
				.setValidators([Validators.minLength(10), Validators.maxLength(12)]);
			this.frmCondominio.get('constructora_domicilio').setValidators([Validators.maxLength(255)]);

			this.frmCondominio.addControl('archivo_imagen', new FormControl());
			this.frmCondominio.updateValueAndValidity();
			this.mostrarDialogoEdicionCondominio = true;
		} catch (e) {
			hlpSwal.Error(e);
		}
	}

	async onImagenSeleccionada(event) {
		if (event.target.files.length != 1) return;
		let file: any;
		file = event.target.files[0];

		this.frmCondominio.patchValue({ archivo_imagen: file });
		this.frmCondominio.get('archivo_imagen').updateValueAndValidity();
		file.src = await hlpApp
			.readFile(file)
			.then((r) => r)
			.catch((e) => {
				hlpSwal.Error(e);
			});
		this.srcImagen = file.src;

		/* let reader = new FileReader();

		reader.onload = (e) => {
			file.src = reader.result;
			this.srcImagen = file.src;
		};
		reader.readAsDataURL(file); */
	}

	onImagenSeleccionadaCancelar() {
		(<HTMLInputElement>document.getElementById('txtImagenArchivo')).value = '';
		this.frmCondominio.get('archivo_imagen').setValue(null);

		this.srcImagen = this.frmCondominio.get('imagen').value
			? environment.urlBackendCondominiosFiles + this.Condominio.id_condominio + '/' + this.Condominio.imagen
			: null;
	}

	onImagenEliminada() {
		this.frmCondominio.get('imagen').setValue(null);
		this.onImagenSeleccionadaCancelar();
	}

	onImagenMostrar() {
		this.mostrarDialogoImagenCondominio = true;
	}

	onCondominioGuardar() {
		if (!this.frmCondominio.valid) {
			this.frmCondominio.markAllAsTouched();
			hlpSwal.Error('Se detectaron errores en la información solicitada.');
			return;
		}

		let condominio = this.frmCondominio.value;
		delete condominio.imagen;

		hlpSwal
			.Pregunta({
				html: '¿Deseas guardar la información?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.condominiosService.Guardar(condominio).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err && r.value.condominio) {
					const c = r.value.condominio;
					if (condominio.id_condominio == 0) {
						this.Condominios.push(c);
					} else {
						/* this.Condominios = this.Condominios.map(
							(C) => c.find((N: CondominioModel) => N.id_condominio === C.id_condominio) || C,
						); */
						this.Condominios = this.Condominios.map((C) => (C.id_condominio === c.id_condominio ? c : C));
						/* condominio = this.Condominios.find(C => C.id_condominio === c.id_condominio);
            Object.assign(condominio, c); */
					}
					this.Condominios = this.OrdenarCondominios(this.Condominios);
					hlpSwal.ExitoToast(r.value.msg);
					this.mostrarDialogoEdicionCondominio = false;
				}
			});
	}

	onCondominioCancelar() {
		this.srcImagen = null;
		this.mostrarDialogoEdicionCondominio = false;
	}

	async onCondominioDetalles(idCondominio: number = 0) {
		if (idCondominio == 0) {
			return;
		}
		hlpSwal.Cargando();
		this.Condominio = await this.condominiosService
			.ListarCondominio(idCondominio)
			.toPromise()
			.then((r) => r['condominios'])
			.catch(async (e) => {
				await hlpSwal.Error(e).then(() => null);
			})
			.finally(() => {
				hlpSwal.Cerrar();
			});
		this.srcImagen = this.Condominio.imagen
			? environment.urlBackendCondominiosFiles + this.Condominio.id_condominio + '/' + this.Condominio.imagen
			: null;

		this.mostrarDialogoDetallesCondominio = this.Condominio != null;
	}

	onCondominioAlternarEstatus(condominio: CondominioResumenModel) {
		hlpSwal
			.Pregunta({
				html: '¿Deseas ' + (condominio.estatus == 1 ? 'des' : '') + 'habilitar el Condominio?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.condominiosService.AlternarEstatus(condominio.id_condominio).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err) {
					condominio.estatus = condominio.estatus == 1 ? 0 : 1;
					hlpSwal.ExitoToast(r.value.msg);
				}
			});
	}
}
