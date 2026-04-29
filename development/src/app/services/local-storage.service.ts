import { Injectable } from '@angular/core';

@Injectable({
	providedIn: 'root',
})
export class LocalStorageService {
	constructor() {}

	guardar(item: string, value: any) {
		localStorage.setItem(item, value);
	}

	leer(item: string) {
		return localStorage.getItem(item) ? localStorage.getItem(item) : null;
	}

	remover(item: string) {
		localStorage.removeItem(item);
	}
}
