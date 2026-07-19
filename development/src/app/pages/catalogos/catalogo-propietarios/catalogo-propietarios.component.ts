import { PropositoGeneralService } from '../../../services/proposito-general.service';
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
import { CondominiosService } from '../../../services/condominios.service';
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
		{ header: 'Condominio' },
	{ header: 'Estatus', width: '70px' },
		// Botones de acción
		{ textAlign: 'center', width: '90px' },
	];
	PropietariosFilter: any[] = ['nombre', 'usuario', 'email', 'domicilio', 'unidades.unidad'];

	Propietarios: PropietarioResumenModel[] = [];
	Propietario: PropietarioModel;
	UnidadesSinPropietario: UnidadesEdificioModel[] = [];

	frmPropietario: FormGroup;
	modoOpciones: any[] = [{label: 'Vivienda', value: 'Vivienda'}, {label: 'Alquiler', value: 'Alquiler'}, {label: 'Airbnb', value: 'Airbnb'}];
	pagoCuotaOpciones: any[] = [{label: 'Propietario', value: 'Propietario'}, {label: 'Condómino', value: 'Condómino'}];
	mostrarDialogoResetContrasenia: boolean = false;
nuevaContraseniaReset: string = "";
idUsuarioReset: number = 0;
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
	filtroCondominio: number = null;
	filtroEstatus: number = null;

	get PropietariosFiltrados() {
		return this.Propietarios.filter((p: any) => {
			if (this.filtroCondominio) {
				const cond = this.condominiosLista.find((c: any) => c.value === this.filtroCondominio);
				if (cond && p.condominio_nombre !== cond.label) return false;
			}
			if (this.filtroEstatus !== null && this.filtroEstatus !== undefined && +p.estatus !== this.filtroEstatus) return false;
			return true;
		});
	}
	get kpiActivos() { return this.Propietarios.filter((p: any) => p.estatus == 1).length; }
	get kpiInactivos() { return this.Propietarios.filter((p: any) => p.estatus != 1).length; }
	limpiarFiltros() { this.filtroCondominio = null; this.filtroEstatus = null; }
	esSuperAdminSinCondominio: boolean = false;
	condominiosLista: any[] = [];

	constructor(
		private formBuilder: FormBuilder,
		private sesionUsuarioService: SesionUsuarioService,
		private propietariosService: UsuariosPropietariosService,
		private unidadesService: UnidadesService,
		private condominiosService: CondominiosService,
		private usuariosService: UsuariosService,
    private propositoGeneralService: PropositoGeneralService,
	) {
		this.frmPropietario = this.formBuilder.group(new PropietarioModel());
		this.frmPropietario.addControl('apellidos', new FormControl(null));}

	ngOnInit(): void {
		this.permitirAgregarEditar = [1, 2].includes(this.sesionUsuarioService.obtenerIDPerfilUsuario());
		const perfil = this.sesionUsuarioService.obtenerIDPerfilUsuario();
		const idCond = this.sesionUsuarioService.obtenerIDCondominioUsuario();
		this.esSuperAdminSinCondominio = perfil === 1 && (!idCond || idCond === 0);
		if (this.esSuperAdminSinCondominio) {
			this.condominiosService.Listar(true).toPromise().then((r: any) => {
				this.condominiosLista = (r['condominios'] || []).map((c: any) => ({ label: c.condominio, value: +c.id_condominio }));
			});
		}
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
				await hlpSwal.Error(e || 'Error desconocido').then(() => null);
			});

		if (!this.UnidadesSinPropietario || this.UnidadesSinPropietario.length === 0) {
		  if (idUsuario === 0) {
		    hlpSwal.Cerrar();
		    hlpSwal.Error('No hay unidades disponibles para asignar a un propietario.');
		    return;
		  }
		}

		if (idUsuario > 0) {
			this.Propietario = await this.propietariosService
				.ListarPropietario(idUsuario)
				.toPromise()
				.then((r) => r['propietario'])
				.catch(async (e) => {
					await hlpSwal.Error(e || 'Error desconocido').then(() => null);
				});
			if (this.Propietario == null) return;
// Recargar unidades filtradas por condominio
const condId = this.Propietario['fk_id_condominio'];
if (condId) {
this.UnidadesSinPropietario = await this.unidadesService
.ListarUnidadesSinPropietario(condId)
.toPromise()
.then((r) => r['unidades'] || [])
.catch(() => []);
}
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

			this.frmPropietario.addControl('fk_id_condominio', new FormControl(null));
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
		if (!this.Propietario.unidades || this.Propietario.unidades.length === 0) {
			hlpSwal.Error('Debe asignar al menos una unidad al propietario.');
			return;
		}

		let propietario = this.frmPropietario.value;

		propietario.unidades = JSON.stringify(this.Propietario.unidades.map((u) => ({ id_unidad: u.id_unidad, modo: u.modo || 'Vivienda', pago_cuota_mantenimiento: u.pago_cuota_mantenimiento || 'Propietario' })));
		if (this.esSuperAdminSinCondominio && this.frmPropietario.get('fk_id_condominio')) {
			propietario.fk_id_condominio = this.frmPropietario.get('fk_id_condominio').value;
		}
		propietario.borrar_imagen = this.bImagenBorrar ? 1 : 0;
		propietario.borrar_identificacion_anverso = this.bIdentificacionAnversoBorrar ? 1 : 0;
		propietario.borrar_identificacion_reverso = this.bIdentificacionReversoBorrar ? 1 : 0;

		delete propietario.imagen;
		delete propietario.identificacion_anverso;
		delete propietario.identificacion_reverso;


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
          this.onActualizarInformacion();
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
				await hlpSwal.Error(e || 'Error desconocido').then(() => null);
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

	getCondominioColor(nombre: string): string {
		if (!nombre) return '#8a8f9e';
		const colores = [
			'#e91e8c', '#3B82F6', '#1BC99A', '#f59e0b', '#8b5cf6',
			'#ef4444', '#06b6d4', '#84cc16', '#f97316', '#ec4899',
		];
		let hash = 0;
		for (let i = 0; i < nombre.length; i++) {
			hash = nombre.charCodeAt(i) + ((hash << 5) - hash);
		}
		return colores[Math.abs(hash) % colores.length];
	}


	onGenerarUsuario() {
		const nombre = (this.frmPropietario.get('nombre').value || '').trim();
		const apellidos = (this.frmPropietario.get('apellidos')?.value || '').trim();
		if (!nombre || nombre.length < 2) return;
		let usuario = nombre.toLowerCase().split(/\s+/)[0];
		if (apellidos) {
			usuario += '.' + apellidos.toLowerCase().split(/\s+/)[0];
		}
		usuario = usuario.normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9.]/g, '');
		if (this.frmPropietario.get('id_usuario').value == 0) {
   this.usuarioManualmenteEditado = false;
   this.frmPropietario.patchValue({ usuario: usuario });
   this.frmPropietario.get('usuario')?.setErrors(null);
   this.onVerificarUsuario();
		}
	}


	onModoChange(unidad: any) {
		if (unidad.modo === 'Vivienda') {
			unidad.pago_cuota_mantenimiento = 'Propietario';
		}
	}

	onUnidadAsignada() {
		for (const u of this.Propietario.unidades) {
			if (!u.modo) u.modo = 'Vivienda';
			if (!u.pago_cuota_mantenimiento) u.pago_cuota_mantenimiento = 'Propietario';
		}
	}


	async onCondominioChange(event) {
		const condId = event?.target?.value || event?.value || null;
		if (condId) {
			this.UnidadesSinPropietario = await this.unidadesService
				.ListarUnidadesSinPropietario(condId)
				.toPromise()
				.then((r) => r['unidades'] || [])
				.catch(() => []);
			this.Propietario.unidades = [];
		}
	}


	async onPropietarioEliminar(propietario: any) {
		const result = await hlpSwal.Pregunta({
			html: '¿Estás seguro de eliminar este registro? Esta acción no se puede deshacer.',
			showLoaderOnConfirm: true,
			preConfirm: async () => {
				try {
					return await this.propietariosService.Eliminar(propietario.id_usuario).toPromise();
				} catch (e) {
					return hlpSwal.Error(e || 'Error al eliminar').then(() => ({ err: true }));
				}
			},
			allowOutsideClick: () => false,
		});
		if (result.value && !result.value.err && !result.value.error) {
			hlpSwal.ExitoToast('Registro eliminado correctamente.');
			this.onActualizarInformacion();
		}
	}

  usuarioManualmenteEditado: boolean = false;

  async onVerificarUsuario() {
    const usuario = (this.frmPropietario.get('usuario')?.value || '').trim();
    if (!usuario) return;
    try {
      const r: any = await this.propositoGeneralService.VerificarUsuario(usuario).toPromise();
      if (r.existe) {
        const nombre = (this.frmPropietario.get('nombre')?.value || '').trim().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9.]/g, '');
        const apellidos = (this.frmPropietario.get('apellidos')?.value || '').trim().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9.]/g, '');
        const partes = apellidos.split(/\s+/);
        const candidatos = [nombre + (partes[0] ? '.' + partes[0] : ''), nombre + (partes[1] ? '.' + partes[1] : ''), nombre + '.' + apellidos.replace(/\s+/g, '.'), usuario + '2', usuario + '3'];
        for (const candidato of candidatos) {
          const check: any = await this.propositoGeneralService.VerificarUsuario(candidato).toPromise();
          if (!check.existe) { this.frmPropietario.patchValue({ usuario: candidato }); this.frmPropietario.get('usuario')?.setErrors(null); return; }
        }
        if (this.usuarioManualmenteEditado) { this.frmPropietario.get('usuario').setErrors({ duplicado: true }); }
      } else {
        this.frmPropietario.get('usuario')?.setErrors(null);
      }
    } catch(e) {}
  }

