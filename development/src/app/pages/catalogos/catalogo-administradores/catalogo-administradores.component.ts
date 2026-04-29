import { Component, OnInit, isDevMode } from '@angular/core';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';

import { environment } from '../../../../environments/environment';
import * as hlpApp from '../../../helpers/app-helper';
import * as hlpSwal from '../../../helpers/sweetalert2-helper';
import * as hlpPrimeNGTable from '../../../helpers/primeng-table-helper';
import { UsuariosService } from '../../../services/usuarios.service';
import { AdministradorModel, AdministradorResumenModel } from '../../../models/usuario-administrador.model';
import { UsuariosAdministradoresService } from '../../../services/usuarios-administradores.service';

@Component({
	selector: 'app-catalogo-administradores',
	templateUrl: './catalogo-administradores.component.html',
	styleUrls: ['./catalogo-administradores.component.css'],
})
export class CatalogoAdministradoresComponent implements OnInit {
	appData = environment;
	hlpApp = hlpApp;
	hlpPrimeNGTable = hlpPrimeNGTable;
	isDevelopment = isDevMode;

	// Tabla Administrador
	// Columnas de la tabla
	AdministradoresCols: any[] = [
		{ header: '', width: '80px' },
		{ header: 'Nombre' },
		{ header: 'Usuario' },
		{ header: 'Email' },
		{ header: 'Contacto' },
		{ header: 'Domicilio' },
		{ header: 'Estatus', width: '70px' },
		// Botones de acción
		{ textAlign: 'center', width: '90px' },
	];
	AdministradoresFilter: any[] = ['nombre', 'usuario', 'email', 'domicilio'];

	Administradores: AdministradorResumenModel[] = [];
	Administrador: AdministradorModel;

	frmAdministrador: FormGroup;
	mostrarDialogoEdicionAdministrador: boolean = false;
	mostrarDialogoImagenAdministrador: boolean = false;
	mostrarDialogoDetallesAdministrador: boolean = false;
	srcImagen: string = null;
	srcIdentificacionAnverso: string = null;
	srcIdentificacionReverso: string = null;
	srcImagenMostrar: string = null;
	bImagenBorrar: boolean = false;
	bIdentificacionAnversoBorrar: boolean = false;
	bIdentificacionReversoBorrar: boolean = false;

	constructor(
		private formBuilder: FormBuilder,
		private administradoresService: UsuariosAdministradoresService,
		private usuariosService: UsuariosService,
	) {}

	ngOnInit(): void {
		this.onActualizarInformacion();
	}

	private OrdenarAdministradores(administradores: AdministradorResumenModel[]) {
		return administradores.sort((a, b) => (a.nombre > b.nombre ? 1 : -1));
	}

	public onActualizarInformacion() {
		this.Administradores = [];

		hlpSwal.Cargando();

		this.administradoresService
			.Listar()
			.toPromise()
			.then((r) => {
				this.Administradores = this.OrdenarAdministradores(r['administradores']);
			})
			.catch(async (e) => {
				await hlpSwal.Error(e);
			})
			.finally(() => {
				hlpSwal.Cerrar();
			});
	}

