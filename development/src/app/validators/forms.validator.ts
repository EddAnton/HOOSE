import { AbstractControl, ValidationErrors, FormGroup, ValidatorFn } from '@angular/forms';

export class FormsValidator {
	static sinEspaciosEnBlanco(control: AbstractControl): ValidationErrors | null {
		if (control.value != null && (control.value as string).indexOf(' ') >= 0) {
			return { sinEspaciosEnBlanco: true };
		}

		return null;
	}

	static debenCoincidir(controlName: string, matchingControlName: string) {
		return (formGroup: FormGroup) => {
			const control = formGroup.controls[controlName];
			const matchingControl = formGroup.controls[matchingControlName];

			if (matchingControl.errors && !matchingControl.errors.noCoinciden) {
				// return if another validator has already found an error on the matchingControl
				return;
			}

			// set error on matchingControl if validation fails
			if (control.value !== matchingControl.value) {
				matchingControl.setErrors({ noCoinciden: true });
			} else {
				matchingControl.setErrors(null);
			}
		};
	}

	static generalValidacionFechas(dateField1: string, dateField2: string, validatorField: { [key: string]: boolean }) {
		return (c: AbstractControl): { [key: string]: boolean } | null => {
			let date1 = c.get(dateField1).value;
			let date2 = c.get(dateField2).value;
			if (date1 == null || date2 == null) {
				return null;
			}

			if (!(date1 instanceof Date)) {
				const date1Parts = String(date1).split('/');
				date1 = new Date(+date1Parts[2], +date1Parts[1] - 1, +date1Parts[0]);
			}
			if (!(date2 instanceof Date)) {
				const date2Parts = String(date2).split('/');
				date2 = new Date(+date2Parts[2], +date2Parts[1] - 1, +date2Parts[0]);
			}

			let retorno: any = null;
			switch (String(Object.keys(validatorField)[0])) {
				case 'fechaMenorQue':
					retorno = !(date1 < date2) ? validatorField : null;
					break;
				case 'fechaMenorOIgualQue':
					retorno = !(date1 <= date2) ? validatorField : null;
					break;
				case 'fechaMayorQue':
					retorno = !(date1 > date2) ? validatorField : null;
					break;
				case 'fechaMayorOIgualQue':
					retorno = !(date1 >= date2) ? validatorField : null;
					break;
			}
			return retorno;
		};
	}

	static fechaMenorOIgualQue(dateField1: string, dateField2: string) {
		return this.generalValidacionFechas(dateField1, dateField2, { fechaMenorOIgualQue: true });
	}
	static fechaMenorQue(dateField1: string, dateField2: string) {
		return this.generalValidacionFechas(dateField1, dateField2, { fechaMenorQue: true });
	}
	static fechaMayorQue(dateField1: string, dateField2: string) {
		return this.generalValidacionFechas(dateField1, dateField2, { fechaMayorQue: true });
	}
	static fechaMayorOIgualQue(dateField1: string, dateField2: string) {
		return this.generalValidacionFechas(dateField1, dateField2, { fechaMayorOIgualQue: true });
	}

	/* 	static fechaMayorQue(dateField1: string, dateField2: string) {
		return (c: AbstractControl): { [key: string]: boolean } | null => {
			let date1 = c.get(dateField1).value;
			let date2 = c.get(dateField2).value;
			if (date1 == null || date2 == null) {
				return null;
			}

			if (!(date1 instanceof Date)) {
				const date1Parts = String(date1).split('/');
				date1 = new Date(+date1Parts[2], +date1Parts[1] - 1, +date1Parts[0]);
			}
			if (!(date2 instanceof Date)) {
				const date2Parts = String(date2).split('/');
				date2 = new Date(+date2Parts[2], +date2Parts[1] - 1, +date2Parts[0]);
			}

			if (date1 <= date2) {
				console.log('Fecha1 menor o igual a Fecha2');
				return { fechaMenorOIgual: true };
			}
			return null;
		};
	} */

	/* static fechaMenorQue(dateField1: string, dateField2: string) {
		return (c: AbstractControl): { [key: string]: boolean } | null => {
			let date1 = c.get(dateField1).value;
			let date2 = c.get(dateField2).value;
			if (date1 == null || date2 == null) {
				return null;
			}

			if (!(date1 instanceof Date)) {
				const date1Parts = String(date1).split('/');
				date1 = new Date(+date1Parts[2], +date1Parts[1] - 1, +date1Parts[0]);
			}
			if (!(date2 instanceof Date)) {
				const date2Parts = String(date2).split('/');
				date2 = new Date(+date2Parts[2], +date2Parts[1] - 1, +date2Parts[0]);
			}

			if (date1 >= date2) {
				console.log('Fecha1 mayor o igual a Fecha2');
				return { fechaMayorOIgual: true };
			}
			return null;
		};
	} */
}
