import { HttpErrorResponse } from '@angular/common/http';
/* import { isDevMode } from '@angular/core'; */
import Swal from 'sweetalert2';
import { SweetAlertOptions } from 'sweetalert2';
import type { SweetAlertIcon } from 'sweetalert2';

export var MensajeSimple = Swal.mixin({
	backdrop: true,
	allowOutsideClick: false,
	heightAuto: false,
	confirmButtonText: 'Aceptar',
	showCancelButton: false,
	buttonsStyling: false,
	customClass: {
		htmlContainer: 'py-1',
		confirmButton: 'btn btn-secondary btn-lg me-3',
	},
	showClass: {
		popup: 'swal2-show',
		backdrop: 'swal2-backdrop-show',
		icon: 'swal2-icon-show',
	},
	hideClass: {
		popup: 'swal2-hide',
		backdrop: 'swal2-backdrop-hide',
		icon: 'swal2-icon-hide',
	},
});

var MensajePreguntaSimple = Swal.mixin({
	backdrop: true,
	allowOutsideClick: false,
	heightAuto: false,
	icon: 'question',
	title: 'Necesitamos su confirmación...',
	confirmButtonText: 'Si',
	showCancelButton: true,
	cancelButtonText: 'No',
	buttonsStyling: false,
	focusCancel: true,
	customClass: {
		htmlContainer: 'py-1',
		confirmButton: 'btn btn-lg btn-secondary me-3',
		cancelButton: 'btn btn-lg btn-danger ',
		denyButton: 'btn btn-lg btn-primary me-3 ',
	},
	showClass: {
		popup: 'swal2-show',
		backdrop: 'swal2-backdrop-show',
		icon: 'swal2-icon-show',
	},
	hideClass: {
		popup: 'swal2-hide',
		backdrop: 'swal2-backdrop-hide',
		icon: 'swal2-icon-hide',
	},
});

export var MensajeToast = Swal.mixin({
	toast: true,
	icon: 'error',
	background: '#DEF3FF',
	title: 'Oh no!',
	position: 'top-end',
	showConfirmButton: false,
	timer: 4000,
	timerProgressBar: true,
	didOpen: (toast) => {
		toast.addEventListener('mouseenter', Swal.stopTimer);
		toast.addEventListener('mouseleave', Swal.resumeTimer);
	},
});

function instanceOfSweetAlertOptions(object: any): object is SweetAlertOptions {
	return 'icon' in object || 'title' in object || 'text' in object || 'html' in object;
}

const esHTML = (str) =>
	!(str || '')
		// replace new line
		.replace(/\r?\n|\r/g, ' ')
		// replace html tag with content
		.replace(/<([^>]+?)([^>]*?)>(.*?)<\/\1>/gi, '')
		// remove remaining self closing tags
		.replace(/(<([^>]+)>)/gi, '')
		// remove extra space at start and end
		.trim();

export function estaCargando() {
	return Swal.isVisible() && Swal.isLoading();
}

export function mostrarCargando() {
	return Swal.showLoading();
}

export function Cargando() {
	Swal.fire({
		title: 'Recuperando información',
		html: 'Por favor espere...',
		width: '425px',
		didOpen: () => {
			Swal.showLoading();
		},
	});
}

export function Cerrar() {
	return Swal.close();
}

function GenerarContenidoMensajeErrorREST(error: HttpErrorResponse) {
	// console.error(error);
	let mensaje = {
		icon: null,
		title: 'Oh no!',
		width: undefined,
		html: null,
		confirmButtonText: 'Aceptar',
	};
	mensaje.icon = 'error';

	if ([400, 401, 403].includes(error.status)) {
		mensaje.html = error.error.msg ? error.error.msg : error.message;
		if (typeof mensaje.html === 'string') mensaje.html = mensaje.html.replace(/(\r\n|\n|\r)/g, '<br />');
	} else {
		//		if (isDevMode()) {
		if (typeof error.error !== 'object') {
			mensaje.html = error.error;
			// mensaje.width = '75vw';
		} else {
			const url = error.url ? error.url : undefined;
			mensaje.html = error.error.text
				? error.error.text
				: error.message
				? error.message
				: error.status + ' - ' + error.statusText + (url ? '<br />' + url : '');
			/* mensaje.html = error.error.text
				? error.error.text
				: error.status + ' - ' + error.statusText + (url ? '<br />' + url : ''); */
		}
		// if (esHTML(mensaje.html)) mensaje.width = '90vw';
		mensaje.width = 'auto';
		/*
		} else {
			const url = error.url ? error.url : undefined;
			mensaje.html = error.status + ' - ' + error.statusText + (url ? '<br />' + url : '');
		}
*/
	}
	return mensaje;
}

function GenerarContenidoMensajeError(error: string) {
	// console.error(error);
	let mensaje = {
		icon: <SweetAlertIcon>'error',
		title: 'Oh no!',
		html: error.replace(/(\r\n|\n|\r)/g, '<br />'),
		confirmButtonText: 'Aceptar',
	};
	return mensaje;
}

