import { Pipe, PipeTransform } from '@angular/core';

@Pipe({
	name: 'phoneFormat',
})
export class PhoneFormatPipe implements PipeTransform {
	transform_original(number) {
		number = number.charAt(0) != 0 ? '0' + number : '' + number;

		let newStr = '';
		let i = 0;

		for (; i < Math.floor(number.length / 2) - 1; i++) {
			newStr = newStr + number.substr(i * 2, 2) + '-';
		}

		return newStr + number.substr(i * 2);
	}

	transform(phoneNumber: string) {
		if (!phoneNumber) {
			return;
		}
		let mainIndex = phoneNumber.length > 10 ? 2 : 0;
		return (
			(phoneNumber.length > 10 ? phoneNumber.substring(0, 2) + ' ' : '') +
			'(' +
			phoneNumber.substring(mainIndex, 4) +
			') ' +
			phoneNumber.substring(mainIndex + 4, 6) +
			'-' +
			phoneNumber.substring(mainIndex + 6, phoneNumber.length)
		);
	}
}
