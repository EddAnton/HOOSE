import { Component, Input, OnInit } from '@angular/core';
import { SesionUsuarioService } from '../../../../services/sesion-usuario.service';

@Component({
	selector: 'app-dashboard-card',
	templateUrl: './dashboard-card.component.html',
	styleUrls: ['./dashboard-card.component.css'],
})
export class DashboardCardComponent implements OnInit {
	@Input() urlLogo: string = null;
	@Input() titleMessage: string = null;
	@Input() subtitleMessage: string = null;
	@Input() contentMessage: string = null;
	@Input() footerPath: string = null;

	idCondominio: number = 0;

	constructor(private sesionUsuarioService: SesionUsuarioService) {}

	ngOnInit() {
		this.idCondominio = this.sesionUsuarioService.obtenerIDCondominioUsuario();
	}
}
