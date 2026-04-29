import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable, isDevMode } from '@angular/core';
import { Router } from '@angular/router';
import { map } from 'rxjs/operators';
import { environment } from '../../environments/environment';
import { LoginModel, SesionUsuarioModel } from '../models/sesion-usuario.model';
import { LocalStorageService } from './local-storage.service';

@Injectable({
  providedIn: 'root',
})
export class SesionUsuarioService {
  private appData = environment;
  private sesionUsuarioModel: SesionUsuarioModel = new SesionUsuarioModel();
  lsSesionUsuario = this.appData.appName + '-usr';

  /* constructor(private http: HttpClient, private router: Router, private localStorage: LocalStorageService) {
    this.abc();
  } */
  constructor(private http: HttpClient, private router: Router, private localStorage: LocalStorageService) {
    if (isDevMode() && !this.localStorage.leer(this.lsSesionUsuario)) {
      this.fingirSesion();
    } else {
      this.sesionUsuarioModel = JSON.parse(this.localStorage.leer(this.lsSesionUsuario));
    }
  }

  private fingirSesion() {
    this.guardarUsuario(this.dataSesionSuperAdmin());
    // this.guardarUsuario(this.dataSesionAdministrador());
    // this.guardarUsuario(this.dataSesionColaborador());
    // this.guardarUsuario(this.dataSesionPropietario());
    // this.guardarUsuario(this.dataSesionCondomino());
  }

  private dataSesionSuperAdmin() {
    const tmpSesionUsuario: SesionUsuarioModel = new SesionUsuarioModel();

    tmpSesionUsuario.id_usuario = 1;
    tmpSesionUsuario.usuario = 'hoose';
    tmpSesionUsuario.nombre = 'SUPER ADMINISTRADOR';
    tmpSesionUsuario.email = 'sa';
    tmpSesionUsuario.id_perfil_usuario = 1;
    tmpSesionUsuario.perfil_usuario = 'SUPER ADMINISTRADOR';
    tmpSesionUsuario.token =
      'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6IjEiLCJpZF9wZXJmaWxfdXN1YXJpbyI6IjEiLCJpZF9jb25kb21pbmlvX3VzdWFyaW8iOiIwIiwidGltZSI6MTY3Nzg2ODg0MCwiZXhwaXJlIjoxNjc4NDczNjQwfQ.SUTQp5owVj7cJvgfolMER7vIHGZ8XGu34adVXXxIMj4';
    tmpSesionUsuario.tokenExpire = 1678473640;

    return tmpSesionUsuario;
  }

  private dataSesionAdministrador() {
    const tmpSesionUsuario: SesionUsuarioModel = new SesionUsuarioModel();

    tmpSesionUsuario.id_usuario = 2;
    tmpSesionUsuario.usuario = 'admin.c01';
    tmpSesionUsuario.nombre = 'ADMINISTRADOR 01';
    tmpSesionUsuario.email = 'administrador.01.condominio.01@pontevedra.com';
    tmpSesionUsuario.id_perfil_usuario = 2;
    tmpSesionUsuario.perfil_usuario = 'ADMINISTRADOR';
    tmpSesionUsuario.id_condominio_usuario = 1;
    tmpSesionUsuario.condominio_usuario = 'CONDOMINIO DE PRUEBA #1';
    tmpSesionUsuario.tiene_tablero_avisos = 0;
    tmpSesionUsuario.imagen_archivo = 'uploads/usuarios/2/profile_63702c3699f3e.jpg';
    tmpSesionUsuario.debe_cambiar_contrasenia = 0;
    tmpSesionUsuario.token =
      'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6IjIiLCJpZF9wZXJmaWxfdXN1YXJpbyI6IjIiLCJpZF9jb25kb21pbmlvX3VzdWFyaW8iOiIxIiwidGltZSI6MTY3Nzg2ODkxOCwiZXhwaXJlIjoxNjc4NDczNzE4fQ.NLY_QjAVIIXbDTq8gC8okVAj4zECXEehlmvpqF62ndg';
    tmpSesionUsuario.tokenExpire = 1678473718;

    return tmpSesionUsuario;
  }

  private dataSesionColaborador() {
    const tmpSesionUsuario: SesionUsuarioModel = new SesionUsuarioModel();

    tmpSesionUsuario.id_usuario = 3;
    tmpSesionUsuario.usuario = 'guardia01.c01';
    tmpSesionUsuario.nombre = 'GUARDIA SEGURIDAD 01';
    tmpSesionUsuario.email = 'guardia.01.condominio.01@pontevedra.com';
    tmpSesionUsuario.id_perfil_usuario = 3;
    tmpSesionUsuario.perfil_usuario = 'COLABORADOR';
    tmpSesionUsuario.id_condominio_usuario = 1;
    tmpSesionUsuario.condominio_usuario = 'CONDOMINIO DE PRUEBA #1';
    tmpSesionUsuario.tiene_tablero_avisos = 1;
    tmpSesionUsuario.imagen_archivo = 'uploads/usuarios/3/profile_631606fe3c31e.png';
    tmpSesionUsuario.debe_cambiar_contrasenia = 1;
    tmpSesionUsuario.token =
      'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6IjMiLCJpZF9wZXJmaWxfdXN1YXJpbyI6IjMiLCJpZF9jb25kb21pbmlvX3VzdWFyaW8iOiIxIiwidGltZSI6MTY3Nzg3MjIzMSwiZXhwaXJlIjoxNjc4NDc3MDMxfQ.bHClw1VhHo_ykrNbNxY9Ex-HDZrN5SN_iyxifDUkG4s';
    tmpSesionUsuario.tokenExpire = 1678477031;
    /*
    "id_tipo_miembro": "3",
    "tipo_miembro": "GUARDIA DE SEGURIDAD",
    "es_colaborador": "1",
    "fecha_inicio": "2022-08-01",
    "fecha_fin": null,
    */

    return tmpSesionUsuario;
  }

