import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { map } from 'rxjs/operators';
import { environment } from '../../environments/environment';
import { SesionUsuarioService } from './sesion-usuario.service';
import { FondoMonetarioModel } from '../models/fondo-monetario.model';

@Injectable({
  providedIn: 'root',
})
export class FondosMonetariosService {
  constructor(private http: HttpClient, private sesionUsuarioService: SesionUsuarioService) { }

  Listar(soloActivos: boolean = false) {
    const url = 'fondos-monetarios' + (soloActivos ? '/activos' : '');
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

  ListarFondoMonetario(idFondoMonetario: number = 0) {
    const url = 'fondos-monetarios/' + idFondoMonetario;
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

  ListarMovimientosFondoMonetario(idFondoMonetario: number = 0) {
    const url = 'fondos-monetarios/movimientos/' + idFondoMonetario;
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

  Guardar(data: FondoMonetarioModel) {
    let url =
      'fondos-monetarios/' + (data.id_fondo_monetario == 0 ? 'insertar' : 'actualizar/' + data.id_fondo_monetario);

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

  Eliminar(idFondoMonetario: number = 0) {
    const url = 'fondos-monetarios/eliminar/' + idFondoMonetario;
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

  RegistrarTraspaso(idFondoMonetario: number = 0, data: any = null) {
    let url = 'fondos-monetarios/traspaso/' + idFondoMonetario;

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

  RegistrarMovimiento(idFondoMonetario: number = 0, data: any = null) {
    let url = 'fondos-monetarios/registrar-movimiento/' + idFondoMonetario;

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

  EliminarMovimiento(idFondoMonetarioMovimiento: number = 0) {
    const url = 'fondos-monetarios/eliminar-movimiento/' + idFondoMonetarioMovimiento;
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
