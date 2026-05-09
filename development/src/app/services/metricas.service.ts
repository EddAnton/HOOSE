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

  Tablero(comparativo: string = 'mes_anterior', fechaInicio?: string, fechaFin?: string) {
    let url = `${environment.urlBackend}metricas/tablero?comparativo=${comparativo}`;
    if (fechaInicio) url += `&fecha_inicio=${fechaInicio}`;
    if (fechaFin) url += `&fecha_fin=${fechaFin}`;
    return this.http.get(url, { headers: this.headers }).pipe(map(r => r));
  }
}