  private dataSesionPropietario() {
    const tmpSesionUsuario: SesionUsuarioModel = new SesionUsuarioModel();

    tmpSesionUsuario.id_usuario = 6;
    tmpSesionUsuario.usuario = 'propietario01.c01';
    tmpSesionUsuario.nombre = 'PROPIETARIO 001';
    tmpSesionUsuario.email = 'propietario01.condominio01@pontevedra.com.mx';
    tmpSesionUsuario.id_perfil_usuario = 4;
    tmpSesionUsuario.perfil_usuario = 'PROPIETARIO';
    tmpSesionUsuario.id_condominio_usuario = 1;
    tmpSesionUsuario.condominio_usuario = 'CONDOMINIO DE PRUEBA #1';
    tmpSesionUsuario.tiene_tablero_avisos = 1;
    tmpSesionUsuario.imagen_archivo = null;
    tmpSesionUsuario.debe_cambiar_contrasenia = 1;
    tmpSesionUsuario.token =
      'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6IjYiLCJpZF9wZXJmaWxfdXN1YXJpbyI6IjQiLCJpZF9jb25kb21pbmlvX3VzdWFyaW8iOiIxIiwidGltZSI6MTY3ODEzNTIxOSwiZXhwaXJlIjoxNjc4NzQwMDE5fQ.Hq_G0_1m9mAOvfWeEA9YOd1eYQa6yAeUM5hYkiqUv-4';
    tmpSesionUsuario.tokenExpire = 1678740019;
    /*
    "id_tipo_miembro": "3",
    "tipo_miembro": "GUARDIA DE SEGURIDAD",
    "es_colaborador": "1",
    "fecha_inicio": "2022-08-01",
    "fecha_fin": null,
    */

    return tmpSesionUsuario;
  }

  private dataSesionCondomino() {
    const tmpSesionUsuario: SesionUsuarioModel = new SesionUsuarioModel();

    tmpSesionUsuario.id_usuario = 9;
    tmpSesionUsuario.usuario = 'condomino01';
    tmpSesionUsuario.nombre = 'CONDÓMINO 01';
    tmpSesionUsuario.email = 'condomino01@pontevedra.com';
    tmpSesionUsuario.id_perfil_usuario = 5;
    tmpSesionUsuario.perfil_usuario = 'CONDÓMINO';
    tmpSesionUsuario.id_condominio_usuario = 1;
    tmpSesionUsuario.condominio_usuario = 'CONDOMINIO DE PRUEBA #1';
    tmpSesionUsuario.tiene_tablero_avisos = 1;
    tmpSesionUsuario.imagen_archivo = 'uploads/usuarios/9/profile_6352e6b89131e.jpg';
    tmpSesionUsuario.debe_cambiar_contrasenia = 1;
    tmpSesionUsuario.token =
      'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6IjkiLCJpZF9wZXJmaWxfdXN1YXJpbyI6IjUiLCJpZF9jb25kb21pbmlvX3VzdWFyaW8iOiIxIiwidGltZSI6MTY3ODIzNTAxNiwiZXhwaXJlIjoxNjc4ODM5ODE2fQ.53aDbqOqek_pZSX_xuXr62IGUGNFr8ddnuLx5gydmVE';
    tmpSesionUsuario.tokenExpire = 1678839816;
    /*
    "id_tipo_miembro": "3",
    "tipo_miembro": "GUARDIA DE SEGURIDAD",
    "es_colaborador": "1",
    "fecha_inicio": "2022-08-01",
    "fecha_fin": null,
    */

    return tmpSesionUsuario;
  }

  iniciarSesion(inicioSesion: LoginModel) {
    const url = 'iniciar-sesion';
    const headers = new HttpHeaders({
      'X-API-KEY': environment.appKey,
    });

    const params = Object.assign({}, inicioSesion);
    return this.http.post(environment.urlBackend + `${url}`, params, { headers }).pipe(
      map((r) => {
        return r;
      }),
    );
  }