	async onAdministradorEditar(idUsuario: number = 0) {
		hlpSwal.Cargando();

		if (idUsuario > 0) {
			this.Administrador = await this.administradoresService
				.ListarAdministrador(idUsuario)
				.toPromise()
				.then((r) => r['administrador'])
				.catch(async (e) => {
					await hlpSwal.Error(e).then(() => null);
				});
			if (this.Administrador == null) return;
		} else {
			this.Administrador = new AdministradorModel();
		}
		hlpSwal.Cerrar();

		try {
			this.srcImagen = this.Administrador.imagen
				? environment.urlBackendUsuariosFiles + this.Administrador.id_usuario + '/' + this.Administrador.imagen
				: null;
			this.srcIdentificacionAnverso = this.Administrador.identificacion_anverso
				? environment.urlBackendUsuariosFiles +
				  this.Administrador.id_usuario +
				  '/' +
				  this.Administrador.identificacion_anverso
				: null;
			this.srcIdentificacionReverso = this.Administrador.identificacion_anverso
				? environment.urlBackendUsuariosFiles +
				  this.Administrador.id_usuario +
				  '/' +
				  this.Administrador.identificacion_reverso
				: null;
			this.frmAdministrador = this.formBuilder.group(this.Administrador);
			this.frmAdministrador
				.get('nombre')
				.setValidators([Validators.required, Validators.minLength(3), Validators.maxLength(255)]);
			this.frmAdministrador
				.get('usuario')
				.setValidators([
					Validators.required,
					Validators.minLength(3),
					Validators.maxLength(25),
					Validators.pattern('^[a-z0-9.]+$'),
				]);
			this.frmAdministrador
				.get('email')
				.setValidators([Validators.required, Validators.pattern('^[a-z0-9._%+-]+@[a-z0-9.-]+\\.[a-z]{2,4}$')]);
			this.frmAdministrador
				.get('telefono')
				.setValidators([Validators.required, Validators.minLength(10), Validators.maxLength(12)]);
			this.frmAdministrador.get('domicilio').setValidators([Validators.maxLength(255)]);
			this.frmAdministrador.get('identificacion_folio').setValidators([Validators.maxLength(50)]);
			this.frmAdministrador.get('identificacion_domicilio').setValidators([Validators.maxLength(255)]);

			this.frmAdministrador.addControl('archivo_imagen', new FormControl());
			this.frmAdministrador.addControl('archivo_identificacion_anverso', new FormControl());
			this.frmAdministrador.addControl('archivo_identificacion_reverso', new FormControl());
			this.frmAdministrador.updateValueAndValidity();

			this.bImagenBorrar = false;
			this.bIdentificacionAnversoBorrar = false;
			this.bIdentificacionReversoBorrar = false;
			this.mostrarDialogoEdicionAdministrador = true;
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
				this.frmAdministrador.patchValue({ archivo_imagen: file });
				this.frmAdministrador.get('archivo_imagen').updateValueAndValidity();
				break;
			case 2:
				this.bIdentificacionAnversoBorrar = false;
				this.srcIdentificacionAnverso = file.src;
				this.frmAdministrador.patchValue({ archivo_identificacion_anverso: file });
				this.frmAdministrador.get('archivo_identificacion_anverso').updateValueAndValidity();
				break;
			case 3:
				this.bIdentificacionReversoBorrar = false;
				this.srcIdentificacionReverso = file.src;
				this.frmAdministrador.patchValue({ archivo_identificacion_reverso: file });
				this.frmAdministrador.get('archivo_identificacion_reverso').updateValueAndValidity();
				break;
		}
	}

	onImagenSeleccionadaCancelar(idImagen: number = 0) {
		switch (idImagen) {
			case 1:
				(<HTMLInputElement>document.getElementById('txtImagenArchivo')).value = '';
				this.frmAdministrador.get('archivo_imagen').setValue(null);

				this.srcImagen = this.frmAdministrador.get('imagen').value
					? environment.urlBackendUsuariosFiles + this.Administrador.id_usuario + '/' + this.Administrador.imagen
					: null;
				this.bImagenBorrar = !this.srcImagen;
				break;
			case 2:
				(<HTMLInputElement>document.getElementById('txtAnversoIdentificacionArchivo')).value = '';
				this.frmAdministrador.get('archivo_identificacion_anverso').setValue(null);

				this.srcIdentificacionAnverso = this.frmAdministrador.get('identificacion_anverso').value
					? environment.urlBackendUsuariosFiles +
					  this.Administrador.id_usuario +
					  '/' +
					  this.Administrador.identificacion_anverso
					: null;
				this.bIdentificacionAnversoBorrar = !this.srcIdentificacionAnverso;
				break;
			case 3:
				(<HTMLInputElement>document.getElementById('txtReversoIdentificacionArchivo')).value = '';
				this.frmAdministrador.get('archivo_identificacion_reverso').setValue(null);

				this.srcIdentificacionReverso = this.frmAdministrador.get('identificacion_reverso').value
					? environment.urlBackendUsuariosFiles +
					  this.Administrador.id_usuario +
					  '/' +
					  this.Administrador.identificacion_reverso
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
				this.frmAdministrador.get('imagen').setValue(null);
				break;
			case 2:
				this.frmAdministrador.get('identificacion_anverso').setValue(null);
				break;
			case 3:
				this.frmAdministrador.get('identificacion_reverso').setValue(null);
				break;
		}
		this.onImagenSeleccionadaCancelar(idImagen);
	}

