import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { map } from 'rxjs/operators';
import { environment } from '../../environments/environment';
import { SesionUsuarioService } from './sesion-usuario.service';
import { TipoMiembroModel } from '../models/tipo-miembro.model';

@Injectable({
	providedIn: 'root',
})
export class TiposMiembrosService {
	constructor(private http: HttpClient, private sesionUsuarioService: SesionUsuarioService) {}

	Listar(soloActivos: boolean = false) {
		const url = 'tipos-miembros' + (soloActivos ? '/activos' : '');
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

	// Tipos Miembros Colaboradores
	ListarMiembrosColaboradores(soloActivos: boolean = false) {
		const url = 'tipos-miembros-colaboradores' + (soloActivos ? '/activos' : '');
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

	ListarMiembrosColaboradoresActivos() {
		return this.ListarMiembrosColaboradores(true);
	}

	// Tipos Miembros No Colaboradores
	ListarMiembrosComiteAdministracion(soloActivos: boolean = false) {
		const url = 'tipos-miembros-no-colaboradores' + (soloActivos ? '/activos' : '');
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

	ListarMiembrosComiteAdministracionActivos() {
		return this.ListarMiembrosComiteAdministracion(true);
	}

	ListarTipoMiembro(idTipoMiembro: number = 0) {
		const url = 'tipos-miembros/' + idTipoMiembro;
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

	Guardar(data: TipoMiembroModel) {
		let url = 'tipos-miembros/' + (data.id_tipo_miembro == 0 ? 'insertar' : 'actualizar/' + data.id_tipo_miembro);

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

	AlternarEstatus(idTipoMiembro: number = 0) {
		const url = 'tipos-miembros/alternar-estatus/' + idTipoMiembro;
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
