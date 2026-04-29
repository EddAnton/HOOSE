import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { map } from 'rxjs/operators';
import { environment } from '../../environments/environment';
import { EdificioModel } from '../models/edificio.model';
import { SesionUsuarioService } from './sesion-usuario.service';

@Injectable({
	providedIn: 'root',
})
export class EdificiosService {
	constructor(private http: HttpClient, private sesionUsuarioService: SesionUsuarioService) {}

	Listar(soloActivos: boolean = false) {
		const url = 'edificios' + (soloActivos ? '/activos' : '');
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

	ListarEdificio(idEdificio: number = 0) {
		const url = 'edificios/' + idEdificio;
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

	Guardar(data: EdificioModel) {
		let url = 'edificios/' + (data.id_edificio == 0 ? 'insertar' : 'actualizar/' + data.id_edificio);

		const headers = new HttpHeaders({
			'X-API-KEY': environment.appKey,
			Authorization: this.sesionUsuarioService.obtenerToken(),
		});

		/* const params: any = new FormData();
		for (var [key, value] of Object.entries(data)) {
			if (value != null) {
				params.append(key, value);
			}
		} */
		const params = Object.assign({}, data);

		return this.http.post(environment.urlBackend + `${url}`, params, { headers }).pipe(
			map((respuesta) => {
				return respuesta;
			}),
		);
	}

	AlternarEstatus(idEdificio: number = 0) {
		const url = 'edificios/alternar-estatus/' + idEdificio;
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

	Deshabilitar(idEdificio: number = 0) {
		const url = 'edificios/deshabilitar/' + idEdificio;
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
