import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { map } from 'rxjs/operators';
import { environment } from '../../environments/environment';
import { ActaModel, ConvocatoriaModel } from '../models/asamblea.model';
import { SesionUsuarioService } from './sesion-usuario.service';

@Injectable({
  providedIn: 'root',
})
export class AsambleasService {
  constructor(private http: HttpClient, private sesionUsuarioService: SesionUsuarioService) { }

  Listar(soloActivos: boolean = false) {
    const url = 'asambleas' + (soloActivos ? '/activos' : '');
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

  ListarConvocatoria(idAsamblea: number = 0) {
    const url = 'asambleas/detalle/' + idAsamblea;
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

  ListarConvocatoriaDetalle(idAsamblea: number = 0) {
    const url = 'asambleas/detalle/' + idAsamblea;
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

  GuardarConvocatoria(data: ConvocatoriaModel) {
    let url = 'asambleas/' + (data.id_asamblea == 0 ? 'insertar' : 'actualizar/' + data.id_asamblea);

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

  EliminarConvocatoria(idAsamblea: number = 0) {
    const url = 'asambleas/eliminar/' + idAsamblea;
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

  ListarOrdenDiaConvocatoria(idAsamblea: number = 0) {
    const url = 'asambleas/orden-dia/' + idAsamblea;
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

  GuardarActa(idAsamblea: number, data: ActaModel) {
    let url = 'asambleas/' + idAsamblea + '/acta';

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

  ListarActa(idActa: number = 0) {
    const url = 'asambleas/acta/' + idActa;
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
