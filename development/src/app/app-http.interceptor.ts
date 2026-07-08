import { Injectable, NgZone } from '@angular/core';
import { HttpRequest, HttpHandler, HttpInterceptor, HttpErrorResponse } from '@angular/common/http';
import { catchError } from 'rxjs/operators';
import { throwError } from 'rxjs';
import { SesionUsuarioService } from './services/sesion-usuario.service';
import { Router } from '@angular/router';
import * as hlpSwal from './helpers/sweetalert2-helper';

@Injectable()
export class AppHttpInterceptor implements HttpInterceptor {
constructor(
  private sesionUsuarioService: SesionUsuarioService,
  private router: Router,
  private ngZone: NgZone
) {}

intercept(request: HttpRequest<any>, next: HttpHandler) {
  const authReq = request.clone({
    setHeaders: {
      'Cache-Control': 'no-cache',
      Pragma: 'no-cache',
    },
  });
  return next.handle(authReq).pipe(
    catchError((err: HttpErrorResponse) => {
      if (err.status === 401) {
        const msg = err.error?.msg || 'Tu cuenta ha sido desactivada.';
        this.ngZone.run(() => {
          hlpSwal.Advertencia(msg).then(() => {
            this.sesionUsuarioService.cerrarSesion();
          });
        });
      }
      return throwError(err);
    })
  );
}
}
