import { Component, OnInit, isDevMode } from '@angular/core';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';
import { DomSanitizer } from '@angular/platform-browser';
import { MenuItem } from 'primeng/api';

import { environment } from '../../../../environments/environment';
import * as hlpApp from '../../../helpers/app-helper';
import * as hlpSwal from '../../../helpers/sweetalert2-helper';
import * as hlpPrimeNGTable from '../../../helpers/primeng-table-helper';
import { UsuariosService } from '../../../services/usuarios.service';
import { ColaboradorModel, ColaboradorResumenModel } from '../../../models/usuario-colaborador.model';
import { UsuariosColaboradoresService } from '../../../services/usuarios-colaboradores.service';
import { TiposMiembrosService } from '../../../services/tipos-miembros.service';
import { TipoMiembroModel } from '../../../models/tipo-miembro.model';
import { SesionUsuarioService } from '../../../services/sesion-usuario.service';
import { filter } from 'rxjs/operators';

@Component({
	selector: 'app-catalogo-colaboradores',
	templateUrl: './catalogo-colaboradores.component.html',
	styleUrls: ['./catalogo-colaboradores.component.css'],
})
export class CatalogoColaboradoresComponent implements OnInit {
	appData = environment;
	hlpApp = hlpApp;
	hlpPrimeNGTable = hlpPrimeNGTable;
	isDevelopment = isDevMode;
	mnuAcciones: MenuItem[];

	// Tabla Colaborador
	// Columnas de la tabla
	ColaboradoresCols: any[] = [
		{ header: '', width: '80px' },
		{ header: 'Nombre' },
		{ header: 'Usuario' },
		{ header: 'Email' },
		{ header: 'Contacto' },
		{ header: 'Domicilio' },
		{ header: 'Tipo' },
		{ header: 'Inicio', width: '90px' },
		{ header: 'Salario', width: '80px' },
		{ header: 'Estatus', width: '70px' },
		// Botones de acción
		{ textAlign: 'center', width: '90px' },
	];
	ColaboradoresFilter: any[] = ['nombre', 'usuario', 'email', 'domicilio', 'tipo_miembro'];

	Colaboradores: ColaboradorResumenModel[] = [];
	Colaborador: ColaboradorModel;
	idColaborador: number = 0;
	TiposColaboradores: TipoMiembroModel[] = [];

	frmColaborador: FormGroup;
	mostrarDialogoEdicionColaborador: boolean = false;
	mostrarDialogoImagenColaborador: boolean = false;
	mostrarDialogoDetallesColaborador: boolean = false;
	mostrarDialogoContratoColaborador: boolean = false;
	srcImagen: string = null;
	srcIdentificacionAnverso: string = null;
	srcIdentificacionReverso: string = null;
	srcImagenMostrar: string = null;
	srcContrato: string = null;
	bImagenBorrar: boolean = false;
	bIdentificacionAnversoBorrar: boolean = false;
	bIdentificacionReversoBorrar: boolean = false;
	bContratoBorrar: boolean = false;
	permitirAgregarEditar: boolean = false;

	constructor(
		private sesionUsuarioService: SesionUsuarioService,
		private colaboradoresService: UsuariosColaboradoresService,
		private tiposMiembrosService: TiposMiembrosService,
		private formBuilder: FormBuilder,
		private usuariosService: UsuariosService,
		private sanitizer: DomSanitizer,
	) {}

	ngOnInit(): void {
		this.permitirAgregarEditar = [1, 2].includes(this.sesionUsuarioService.obtenerIDPerfilUsuario());
		this.onActualizarInformacion();
	}

	private OrdenarColaboradores(colaboradores: ColaboradorResumenModel[]) {
		return colaboradores.sort((a, b) => {
			return (a.estatus == 1 ? 0 : 1) + a.nombre > (b.estatus == 1 ? 0 : 1) + b.nombre ? 1 : -1;
		});
	}

	onDropdownActions(Colaborador: ColaboradorResumenModel) {
		this.mnuAcciones = [
			{
				label: 'Editar',
				icon: 'pi pi-pencil',
				visible: Colaborador.estatus == 1,
				command: () => {
					this.onColaboradorEditar(Colaborador.id_usuario);
				},
			},
			{
				separator: true,
				visible: Colaborador.estatus == 1,
			},
			{
				label: 'Salarios',
				icon: 'pi pi-money-bill',
				command: () => {
					console.log('Salarios :>> ', Colaborador);
				},
			},
			{
				label: 'Solicitudes ausencia',
				icon: 'pi pi-calendar',
				visible: Colaborador.estatus == 1,
				command: () => {
					console.log('Solicitudes ausencia :>> ', Colaborador);
				},
			},
		];
		// console.log(e);
	}

