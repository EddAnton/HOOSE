import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { map } from 'rxjs/operators';
import { environment } from '../../environments/environment';
import { SesionUsuarioService } from './sesion-usuario.service';
import { ProyectoModel } from '../models/proyecto.model';

@Injectable({
	providedIn: 'root',
})
export class ProyectosService {
	constructor(private http: HttpClient, private sesionUsuarioService: SesionUsuarioService) {}

	Listar(soloActivos: boolean = false) {
		const url = 'proyectos' + (soloActivos ? '/activos' : '');
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

	ListarProyecto(idProyecto: number = 0) {
		const url = 'proyectos/' + idProyecto;
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

	// Guardar(idProyecto: number = 0, data: FormData) {
	//	let url = 'proyectos/' + (idProyecto == 0 ? 'insertar' : 'actualizar/' + idProyecto);
	Guardar(data: ProyectoModel) {
		let url = 'proyectos/' + (data.id_proyecto == 0 ? 'insertar' : 'actualizar/' + data.id_proyecto);

		const headers = new HttpHeaders({
			'X-API-KEY': environment.appKey,
			Authorization: this.sesionUsuarioService.obtenerToken(),
		});

		const params: any = new FormData();
		for (var [key, value] of Object.entries(data)) {
			if (key != 'archivos_imagenes' && value != null) {
				params.append(key, value);
			}
		}
		for (var i = 0; i < data.archivos_imagenes.length; i++) {
			params.append('archivos_imagenes[]', data.archivos_imagenes[i]);
		}

		return this.http.post(environment.urlBackend + `${url}`, params, { headers }).pipe(
			// return this.http.post(environment.urlBackend + `${url}`, data, { headers }).pipe(
			map((respuesta) => {
				return respuesta;
			}),
		);
	}

	Eliminar(idProyecto: number = 0) {
		const url = 'proyectos/eliminar/' + idProyecto;
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
