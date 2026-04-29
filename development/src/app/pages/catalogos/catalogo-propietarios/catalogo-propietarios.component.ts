import { Component, OnInit, isDevMode } from '@angular/core';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';

import { environment } from '../../../../environments/environment';
import * as hlpApp from '../../../helpers/app-helper';
import * as hlpSwal from '../../../helpers/sweetalert2-helper';
import * as hlpPrimeNGTable from '../../../helpers/primeng-table-helper';

import { PropietarioModel, PropietarioResumenModel } from '../../../models/usuario-propietario.model';
import { UnidadesEdificioModel } from '../../../models/unidad.model';
import { UsuariosPropietariosService } from '../../../services/usuarios-propietarios.service';
import { UnidadesService } from '../../../services/unidades.service';
import { UsuariosService } from '../../../services/usuarios.service';
import { SesionUsuarioService } from '../../../services/sesion-usuario.service';

@Component({
	selector: 'app-catalogo-propietarios',
	templateUrl: './catalogo-propietarios.component.html',
	styleUrls: ['./catalogo-propietarios.component.css'],
})
export class CatalogoPropietariosComponent implements OnInit {
	appData = environment;
	hlpApp = hlpApp;
	hlpPrimeNGTable = hlpPrimeNGTable;
	isDevelopment = isDevMode;

	// Tabla Propietario
	// Columnas de la tabla
	PropietariosCols: any[] = [
		{ header: '', width: '80px' },
		{ header: 'Nombre' },
		{ header: 'Usuario' },
		{ header: 'Email' },
		{ header: 'Contacto', width: '120px' },
		{ header: 'Domicilio' },
		{ header: 'Unidad(es)' },
		{ header: 'Estatus', width: '70px' },
		// Botones de acción
		{ textAlign: 'center', width: '90px' },
	];
	PropietariosFilter: any[] = ['nombre', 'usuario', 'email', 'domicilio', 'unidades.unidad'];

	Propietarios: PropietarioResumenModel[] = [];
	Propietario: PropietarioModel;
	UnidadesSinPropietario: UnidadesEdificioModel[] = [];

	frmPropietario: FormGroup;
	mostrarDialogoEdicionPropietario: boolean = false;
	mostrarDialogoImagenPropietario: boolean = false;
	mostrarDialogoDetallesPropietario: boolean = false;
	srcImagen: string = null;
	srcIdentificacionAnverso: string = null;
	srcIdentificacionReverso: string = null;
	srcImagenMostrar: string = null;
	bImagenBorrar: boolean = false;
	bIdentificacionAnversoBorrar: boolean = false;
	bIdentificacionReversoBorrar: boolean = false;
	permitirAgregarEditar: boolean = false;

	constructor(
		private formBuilder: FormBuilder,
		private sesionUsuarioService: SesionUsuarioService,
		private propietariosService: UsuariosPropietariosService,
		private unidadesService: UnidadesService,
		private usuariosService: UsuariosService,
	) {}

	ngOnInit(): void {
		this.permitirAgregarEditar = [1, 2].includes(this.sesionUsuarioService.obtenerIDPerfilUsuario());
		this.onActualizarInformacion();
	}

	private OrdenarPropietarios(propietarios: PropietarioResumenModel[]) {
		return propietarios.sort((a, b) => (a.nombre > b.nombre ? 1 : -1));
	}

	onOrdenarUnidades(unidades: UnidadesEdificioModel[] = []) {
		unidades = unidades.sort((a, b) => (a.unidad > b.unidad ? 1 : -1));
	}

	public onActualizarInformacion() {
		this.Propietarios = [];

		hlpSwal.Cargando();

		(this.permitirAgregarEditar ? this.propietariosService.Listar() : this.propietariosService.ListarActivos())
			.toPromise()
			.then((r) => {
				this.Propietarios = this.OrdenarPropietarios(r['propietarios']);
			})
			.catch(async (e) => {
				await hlpSwal.Error(e);
			})
			.finally(() => {
				hlpSwal.Cerrar();
			});
	}