	public onActualizarInformacion() {
		this.Colaboradores = [];

		hlpSwal.Cargando();

		(this.permitirAgregarEditar ? this.colaboradoresService.Listar() : this.colaboradoresService.ListarActivos())
			.toPromise()
			.then((r) => {
				this.Colaboradores = this.OrdenarColaboradores(r['colaboradores']);
			})
			.catch(async (e) => {
				await hlpSwal.Error(e);
			})
			.finally(() => {
				hlpSwal.Cerrar();
			});
	}

	async onColaboradorEditar(idUsuario: number = 0) {
		hlpSwal.Cargando();

		this.TiposColaboradores = await this.tiposMiembrosService
			.ListarMiembrosColaboradoresActivos()
			.toPromise()
			.then((r) => r['tipos_miembros'])
			.catch(async (e) => {
				await hlpSwal.Error(e).then(() => null);
			});

		if (this.TiposColaboradores.length < 1) {
			return;
		}

		if (idUsuario > 0) {
			this.Colaborador = await this.colaboradoresService
				.ListarColaborador(idUsuario)
				.toPromise()
				.then((r) => r['colaborador'])
				.catch(async (e) => {
					await hlpSwal.Error(e).then(() => null);
				});
			if (this.Colaborador == null) return;

			this.Colaborador.fecha_inicio = new Date(this.Colaborador.fecha_inicio + 'T00:00:00');
		} else {
			this.Colaborador = new ColaboradorModel();
		}
		hlpSwal.Cerrar();

		try {
			this.srcImagen = this.Colaborador.imagen
				? environment.urlBackendUsuariosFiles + this.Colaborador.id_usuario + '/' + this.Colaborador.imagen
				: null;
			this.srcIdentificacionAnverso = this.Colaborador.identificacion_anverso
				? environment.urlBackendUsuariosFiles +
				  this.Colaborador.id_usuario +
				  '/' +
				  this.Colaborador.identificacion_anverso
				: null;
			this.srcIdentificacionReverso = this.Colaborador.identificacion_anverso
				? environment.urlBackendUsuariosFiles +
				  this.Colaborador.id_usuario +
				  '/' +
				  this.Colaborador.identificacion_reverso
				: null;
			this.srcContrato = this.Colaborador.contrato
				? environment.urlBackendUsuariosFiles + this.Colaborador.id_usuario + '/' + this.Colaborador.contrato
				: null;
			this.frmColaborador = this.formBuilder.group(this.Colaborador);
			this.frmColaborador
				.get('nombre')
				.setValidators([Validators.required, Validators.minLength(3), Validators.maxLength(255)]);
			this.frmColaborador
				.get('usuario')
				.setValidators([
					Validators.required,
					Validators.minLength(3),
					Validators.maxLength(25),
					Validators.pattern('^[a-z0-9.]+$'),
				]);
			this.frmColaborador
				.get('email')
				.setValidators([Validators.required, Validators.pattern('^[a-z0-9._%+-]+@[a-z0-9.-]+\\.[a-z]{2,4}$')]);
			this.frmColaborador
				.get('telefono')
				.setValidators([Validators.required, Validators.minLength(10), Validators.maxLength(12)]);
			this.frmColaborador.get('domicilio').setValidators([Validators.maxLength(255)]);
			this.frmColaborador.get('identificacion_folio').setValidators([Validators.maxLength(50)]);
			this.frmColaborador.get('identificacion_domicilio').setValidators([Validators.maxLength(255)]);
			this.frmColaborador.get('id_tipo_miembro').setValidators([Validators.required, Validators.min(1)]);
			this.frmColaborador.get('salario').setValidators([Validators.required, Validators.min(0.01)]);
			this.frmColaborador.get('fecha_inicio').setValidators([Validators.required]);

			this.frmColaborador.addControl('archivo_imagen', new FormControl());
			this.frmColaborador.addControl('archivo_identificacion_anverso', new FormControl());
			this.frmColaborador.addControl('archivo_identificacion_reverso', new FormControl());
			this.frmColaborador.addControl('archivo_contrato', new FormControl());
			this.frmColaborador.updateValueAndValidity();

			this.bImagenBorrar = false;
			this.bIdentificacionAnversoBorrar = false;
			this.bIdentificacionReversoBorrar = false;
			this.bContratoBorrar = false;
			this.mostrarDialogoEdicionColaborador = true;
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
				this.frmColaborador.patchValue({ archivo_imagen: file });
				this.frmColaborador.get('archivo_imagen').updateValueAndValidity();
				break;
			case 2:
				this.bIdentificacionAnversoBorrar = false;
				this.srcIdentificacionAnverso = file.src;
				this.frmColaborador.patchValue({ archivo_identificacion_anverso: file });
				this.frmColaborador.get('archivo_identificacion_anverso').updateValueAndValidity();
				break;
			case 3:
				this.bIdentificacionReversoBorrar = false;
				this.srcIdentificacionReverso = file.src;
				this.frmColaborador.patchValue({ archivo_identificacion_reverso: file });
				this.frmColaborador.get('archivo_identificacion_reverso').updateValueAndValidity();
				break;
		}
	}

