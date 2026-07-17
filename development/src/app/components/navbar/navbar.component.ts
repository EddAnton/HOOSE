import { PropositoGeneralService } from "../../services/proposito-general.service";
import { UsuariosPropietariosService } from "../../services/usuarios-propietarios.service";
import { UsuariosCondominosService } from "../../services/usuarios-condominos.service";
import { UsuariosColaboradoresService } from "../../services/usuarios-colaboradores.service";
import { UsuariosAdministradoresService } from "../../services/usuarios-administradores.service";
import { Component, ElementRef, HostListener, OnInit, Renderer2, ViewChild } from '@angular/core';
import { Location } from '@angular/common';
import { Router } from '@angular/router';

import * as hlpSwal from '../../helpers/sweetalert2-helper';
import * as hlpApp from '../../helpers/app-helper';

import { environment } from '../../../environments/environment';
import { mnuOpciones } from '../sidebar/sidebar.component';
import { CondominioResumenModel } from '../../models/condominio.model';
import { CondominiosService } from '../../services/condominios.service';
import { SidebarService } from '../../services/sidebar.service';
import { SesionUsuarioService } from '../../services/sesion-usuario.service';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';
import { UsuarioCambiarContraseniaModel } from '../../models/usuario.model';
import { UsuariosService } from '../../services/usuarios.service';
import { CryptoService } from '../../services/crypto.service';

@Component({
	selector: 'app-navbar',
	templateUrl: './navbar.component.html',
	styleUrls: ['./navbar.component.css'],
})
export class NavbarComponent implements OnInit {
	appData = environment;
	hlpApp = hlpApp;
	private listTitles: any[];
	public sidebarVisible: boolean = false;

	location: Location;
	isCollapsed = true;

	Condominios: CondominioResumenModel[] = [];
	mostrarDialogoDetallesUsuario: boolean = false;
datosPerfilUsuario: any = null;
cargandoPerfil: boolean = false;
modoEdicionPerfil: boolean = false;
frmPerfil: FormGroup = null;
guardandoPerfil: boolean = false;
archivoImagenPerfil: File = null;
	srcImagenPerfilUsuario: string = null;
	mostrarDialogoCambiarContrasenia: boolean = false;
	frmCambiarContrasenia: FormGroup;
	mostrarDialogoSeleccionCondominio: boolean = false;
	frmSeleccionarCondominio: FormGroup;

	@ViewChild('btnToggleSidebar', { static: true }) btnToggleSidebar: ElementRef;

	@HostListener('window:resize', ['$event'])
	onResize() {
		if (window.innerWidth >= 991) {
			if (this.sidebarVisible) {
				this.sidebarVisible = true;
				this.setIconsClosedSidebar();
			} else {
				this.showSidebar();
			}
		} else if (window.innerWidth < 991) {
			if (!this.sidebarVisible) {
				this.setIconsClosedSidebar();
				this.hideSidebar();
			}
		}
	}

	// constructor(location: Location, private renderer: Renderer2, private element: ElementRef, private router: Router) {
	constructor(
		location: Location,
		private router: Router,
		public sesionUsuarioService: SesionUsuarioService,
		private cryptoService: CryptoService,
		private usuariosService: UsuariosService,
		private condominiosService: CondominiosService,
		private formBuilder: FormBuilder,
    private sidebarService: SidebarService,
private propositoGeneralService: PropositoGeneralService,
private propietariosService: UsuariosPropietariosService,
private condominosService: UsuariosCondominosService,
private colaboradoresService: UsuariosColaboradoresService,
private administradoresService: UsuariosAdministradoresService,
	) {
		this.location = location;
		this.sidebarVisible = false;
	}

	ngOnInit() {
    if (window.innerWidth >= 991) { this.sidebarOpen(); } else { this.hideSidebar(); this.sidebarVisible = false; }
		this.listTitles = mnuOpciones.filter((listTitle) => listTitle && listTitle.visible);
this.srcImagenPerfilUsuario = this.getImagenPerfilUsuario();
    this.sidebarService.visible$.subscribe(v => { this.sidebarVisible = v; });
		this.router.events.subscribe((event) => {
			if (this.sidebarVisible) this.sidebarClose();
		});
		// Forzar cambio de contraseña si es primer ingreso
		setTimeout(() => {
			if (this.getDebeCambiarContraseniaUsuario()) {
				this.onCambiarContrasenia();
			}
		}, 500);
	}

