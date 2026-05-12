import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { environment } from '../../environments/environment';
import { SesionUsuarioService } from './sesion-usuario.service';

@Injectable({ providedIn: 'root' })
export class ConfiguracionService {

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

  Listar() {
    return this.http.get(environment.urlBackend + 'configuracion', { headers: this.headers });
  }

  Guardar(configs: any) {
    return this.http.post(environment.urlBackend + 'configuracion/guardar', { configs }, { headers: this.headers });
  }
}