	onImagenSeleccionadaCancelar(idImagen: number = 0) {
		switch (idImagen) {
			case 1:
				(<HTMLInputElement>document.getElementById('txtImagenArchivo')).value = '';
				this.frmColaborador.get('archivo_imagen').setValue(null);

				this.srcImagen = this.frmColaborador.get('imagen').value
					? environment.urlBackendUsuariosFiles + this.Colaborador.id_usuario + '/' + this.Colaborador.imagen
					: null;
				this.bImagenBorrar = !this.srcImagen;
				break;
			case 2:
				(<HTMLInputElement>document.getElementById('txtAnversoIdentificacionArchivo')).value = '';
				this.frmColaborador.get('archivo_identificacion_anverso').setValue(null);

				this.srcIdentificacionAnverso = this.frmColaborador.get('identificacion_anverso').value
					? environment.urlBackendUsuariosFiles +
					  this.Colaborador.id_usuario +
					  '/' +
					  this.Colaborador.identificacion_anverso
					: null;
				this.bIdentificacionAnversoBorrar = !this.srcIdentificacionAnverso;
				break;
			case 3:
				(<HTMLInputElement>document.getElementById('txtReversoIdentificacionArchivo')).value = '';
				this.frmColaborador.get('archivo_identificacion_reverso').setValue(null);

				this.srcIdentificacionReverso = this.frmColaborador.get('identificacion_reverso').value
					? environment.urlBackendUsuariosFiles +
					  this.Colaborador.id_usuario +
					  '/' +
					  this.Colaborador.identificacion_reverso
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
				this.frmColaborador.get('imagen').setValue(null);
				break;
			case 2:
				this.frmColaborador.get('identificacion_anverso').setValue(null);
				break;
			case 3:
				this.frmColaborador.get('identificacion_reverso').setValue(null);
				break;
		}
		this.onImagenSeleccionadaCancelar(idImagen);
	}

	onContratoSeleccionado(event) {
		if (event.target.files.length != 1) return;
		let file: any;
		file = event.target.files[0];

		this.frmColaborador.patchValue({ archivo_contrato: file });
		this.frmColaborador.get('archivo_contrato').updateValueAndValidity();

		let reader = new FileReader();

		reader.onload = (e) => {
			file.src = reader.result;
			this.srcContrato = file.src;
		};
		reader.readAsDataURL(file);
	}

	onContratoSeleccionadoCancelar() {
		(<HTMLInputElement>document.getElementById('txtContratoArchivo')).value = '';
		this.frmColaborador.get('archivo_contrato').setValue(null);

		this.srcContrato = this.frmColaborador.get('contrato').value
			? environment.urlBackendUsuariosFiles + this.Colaborador.id_usuario + '/' + this.Colaborador.contrato
			: null;
		this.bContratoBorrar = !this.srcContrato;
	}

	onContratoEliminado() {
		this.frmColaborador.get('contrato').setValue(null);
		this.onContratoSeleccionadoCancelar();
	}

	onImagenMostrar(imagen: string = null) {
		if (!imagen) {
			return;
		}
		this.srcImagenMostrar = imagen;
		this.mostrarDialogoImagenColaborador = true;
	}

