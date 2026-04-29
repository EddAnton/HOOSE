import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { map } from 'rxjs/operators';
import { environment } from '../../environments/environment';
import { SesionUsuarioService } from './sesion-usuario.service';
import { FondoMonetarioModel } from '../models/fondo-monetario.model';

@Injectable({
  providedIn: 'root'
})
export class ComitesService {

  constructor(private http: HttpClient, private sesionUsuarioService: SesionUsuarioService) { }

  ListarTiposComite() {
    const url = 'tipos-comite';
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

  ListarMiembros(idTipoComite: number = 0) {
    const url = 'miembros-comite' + (idTipoComite > 0 ? '/' + idTipoComite : '');
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

  Guardar(data: any) {
    let url =
      'miembros-comite/' + (data.id_miembro_comite == 0 ? 'insertar' : 'actualizar/' + data.id_miembro_comite);

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

  Eliminar(idMiembroComite: number = 0) {
    const url = 'miembros-comite/eliminar/' + idMiembroComite;
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
