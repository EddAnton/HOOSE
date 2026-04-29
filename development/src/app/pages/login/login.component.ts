import { Component, OnInit, isDevMode } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';

import * as hlpApp from '../../helpers/app-helper';
import * as hlpSwal from '../../helpers/sweetalert2-helper';
import { environment } from '../../../environments/environment';

import { LoginModel } from '../../models/sesion-usuario.model';
import { CryptoService } from '../../services/crypto.service';
import { SesionUsuarioService } from '../../services/sesion-usuario.service';
import { PropositoGeneralService } from '../../services/proposito-general.service';
import { filter } from 'rxjs/operators';

@Component({
	selector: 'app-login',
	templateUrl: './login.component.html',
	styleUrls: ['./login.component.css'],
})
export class LoginComponent implements OnInit {
	// loginData: LoginModel;
	hlpApp = hlpApp;
	appData = environment;
	frmLogin: FormGroup;
	imgBackground: string = null;
	imgLogo: string = null;

	constructor(
		private cryptoService: CryptoService,
		private formBuilder: FormBuilder,
		private sesionUsuarioService: SesionUsuarioService,
		private propositoGeneralService: PropositoGeneralService,
	) {
		this.sesionUsuarioService.borrarSesion();
	}

	ngOnInit(): void {
		hlpSwal.Cargando();
		this.imgBackground = null;
		this.imgLogo = null;

		this.propositoGeneralService
			.LoginImagenes()
			.toPromise()
			.then((r) => {
				const data = r['data'];
				const imgBackground =
					data.filter((d) => {
						if (d.opcion == 'login_background') return d.valor;
					})[0] || null;
				if (imgBackground != null) {
					this.imgBackground = environment.urlBackendImagesFiles + 'background/' + imgBackground.valor;
				}
				const imgLogo =
					data.filter((d) => {
						if (d.opcion == 'login_logo') return d.valor;
					})[0] || null;
				if (imgLogo != null) {
					this.imgLogo = environment.urlBackendImagesFiles + 'logos/' + imgLogo.valor;
				}
			})
			.catch(async (e) => {
				await hlpSwal.Error(e);
			})
			.finally(() => {
				hlpSwal.Cerrar();
			});

		this.onInicializarFormulario();
	}

	onInicializarFormulario() {
		try {
			this.frmLogin = this.formBuilder.group(new LoginModel());
			this.frmLogin.get('usuario').setValidators([Validators.required]);
			this.frmLogin.get('contrasenia').setValidators([Validators.required]);

			/* setTimeout(() => {
				this.frmLogin.updateValueAndValidity();
			}, 5000); */
		} catch (e) {
			hlpSwal.Error(e);
		}
	}

	onAceptar() {
		if (!this.frmLogin.valid) {
			this.frmLogin.markAllAsTouched();
			hlpSwal.Error('Se detectaron errores en la información solicitada.');
			return;
		}

		hlpSwal.Info('Espere por favor...');
		hlpSwal.mostrarCargando();

		let usuario = this.frmLogin.value;
		usuario.contrasenia = this.cryptoService.set(usuario.contrasenia);

		this.sesionUsuarioService
			.iniciarSesion(usuario)
			.toPromise()
			.then(async (r: any) => {
				if (!r.err && r.usuario) {
					this.sesionUsuarioService.guardarUsuario(r.usuario);

					if (isDevMode && this.sesionUsuarioService.obtenerIDPerfilUsuario() == 1) {
						await this.propositoGeneralService
							.IdCondominioDefecto()
							.toPromise()
							.then((r) => {
								const idCondominioDefecto = r['id_condominio'];
								if (idCondominioDefecto < 1) {
									return;
								}
								const condominio = {
									id_condominio: idCondominioDefecto,
								};

								this.sesionUsuarioService
									.seleccionarCondominio(condominio)
									.toPromise()
									.then(async (r) => {
										this.sesionUsuarioService.recargar();
									})
									.catch(async (e) => {
										await hlpSwal.Error(e);
									});
							})
							.catch(async (e) => {
								await hlpSwal.Error(e);
							})
							.finally(() => {
								hlpSwal.Cerrar();
							});
					}
					this.sesionUsuarioService.redireccionar();
					hlpSwal.Cerrar();
				}
			})
			.catch(async (e) => {
				await hlpSwal.Error(e);
			});
	}
}