	async onPropietarioEditar(idUsuario: number = 0) {
		hlpSwal.Cargando();

		this.UnidadesSinPropietario = await this.unidadesService
			.ListarUnidadesSinPropietario()
			.toPromise()
			.then((r) => r['unidades'])
			.catch(async (e) => {
				await hlpSwal.Error(e).then(() => null);
			});

		if (!this.unidadesService) {
			return;
		}

		if (idUsuario > 0) {
			this.Propietario = await this.propietariosService
				.ListarPropietario(idUsuario)
				.toPromise()
				.then((r) => r['propietario'])
				.catch(async (e) => {
					await hlpSwal.Error(e).then(() => null);
				});
			if (this.Propietario == null) return;
		} else {
			this.Propietario = new PropietarioModel();
		}
		hlpSwal.Cerrar();

		try {
			this.srcImagen = this.Propietario.imagen
				? environment.urlBackendUsuariosFiles + this.Propietario.id_usuario + '/' + this.Propietario.imagen
				: null;
			this.srcIdentificacionAnverso = this.Propietario.identificacion_anverso
				? environment.urlBackendUsuariosFiles +
				  this.Propietario.id_usuario +
				  '/' +
				  this.Propietario.identificacion_anverso
				: null;
			this.srcIdentificacionReverso = this.Propietario.identificacion_anverso
				? environment.urlBackendUsuariosFiles +
				  this.Propietario.id_usuario +
				  '/' +
				  this.Propietario.identificacion_reverso
				: null;
			this.frmPropietario = this.formBuilder.group(this.Propietario);
			this.frmPropietario
				.get('nombre')
				.setValidators([Validators.required, Validators.minLength(3), Validators.maxLength(255)]);
			this.frmPropietario
				.get('usuario')
				.setValidators([
					Validators.required,
					Validators.minLength(3),
					Validators.maxLength(25),
					Validators.pattern('^[a-z0-9.]+$'),
				]);
			this.frmPropietario
				.get('email')
				.setValidators([Validators.required, Validators.pattern('^[a-z0-9._%+-]+@[a-z0-9.-]+\\.[a-z]{2,4}$')]);
			this.frmPropietario
				.get('telefono')
				.setValidators([Validators.required, Validators.minLength(10), Validators.maxLength(12)]);
			this.frmPropietario.get('domicilio').setValidators([Validators.maxLength(255)]);
			this.frmPropietario.get('identificacion_folio').setValidators([Validators.maxLength(50)]);
			this.frmPropietario.get('identificacion_domicilio').setValidators([Validators.maxLength(255)]);

			this.frmPropietario.addControl('archivo_imagen', new FormControl());
			this.frmPropietario.addControl('archivo_identificacion_anverso', new FormControl());
			this.frmPropietario.addControl('archivo_identificacion_reverso', new FormControl());
			this.frmPropietario.updateValueAndValidity();

			this.bImagenBorrar = false;
			this.bIdentificacionAnversoBorrar = false;
			this.bIdentificacionReversoBorrar = false;
			this.mostrarDialogoEdicionPropietario = true;
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
				this.frmPropietario.patchValue({ archivo_imagen: file });
				this.frmPropietario.get('archivo_imagen').updateValueAndValidity();
				break;
			case 2:
				this.bIdentificacionAnversoBorrar = false;
				this.srcIdentificacionAnverso = file.src;
				this.frmPropietario.patchValue({ archivo_identificacion_anverso: file });
				this.frmPropietario.get('archivo_identificacion_anverso').updateValueAndValidity();
				break;
			case 3:
				this.bIdentificacionReversoBorrar = false;
				this.srcIdentificacionReverso = file.src;
				this.frmPropietario.patchValue({ archivo_identificacion_reverso: file });
				this.frmPropietario.get('archivo_identificacion_reverso').updateValueAndValidity();
				break;
		}
	}

	onImagenSeleccionadaCancelar(idImagen: number = 0) {
		switch (idImagen) {
			case 1:
				(<HTMLInputElement>document.getElementById('txtImagenArchivo')).value = '';
				this.frmPropietario.get('archivo_imagen').setValue(null);

				this.srcImagen = this.frmPropietario.get('imagen').value
					? environment.urlBackendUsuariosFiles + this.Propietario.id_usuario + '/' + this.Propietario.imagen
					: null;
				this.bImagenBorrar = !this.srcImagen;
				break;
			case 2:
				(<HTMLInputElement>document.getElementById('txtAnversoIdentificacionArchivo')).value = '';
				this.frmPropietario.get('archivo_identificacion_anverso').setValue(null);

				this.srcIdentificacionAnverso = this.frmPropietario.get('identificacion_anverso').value
					? environment.urlBackendUsuariosFiles +
					  this.Propietario.id_usuario +
					  '/' +
					  this.Propietario.identificacion_anverso
					: null;
				this.bIdentificacionAnversoBorrar = !this.srcIdentificacionAnverso;
				break;
			case 3:
				(<HTMLInputElement>document.getElementById('txtReversoIdentificacionArchivo')).value = '';
				this.frmPropietario.get('archivo_identificacion_reverso').setValue(null);

				this.srcIdentificacionReverso = this.frmPropietario.get('identificacion_reverso').value
					? environment.urlBackendUsuariosFiles +
					  this.Propietario.id_usuario +
					  '/' +
					  this.Propietario.identificacion_reverso
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
				this.frmPropietario.get('imagen').setValue(null);
				break;
			case 2:
				this.frmPropietario.get('identificacion_anverso').setValue(null);
				break;
			case 3:
				this.frmPropietario.get('identificacion_reverso').setValue(null);
				break;
		}
		this.onImagenSeleccionadaCancelar(idImagen);
	}

