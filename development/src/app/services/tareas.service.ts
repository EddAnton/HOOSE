import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { map } from 'rxjs/operators';
import { environment } from '../../environments/environment';
import { SesionUsuarioService } from './sesion-usuario.service';

@Injectable({ providedIn: 'root' })
export class TareasService {
  constructor(private http: HttpClient, private sesionUsuarioService: SesionUsuarioService) {}

  private get headers() {
    return new HttpHeaders({
      'X-API-KEY': environment.appKey,
      Authorization: this.sesionUsuarioService.obtenerToken(),
    });
  }

  Listar() {
    return this.http.get(environment.urlBackend + 'tareas', { headers: this.headers }).pipe(map(r => r));
  }

  ListarUsuariosAsignables() {
    return this.http.get(environment.urlBackend + 'tareas/usuarios-asignables', { headers: this.headers }).pipe(map(r => r));
  }

  Insertar(data: FormData) {
    return this.http.post(environment.urlBackend + 'tareas/insertar', data, { headers: this.headers }).pipe(map(r => r));
  }

  Actualizar(id: number, data: FormData) {
    return this.http.post(environment.urlBackend + `tareas/actualizar/${id}`, data, { headers: this.headers }).pipe(map(r => r));
  }

  CambiarEstatus(id: number, data: FormData) {
    return this.http.post(environment.urlBackend + `tareas/cambiar-estatus/${id}`, data, { headers: this.headers }).pipe(map(r => r));
  }

  Eliminar(id: number) {
    return this.http.post(environment.urlBackend + `tareas/eliminar/${id}`, {}, { headers: this.headers }).pipe(map(r => r));
  }
}