	onColaboradorGuardar() {
		if (!this.frmColaborador.valid) {
			this.frmColaborador.markAllAsTouched();
			hlpSwal.Error('Se detectaron errores en la información solicitada.');
			return;
		}

		let colaborador = this.frmColaborador.value;

		colaborador.borrar_imagen = this.bImagenBorrar ? 1 : 0;
		colaborador.borrar_identificacion_anverso = this.bIdentificacionAnversoBorrar ? 1 : 0;
		colaborador.borrar_identificacion_reverso = this.bIdentificacionReversoBorrar ? 1 : 0;
		colaborador.borrar_contrato = this.bContratoBorrar ? 1 : 0;
		colaborador.fecha_inicio = hlpApp.formatDateToMySQL(colaborador.fecha_inicio);
		delete colaborador.imagen;
		delete colaborador.identificacion_anverso;
		delete colaborador.identificacion_reverso;
		delete colaborador.contrato;

		// console.log(colaborador);

		hlpSwal
			.Pregunta({
				html: '¿Deseas guardar la información?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.colaboradoresService.Guardar(colaborador).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err && r.value.colaborador) {
					const c = r.value.colaborador;
					if (colaborador.id_usuario == 0) {
						this.Colaboradores.push(c);
					} else {
						this.Colaboradores = this.Colaboradores.map((C) => (C.id_usuario === c.id_usuario ? c : C));
					}
					this.Colaboradores = this.OrdenarColaboradores(this.Colaboradores);
					hlpSwal.ExitoToast(r.value.msg);
					this.mostrarDialogoEdicionColaborador = false;
				}
			});
	}

	onColaboradorCancelar() {
		this.srcImagen = null;
		this.srcIdentificacionAnverso = null;
		this.srcIdentificacionReverso = null;
		this.srcContrato = null;
		this.mostrarDialogoEdicionColaborador = false;
	}

	contratoURL() {
		return this.sanitizer.bypassSecurityTrustResourceUrl(this.srcContrato + '#toolbar=0&view=fitH');
	}

	async onContratoMostrar(Colaborador: ColaboradorResumenModel = null) {
		if (Colaborador != null) {
			this.srcContrato = this.Colaborador.contrato
				? environment.urlBackendUsuariosFiles + this.Colaborador.id_usuario + '/' + this.Colaborador.contrato
				: null;
		}

		this.mostrarDialogoContratoColaborador = this.srcContrato != null;
		if (this.mostrarDialogoContratoColaborador) {
			hlpSwal.Cargando();
		}
	}

	onContratoMostrado() {
		hlpSwal.Cerrar();
	}

	async onColaboradorDetalles(idUsuario: number = 0) {
		if (idUsuario == 0) {
			return;
		}
		hlpSwal.Cargando();
		this.Colaborador = await this.colaboradoresService
			.ListarColaborador(idUsuario)
			.toPromise()
			.then((r) => r['colaborador'])
			.catch(async (e) => {
				await hlpSwal.Error(e).then(() => null);
			})
			.finally(() => {
				hlpSwal.Cerrar();
			});
		this.srcImagen = this.Colaborador.imagen
			? environment.urlBackendUsuariosFiles + this.Colaborador.id_usuario + '/' + this.Colaborador.imagen
			: null;
		this.srcIdentificacionAnverso = this.Colaborador.identificacion_anverso
			? environment.urlBackendUsuariosFiles +
			  this.Colaborador.id_usuario +
			  '/' +
			  this.Colaborador.identificacion_anverso
			: null;
		this.srcIdentificacionReverso = this.Colaborador.identificacion_reverso
			? environment.urlBackendUsuariosFiles +
			  this.Colaborador.id_usuario +
			  '/' +
			  this.Colaborador.identificacion_reverso
			: null;

		this.mostrarDialogoDetallesColaborador = this.Colaborador != null;
	}

	/* onColaboradorDeshabilitar(colaborador: ColaboradorResumenModel) {
		if (colaborador.estatus == 0) {
			return;
		}

		hlpSwal
			.Pregunta({
				html: '¿Deseas eliminar el Colaborador?<br /><p class="text-danger"><b>ESTE PROCESO ES IRREVERSIBLE</b></p>',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.colaboradoresService.Deshabilitar(colaborador.id_usuario).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err) {
					this.Colaboradores = this.Colaboradores.filter(function (e) {
						return e.id_usuario !== colaborador.id_usuario;
					});
					hlpSwal.ExitoToast(r.value.msg);
				}
			});
	} */

	onColaboradorAlternarEstatus(colaborador: ColaboradorResumenModel) {
		hlpSwal
			.Pregunta({
				html: '¿Deseas ' + (colaborador.estatus == 1 ? 'des' : '') + 'habilitar el Colaborador?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.usuariosService.AlternarEstatus(colaborador.id_usuario).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err) {
					colaborador.estatus = colaborador.estatus == 1 ? 0 : 1;
					hlpSwal.ExitoToast(r.value.msg);
				}
			});
	}
}
