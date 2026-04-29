import { Component, OnInit, isDevMode } from '@angular/core';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';

import { environment } from '../../../environments/environment';
import * as hlpApp from '../../helpers/app-helper';
import * as hlpSwal from '../../helpers/sweetalert2-helper';
import * as hlpPrimeNGTable from '../../helpers/primeng-table-helper';
import { MiembroModel, MiembroResumenModel } from '../../models/miembro-comite-administracion.model';
import { MiembrosComiteAdministracionService } from '../../services/miembros-comite-administracion.service';
import { TipoMiembroModel } from '../../models/tipo-miembro.model';
import { TiposMiembrosService } from '../../services/tipos-miembros.service';
import { SesionUsuarioService } from '../../services/sesion-usuario.service';

@Component({
	selector: 'app-comite-administracion',
	templateUrl: './miembros-comite-administracion.component.html',
	styleUrls: ['./miembros-comite-administracion.component.css'],
})
export class MiembrosComiteAdministracionComponent implements OnInit {
	appData = environment;
	hlpApp = hlpApp;
	hlpPrimeNGTable = hlpPrimeNGTable;
	isDevelopment = isDevMode;

	// Tabla Miembros
	// Columnas de la tabla
	MiembrosCols: any[] = [
		{ header: '', width: '80px' },
		{ header: 'Nombre' },
		{ header: 'Tipo' },
		{ header: 'Email' },
		{ header: 'Contacto' },
		{ header: 'Domicilio' },
		{ header: 'Inicio', width: '90px' },
		/* { header: 'Fin', width: '140px' }, */
		{ header: 'Estatus', width: '70px' },
		// Botones de acción
		{ textAlign: 'center', width: '90px' },
	];
	MiembrosFilter: any[] = ['nombre', 'email', 'domicilio', 'tipo_miembro'];

	Miembros: MiembroResumenModel[] = [];
	Miembro: MiembroModel;
	TiposMiembros: TipoMiembroModel[] = [];

	frmMiembro: FormGroup;
	mostrarDialogoEdicionMiembro: boolean = false;
	mostrarDialogoImagenMiembro: boolean = false;
	mostrarDialogoDetallesMiembro: boolean = false;
	srcImagen: string = null;
	srcIdentificacionAnverso: string = null;
	srcIdentificacionReverso: string = null;
	srcImagenMostrar: string = null;
	bImagenBorrar: boolean = false;
	bIdentificacionAnversoBorrar: boolean = false;
	bIdentificacionReversoBorrar: boolean = false;
	permitirAgregarEditar: boolean = false;

	constructor(
		private sesionUsuarioService: SesionUsuarioService,
		private miembrosService: MiembrosComiteAdministracionService,
		private tiposMiembrosService: TiposMiembrosService,
		private formBuilder: FormBuilder,
	) {}

	ngOnInit(): void {
		this.permitirAgregarEditar = [1, 2].includes(this.sesionUsuarioService.obtenerIDPerfilUsuario());
		this.onActualizarInformacion();
	}

	private OrdenarMiembros(miembros: MiembroResumenModel[]) {
		return miembros.sort((a, b) => (a.nombre > b.nombre ? 1 : -1));
	}

	public onActualizarInformacion() {
		this.Miembros = [];

		hlpSwal.Cargando();

		(this.permitirAgregarEditar ? this.miembrosService.Listar() : this.miembrosService.ListarActivos())
			.toPromise()
			.then((r) => {
				this.Miembros = this.OrdenarMiembros(r['miembros']);
			})
			.catch(async (e) => {
				await hlpSwal.Error(e);
			})
			.finally(() => {
				hlpSwal.Cerrar();
			});
	}