	onImagenMostrar(imagen: string = null) {
		if (!imagen) {
			return;
		}
		this.srcImagenMostrar = imagen;
		this.mostrarDialogoImagenPropietario = true;
	}

	onPropietarioGuardar() {
		if (!this.frmPropietario.valid) {
			this.frmPropietario.markAllAsTouched();
			hlpSwal.Error('Se detectaron errores en la información solicitada.');
			return;
		}

		let propietario = this.frmPropietario.value;

		propietario.unidades = JSON.stringify(this.Propietario.unidades.map((u) => ({ id_unidad: u.id_unidad })));
		propietario.borrar_imagen = this.bImagenBorrar ? 1 : 0;
		propietario.borrar_identificacion_anverso = this.bIdentificacionAnversoBorrar ? 1 : 0;
		propietario.borrar_identificacion_reverso = this.bIdentificacionReversoBorrar ? 1 : 0;

		delete propietario.imagen;
		delete propietario.identificacion_anverso;
		delete propietario.identificacion_reverso;

		console.log(propietario);

		hlpSwal
			.Pregunta({
				html: '¿Deseas guardar la información?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.propietariosService.Guardar(propietario).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err && r.value.propietario) {
					const c = r.value.propietario;
					if (propietario.id_usuario == 0) {
						this.Propietarios.push(c);
					} else {
						this.Propietarios = this.Propietarios.map((C) => (C.id_usuario === c.id_usuario ? c : C));
					}
					this.Propietarios = this.OrdenarPropietarios(this.Propietarios);
					hlpSwal.ExitoToast(r.value.msg);
					this.mostrarDialogoEdicionPropietario = false;
				}
			});
	}

	onPropietarioCancelar() {
		this.srcImagen = null;
		this.srcIdentificacionAnverso = null;
		this.srcIdentificacionReverso = null;
		this.mostrarDialogoEdicionPropietario = false;
	}

	async onPropietarioDetalles(idUsuario: number = 0) {
		if (idUsuario == 0) {
			return;
		}
		hlpSwal.Cargando();
		this.Propietario = await this.propietariosService
			.ListarPropietario(idUsuario)
			.toPromise()
			.then((r) => r['propietario'])
			.catch(async (e) => {
				await hlpSwal.Error(e).then(() => null);
			})
			.finally(() => {
				hlpSwal.Cerrar();
			});
		this.srcImagen = this.Propietario.imagen
			? environment.urlBackendUsuariosFiles + this.Propietario.id_usuario + '/' + this.Propietario.imagen
			: null;
		this.srcIdentificacionAnverso = this.Propietario.identificacion_anverso
			? environment.urlBackendUsuariosFiles +
			  this.Propietario.id_usuario +
			  '/' +
			  this.Propietario.identificacion_anverso
			: null;
		this.srcIdentificacionReverso = this.Propietario.identificacion_reverso
			? environment.urlBackendUsuariosFiles +
			  this.Propietario.id_usuario +
			  '/' +
			  this.Propietario.identificacion_reverso
			: null;

		this.mostrarDialogoDetallesPropietario = this.Propietario != null;
	}

	/* onPropietarioDeshabilitar(propietario: PropietarioResumenModel) {
		if (propietario.estatus == 0) {
			return;
		}

		hlpSwal
			.Pregunta({
				html: '¿Deseas eliminar el Propietario?<br /><p class="text-danger"><b>ESTE PROCESO ES IRREVERSIBLE</b></p>',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.propietariosService.Deshabilitar(propietario.id_usuario).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err) {
					this.Propietarios = this.Propietarios.filter(function (e) {
						return e.id_usuario !== propietario.id_usuario;
					});
					hlpSwal.ExitoToast(r.value.msg);
				}
			});
	} */

	onPropietarioAlternarEstatus(propietario: PropietarioResumenModel) {
		hlpSwal
			.Pregunta({
				html: '¿Deseas ' + (propietario.estatus == 1 ? 'des' : '') + 'habilitar el Propietario?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.usuariosService.AlternarEstatus(propietario.id_usuario).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err) {
					propietario.estatus = propietario.estatus == 1 ? 0 : 1;
					hlpSwal.ExitoToast(r.value.msg);
				}
			});
	}
}
