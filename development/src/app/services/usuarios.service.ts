import { Injectable, isDevMode } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Router } from '@angular/router';
import { map } from 'rxjs/operators';

import { environment } from '../../environments/environment';
// import { UsuarioInicioSesionModel, UsuarioModel, UsuarioSesionModel } from '../models/usuario.model';
import { UsuarioSesionInterface } from '../interfaces/usuario-interface';
import { LocalStorageService } from './local-storage.service';
import { SesionUsuarioService } from './sesion-usuario.service';

@Injectable({
  providedIn: 'root',
})
export class UsuariosService {
  // lsUsr = 'p31250n';
  // private usuarioSesion: UsuarioSesionInterface;

  /* constructor(private localStorage: LocalStorageService, private http: HttpClient, private router: Router) {
    this.usuarioSesion = JSON.parse(this.localStorage.leer(this.lsUsr));
  } */
  constructor(private http: HttpClient, private router: Router, private sesionUsuarioService: SesionUsuarioService) { }

  Listar(idUsuario: number = 0) {
    const url = 'usuarios/' + (idUsuario > 0 ? idUsuario : '');
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

  ListarPerfilesUsuariosTableroAvisos() {
    const url = 'usuarios/perfiles-tablero-avisos';
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

  ListarPropietariosYCondominos() {
    const url = 'usuarios/propietarios-y-condominos';
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

  ListarUsuariosParaNotificaciones() {
    const url = 'usuarios/para-notificaciones';
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

  ListarUsuariosActaAsambleas() {
    const url = 'usuarios/para-acta-asamblea';
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

  AlternarEstatus(idUsuario: number = 0) {
    const url = 'usuarios/alternar-estatus/' + idUsuario;
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

  CambiarContrasenia(data: any = null) {
    const url = 'usuarios/cambiar-contrasenia/' + this.sesionUsuarioService.leerUsuario().id_usuario;
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

  ReiniciarContrasenia(idUsuario: number = 0) {
    const url = 'usuarios/reiniciar-contrasenia/' + idUsuario;
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
