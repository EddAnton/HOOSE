import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { map } from 'rxjs/operators';
import { environment } from '../../environments/environment';
import { SesionUsuarioService } from './sesion-usuario.service';

@Injectable({ providedIn: 'root' })
export class LayoutService {
  constructor(private http: HttpClient, private sesionUsuarioService: SesionUsuarioService) {}

  private get headers() {
    return new HttpHeaders({
      'X-API-KEY': environment.appKey,
      Authorization: this.sesionUsuarioService.obtenerToken(),
    });
  }

  getLayout() {
    return this.http.get(`${environment.urlBackend}layout/tablero`, { headers: this.headers }).pipe(map(r => r));
  }

  saveLayout(items: any[]) {
    const form = new FormData();
    items.forEach((s, i) => {
      form.append(`secciones[${i}][seccion]`, s.id || '');
      form.append(`secciones[${i}][cols]`, (s.span || 4).toString());
      form.append(`secciones[${i}][rows]`, (s.rows || 2).toString());
      form.append(`secciones[${i}][x]`, (s.col || 1).toString());
      form.append(`secciones[${i}][y]`, (s.row || 1).toString());
      form.append(`secciones[${i}][orden]`, i.toString());
      form.append(`secciones[${i}][visible]`, '1');
      form.append(`secciones[${i}][col_size]`, '12');
    });
    return this.http.post(`${environment.urlBackend}layout/tablero`, form, { headers: this.headers }).pipe(map(r => r));
  }
}
