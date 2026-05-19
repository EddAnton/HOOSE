import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { map } from 'rxjs/operators';
import { environment } from '../../environments/environment';
import { CondominioModel } from '../models/condominio.model';
import { SesionUsuarioService } from './sesion-usuario.service';

@Injectable({ providedIn: 'root' })
export class CondominiosService {
  constructor(private http: HttpClient, private sesionUsuarioService: SesionUsuarioService) {}

  private get headers() {
    return new HttpHeaders({
      'X-API-KEY': environment.appKey,
      Authorization: this.sesionUsuarioService.obtenerToken(),
    });
  }

  Listar(soloActivos: boolean = false) {
    const url = 'condominios' + (soloActivos ? '/activos' : '');
    return this.http.get(environment.urlBackend + url, { headers: this.headers }).pipe(map(r => r));
  }

  ListarActivos() { return this.Listar(true); }

  ListarCondominio(idCondominio: number = 0) {
    return this.http.get(environment.urlBackend + 'condominios/' + idCondominio, { headers: this.headers }).pipe(map(r => r));
  }

  GuardarFormData(formData: FormData, idCondominio: number = 0) {
    const url = idCondominio == 0 ? 'condominios/insertar' : 'condominios/actualizar/' + idCondominio;
    return this.http.post(environment.urlBackend + url, formData, { headers: this.headers }).pipe(map(r => r));
  }

  Guardar(data: CondominioModel) {
    const url = 'condominios/' + (data.id_condominio == 0 ? 'insertar' : 'actualizar/' + data.id_condominio);
    const params: any = new FormData();
    for (const [key, value] of Object.entries(data)) {
      if (value != null) params.append(key, value);
    }
    return this.http.post(environment.urlBackend + url, params, { headers: this.headers }).pipe(map(r => r));
  }

  AlternarEstatus(idCondominio: number = 0) {
    return this.http.post(environment.urlBackend + 'condominios/alternar-estatus/' + idCondominio, null, { headers: this.headers }).pipe(map(r => r));
  }
}
