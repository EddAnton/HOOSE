import { Component } from '@angular/core';
import { SesionUsuarioService } from '../../services/sesion-usuario.service';

@Component({
	selector: 'app-page-not-found',
	templateUrl: './page-not-found.component.html',
	styleUrls: ['./page-not-found.component.css'],
})
export class PageNotFoundComponent {
	constructor(private sesionUsuarioService: SesionUsuarioService) {}

	onGoToHome() {
		this.sesionUsuarioService.redireccionar();
	}
}
