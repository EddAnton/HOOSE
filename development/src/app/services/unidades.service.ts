import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { map } from 'rxjs/operators';
import { environment } from '../../environments/environment';
import { UnidadModel } from '../models/unidad.model';
import { SesionUsuarioService } from './sesion-usuario.service';

@Injectable({
  providedIn: 'root',
})
export class UnidadesService {
  constructor(private http: HttpClient, private sesionUsuarioService: SesionUsuarioService) { }

  Listar(soloActivos: boolean = false) {
    const url = 'unidades' + (soloActivos ? '/activos' : '');
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

  ListarParaRecaudaciones() {
    const url = 'unidades/para-recaudaciones';
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

  ListarParaVisita() {
    const url = 'unidades/para-visita';
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

  ListarUnidad(idUnidad: number = 0) {
    const url = 'unidades/' + idUnidad;
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

  Guardar(data: UnidadModel) {
    let url = 'unidades/' + (data.id_unidad == 0 ? 'insertar' : 'actualizar/' + data.id_unidad);

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
    // const params = Object.assign({}, data);

    return this.http.post(environment.urlBackend + `${url}`, params, { headers }).pipe(
      map((respuesta) => {
        return respuesta;
      }),
    );
  }

  AlternarEstatus(idUnidad: number = 0) {
    const url = 'unidades/alternar-estatus/' + idUnidad;
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

  Deshabilitar(idUnidad: number = 0) {
    const url = 'unidades/deshabilitar/' + idUnidad;
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

  ListarUnidadesSinPropietario() {
    const url = 'unidades/sin-propietario';
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

  ListarUnidadesDisponiblesRenta() {
    const url = 'unidades/disponibles-renta';
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

  ListarUnidadesActaAsambleas() {
    const url = 'unidades/para-acta-asamblea';
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
}