	async onMiembroEditar(idMiembro: number = 0) {
		hlpSwal.Cargando();

		this.TiposMiembros = await this.tiposMiembrosService
			.ListarMiembrosComiteAdministracionActivos()
			.toPromise()
			.then((r) => r['tipos_miembros'])
			.catch(async (e) => {
				await hlpSwal.Error(e).then(() => null);
			});

		if (this.TiposMiembros.length < 1) {
			return;
		}

		if (idMiembro > 0) {
			this.Miembro = await this.miembrosService
				.ListarMiembro(idMiembro)
				.toPromise()
				.then((r) => r['miembro'])
				.catch(async (e) => {
					await hlpSwal.Error(e).then(() => null);
				});
			if (this.Miembro == null) return;

			this.Miembro.fecha_inicio = new Date(this.Miembro.fecha_inicio + 'T00:00:00');
		} else {
			this.Miembro = new MiembroModel();
		}
		hlpSwal.Cerrar();

		try {
			this.srcImagen = this.Miembro.imagen
				? environment.urlBackendMiembrosComiteFiles + this.Miembro.id_miembro + '/' + this.Miembro.imagen
				: null;
			this.srcIdentificacionAnverso = this.Miembro.identificacion_anverso
				? environment.urlBackendMiembrosComiteFiles +
				  this.Miembro.id_miembro +
				  '/' +
				  this.Miembro.identificacion_anverso
				: null;
			this.srcIdentificacionReverso = this.Miembro.identificacion_anverso
				? environment.urlBackendMiembrosComiteFiles +
				  this.Miembro.id_miembro +
				  '/' +
				  this.Miembro.identificacion_reverso
				: null;
			this.frmMiembro = this.formBuilder.group(this.Miembro);
			this.frmMiembro
				.get('nombre')
				.setValidators([Validators.required, Validators.minLength(3), Validators.maxLength(255)]);
			this.frmMiembro
				.get('email')
				.setValidators([Validators.required, Validators.pattern('^[a-z0-9._%+-]+@[a-z0-9.-]+\\.[a-z]{2,4}$')]);
			this.frmMiembro
				.get('telefono')
				.setValidators([Validators.required, Validators.minLength(10), Validators.maxLength(12)]);
			this.frmMiembro.get('domicilio').setValidators([Validators.maxLength(255)]);
			this.frmMiembro.get('identificacion_folio').setValidators([Validators.maxLength(50)]);
			this.frmMiembro.get('identificacion_domicilio').setValidators([Validators.maxLength(255)]);
			this.frmMiembro.get('id_tipo_miembro').setValidators([Validators.required, Validators.min(1)]);
			this.frmMiembro.get('fecha_inicio').setValidators([Validators.required]);

			this.frmMiembro.addControl('archivo_imagen', new FormControl());
			this.frmMiembro.addControl('archivo_identificacion_anverso', new FormControl());
			this.frmMiembro.addControl('archivo_identificacion_reverso', new FormControl());
			this.frmMiembro.updateValueAndValidity();

			this.bImagenBorrar = false;
			this.bIdentificacionAnversoBorrar = false;
			this.bIdentificacionReversoBorrar = false;
			this.mostrarDialogoEdicionMiembro = true;
		} catch (e) {
			hlpSwal.Error(e);
		}
	}

	async onImagenSeleccionada(event, idImagen: number = 0) {
		if (event.target.files.length != 1 || idImagen == 0) return;

		let file: any = event.target.files[0];
		file.src = await hlpApp
			.readFile(file)
			.then((r) => r)
			.catch((e) => {
				idImagen = 0;
				hlpSwal.Error(e);
			});

		if (!file.src) return;

		switch (idImagen) {
			case 1:
				this.bImagenBorrar = false;
				this.srcImagen = file.src;
				this.frmMiembro.patchValue({ archivo_imagen: file });
				this.frmMiembro.get('archivo_imagen').updateValueAndValidity();
				break;
			case 2:
				this.bIdentificacionAnversoBorrar = false;
				this.srcIdentificacionAnverso = file.src;
				this.frmMiembro.patchValue({ archivo_identificacion_anverso: file });
				this.frmMiembro.get('archivo_identificacion_anverso').updateValueAndValidity();
				break;
			case 3:
				this.bIdentificacionReversoBorrar = false;
				this.srcIdentificacionReverso = file.src;
				this.frmMiembro.patchValue({ archivo_identificacion_reverso: file });
				this.frmMiembro.get('archivo_identificacion_reverso').updateValueAndValidity();
				break;
		}
	}

	onImagenSeleccionadaCancelar(idImagen: number = 0) {
		switch (idImagen) {
			case 1:
				(<HTMLInputElement>document.getElementById('txtImagenArchivo')).value = '';
				this.frmMiembro.get('archivo_imagen').setValue(null);

				this.srcImagen = this.frmMiembro.get('imagen').value
					? environment.urlBackendMiembrosComiteFiles + this.Miembro.id_miembro + '/' + this.Miembro.imagen
					: null;
				this.bImagenBorrar = !this.srcImagen;
				break;
			case 2:
				(<HTMLInputElement>document.getElementById('txtAnversoIdentificacionArchivo')).value = '';
				this.frmMiembro.get('archivo_identificacion_anverso').setValue(null);

				this.srcIdentificacionAnverso = this.frmMiembro.get('identificacion_anverso').value
					? environment.urlBackendMiembrosComiteFiles +
					  this.Miembro.id_miembro +
					  '/' +
					  this.Miembro.identificacion_anverso
					: null;
				this.bIdentificacionAnversoBorrar = !this.srcIdentificacionAnverso;
				break;
			case 3:
				(<HTMLInputElement>document.getElementById('txtReversoIdentificacionArchivo')).value = '';
				this.frmMiembro.get('archivo_identificacion_reverso').setValue(null);

				this.srcIdentificacionReverso = this.frmMiembro.get('identificacion_reverso').value
					? environment.urlBackendMiembrosComiteFiles +
					  this.Miembro.id_miembro +
					  '/' +
					  this.Miembro.identificacion_reverso
					: null;
				this.bIdentificacionReversoBorrar = !this.srcIdentificacionReverso;
				break;
		}
	}