	getTitle() {
		var titlee = this.location.prepareExternalUrl(this.location.path());
		if (titlee.charAt(0) === '#') {
			titlee = titlee.slice(1);
		}
		for (var item = 0; item < this.listTitles.length; item++) {
			if (this.listTitles[item].path === titlee) {
				return this.listTitles[item].title;
			}
		}
		return '';
	}

	getImagenPerfilUsuario() {
		const u = this.sesionUsuarioService.leerUsuario();
if (u.imagen_archivo) {
if (u.imagen_archivo.startsWith('uploads/')) return this.appData.urlBackend.replace('index.php/', '') + u.imagen_archivo;
return this.appData.urlBackendUsuariosFiles + u.id_usuario + '/' + u.imagen_archivo;
}
return null;
	}
	getNombreUsuario() {
		const u = this.sesionUsuarioService.leerUsuario(); return (u.nombre || "") + (u.apellidos ? " " + u.apellidos : "");
	}
	getEmailUsuario() {
		return this.sesionUsuarioService.leerUsuario().email;
	}
	getTelefonoUsuario() {
		return this.sesionUsuarioService.leerUsuario().telefono || '';
	}
	getDebeCambiarContraseniaUsuario(): boolean {
		return this.sesionUsuarioService.leerUsuario().debe_cambiar_contrasenia == 1;
	}

	getCondominioUsuario() {
		return this.sesionUsuarioService.leerUsuario().condominio_usuario;
	}

	getIDPerfilUsuario() {
		return Number(this.sesionUsuarioService.leerUsuario().id_perfil_usuario);
	}
	getPerfilUsuario() {
		return this.sesionUsuarioService.leerUsuario().perfil_usuario;
	}

	showSidebar() {
		const contentContainer = <HTMLElement>document.getElementsByClassName('content-container')[0];
		const sidebarSection = <HTMLElement>document.getElementsByClassName('sidebar-section')[0];
		sidebarSection.style.width = '250px';
		sidebarSection.style.minWidth = '250px';
		sidebarSection.style.display = 'block';

		/*
		contentContainer.style.width = 'calc(100% - 315px)';
    */
	}

	hideSidebar() {
		const contentContainer = <HTMLElement>document.getElementsByClassName('content-container')[0];
		const sidebarSection = <HTMLElement>document.getElementsByClassName('sidebar-section')[0];
		sidebarSection.style.width = '0';
		sidebarSection.style.minWidth = '0';
		sidebarSection.style.display = 'none';
		// sidebarSection.style.marginLeft
		// contentContainer.style.width = 'calc(100% - 15px)';
	}

	setIconsClosedSidebar() {
    // display manejado por CSS

		this.btnToggleSidebar.nativeElement.classList.remove('bx-left-arrow-circle');
		this.btnToggleSidebar.nativeElement.classList.remove('animate__fadeInLeft');
		this.btnToggleSidebar.nativeElement.classList.add('bx-menu');
    // marginLeft removido
		setTimeout(() => {
			this.btnToggleSidebar.nativeElement.style.display = 'block';
			this.btnToggleSidebar.nativeElement.classList.add('animate__fadeIn');
		}, 100);
	}

	sidebarToggle() {
		if (this.sidebarVisible === false) {
			this.sidebarOpen();
		} else {
			this.sidebarClose();
		}
	}

	sidebarOpen() {
    // display manejado por CSS
		this.showSidebar();
		this.btnToggleSidebar.nativeElement.classList.remove('bx-menu');
		this.btnToggleSidebar.nativeElement.classList.remove('animate__fadeIn');
		this.btnToggleSidebar.nativeElement.classList.add('bx-left-arrow-circle');
    // marginLeft removido
		setTimeout(() => {
			this.btnToggleSidebar.nativeElement.style.display = 'block';
			this.btnToggleSidebar.nativeElement.classList.add('animate__fadeInLeft');
		}, 200);

		this.sidebarVisible = true;
    this.sidebarService.setVisible(true);
	}

	sidebarClose() {
		this.hideSidebar();
		this.setIconsClosedSidebar();
		this.sidebarVisible = false;
    this.sidebarService.setVisible(false);
	}

	collapse() {
		this.isCollapsed = !this.isCollapsed;
		const navbar = document.getElementsByTagName('nav')[0];
		// const sectionNavbar = document.querySelector<HTMLElement>('.navbar-section');
		if (!this.isCollapsed) {
			navbar.classList.remove('navbar-transparent');
			navbar.classList.add('bg-white');
			// sectionNavbar.style.zIndex = '99999';
		} else {
			navbar.classList.add('navbar-transparent');
			navbar.classList.remove('bg-white');
			// sectionNavbar.style.zIndex = 'auto';
		}
	}

