import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { map } from 'rxjs/operators';
import { environment } from '../../environments/environment';
import { SesionUsuarioService } from './sesion-usuario.service';
import {
	QuejaActualizarEstatusModel,
	QuejaAsignarColaboradorModel,
	QuejaModel,
	QuejaSeguimientoModel,
} from '../models/queja.model';

@Injectable({
	providedIn: 'root',
})
export class QuejasService {
	constructor(private http: HttpClient, private sesionUsuarioService: SesionUsuarioService) {}

	Listar(soloActivos: boolean = false) {
		const url = 'quejas' + (soloActivos ? '/activos' : '');
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

	ListarQueja(idQueja: number = 0) {
		const url = 'quejas/' + idQueja;
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

	ListarSeguimiento(idQueja: number = 0) {
		const url = 'quejas/seguimiento/' + idQueja;
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

	Guardar(data: QuejaModel) {
		let url = 'quejas/' + (data.id_queja == 0 ? 'insertar' : 'actualizar/' + data.id_queja);

		const headers = new HttpHeaders({
			'X-API-KEY': environment.appKey,
			Authorization: this.sesionUsuarioService.obtenerToken(),
		});

		// const params = Object.assign({}, data);
		const params: any = new FormData();
		for (var [key, value] of Object.entries(data)) {
			if (key != 'archivos' && value != null) {
				params.append(key, value);
			}
		}
		for (var i = 0; i < data.archivos.length; i++) {
			params.append('archivos[]', data.archivos[i]);
		}

		return this.http.post(environment.urlBackend + `${url}`, params, { headers }).pipe(
			map((respuesta) => {
				return respuesta;
			}),
		);
	}

	Eliminar(idQueja: number = 0) {
		const url = 'quejas/eliminar/' + idQueja;
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

	AsignarColaborador(data: QuejaAsignarColaboradorModel) {
		let url = 'quejas/asignar-colaborador/' + data.id_queja;

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

	ActualizarEstatus(data: QuejaActualizarEstatusModel) {
		let url = 'quejas/actualizar-estatus/' + data.id_queja;

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

	GuardarSeguimiento(data: QuejaSeguimientoModel) {
		let url =
			'quejas/seguimiento/' +
			(data.id_queja_seguimiento == 0 ? 'insertar/' + data.id_queja : 'actualizar/' + data.id_queja_seguimiento);

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

	EliminarSeguimiento(idSeguimiento: number = 0) {
		const url = 'quejas/seguimiento/eliminar/' + idSeguimiento;
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
