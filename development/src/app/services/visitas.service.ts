import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { map } from 'rxjs/operators';
import { environment } from '../../environments/environment';
import { VisitaModel, VisitaRegistrarSalidaModel } from '../models/visita.model';
import { SesionUsuarioService } from './sesion-usuario.service';

@Injectable({
	providedIn: 'root',
})
export class VisitasService {
	constructor(private http: HttpClient, private sesionUsuarioService: SesionUsuarioService) {}

	Listar(soloActivos: boolean = false) {
		const url = 'visitas' + (soloActivos ? '/activos' : '');
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

	ListarActivos() {
		return this.Listar(true);
	}

	ListarVisita(idVisita: number = 0) {
		const url = 'visitas/' + idVisita;
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

	Guardar(data: VisitaModel) {
		let url = 'visitas/' + (data.id_visita == 0 ? 'insertar' : 'actualizar/' + data.id_visita);

		const headers = new HttpHeaders({
			'X-API-KEY': environment.appKey,
			Authorization: this.sesionUsuarioService.obtenerToken(),
		});

		const params = Object.assign({}, data);

		return this.http.post(environment.urlBackend + `${url}`, params, { headers }).pipe(
			map((respuesta) => {
				return respuesta;
			}),
		);
	}

	RegistrarSalida(idVisita: number, data: VisitaRegistrarSalidaModel) {
		let url = 'visitas/registrar-salida/' + idVisita;

		const headers = new HttpHeaders({
			'X-API-KEY': environment.appKey,
			Authorization: this.sesionUsuarioService.obtenerToken(),
		});

		const params = Object.assign({}, data);

		return this.http.post(environment.urlBackend + `${url}`, params, { headers }).pipe(
			map((respuesta) => {
				return respuesta;
			}),
		);
	}

	Eliminar(idVisita: number = 0) {
		const url = 'visitas/eliminar/' + idVisita;
		const headers = new HttpHeaders({
			'X-API-KEY': environment.appKey,
			Authorization: this.sesionUsuarioService.obtenerToken(),
		});

		return this.http.post(environment.urlBackend + `${url}`, null, { headers }).pipe(
			map((respuesta) => {
				return respuesta;
			}),
		);
	}
}
