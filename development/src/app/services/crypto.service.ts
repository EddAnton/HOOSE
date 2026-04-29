import { Injectable } from '@angular/core';
import * as CryptoJS from 'crypto-js';

@Injectable({
	providedIn: 'root',
})
export class CryptoService {
	pKey = '12u^3k#c4^t3nch@';

	constructor() {}

	//The set method is use for encrypt the value.
	set(value: { toString: () => string }, keys: string = null) {
		if (keys == null) {
			keys = this.pKey;
		}
		var key = CryptoJS.enc.Utf8.parse(keys);
		var iv = CryptoJS.enc.Utf8.parse(keys);
		const encrypted = CryptoJS.MD5(value);

		return encrypted.toString();
	}

	//The get method is use for decrypt the value.
	get(value: string | CryptoJS.lib.CipherParams, keys: string = null) {
		if (keys == null) {
			keys = this.pKey;
		}
		var key = CryptoJS.enc.Utf8.parse(keys);
		var iv = CryptoJS.enc.Utf8.parse(keys);
		var decrypted = CryptoJS.AES.decrypt(value, key, {
			keySize: 128 / 8,
			iv: iv,
			mode: CryptoJS.mode.CBC,
			padding: CryptoJS.pad.Pkcs7,
		});

		return decrypted.toString(CryptoJS.enc.Utf8);
	}
}
