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
		private sesionUsuarioService: SesionUsuarioService,
		private cryptoService: CryptoService,
		private usuariosService: UsuariosService,
		private condominiosService: CondominiosService,
		private formBuilder: FormBuilder,
    private sidebarService: SidebarService,
	) {
		this.location = location;
		this.sidebarVisible = false;
	}

	ngOnInit() {
    if (window.innerWidth >= 991) { this.sidebarOpen(); } else { this.hideSidebar(); this.sidebarVisible = false; }
		this.listTitles = mnuOpciones.filter((listTitle) => listTitle && listTitle.visible);
		this.router.events.subscribe((event) => {
			if (this.sidebarVisible) this.sidebarClose();
		});
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
		return this.sesionUsuarioService.leerUsuario().imagen_archivo;
	}
	getNombreUsuario() {
		return this.sesionUsuarioService.leerUsuario().nombre;
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
		return Number(this.sesionUsuarioService.leerUsuario().perfil_usuario);
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

	onMostrarDetallesUsuario() {
		this.srcImagenPerfilUsuario = this.getImagenPerfilUsuario()
			? this.appData.urlBackend + this.getImagenPerfilUsuario()
			: null;
		this.mostrarDialogoDetallesUsuario = true;
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
}
