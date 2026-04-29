import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { map } from 'rxjs/operators';
import { environment } from '../../environments/environment';
import { SesionUsuarioService } from './sesion-usuario.service';

@Injectable({
	providedIn: 'root',
})
export class PropositoGeneralService {
	constructor(private http: HttpClient, private sesionUsuarioService: SesionUsuarioService) {}

	LoginImagenes(soloActivos: boolean = false) {
		const url = 'proposito-general/login-imagenes';
		const headers = new HttpHeaders({
			'X-API-KEY': environment.appKey,
		});

		return this.http.get(environment.urlBackend + `${url}`, { headers }).pipe(
			map((respuesta) => {
				return respuesta;
			}),
		);
	}

	IdCondominioDefecto() {
		const url = 'proposito-general/condominio-default';
		const headers = new HttpHeaders({
			'X-API-KEY': environment.appKey,
			Authorization: this.sesionUsuarioService.obtenerToken(),
		});

		return this.http.get(environment.urlBackend + `${url}`, { headers }).pipe(
			map((respuesta) => {
				return respuesta;
			}),
		);
	}
}
