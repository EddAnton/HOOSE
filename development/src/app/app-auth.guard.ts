import { Injectable } from '@angular/core';
import { ActivatedRouteSnapshot, CanActivate, Router } from '@angular/router';

import * as Swal from './helpers/sweetalert2-helper';
import { SesionUsuarioService } from './services/sesion-usuario.service';

@Injectable({
	providedIn: 'root',
})
export class AppAuthGuard implements CanActivate {
	routesNotToValidate: string[] = ['', 'tablero', 'catalogos', 'condominios', 'tipos-miembros', 'tareas'];

	constructor(private router: Router, private sesionUsuarioService: SesionUsuarioService) {}

	canActivate(route: ActivatedRouteSnapshot): boolean {
		if (
			!this.routesNotToValidate.includes(route.routeConfig.path) &&
			!this.sesionUsuarioService.condominioSeleccionado()
    ) {
			this.sesionUsuarioService.redireccionar('/tablero');
      Swal.Error('Debe seleccionar un condominio.');
			return false;
		}
		const canActivate = this.sesionUsuarioService.estaAutenticado(route.data);

		if (!canActivate) {
			// Swal.Error('Acceso denegado.');
			this.router.navigateByUrl('/inicio-sesion');
		}

		return canActivate;
	}

	canActivateChild(route: ActivatedRouteSnapshot): boolean {
		/* if (!['condominios'].includes(route.routeConfig.path) && !this.sesionUsuarioService.condominioSeleccionado()) {
			Swal.Error('Debe seleccionar un condominio.');
			// this.sesionUsuarioService.redireccionar('/tablero');
			// this.sesionUsuarioService.redireccionar();
			return false;
		} */
		return this.canActivate(route);
	}
}
