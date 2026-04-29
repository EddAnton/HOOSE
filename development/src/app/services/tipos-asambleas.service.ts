import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { map } from 'rxjs/operators';
import { environment } from '../../environments/environment';
import { SesionUsuarioService } from './sesion-usuario.service';

@Injectable({
  providedIn: 'root'
})
export class TiposAsambleasService {
  constructor(private http: HttpClient, private sesionUsuarioService: SesionUsuarioService) { }

  Listar(soloActivos: boolean = false) {
    const url = 'tipos-asambleas' + (soloActivos ? '/activos' : '');
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
}
