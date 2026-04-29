import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { map } from 'rxjs/operators';
import { environment } from '../../environments/environment';
import { SesionUsuarioService } from './sesion-usuario.service';

@Injectable({
	providedIn: 'root',
})
export class PonteCloudService {
	constructor(private http: HttpClient, private sesionUsuarioService: SesionUsuarioService) {}

	Listar(idCarpeta: number = 0) {
		const url = 'cloud' + (idCarpeta > 0 ? '/' + idCarpeta : '');
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

	CarpetaCrear(idCarpetaPadre: number = 0, data: any) {
		let url = 'cloud/carpeta/crear/' + idCarpetaPadre;

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

	CarpetaRenombrar(idCarpeta: number = 0, data: any) {
		let url = 'cloud/carpeta/renombrar/' + idCarpeta;

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

	CarpetaAlternarEstatus(idCarpeta: number = 0) {
		let url = 'cloud/carpeta/alternar-estatus/' + idCarpeta;
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

	ArchivoSubir(idCarpetaPadre: number = 0, data: any) {
		let url = 'cloud/archivo/subir/' + idCarpetaPadre;

		const headers = new HttpHeaders({
			'X-API-KEY': environment.appKey,
			Authorization: this.sesionUsuarioService.obtenerToken(),
		});

		const params: any = new FormData();
		for (var [key, value] of Object.entries(data)) {
			if (value != null) {
				params.append(key, value);
			}
		}

		return this.http.post(environment.urlBackend + `${url}`, params, { headers }).pipe(
			map((respuesta) => {
				return respuesta;
			}),
		);
	}

	ArchivoRenombrar(idArchivo: number = 0, data: any) {
		let url = 'cloud/archivo/renombrar/' + idArchivo;

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

	ArchivoAlternarEstatus(idArchivo: number = 0) {
		let url = 'cloud/archivo/alternar-estatus/' + idArchivo;
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