export async function Pregunta(mensaje: any): Promise<any> {
	let respuesta: any = null;
	if (typeof mensaje === 'object' && instanceOfSweetAlertOptions(mensaje)) {
		respuesta = await MensajePreguntaSimple.fire(mensaje).then((r) => r);
	} else if (typeof mensaje === 'string') {
		respuesta = await MensajePreguntaSimple.fire({
			icon: <SweetAlertIcon>'question',
			title: 'Necesita confirmar...',
			html: mensaje,
		}).then((r) => r);
	}
	if (!respuesta) {
		MensajeSimple.fire(GenerarContenidoMensajeError('MensajeExito(). Parámetros no válidos.'));
	}
	return respuesta;
}

export function Exito(mensaje: any) {
	let respuesta: any = null;
	if (typeof mensaje === 'object' && instanceOfSweetAlertOptions(mensaje)) {
		respuesta = MensajeSimple.fire(mensaje).then((r) => r);
	} else if (typeof mensaje === 'string') {
		respuesta = MensajeSimple.fire({
			icon: <SweetAlertIcon>'success',
			title: 'Listo!',
			html: mensaje,
		}).then((r) => r);
	}
	if (!respuesta) {
		MensajeSimple.fire(GenerarContenidoMensajeError('Exito(). Parámetros no válidos.'));
	}
	return respuesta;
}

export function ExitoToast(mensaje: any) {
	if (typeof mensaje === 'object' && instanceOfSweetAlertOptions(mensaje)) {
		MensajeToast.fire(mensaje);
	} else if (typeof mensaje === 'string') {
		MensajeToast.fire({
			icon: <SweetAlertIcon>'success',
			title: 'Listo!',
			html: mensaje,
			timer: 2000,
		});
	} else {
		MensajeSimple.fire(GenerarContenidoMensajeError('ExitoToast(). Parámetros no válidos.'));
	}
}

/* export function Error(error: any) {
	let respuesta: any = null;
	if (typeof error === 'object') {
		if (instanceOfSweetAlertOptions(error)) {
			respuesta = MensajeSimple.fire(error).then((r) => r);
		} else if (error instanceof HttpErrorResponse) {
			respuesta = MensajeSimple.fire(GenerarContenidoMensajeErrorREST(error)).then((r) => r);
		} else if (error.message && typeof error.message === 'string') {
			respuesta = MensajeSimple.fire(GenerarContenidoMensajeError(error.message)).then((r) => r);
		}
	} else if (typeof error === 'string') {
		respuesta = MensajeSimple.fire(GenerarContenidoMensajeError(error)).then((r) => r);
	}
	if (!respuesta) {
		MensajeSimple.fire(GenerarContenidoMensajeError('Error(). Parámetros no válidos.'));
	}
	return respuesta;
} */

export function Error(error: any) {
	let respuesta: any = null;
	if (typeof error === 'object') {
		if (instanceOfSweetAlertOptions(error)) {
			respuesta = MensajeSimple.fire(error).then((r) => r);
		} else if (error instanceof HttpErrorResponse) {
			respuesta = MensajeSimple.fire(GenerarContenidoMensajeErrorREST(error)).then((r) => r);
		} else if (error.message && typeof error.message === 'string') {
			respuesta = MensajeSimple.fire(GenerarContenidoMensajeError(error.message)).then((r) => r);
		}
	} else if (typeof error === 'string') {
		respuesta = MensajeSimple.fire(GenerarContenidoMensajeError(error)).then((r) => r);
	}
	if (!respuesta) {
		MensajeSimple.fire(GenerarContenidoMensajeError('Error(). Parámetros no válidos.'));
	}
	return respuesta;
}

export function ErrorToast(mensaje: any) {
	if (typeof mensaje === 'object' && instanceOfSweetAlertOptions(mensaje)) {
		MensajeToast.fire(mensaje);
	} else if (typeof mensaje === 'string') {
		MensajeToast.fire({
			icon: <SweetAlertIcon>'error',
			title: 'Oh no!',
			html: mensaje,
		});
	} else {
		MensajeSimple.fire(GenerarContenidoMensajeError('ExitoToast(). Parámetros no válidos.'));
	}
}

export function Advertencia(mensaje: any) {
	let respuesta: any = null;
	if (typeof mensaje === 'object' && instanceOfSweetAlertOptions(mensaje)) {
		respuesta = MensajeSimple.fire(mensaje).then((r) => r);
	} else if (typeof mensaje === 'string') {
		respuesta = MensajeSimple.fire({
			icon: <SweetAlertIcon>'warning',
			title: 'Lo sentimos...',
			html: mensaje,
		}).then((r) => r);
	}
	if (!respuesta) {
		MensajeSimple.fire(GenerarContenidoMensajeError('Advertencia(). Parámetros no válidos.'));
	}
	return respuesta;
}

export function Info(mensaje: any) {
	let respuesta: any = null;
	if (typeof mensaje === 'object' && instanceOfSweetAlertOptions(mensaje)) {
		respuesta = MensajeSimple.fire(mensaje).then((r) => r);
	} else if (typeof mensaje === 'string') {
		respuesta = MensajeSimple.fire({
			icon: <SweetAlertIcon>'info',
			// title: 'Lo sentimos...',
			html: mensaje,
		}).then((r) => r);
	}
	if (!respuesta) {
		MensajeSimple.fire(GenerarContenidoMensajeError('Info(). Parámetros no válidos.'));
	}
	return respuesta;
}
