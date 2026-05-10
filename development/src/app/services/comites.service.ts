import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { map } from 'rxjs/operators';
import { environment } from '../../environments/environment';
import { SesionUsuarioService } from './sesion-usuario.service';

@Injectable({
  providedIn: 'root'
})
export class ComitesService {

  constructor(
    private http: HttpClient,
    private sesionUsuarioService: SesionUsuarioService
  ) {}

  private get headers() {
    return new HttpHeaders({
      'X-API-KEY': environment.appKey,
      Authorization: this.sesionUsuarioService.obtenerToken(),
    });
  }

  ListarTipos() {
    return this.http.get(environment.urlBackend + 'comites/tipos', { headers: this.headers });
  }

  ListarCargos(idTipoComite: number) {
    return this.http.get(environment.urlBackend + `comites/cargos/${idTipoComite}`, { headers: this.headers });
  }

  Listar() {
    return this.http.get(environment.urlBackend + 'comites', { headers: this.headers });
  }

  Insertar(data: any) {
    return this.http.post(environment.urlBackend + 'comites/insertar', data, { headers: this.headers });
  }

  Actualizar(idMiembro: number, data: any) {
    return this.http.post(environment.urlBackend + `comites/actualizar/${idMiembro}`, data, { headers: this.headers });
  }

  Eliminar(idMiembro: number) {
    return this.http.post(environment.urlBackend + `comites/eliminar/${idMiembro}`, {}, { headers: this.headers });
  }

  InsertarTipo(data: any) {
    return this.http.post(environment.urlBackend + 'comites/tipos/insertar', data, { headers: this.headers });
  }

  ActualizarTipo(idTipoComite: number, data: any) {
    return this.http.post(environment.urlBackend + `comites/tipos/actualizar/${idTipoComite}`, data, { headers: this.headers });
  }

  EliminarTipo(idTipoComite: number) {
    return this.http.post(environment.urlBackend + `comites/tipos/eliminar/${idTipoComite}`, {}, { headers: this.headers });
  }
}
