import { Injectable } from '@angular/core';
import { HttpRequest, HttpHandler, HttpInterceptor, HttpErrorResponse } from '@angular/common/http';
import { tap } from 'rxjs/operators';
import { SesionUsuarioService } from './services/sesion-usuario.service';
import { Router } from '@angular/router';

@Injectable()
export class AppHttpInterceptor implements HttpInterceptor {
constructor(private sesionUsuarioService: SesionUsuarioService, private router: Router) {}

intercept(request: HttpRequest<any>, next: HttpHandler) {
const authReq = request.clone({
setHeaders: {
'Cache-Control': 'no-cache',
Pragma: 'no-cache',
},
});
return next.handle(authReq).pipe(
tap({
error: (err) => {
if (err instanceof HttpErrorResponse && (err.status === 401 || err.status === 403)) {
this.sesionUsuarioService.cerrarSesion();
this.router.navigate(['/inicio-sesion']);
}
}
})
);
}
}
