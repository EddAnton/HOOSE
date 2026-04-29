import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { map } from 'rxjs/operators';
import { environment } from '../../environments/environment';
import { AvisoModel } from '../models/tablero-avisos.model';
import { SesionUsuarioService } from './sesion-usuario.service';

@Injectable({
	providedIn: 'root',
})
export class TableroAvisosService {
	constructor(private http: HttpClient, private sesionUsuarioService: SesionUsuarioService) {}

	Listar(idPerfil: number = 0, tipoActivos: number = 0) {
		let urlComplementario = '';
		switch (tipoActivos) {
			case 1:
				urlComplementario = '/activos';
				break;
			case 2:
				urlComplementario = '/publicados';
				break;
			default:
				urlComplementario = '';
				break;
		}
		const url = 'tablero-avisos/perfil/' + idPerfil.toString() + urlComplementario;
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

	ListarActivos(idPerfil: number = 0, publicados = false) {
		const tipoActivos = !publicados ? 1 : 2;
		return this.Listar(idPerfil, tipoActivos);
	}

	ListarAviso(idAviso: number = 0) {
		const url = 'tablero-avisos/' + idAviso;
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

	Guardar(data: AvisoModel) {
		let url = 'tablero-avisos/' + (data.id_aviso == 0 ? 'insertar' : 'actualizar/' + data.id_aviso);

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

	AlternarEstatusPublicado(idAviso: number = 0) {
		const url = 'tablero-avisos/alternar-estatus-publicado/' + idAviso;
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

	Eliminar(idAviso: number = 0) {
		const url = 'tablero-avisos/eliminar/' + idAviso;
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