  seleccionarCondominio(data: any = null) {
    const url = 'seleccionar-condominio/';
    const headers = new HttpHeaders({
      'X-API-KEY': environment.appKey,
      Authorization: this.obtenerToken(),
    });

    const params = Object.assign({}, data);

    return this.http.post(environment.urlBackend + `${url}`, params, { headers }).pipe(
      map((respuesta) => {
        if (!respuesta['err']) {
          const usuario = respuesta['usuario'];
          const sesionUsuario = this.sesionUsuarioModel;

          sesionUsuario.id_condominio_usuario = Number(usuario.id_condominio_usuario);
          sesionUsuario.condominio_usuario = usuario.condominio_usuario;
          sesionUsuario.token = usuario.token;
          sesionUsuario.tokenExpire = usuario.tokenExpire;

          this.guardarUsuario(sesionUsuario);
        }
        return respuesta;
      }),
    );
  }

  guardarUsuario(usuario: SesionUsuarioModel) {
    this.sesionUsuarioModel = usuario;
    this.localStorage.guardar(this.lsSesionUsuario, JSON.stringify(this.sesionUsuarioModel));
  }

  leerUsuario(): SesionUsuarioModel {
    return this.sesionUsuarioModel;
  }

  obtenerToken(): string {
    return this.sesionUsuarioModel !== null && this.sesionUsuarioModel.token !== null
      ? this.sesionUsuarioModel.token
      : '';
  }

  obtenerIDCondominioUsuario(): number {
    return Number(this.sesionUsuarioModel?.id_condominio_usuario);
    // return Number(this.sesionUsuarioModel ? this.sesionUsuarioModel.id_perfil_usuario : 0);
  }

  obtenerIDUsuario(): number {
    return Number(this.sesionUsuarioModel?.id_usuario);
    // return Number(this.sesionUsuarioModel ? this.sesionUsuarioModel.id_perfil_usuario : 0);
  }

  obtenerPerfilUsuario(): any {
    return this.sesionUsuarioModel
      ? {
        id_perfil_usuario: this.sesionUsuarioModel.id_perfil_usuario,
        perfil_usuario: this.sesionUsuarioModel.perfil_usuario,
      }
      : 0;
  }

  obtenerIDPerfilUsuario(): number {
    return Number(this.sesionUsuarioModel ? this.sesionUsuarioModel.id_perfil_usuario : 0);
  }

  /* UsuarioEstaAutenticado(data: any): number {
    if (isDevMode()) {
      return 1;
    }

    this.usuarioSesion = JSON.parse(this.localStorage.leer(this.lsUsr));

    if (this.usuarioSesion == null || this.usuarioSesion.token == null) {
      this.router.navigateByUrl('/inicio-sesion');
      return -1;
    }

    const expiraFecha = new Date(0);
    expiraFecha.setUTCSeconds(this.usuarioSesion.tokenExpire);
    if (this.usuarioSesion.token.length < 0 || expiraFecha < new Date()) {
      this.router.navigateByUrl('/inicio-sesion');
      return -1;
    }

    if (this.usuarioSesion.id_perfil_usuario == 1) {
      return 1;
    }

    if (data.allowedRoles == null || data.allowedRoles.length === 0) {
      return 1;
    }

    const rolEncontrado =
      data.allowedRoles.filter((rol) => rol.PeU.includes(this.usuarioSesion.id_perfil_usuario)).length > 0;
    console.log('Usuario ' + (!rolEncontrado ? 'NO ' : '') + 'autenticado por rol.');

    return rolEncontrado ? 1 : 0;
  } */

  estaAutenticado(data: any): boolean {
    /* if (isDevMode()) {
      return true;
    } */

    // this.sesionModel = JSON.parse(this.localStorage.leer(this.lsUsuarioSesion));

    if (this.sesionUsuarioModel == null || this.sesionUsuarioModel.token == null) {
      return false;
    }

    const expiraFecha = new Date(0);
    expiraFecha.setUTCSeconds(this.sesionUsuarioModel.tokenExpire);
    if (this.sesionUsuarioModel.token.length < 0 || expiraFecha < new Date()) {
      return false;
    }
    if (data == undefined || data.perfilesUsuarioPermitidos == null || data.perfilesUsuarioPermitidos.length === 0) {
      return true;
    }

    return data.perfilesUsuarioPermitidos.includes(this.obtenerIDPerfilUsuario());
    // return data.perfilesUsuarioPermitidos.filter((rol) => rol.includes(this.sesionUsuarioModel.id_perfil_usuario)).length > 0;
  }

  redireccionar(url: string = '/') {
    this.router.navigateByUrl(url);
  }

  recargar() {
    const url = this.router.url;
    this.router.routeReuseStrategy.shouldReuseRoute = () => false;
    this.router.onSameUrlNavigation = 'reload';
    this.router.navigate([url]);
  }

  condominioSeleccionado() {
    return this.sesionUsuarioModel.id_condominio_usuario > 0;
  }

  borrarSesion() {
    this.sesionUsuarioModel = null;
    this.localStorage.remover(this.lsSesionUsuario);
  }

  cerrarSesion() {
    this.borrarSesion();
    this.redireccionar('/inicio-sesion');
  }
}
