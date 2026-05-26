import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { map } from 'rxjs/operators';
import { environment } from '../../environments/environment';
import { AdministradorModel } from '../models/usuario-administrador.model';
import { SesionUsuarioService } from './sesion-usuario.service';

@Injectable({ providedIn: 'root' })
export class UsuariosAdministradoresService {
  constructor(private http: HttpClient, private sesionUsuarioService: SesionUsuarioService) {}

  private get headers() {
    return new HttpHeaders({
      'X-API-KEY': environment.appKey,
      Authorization: this.sesionUsuarioService.obtenerToken(),
    });
  }

  Listar(soloActivos: boolean = false) {
    const url = 'administradores' + (soloActivos ? '/activos' : '');
    return this.http.get(environment.urlBackend + url, { headers: this.headers }).pipe(map(r => r));
  }

  ListarActivos() { return this.Listar(true); }

  ListarAdministrador(idUsuario: number = 0) {
    return this.http.get(environment.urlBackend + 'administradores/' + idUsuario, { headers: this.headers }).pipe(map(r => r));
  }

  ListarTodos() {
    return this.http.get(environment.urlBackend + 'administradores', { headers: this.headers }).pipe(map(r => r));
  }

  ListarSinAsignar() {
    return this.http.get(environment.urlBackend + 'administradores/sin-asignar', { headers: this.headers }).pipe(map(r => r));
  }

  Guardar(data: AdministradorModel) {
    const url = 'administradores/' + (data.id_usuario == 0 ? 'insertar' : 'actualizar/' + data.id_usuario);
    const params: any = new FormData();
    for (const [key, value] of Object.entries(data)) {
      if (value != null) params.append(key, value);
    }
    return this.http.post(environment.urlBackend + url, params, { headers: this.headers }).pipe(map(r => r));
  }

  Actualizar(idUsuario: number, data: any) {
    const params = new FormData();
    Object.keys(data).forEach(k => { if (data[k] !== null) params.append(k, data[k]); });
    return this.http.post(environment.urlBackend + `administradores/actualizar/${idUsuario}`, params, { headers: this.headers }).pipe(map(r => r));
  }
}
