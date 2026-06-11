import { Component, OnInit, isDevMode } from '@angular/core';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';
import { HttpClient, HttpHeaders } from '@angular/common/http';

import { environment } from '../../../../environments/environment';
import * as hlpApp from '../../../helpers/app-helper';
import * as hlpSwal from '../../../helpers/sweetalert2-helper';
import * as hlpPrimeNGTable from '../../../helpers/primeng-table-helper';
import { UsuariosService } from '../../../services/usuarios.service';
import { CondominiosService } from '../../../services/condominios.service';
import { SesionUsuarioService } from '../../../services/sesion-usuario.service';
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
		{ header: 'Condominio' },
		{ header: 'Tipo' },
		{ header: 'Estructura' },
		{ header: 'Email' },
		{ header: 'Contacto' },
		{ header: 'Estatus', width: '70px' },
		{ textAlign: 'center', width: '90px' },
	];
	AdministradoresFilter: any[] = ['nombre', 'email', 'condominio_nombre'];
	Administradores: AdministradorResumenModel[] = [];
	Administrador: AdministradorModel;


	frmAdministrador: FormGroup;
	mostrarDialogoEdicionAdministrador: boolean = false;
	mostrarDialogoImagenAdministrador: boolean = false;
	mostrarDialogoDetallesAdministrador: boolean = false;
	mostrarFiltros: boolean = false;

	// Tipo de administración
	tipoAdministracion: string = 'UNICO';
	tipoAcceso: string = 'EXTERNO';
	tipoPersona: string = 'FISICA';
	UsuariosInternos: any[] = [];
	MiembrosComite: any[] = [];
  miembrosComiteDetalle: any[] = [];
	frmMiembroComite: any = null;
	mostrarFrmMiembro: boolean = false;
	razonSocial: string = null;
	rfcMoral: string = null;
	representanteLegal: string = null;
	archivosPersonaMoral: any = { acta_constitutiva: null, constancia_fiscal: null, id_representante: null };

	opcionesTipoAdmin = [
		{ label: 'Administrador Único', value: 'UNICO' },
		{ label: 'Comité de Administración', value: 'COMITE' },
	];
	opcionesTipoAcceso = [
		{ label: 'Interno (Propietario/Condómino)', value: 'INTERNO' },
		{ label: 'Externo', value: 'EXTERNO' },
	];
	opcionesTipoPersona = [
		{ label: 'Persona Física', value: 'FISICA' },
		{ label: 'Persona Moral', value: 'MORAL' },
	];
	opcionesCargos = [
		{ label: 'PRESIDENTE', value: 1 },
		{ label: 'SECRETARIO', value: 2 },
		{ label: 'TESORERO', value: 3 },
		{ label: 'VOCAL', value: 4 },
	];
	srcImagen: string = null;
	srcIdentificacionAnverso: string = null;
	srcIdentificacionReverso: string = null;
	srcImagenMostrar: string = null;
	bImagenBorrar: boolean = false;
	bIdentificacionAnversoBorrar: boolean = false;
	bIdentificacionReversoBorrar: boolean = false;
	Condominios: any[] = [];
	opcionesCondominios: any[] = [];

	constructor(
		private formBuilder: FormBuilder,
		private administradoresService: UsuariosAdministradoresService,
		private http: HttpClient,
		private usuariosService: UsuariosService,
		private condominiosService: CondominiosService,
		private sesionUsuarioService: SesionUsuarioService,
	) {}

	ngOnInit(): void {
		this.frmAdministrador = this.formBuilder.group({
			id_usuario: [0],
			usuario: [null],
			nombre: [null],
			email: [null],
			telefono: [null],
			domicilio: [null],
			identificacion_folio: [null],
			identificacion_domicilio: [null],
			imagen: [null],
			identificacion_anverso: [null],
			identificacion_reverso: [null],
			fk_id_condominio: [null],
			estatus: [0],
			archivo_imagen: [null],
			archivo_identificacion_anverso: [null],
			archivo_identificacion_reverso: [null],
			fecha_inicio: [null],
			fecha_fin: [null],
			contrasenia: [null],
		});
		this.onActualizarInformacion();
					this.condominiosService.ListarSinAdministrador().toPromise().then((r: any) => { this.Condominios = r['condominios'] || []; this.opcionesCondominios = this.Condominios.map((c: any) => ({ label: c.condominio, value: +c.id_condominio })); }).catch(() => {});
		this.condominiosService.ListarSinAdministrador().toPromise().then((r: any) => {
			this.Condominios = r['condominios'] || [];
			this.opcionesCondominios = this.Condominios.map((c: any) => ({ label: c.condominio, value: +c.id_condominio }));
		}).catch(() => {});
	}

	private OrdenarAdministradores(administradores: AdministradorResumenModel[]) {
		return administradores.sort((a, b) => (a.nombre > b.nombre ? 1 : -1));
	}

	get puedeGuardar(): boolean {
		if (this.tipoAcceso === 'EXTERNO') return !!(this.frmAdministrador?.get('nombre')?.value && this.frmAdministrador?.get('email')?.value && this.frmAdministrador?.get('telefono')?.value);
		if (this.tipoAcceso === 'INTERNO') {
			const idCond = this.frmAdministrador?.get('fk_id_condominio')?.value;
			if (!idCond) return false;
			if (this.tipoAdministracion === 'UNICO') return !!this.frmMiembroComite;
			if (this.tipoAdministracion === 'COMITE') {
				// Al menos Presidente requerido
				return this.MiembrosComite.length > 0 && !!this.MiembrosComite.find(m => m.id_cargo === 1);
			}
		}
		return false;
	}

	get opcionesCargosDisponibles() {
		// Vocal (value=4) puede repetirse, el resto no
		return this.opcionesCargos.filter((cargo: any) => {
			if (cargo.value === 4) return true; // Vocal puede repetirse
			return !this.MiembrosComite.find((m: any) => m.id_cargo === cargo.value);
		});
	}

	get kpiTotal() { return this.Administradores.length; }
	get kpiAsignados() { return this.Administradores.filter((a: any) => a.fk_id_condominio).length; }
	get kpiSinAsignar() { return this.Administradores.filter((a: any) => !a.fk_id_condominio).length; }
	get kpiPctAsignados() {
		if (!this.Administradores.length) return 0;
		return Math.round((this.kpiAsignados / this.kpiTotal) * 100);
	}
	get kpiInternos() { return this.Administradores.filter((a: any) => a.tipo_administrador === 'INTERNO').length; }
	get kpiUnicos() { return this.Administradores.filter((a: any) => a.estructura_administracion === 'UNICO').length; }
	get kpiComites() { return this.Administradores.filter((a: any) => a.estructura_administracion === 'COMITE').length; }
	get kpiExternos() { return this.Administradores.filter((a: any) => a.tipo_administrador !== 'INTERNO').length; }
	get kpiFisicas() { return this.Administradores.filter((a: any) => a.tipo_administrador !== 'INTERNO' && a.tipo_persona === 'FISICA').length; }
	get kpiMorales() { return this.Administradores.filter((a: any) => a.tipo_administrador !== 'INTERNO' && a.tipo_persona === 'MORAL').length; }

	public onActualizarInformacion() {
		// Recargar condominios sin administrador
		this.condominiosService.ListarSinAdministrador().toPromise().then((r: any) => {
			this.Condominios = r['condominios'] || [];
			this.opcionesCondominios = this.Condominios.map((c: any) => ({ label: c.condominio, value: +c.id_condominio }));
		}).catch(() => {});
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

		// Si el admin tiene condominio asignado, agregarlo a las opciones si no está
		if (idUsuario > 0 && this.Administrador['fk_id_condominio'] && this.Administrador['condominio_nombre']) {
			const idCond = +this.Administrador['fk_id_condominio'];
			const existe = this.opcionesCondominios.find((o: any) => o.value === idCond);
			if (!existe) {
				this.opcionesCondominios = [
					{ label: this.Administrador['condominio_nombre'], value: idCond },
					...this.opcionesCondominios
				];
			}
		}

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
			const a = this.Administrador;
			this.frmAdministrador = this.formBuilder.group({
				id_usuario: [a.id_usuario],
				usuario: [a.usuario],
				nombre: [a.nombre],
				email: [a.email],
				telefono: [a.telefono],
				domicilio: [a.domicilio],
				identificacion_folio: [a.identificacion_folio],
				identificacion_domicilio: [a.identificacion_domicilio],
				imagen: [a.imagen],
				identificacion_anverso: [a.identificacion_anverso],
				identificacion_reverso: [a.identificacion_reverso],
				fk_id_condominio: [+a.fk_id_condominio || null],
				estatus: [a.estatus],
				archivo_imagen: [null],
				archivo_identificacion_anverso: [null],
				archivo_identificacion_reverso: [null],
			fecha_inicio: [null],
			fecha_fin: [null],
			contrasenia: [null],
			});
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
			this.frmAdministrador.get('fecha_inicio').setValidators([Validators.required]);
			this.frmAdministrador.get('fecha_fin').setValidators([Validators.required]);

			this.frmAdministrador.updateValueAndValidity();

			this.bImagenBorrar = false;
			this.bIdentificacionAnversoBorrar = false;
			this.bIdentificacionReversoBorrar = false;

			// Inicializar tipo administración desde datos del administrador
			this.tipoAdministracion = this.Administrador['estructura_administracion'] || 'UNICO';
			this.tipoAcceso = this.Administrador['tipo_administrador'] || 'EXTERNO';
			this.tipoPersona = this.Administrador['tipo_persona'] || 'FISICA';
			this.MiembrosComite = [];
			this.UsuariosInternos = [];
			this.mostrarFrmMiembro = false;

			// Cargar usuarios internos solo si hay condominio
			this.UsuariosInternos = [];

			// Fechas de mandato default
    const hoyM = new Date(); const unAnioM = new Date(); unAnioM.setFullYear(unAnioM.getFullYear() + 1);
    if (!this.Administrador.id_usuario || this.Administrador.id_usuario === 0) {
      this.frmAdministrador.addControl('fecha_inicio_mandato', new FormControl(hoyM));
      this.frmAdministrador.addControl('fecha_fin_mandato', new FormControl(unAnioM));
    } else {
      if (!this.frmAdministrador.get('fecha_inicio_mandato')) this.frmAdministrador.addControl('fecha_inicio_mandato', new FormControl(this.Administrador.fecha_inicio_mandato ? new Date(this.Administrador.fecha_inicio_mandato + 'T12:00:00') : null));
      if (!this.frmAdministrador.get('fecha_fin_mandato')) this.frmAdministrador.addControl('fecha_fin_mandato', new FormControl(this.Administrador.fecha_fin_mandato ? new Date(this.Administrador.fecha_fin_mandato + 'T12:00:00') : null));
    }
    // Campos Persona Moral
    if (!this.frmAdministrador.get('razon_social')) this.frmAdministrador.addControl('razon_social', new FormControl(this.Administrador.razon_social || null));
    if (!this.frmAdministrador.get('rfc')) this.frmAdministrador.addControl('rfc', new FormControl(this.Administrador.rfc || null));
    if (!this.frmAdministrador.get('domicilio_fiscal')) this.frmAdministrador.addControl('domicilio_fiscal', new FormControl(this.Administrador.domicilio_fiscal || null));
    this.mostrarDialogoEdicionAdministrador = true;
			// Cargar usuarios internos si hay condominio y es interno
			setTimeout(() => {
				const idCond = this.frmAdministrador?.get('fk_id_condominio')?.value;
				if (idCond && this.tipoAcceso === 'INTERNO') {
					this.onCondominioAdminChange(idCond);
				}
			}, 200);
		} catch (e) {
			hlpSwal.Error(e);
		}
	}

	onCondominioAdminChange(idCondominio: number) {
		if (!idCondominio || this.tipoAcceso !== 'INTERNO') {
			this.UsuariosInternos = [];
			return;
		}
		// Autollenar nombre si es Comité
		const cond = this.opcionesCondominios.find((c: any) => c.value === +idCondominio);
		if (cond && this.tipoAdministracion === 'COMITE') {
			this.frmAdministrador.patchValue({ nombre: 'COMITÉ DE ADMINISTRACIÓN ' + cond.label });
		} else if (cond && this.tipoAdministracion === 'UNICO') {
			this.frmAdministrador.patchValue({ nombre: 'ADMINISTRADOR INTERNO ' + cond.label });
		}
		this.usuariosService.ListarPerfilCondominio(idCondominio).toPromise()
			.then((r: any) => {
				const usuarios = r['usuarios'] || [];
				// Filtrar usuarios ya asignados como miembros del comité actual
				const idsEnComite = this.MiembrosComite.map((m: any) => m.id_usuario);
				this.UsuariosInternos = usuarios
					.filter((u: any) => !idsEnComite.includes(u.id_usuario))
					.map((u: any) => ({
						label: u.nombre + ' (' + u.perfil_usuario + ')',
						value: u.id_usuario,
						...u
					}));
			}).catch(() => {});
	}

	onTipoAdminChange(val: string) {
		this.tipoAdministracion = val;
		if (val === 'COMITE') this.MiembrosComite = [];
	}

	onTipoAccesoChange(val: string) { this.tipoAcceso = val; }

	onAgregarMiembroComite() {
		this.mostrarFrmMiembro = true;
		this.frmMiembroComite = { id_usuario: null, id_cargo: null, label: '' };
	}

	onConfirmarMiembro(usuario: any, idCargo: number) {
		if (!usuario || !idCargo) return;
		const cargo = this.opcionesCargos.find(c => c.value === idCargo);
		// Verificar cargo único
		if (this.MiembrosComite.find(m => m.id_cargo === idCargo)) {
			hlpSwal.Advertencia('Ya existe un ' + cargo.label + ' en el comité.'); return;
		}
		// Verificar que el usuario no tenga ya un cargo
		if (this.MiembrosComite.find(m => m.id_usuario === usuario.value)) {
			hlpSwal.Advertencia('Este miembro ya tiene un cargo asignado.'); return;
		}
		this.MiembrosComite.push({
			id_usuario: usuario.value,
			usuario: usuario.label,
			perfil_usuario: usuario.perfil_usuario || '',
			id_cargo: idCargo,
			cargo: cargo.label
		});
		// Quitar el usuario de las opciones disponibles
		this.UsuariosInternos = this.UsuariosInternos.filter((u: any) => u.value !== usuario.value);
		this.mostrarFrmMiembro = false;
		this.frmMiembroComite = { id_usuario: null, id_cargo: null, label: '' };
	}


	onArchivoSeleccionado(event: any, tipo: string) {
		const files = event.target.files;
		if (!files || files.length === 0) return;
		this.archivosPersonaMoral[tipo] = tipo === 'id_representante' ? Array.from(files) : files[0];
	}

	onTipoPersonaChange(val: string) {
		this.tipoPersona = val;
		this.razonSocial = null;
		this.rfcMoral = null;
		this.representanteLegal = null;
		this.archivosPersonaMoral = { acta_constitutiva: null, constancia_fiscal: null, id_representante: null };
	}

	onEliminarMiembro(idx: number) {
		const miembro = this.MiembrosComite[idx];
		// Devolver el usuario a las opciones
		if (miembro) {
			this.UsuariosInternos = [...this.UsuariosInternos, { label: miembro.usuario, value: miembro.id_usuario }]
				.sort((a, b) => a.label.localeCompare(b.label));
		}
		this.MiembrosComite.splice(idx, 1);
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
		let administrador: any = {};

		// Si es INTERNO, construir objeto sin requerir campos del form
		if (this.tipoAcceso === 'INTERNO') {
			const idCond = this.frmAdministrador.get('fk_id_condominio').value;
			if (!idCond) { hlpSwal.Error('Debe seleccionar un condominio.'); return; }
			const condNombre = this.opcionesCondominios.find((c: any) => c.value === +idCond)?.label || '';
			administrador = {
				id_usuario: this.tipoAdministracion === 'UNICO' && this.frmMiembroComite ? this.frmMiembroComite.value : this.frmAdministrador.get('id_usuario').value,
				nombre: this.tipoAdministracion === 'COMITE' ? 'COMITÉ DE ADMINISTRACIÓN ' + condNombre : null,
				usuario: this.tipoAdministracion === 'COMITE' ? 'comite_' + idCond : null,
				email: this.tipoAdministracion === 'COMITE' ? 'comite_' + idCond + '@hoose.mx' : null,
				telefono: this.tipoAdministracion === 'COMITE' ? '0000000000' : null,
				fk_id_condominio: idCond,
				fk_id_usuario_interno: this.tipoAdministracion === 'UNICO' && this.frmMiembroComite ? this.frmMiembroComite.value : null,
				tipo_administrador: 'INTERNO',
				estructura_administracion: this.tipoAdministracion,
				tipo_persona: null,
				miembros_comite: this.tipoAdministracion === 'COMITE' ? this.MiembrosComite : null,
			fecha_inicio: this.frmAdministrador.get('fecha_inicio_mandato')?.value ? this.hlpApp.formatDateToMySQL(this.frmAdministrador.get('fecha_inicio_mandato').value) : null,
			fecha_fin: this.frmAdministrador.get('fecha_fin_mandato')?.value ? this.hlpApp.formatDateToMySQL(this.frmAdministrador.get('fecha_fin_mandato').value) : null,
			};
		} else {
// Validación deshabilitada - puedeGuardar ya valida
			administrador = this.frmAdministrador.value;
			administrador.tipo_administrador = this.tipoAcceso;
			administrador.estructura_administracion = this.tipoAdministracion;
			administrador.tipo_persona = this.tipoPersona;
		}
		administrador.borrar_imagen = this.bImagenBorrar ? 1 : 0;
		administrador.borrar_identificacion_anverso = this.bIdentificacionAnversoBorrar ? 1 : 0;
		administrador.borrar_identificacion_reverso = this.bIdentificacionReversoBorrar ? 1 : 0;
		delete administrador.imagen;
		delete administrador.identificacion_anverso;
		delete administrador.identificacion_reverso;

		console.log('GUARDANDO:', JSON.stringify(administrador));
		console.log('GUARDANDO:', JSON.stringify(administrador));

		hlpSwal
			.Pregunta({
				html: '¿Deseas guardar la información?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						const resp = this.tipoAcceso === 'INTERNO'
							? await this.administradoresService.GuardarInterno(administrador).toPromise()
							: await this.administradoresService.Guardar(administrador).toPromise();
						console.log('RESPUESTA:', JSON.stringify(resp));
						return resp;
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err && !r.value.error) {
					hlpSwal.ExitoToast(r.value.msg);
					this.mostrarDialogoEdicionAdministrador = false;
					this.onActualizarInformacion();
					this.condominiosService.ListarSinAdministrador().toPromise().then((r: any) => { this.Condominios = r['condominios'] || []; this.opcionesCondominios = this.Condominios.map((c: any) => ({ label: c.condominio, value: +c.id_condominio })); }).catch(() => {});
				}
			});
	}




	onAdministradorDesvincular(administrador: any) {
		hlpSwal.Pregunta({
			html: '¿Deseas desvincular a <b>' + administrador.nombre + '</b> del condominio <b>' + administrador.condominio_nombre + '</b>?',
			showLoaderOnConfirm: true,
			preConfirm: async () => {
				try {
					return await this.administradoresService.Eliminar({
						id_usuario: administrador.id_usuario,
						tipo_administrador: administrador.id_administrador_interno ? 'INTERNO' : 'EXTERNO',
						fk_id_condominio: administrador.fk_id_condominio,
					}).toPromise();
				} catch (e) {
					return hlpSwal.Error(e).then(() => ({ err: true }));
				}
			},
			allowOutsideClick: () => !hlpSwal.estaCargando,
		}).then((r: any) => {
			if (r.value && !r.value.err && !r.value.error) {
				hlpSwal.ExitoToast('Administrador desvinculado correctamente.');
				this.onActualizarInformacion();
					this.condominiosService.ListarSinAdministrador().toPromise().then((r: any) => { this.Condominios = r['condominios'] || []; this.opcionesCondominios = this.Condominios.map((c: any) => ({ label: c.condominio, value: +c.id_condominio })); }).catch(() => {});
			}
		});
	}

	onAdministradorEliminar(administrador: any) {
		hlpSwal.Pregunta({
			html: '¿Deseas eliminar a <b>' + administrador.nombre + '</b> como administrador?',
			showLoaderOnConfirm: true,
			preConfirm: async () => {
				try {
					return await this.administradoresService.EliminarCompleto(administrador.id_usuario).toPromise();
				} catch (e) {
					return hlpSwal.Error(e).then(() => ({ err: true }));
				}
			},
			allowOutsideClick: () => !hlpSwal.estaCargando,
		}).then((r: any) => {
			if (r.value && !r.value.err && !r.value.error) {
				hlpSwal.ExitoToast('Administrador eliminado correctamente.');
				this.onActualizarInformacion();
					this.condominiosService.ListarSinAdministrador().toPromise().then((r: any) => { this.Condominios = r['condominios'] || []; this.opcionesCondominios = this.Condominios.map((c: any) => ({ label: c.condominio, value: +c.id_condominio })); }).catch(() => {});
			}
		});
	}

	async onAdministradorDetalles(idUsuario: number = 0) {
    if (idUsuario == 0) return;
    // Usar datos de la tabla directamente (ya tiene todos los campos)
    const admin = this.Administradores.find((a: any) => a.id_usuario == idUsuario);
    if (!admin) { hlpSwal.Error('Administrador no encontrado.'); return; }
    this.Administrador = admin as any;
    this.srcImagen = admin.imagen
      ? environment.urlBackendUsuariosFiles + admin.id_usuario + '/' + admin.imagen
      : null;
    this.srcIdentificacionAnverso = (admin as any).identificacion_anverso
      ? environment.urlBackendUsuariosFiles + admin.id_usuario + '/' + (admin as any).identificacion_anverso
      : null;
    this.srcIdentificacionReverso = (admin as any).identificacion_reverso
      ? environment.urlBackendUsuariosFiles + admin.id_usuario + '/' + (admin as any).identificacion_reverso
      : null;

    // Cargar miembros del comité si es interno comité
    const adm = this.Administrador as any;
    if (adm && adm.id_administrador_interno && adm.estructura_administracion === 'COMITE' && adm.fk_id_condominio) {
      this.miembrosComiteDetalle = await this.administradoresService.MiembrosComite(adm.fk_id_condominio).toPromise().then((r: any) => r['miembros'] || []).catch(() => []);
    } else {
      this.miembrosComiteDetalle = [];
    }
    this.mostrarDialogoDetallesAdministrador = true;
  }
}