generarContrasenaSugerida(): string {
const words = ['Casa', 'Luna', 'Sol', 'Mar', 'Rio', 'Flor', 'Viento', 'Fuego'];
const word = words[Math.floor(Math.random() * words.length)];
const num = Math.floor(Math.random() * 900) + 100;
const symbols = ['!', '@', '#', '$'];
const sym = symbols[Math.floor(Math.random() * symbols.length)];
return word + num + sym;
}

onAbrirResetContrasenia(idUsuario: number) {
this.idUsuarioReset = idUsuario;
this.nuevaContraseniaReset = this.generarContrasenaSugerida();
this.mostrarDialogoResetContrasenia = true;
}

async onGuardarResetContrasenia() {
if (!this.nuevaContraseniaReset || this.nuevaContraseniaReset.length < 6) {
hlpSwal.Advertencia('La contraseña debe tener al menos 6 caracteres.');
return;
}
hlpSwal.Cargando();
try {
const r: any = await this.usuariosService.EstablecerContrasenia(this.idUsuarioReset, this.nuevaContraseniaReset).toPromise();
hlpSwal.Cerrar();
if (!r.err) {
hlpSwal.ExitoToast('Contraseña actualizada correctamente.');
this.mostrarDialogoResetContrasenia = false;
} else { hlpSwal.Error(r.msg); }
} catch(e) { hlpSwal.Cerrar(); hlpSwal.Error(e); }
}

}