import { Table } from 'primeng/table';

export function scrollTop(tTabla: Table) {
	const t = tTabla.containerViewChild.nativeElement.getElementsByClassName('p-datatable-scrollable-body')[0];
	if (t) {
		t.scrollTop = 0;
		t.scrollLeft = 0;
	}
}

export function reset(tTabla: Table, elementIdClear: string = undefined) {
	if (!tTabla) return;

	if (tTabla.filters.global) {
		delete tTabla.filters.global;
	}

	// tTabla.expandedRows = [];
	tTabla.reset();
	tTabla.clear();

	scrollTop(tTabla);

	console.log(elementIdClear);

	const txtInputFilter = document.getElementById(
		elementIdClear ? elementIdClear : 'txtGlobalFilter',
	) as HTMLInputElement;
	if (txtInputFilter) {
		txtInputFilter.value = '';
		setTimeout(() => {
			txtInputFilter.focus();
		}, 5);
	}
}
