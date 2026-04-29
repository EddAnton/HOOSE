import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { map } from 'rxjs/operators';
import { environment } from '../../environments/environment';
import { SesionUsuarioService } from './sesion-usuario.service';
import { CuotaMantenimientoMasivaModel, CuotaMantenimientoModel } from '../models/cuota-mantenimiento.model';

@Injectable({
	providedIn: 'root',
})
export class CuotasMantenimientoService {
	constructor(private http: HttpClient, private sesionUsuarioService: SesionUsuarioService) {}

	Listar(soloActivos: boolean = false) {
		const url = 'cuotas-mantenimiento' + (soloActivos ? '/activos' : '');
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

	ListarCuotaMantenimiento(idCuotaMantenimiento: number = 0) {
		const url = 'cuotas-mantenimiento/' + idCuotaMantenimiento;
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

	GeneracionMasiva(data: CuotaMantenimientoMasivaModel, soloTotal: boolean = true) {
		let url = soloTotal
			? 'cuotas-mantenimiento/total-para-generacion-masiva'
			: 'cuotas-mantenimiento/generacion-masiva';

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

	Guardar(data: CuotaMantenimientoModel) {
		let url =
			'cuotas-mantenimiento/' +
			(data.id_cuota_mantenimiento == 0 ? 'insertar' : 'actualizar/' + data.id_cuota_mantenimiento);

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

	RegistrarPago(idCuotaMantenimiento: number = 0, data: any = null) {
		let url = 'cuotas-mantenimiento/registrar-pago/' + idCuotaMantenimiento;

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

	ListarReciboPago(idCuotaMantenimiento: number = 0) {
		const url = 'cuotas-mantenimiento/recibo-pago/' + idCuotaMantenimiento;
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

	Eliminar(idCuotaMantenimiento: number = 0) {
		const url = 'cuotas-mantenimiento/eliminar/' + idCuotaMantenimiento;
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

	EliminarPago(idCuotaMantenimientoPago: number = 0) {
		const url = 'cuotas-mantenimiento/eliminar-pago/' + idCuotaMantenimientoPago;
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