	onImagenEliminar(idImagen: number = 0) {
		if (idImagen == 0) {
			return;
		}
		switch (idImagen) {
			case 1:
				this.frmMiembro.get('imagen').setValue(null);
				break;
			case 2:
				this.frmMiembro.get('identificacion_anverso').setValue(null);
				break;
			case 3:
				this.frmMiembro.get('identificacion_reverso').setValue(null);
				break;
		}
		this.onImagenSeleccionadaCancelar(idImagen);
	}

	onImagenMostrar(imagen: string = null) {
		if (!imagen) {
			return;
		}
		this.srcImagenMostrar = imagen;
		this.mostrarDialogoImagenMiembro = true;
	}

	onMiembroGuardar() {
		if (!this.frmMiembro.valid) {
			this.frmMiembro.markAllAsTouched();
			hlpSwal.Error('Se detectaron errores en la información solicitada.');
			return;
		}

		let miembro = this.frmMiembro.value;

		miembro.borrar_imagen = this.bImagenBorrar ? 1 : 0;
		miembro.borrar_identificacion_anverso = this.bIdentificacionAnversoBorrar ? 1 : 0;
		miembro.borrar_identificacion_reverso = this.bIdentificacionReversoBorrar ? 1 : 0;
		miembro.fecha_inicio = hlpApp.formatDateToMySQL(miembro.fecha_inicio);
		delete miembro.imagen;
		delete miembro.identificacion_anverso;
		delete miembro.identificacion_reverso;

		// console.log(miembro);

		hlpSwal
			.Pregunta({
				html: '¿Deseas guardar la información?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.miembrosService.Guardar(miembro).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err && r.value.miembro) {
					const c = r.value.miembro;
					if (miembro.id_miembro == 0) {
						this.Miembros.push(c);
					} else {
						this.Miembros = this.Miembros.map((C) => (C.id_miembro === c.id_miembro ? c : C));
					}
					this.Miembros = this.OrdenarMiembros(this.Miembros);
					hlpSwal.ExitoToast(r.value.msg);
					this.mostrarDialogoEdicionMiembro = false;
				}
			});
	}

	onMiembroCancelar() {
		this.srcImagen = null;
		this.srcIdentificacionAnverso = null;
		this.srcIdentificacionReverso = null;
		this.mostrarDialogoEdicionMiembro = false;
	}

	async onMiembroDetalles(idUsuario: number = 0) {
		if (idUsuario == 0) {
			return;
		}
		hlpSwal.Cargando();
		this.Miembro = await this.miembrosService
			.ListarMiembro(idUsuario)
			.toPromise()
			.then((r) => r['miembro'])
			.catch(async (e) => {
				await hlpSwal.Error(e).then(() => null);
			})
			.finally(() => {
				hlpSwal.Cerrar();
			});
		this.srcImagen = this.Miembro.imagen
			? environment.urlBackendMiembrosComiteFiles + this.Miembro.id_miembro + '/' + this.Miembro.imagen
			: null;
		this.srcIdentificacionAnverso = this.Miembro.identificacion_anverso
			? environment.urlBackendMiembrosComiteFiles + this.Miembro.id_miembro + '/' + this.Miembro.identificacion_anverso
			: null;
		this.srcIdentificacionReverso = this.Miembro.identificacion_reverso
			? environment.urlBackendMiembrosComiteFiles + this.Miembro.id_miembro + '/' + this.Miembro.identificacion_reverso
			: null;

		this.mostrarDialogoDetallesMiembro = this.Miembro != null;
	}

	onMiembroAlternarEstatus(miembro: MiembroResumenModel) {
		hlpSwal
			.Pregunta({
				html: '¿Deseas ' + (miembro.estatus == 1 ? 'des' : '') + 'habilitar al miembro del comité?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.miembrosService.AlternarEstatus(miembro.id_miembro).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err) {
					miembro.estatus = miembro.estatus == 1 ? 0 : 1;
					hlpSwal.ExitoToast(r.value.msg);
				}
			});
	}
}