	async onSeleccionarCondominio() {
		this.Condominios = [];

		hlpSwal.Cargando();

		await this.condominiosService
			.ListarActivos()
			.toPromise()
			.then((r) => {
				this.Condominios = r['condominios'];
			})
			.catch(async (e) => {
				await hlpSwal.Error(e);
			})
			.finally(() => {
				hlpSwal.Cerrar();
			});

		if (this.Condominios.length < 1) {
			hlpSwal.Advertencia('No existen condominios registrados.');
			return;
		}

		this.frmSeleccionarCondominio = new FormGroup({
			id_condominio: new FormControl(this.sesionUsuarioService.leerUsuario().id_condominio_usuario, [
				Validators.required,
				Validators.min(1),
			]),
		});
		this.frmSeleccionarCondominio.updateValueAndValidity();
    const condActualId = this.sesionUsuarioService.leerUsuario().id_condominio_usuario;
    const idxActual = this.Condominios.findIndex(c => c.id_condominio == condActualId);
    this.idxCondominioSeleccionado = idxActual >= 0 ? idxActual : 0;
		this.mostrarDialogoSeleccionCondominio = true;
	}

	onCondominioSeleccionado() {
		let condominio = this.frmSeleccionarCondominio.value;

		hlpSwal.Cargando();

		this.sesionUsuarioService
			.seleccionarCondominio(condominio)
			.toPromise()
			.then(async (r) => {
				this.mostrarDialogoSeleccionCondominio = false;
				this.sesionUsuarioService.recargar();
			})
			.catch(async (e) => {
				await hlpSwal.Error(e);
			})
			.finally(() => {
				hlpSwal.Cerrar();
			});
	}

	onCancelarSeleccionarCondominio() {
		this.mostrarDialogoSeleccionCondominio = false;
	}

async onMostrarDetallesUsuario() {
this.srcImagenPerfilUsuario = this.getImagenPerfilUsuario();
this.datosPerfilUsuario = null;
this.cargandoPerfil = true;
this.mostrarDialogoDetallesUsuario = true;
try {
const idUsuario = this.sesionUsuarioService.leerUsuario().id_usuario;
const perfil = this.getIDPerfilUsuario();
let r: any = null;
if (perfil === 4) r = await this.propietariosService.ListarPropietario(idUsuario).toPromise();
else if (perfil === 5) r = await this.condominosService.ListarCondomino(idUsuario).toPromise();
else if (perfil === 3) r = await this.colaboradoresService.ListarColaborador(idUsuario).toPromise();
else if (perfil === 2) r = await this.administradoresService.ListarAdministrador(idUsuario).toPromise();
if (r) {
const key = Object.keys(r).find((k:string) => k !== 'error' && k !== 'err' && k !== 'msg');
this.datosPerfilUsuario = key ? r[key] : null;
const imgField = this.datosPerfilUsuario?.imagen_archivo || this.datosPerfilUsuario?.imagen;
if (imgField) {
const imgUrl = imgField.startsWith('uploads/') ? this.appData.urlBackend.replace('index.php/', '') + imgField : this.appData.urlBackendUsuariosFiles + idUsuario + '/' + imgField;
this.srcImagenPerfilUsuario = imgUrl;
}
}
} catch(e) {} finally { this.cargandoPerfil = false; }
}

	onCambiarContrasenia() {
		try {
			this.frmCambiarContrasenia = this.formBuilder.group(new UsuarioCambiarContraseniaModel());
			this.frmCambiarContrasenia
				.get('contrasenia_actual')
				.setValidators([Validators.required, Validators.minLength(3), Validators.maxLength(20)]);
			this.frmCambiarContrasenia
				.get('contrasenia_nueva')
				.setValidators([Validators.required, Validators.minLength(3), Validators.maxLength(20)]);
			this.frmCambiarContrasenia
				.get('contrasenia_nueva_confirmada')
				.setValidators([Validators.required, Validators.minLength(3), Validators.maxLength(20)]);

			this.frmCambiarContrasenia.updateValueAndValidity();

			this.mostrarDialogoCambiarContrasenia = true;
			// this.mostrarDialogoDetallesUsuario = false;
		} catch (e) {
			hlpSwal.Error(e);
		}
	}

