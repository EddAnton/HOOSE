import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { map } from 'rxjs/operators';
import { environment } from '../../environments/environment';
import { SesionUsuarioService } from './sesion-usuario.service';

@Injectable({ providedIn: 'root' })
export class MetricasService {
  constructor(private http: HttpClient, private sesionUsuarioService: SesionUsuarioService) {}

  private get headers() {
    return new HttpHeaders({
      'X-API-KEY': environment.appKey,
      Authorization: this.sesionUsuarioService.obtenerToken(),
    });
  }

  Tablero(comparativo: string = 'mes_anterior') {
    return this.http.get(
      `${environment.urlBackend}metricas/tablero?comparativo=${comparativo}`,

      { headers: this.headers }
    ).pipe(map(r => r));
  }
}