	onImagenMostrar(imagen: string = null) {
		if (!imagen) {
			return;
		}
		this.srcImagenMostrar = imagen;
		this.mostrarDialogoImagenAdministrador = true;
	}

	onAdministradorGuardar() {
		if (!this.frmAdministrador.valid) {
			this.frmAdministrador.markAllAsTouched();
			hlpSwal.Error('Se detectaron errores en la información solicitada.');
			return;
		}

		let administrador = this.frmAdministrador.value;

		administrador.borrar_imagen = this.bImagenBorrar ? 1 : 0;
		administrador.borrar_identificacion_anverso = this.bIdentificacionAnversoBorrar ? 1 : 0;
		administrador.borrar_identificacion_reverso = this.bIdentificacionReversoBorrar ? 1 : 0;
		delete administrador.imagen;
		delete administrador.identificacion_anverso;
		delete administrador.identificacion_reverso;

		console.log(administrador);

		hlpSwal
			.Pregunta({
				html: '¿Deseas guardar la información?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.administradoresService.Guardar(administrador).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err && r.value.administrador) {
					const c = r.value.administrador;
					if (administrador.id_usuario == 0) {
						this.Administradores.push(c);
					} else {
						this.Administradores = this.Administradores.map((C) => (C.id_usuario === c.id_usuario ? c : C));
					}
					this.Administradores = this.OrdenarAdministradores(this.Administradores);
					hlpSwal.ExitoToast(r.value.msg);
					this.mostrarDialogoEdicionAdministrador = false;
				}
			});
	}

	onAdministradorCancelar() {
		this.srcImagen = null;
		this.srcIdentificacionAnverso = null;
		this.srcIdentificacionReverso = null;
		this.mostrarDialogoEdicionAdministrador = false;
	}

	async onAdministradorDetalles(idUsuario: number = 0) {
		if (idUsuario == 0) {
			return;
		}
		hlpSwal.Cargando();
		this.Administrador = await this.administradoresService
			.ListarAdministrador(idUsuario)
			.toPromise()
			.then((r) => r['administrador'])
			.catch(async (e) => {
				await hlpSwal.Error(e).then(() => null);
			})
			.finally(() => {
				hlpSwal.Cerrar();
			});
		this.srcImagen = this.Administrador.imagen
			? environment.urlBackendUsuariosFiles + this.Administrador.id_usuario + '/' + this.Administrador.imagen
			: null;
		this.srcIdentificacionAnverso = this.Administrador.identificacion_anverso
			? environment.urlBackendUsuariosFiles +
			  this.Administrador.id_usuario +
			  '/' +
			  this.Administrador.identificacion_anverso
			: null;
		this.srcIdentificacionReverso = this.Administrador.identificacion_reverso
			? environment.urlBackendUsuariosFiles +
			  this.Administrador.id_usuario +
			  '/' +
			  this.Administrador.identificacion_reverso
			: null;

		this.mostrarDialogoDetallesAdministrador = this.Administrador != null;
	}

	onAdministradorAlternarEstatus(administrador: AdministradorResumenModel) {
		hlpSwal
			.Pregunta({
				html: '¿Deseas ' + (administrador.estatus == 1 ? 'des' : '') + 'habilitar el Administrador?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.usuariosService.AlternarEstatus(administrador.id_usuario).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err) {
					administrador.estatus = administrador.estatus == 1 ? 0 : 1;
					hlpSwal.ExitoToast(r.value.msg);
				}
			});
	}
}
