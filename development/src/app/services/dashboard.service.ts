import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { map } from 'rxjs/operators';
import { environment } from '../../environments/environment';
import { SesionUsuarioService } from './sesion-usuario.service';

@Injectable({
	providedIn: 'root',
})
export class DashboardService {
	constructor(private http: HttpClient, private sesionUsuarioService: SesionUsuarioService) {}

  Listar(data: FormData) {
		const url = 'dashboard';
		const headers = new HttpHeaders({
			'X-API-KEY': environment.appKey,
			Authorization: this.sesionUsuarioService.obtenerToken(),
		});

    return this.http.post(environment.urlBackend + `${url}`, data, { headers }).pipe(
			map((respuesta) => {
				return respuesta;
			}),
		);
	}
}
