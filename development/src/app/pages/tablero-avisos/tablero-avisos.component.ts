import { Component, OnInit, isDevMode, AfterViewInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';

import { environment } from '../../../environments/environment';
import * as hlpApp from '../../helpers/app-helper';
import * as hlpSwal from '../../helpers/sweetalert2-helper';
import * as hlpPrimeNGTable from '../../helpers/primeng-table-helper';
import { PerfilUsuarioModel } from '../../models/perfiles-usuario.model';
import { AvisoModel } from '../../models/tablero-avisos.model';
import { SesionUsuarioService } from '../../services/sesion-usuario.service';
import { TableroAvisosService } from '../../services/tablero-avisos.service';
import { UsuariosService } from '../../services/usuarios.service';

@Component({
	selector: 'app-avisos',
	templateUrl: './tablero-avisos.component.html',
	styleUrls: ['./tablero-avisos.component.css'],
})
export class TableroAvisosComponent implements OnInit, AfterViewInit {
	appData = environment;
	hlpApp = hlpApp;
	hlpPrimeNGTable = hlpPrimeNGTable;
	isDevelopment = isDevMode;

	// Tabla Avisos
	// Columnas de la tabla
	AvisosCols: any[] = [
		{ header: 'Título' },
		{ header: 'Publicado', width: '90px' },
		{ header: 'Fecha publicación', width: '120px' },
		// Botones de acción
		{ textAlign: 'center', width: '130px' },
	];
	AvisosFilter: any[] = ['titulo'];

	PerfilesUsuarios: PerfilUsuarioModel[] = [];
	PerfilUsuarioSeleccionado: PerfilUsuarioModel;
	PerfilUsuarioDefault: PerfilUsuarioModel;
	// idPerfilUsuarioSeleccionado: number;
	Avisos: AvisoModel[] = [];
	Aviso: AvisoModel;

	frmAviso: FormGroup;
	mostrarDialogoEdicionAviso: boolean = false;
	mostrarDialogoDetalleAviso: boolean = false;
	ModulosEditorDescripcion = {
		imageResize: {
			handleStyles: {
				backgroundColor: 'black',
				border: 'none',
				color: 'white',
			},
			modules: ['DisplaySize', 'Resize'],
		},
	};
	permitirAgregarEliminar: boolean = false;

	constructor(
		private formBuilder: FormBuilder,
		private sesionUsuarioService: SesionUsuarioService,
		private tableroAvisosService: TableroAvisosService,
		private usuariosService: UsuariosService,
	) {}

	ngOnInit(): void {
		// this.idPerfilUsuarioSeleccionado = 0;
		this.permitirAgregarEliminar = [1, 2].includes(this.sesionUsuarioService.obtenerIDPerfilUsuario());
		this.PerfilUsuarioDefault = new PerfilUsuarioModel();
		this.PerfilUsuarioSeleccionado = this.permitirAgregarEliminar
			? new PerfilUsuarioModel()
			: this.sesionUsuarioService.obtenerPerfilUsuario();
		this.PerfilesUsuarios = [];

		if (!this.permitirAgregarEliminar) {
			this.AvisosCols = this.AvisosCols.filter((c) => c.header != 'Publicado');
			this.onActualizarInformacion();
			return;
		}

		hlpSwal.Cargando();

		this.usuariosService
			.ListarPerfilesUsuariosTableroAvisos()
			.toPromise()
			.then((r) => {
				this.PerfilesUsuarios = r['perfiles_usuarios'];
			})
			.catch(async (e) => {
				await hlpSwal.Error(e);
			})
			.finally(() => hlpSwal.Cerrar());
	}

	ngAfterViewInit(): void {
		let s = document.getElementById('cmbPerfilUsuario');
		s.style.display = this.permitirAgregarEliminar ? 'block' : 'none';
	}

	private OrdenarAvisos(avisos: AvisoModel[]) {
		return avisos.sort((a, b) =>
			a.fecha_publicacion.toString() + a.titulo > b.fecha_publicacion.toString() + b.titulo ? 1 : -1,
		);
	}

	public onActualizarInformacion() {
		// if (this.idPerfilUsuarioSeleccionado == 0) {
		if (this.PerfilUsuarioSeleccionado?.id_perfil_usuario == 0) {
			hlpSwal.Error('Debe seleccionar un perfil.');
			return;
		}

		this.Avisos = [];

		hlpSwal.Cargando();

		this.tableroAvisosService
			// .ListarActivos(this.idPerfilUsuarioSeleccionado)
			.ListarActivos(this.PerfilUsuarioSeleccionado.id_perfil_usuario, !this.permitirAgregarEliminar)
			.toPromise()
			.then((r) => {
				this.Avisos = this.OrdenarAvisos(r['avisos']);
			})
			.catch(async (e) => {
				await hlpSwal.Error(e);
			})
			.finally(() => hlpSwal.Cerrar());
	}

	async onAvisoEditar(idAviso: number = 0) {
		//if (this.idPerfilUsuarioSeleccionado == 0) {
		if (this.PerfilUsuarioSeleccionado?.id_perfil_usuario == 0) {
			hlpSwal.Error('Debe seleccionar un perfil.');
			return;
		}

		hlpSwal.Cargando();

		if (idAviso > 0) {
			this.Aviso = await this.tableroAvisosService
				.ListarAviso(idAviso)
				.toPromise()
				.then((r) => r['aviso'])
				.catch(async (e) => {
					await hlpSwal.Error(e).then(() => null);
				});
			if (this.Aviso == null) return;
		} else {
			this.Aviso = new AvisoModel();
		}
		hlpSwal.Cerrar();

		try {
			/* this.Aviso.fecha_limite_pago =
				idAviso > 0
					? new Date(this.Aviso.fecha_limite_pago + 'T00:00:00')
					: this.Aviso.fecha_limite_pago; */
			/* this.Aviso.fecha_pago =
				this.Aviso.id_estatus_recaudacion == 2
					? new Date(this.Aviso.fecha_pago + 'T00:00:00')
					: new Date(); */
			// this.Aviso.id_perfil_usuario_destino = this.idPerfilUsuarioSeleccionado;
			this.Aviso.id_perfil_usuario_destino = this.PerfilUsuarioSeleccionado.id_perfil_usuario;
			this.frmAviso = this.formBuilder.group(this.Aviso);
			this.frmAviso
				.get('titulo')
				.setValidators([Validators.required, Validators.minLength(3), Validators.maxLength(150)]);
			this.frmAviso.get('descripcion').setValidators([Validators.required]);
			this.frmAviso.updateValueAndValidity();

			this.mostrarDialogoEdicionAviso = true;
		} catch (e) {
			hlpSwal.Error(e);
		}
	}

	onAvisoGuardar() {
		if (!this.frmAviso.valid) {
			this.frmAviso.markAllAsTouched();
			hlpSwal.Error('Se detectaron errores en la información solicitada.');
			return;
		}

		let aviso = this.frmAviso.value;
		aviso.publicado = aviso.publicado ? 1 : 0;

		hlpSwal
			.Pregunta({
				html: '¿Deseas guardar la información?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.tableroAvisosService.Guardar(aviso).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err && r.value.aviso) {
					const c = r.value.aviso;
					if (aviso.id_aviso == 0) {
						this.Avisos.push(c);
					} else {
						this.Avisos = this.Avisos.map((C) => (C.id_aviso === c.id_aviso ? c : C));
					}
					this.Avisos = this.OrdenarAvisos(this.Avisos);
					hlpSwal.ExitoToast(r.value.msg);
					this.mostrarDialogoEdicionAviso = false;
				}
			});
	}

	onAvisoCancelar() {
		this.mostrarDialogoEdicionAviso = false;
	}

	async onAvisoDetalle(idAviso: number = 0) {
		if (idAviso == 0) {
			return;
		}

		hlpSwal.Cargando();
		this.Aviso = await this.tableroAvisosService
			.ListarAviso(idAviso)
			.toPromise()
			.then((r) => r['aviso'])
			.catch(async (e) => {
				await hlpSwal.Error(e).then(() => null);
			})
			.finally(() => {
				hlpSwal.Cerrar();
			});

		this.mostrarDialogoDetalleAviso = this.Aviso != null;
	}

	onAvisoAlternarEstatusPublicado(aviso: AvisoModel) {
		hlpSwal
			.Pregunta({
				html: '¿Deseas ' + (aviso.publicado == 1 ? 'anular la publicación' : 'publicar') + ' el Aviso?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.tableroAvisosService.AlternarEstatusPublicado(aviso.id_aviso).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err && r.value.aviso) {
					const c = r.value.aviso;
					this.Avisos = this.Avisos.map((C) => (C.id_aviso === c.id_aviso ? c : C));
					hlpSwal.ExitoToast(r.value.msg);
				}
			});
	}

	onAvisoEliminar(idAviso: number = 0) {
		hlpSwal
			.Pregunta({
				html: '¿Deseas eliminar el registro?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.tableroAvisosService.Eliminar(idAviso).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err) {
					this.Avisos = this.Avisos.filter((a) => a.id_aviso != idAviso);
					hlpSwal.ExitoToast(r.value.msg);
				}
			});
	}
}