	onCambiarContraseniaGuardar() {
		if (!this.frmCambiarContrasenia.valid) {
			this.frmCambiarContrasenia.markAllAsTouched();
			hlpSwal.Error('Se detectaron errores en la información solicitada.');
			return;
		}

		let contrasenias = {
			contrasenia_actual: this.cryptoService.set(this.frmCambiarContrasenia.get('contrasenia_actual').value),
			contrasenia_nueva: this.cryptoService.set(this.frmCambiarContrasenia.get('contrasenia_nueva').value),
		};

		hlpSwal
			.Pregunta({
				html: '¿Confirmas cambiar la contraseña?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.usuariosService.CambiarContrasenia(contrasenias).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ error: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.error) {
					// hlpSwal.Exito('Contraseña modificada con éxito');
					if (this.getDebeCambiarContraseniaUsuario()) {
						let usuario = this.sesionUsuarioService.leerUsuario();
						usuario.debe_cambiar_contrasenia = 0;
						this.sesionUsuarioService.guardarUsuario(usuario);
					}
					hlpSwal.Exito(r.value.msg);
					this.mostrarDialogoCambiarContrasenia = false;
				}
			});
	}

	onCambiarContraseniaCancelar() {
		if (!this.getDebeCambiarContraseniaUsuario()) {
			hlpSwal
				.Pregunta({
					html: 'Para continuar debe cambiar la contraseña.<p>¿Deseas cancelar este proceso?',
				})
				.then((r) => {
					if (r.isConfirmed) {
						this.sesionUsuarioService.cerrarSesion();
					}
				});
		} else {
			this.mostrarDialogoCambiarContrasenia = false;
		}
	}

	onSalir() {
		hlpSwal.Pregunta('¿Ya te vas?').then(async (r) => {
			if (r.isConfirmed) {
				// await hlpSwal.Info('Adios :(').then(() => this.sesionUsuarioService.cerrarSesion());
				this.sesionUsuarioService.cerrarSesion();
			}
		});
	}

  idxCondominioSeleccionado: number = 0;

  getItemStyleNav(i: number): any {
    const n = this.Condominios.length;
    let diff = i - this.idxCondominioSeleccionado;
    if (diff > n / 2) diff -= n;
    if (diff < -n / 2) diff += n;
    if (Math.abs(diff) > 2) return { display: 'none' };
    const configs: any = {
      '-2': { translateX: -380, translateY: 50, rotateY: 25, scale: 0.45, opacity: 0.2, zIndex: 1 },
      '-1': { translateX: -200, translateY: 25, rotateY: 15, scale: 0.65, opacity: 0.55, zIndex: 5 },
       '0': { translateX: 0,    translateY: 0,  rotateY: 0,  scale: 1,   opacity: 1,   zIndex: 10 },
       '1': { translateX: 200,  translateY: 25, rotateY: -15, scale: 0.65, opacity: 0.55, zIndex: 5 },
       '2': { translateX: 380,  translateY: 50, rotateY: -25, scale: 0.45, opacity: 0.2, zIndex: 1 },
    };
    const cfg = configs[diff.toString()];
    const size = diff === 0 ? 240 : Math.abs(diff) === 1 ? 140 : 100;
    return {
      transform: `translateX(${cfg.translateX}px) translateY(${cfg.translateY}px) rotateY(${cfg.rotateY}deg) scale(${cfg.scale})`,
      opacity: cfg.opacity, zIndex: cfg.zIndex, display: 'flex',
      marginLeft: (-size / 2) + 'px',
      pointerEvents: diff === 0 ? 'none' : 'all',
      cursor: diff === 0 ? 'default' : 'pointer',
    };
  }

  getImgStyleNav(i: number): any {
    const n = this.Condominios.length;
    let diff = i - this.idxCondominioSeleccionado;
    if (diff > n / 2) diff -= n;
    if (diff < -n / 2) diff += n;
    const size = diff === 0 ? 240 : Math.abs(diff) === 1 ? 140 : 100;
    const brightness = diff === 0 ? 1 : Math.abs(diff) === 1 ? 0.55 : 0.3;
    return {
      width: size + 'px', height: size + 'px', objectFit: 'contain',
      filter: `brightness(${brightness}) drop-shadow(0 10px 20px rgba(0,0,0,0.5))`,
      transition: 'all 0.4s cubic-bezier(0.25,0.46,0.45,0.94)',
    };
  }

  isVisibleNav(i: number): boolean {
    const n = this.Condominios.length;
    const visibles = [-2,-1,0,1,2].map(o => ((this.idxCondominioSeleccionado + o) % n + n) % n);
    return visibles.includes(i);
  }

  onAnteriorNav() {
    const n = this.Condominios.length;
    this.idxCondominioSeleccionado = (this.idxCondominioSeleccionado - 1 + n) % n;
    this.frmSeleccionarCondominio.get('id_condominio').setValue(this.Condominios[this.idxCondominioSeleccionado].id_condominio);
  }

  onSiguienteNav() {
    const n = this.Condominios.length;
    this.idxCondominioSeleccionado = (this.idxCondominioSeleccionado + 1) % n;
    this.frmSeleccionarCondominio.get('id_condominio').setValue(this.Condominios[this.idxCondominioSeleccionado].id_condominio);
  }

  onSeleccionarCondominioCarrusel(i: number) {
    this.idxCondominioSeleccionado = i;
    this.frmSeleccionarCondominio.get('id_condominio').setValue(this.Condominios[i].id_condominio);
  }

  getImagenUrlNav(c: any): string {
    if (!c?.imagen) return './assets/img/imagen_no_disponible.png';
    return 'http://api.residenciales.hoose.mx/uploads/condominios/' + c.id_condominio + '/' + c.imagen;
  }
