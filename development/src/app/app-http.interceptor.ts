import { Injectable } from '@angular/core';
import { HttpRequest, HttpHandler, HttpInterceptor } from '@angular/common/http';

@Injectable()
export class AppHttpInterceptor implements HttpInterceptor {
	constructor() {}

	intercept(request: HttpRequest<any>, next: HttpHandler) {
		const authReq = request.clone({
			// Prevent caching in IE, in particular IE11.
			// See: https://support.microsoft.com/en-us/help/234067/how-to-prevent-caching-in-internet-explorer
			setHeaders: {
				'Cache-Control': 'no-cache',
				Pragma: 'no-cache',
			},
		});
		return next.handle(authReq);
	}
}