onActivarEdicionPerfil() {
const d = this.datosPerfilUsuario || {};
const u = this.sesionUsuarioService.leerUsuario();
const apellidos = d.apellidos || u.apellidos || '';
const nombre = d.nombre || u.nombre || '';
this.frmPerfil = this.formBuilder.group({
nombre: [nombre],
apellidos: [apellidos],
email: [d.email || u.email || ''],
telefono: [d.telefono || u.telefono || ''],
domicilio: [d.domicilio || ''],
});
this.modoEdicionPerfil = true;
console.log('Perfil edicion:', { nombre, apellidos, d, u });
}

onCancelarEdicionPerfil() {
this.modoEdicionPerfil = false;
this.archivoImagenPerfil = null;
}

onImagenPerfilSeleccionada(event: any) {
const file = event.target.files?.[0];
if (!file) return;
this.archivoImagenPerfil = file;
const reader = new FileReader();
reader.onload = (e: any) => { this.srcImagenPerfilUsuario = e.target.result; };
reader.readAsDataURL(file);
}

async onGuardarPerfil() {
if (!this.frmPerfil?.valid) return;
this.guardandoPerfil = true;
try {
const idUsuario = this.sesionUsuarioService.leerUsuario().id_usuario;
const perfil = this.getIDPerfilUsuario();
const vals = this.frmPerfil.value;
let obs: any = null;
const payload: any = { ...this.datosPerfilUsuario, ...vals, id_usuario: idUsuario };
if (this.archivoImagenPerfil) payload.archivo_imagen = this.archivoImagenPerfil;
if (perfil === 1) obs = this.usuariosService.ActualizarPerfil(idUsuario, payload as any);
else if (perfil === 4) obs = this.propietariosService.Guardar(payload as any);
else if (perfil === 5) obs = this.condominosService.Guardar(payload as any);
else if (perfil === 3) obs = this.colaboradoresService.Guardar(payload as any);
else if (perfil === 2) obs = this.administradoresService.Guardar(payload as any);
if (obs) {
const r: any = await obs.toPromise();
if (!r.error && !r.err) {
hlpSwal.ExitoToast('Perfil actualizado correctamente.');
this.modoEdicionPerfil = false;
this.datosPerfilUsuario = { ...this.datosPerfilUsuario, ...vals };
if (this.archivoImagenPerfil && r.imagen_archivo) { const u2 = this.sesionUsuarioService.leerUsuario(); u2.imagen_archivo = r.imagen_archivo; this.sesionUsuarioService.guardarUsuario(u2); this.srcImagenPerfilUsuario = this.getImagenPerfilUsuario(); }
const u = this.sesionUsuarioService.leerUsuario();
u.nombre = vals.nombre;
u.apellidos = vals.apellidos;
this.sesionUsuarioService.guardarUsuario(u);
} else { console.error('Guardar perfil error:', r); hlpSwal.Error(r.msg); }
} else { hlpSwal.ExitoToast('Perfil actualizado.'); this.modoEdicionPerfil = false; }
} catch(e) { hlpSwal.Error(e); } finally { this.guardandoPerfil = false; }
}

}