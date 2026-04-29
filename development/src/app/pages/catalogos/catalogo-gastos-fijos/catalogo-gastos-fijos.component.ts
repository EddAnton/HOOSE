import { Component, OnInit, isDevMode } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';

import { environment } from '../../../../environments/environment';
import * as hlpApp from '../../../helpers/app-helper';
import * as hlpSwal from '../../../helpers/sweetalert2-helper';
import * as hlpPrimeNGTable from '../../../helpers/primeng-table-helper';
import { GastoFijoModel } from '../../../models/gasto-fijo.model';
import { GastosFijosService } from '../../../services/gastos-fijos.service';

@Component({
	selector: 'app-catalogo-gastos-fijos',
	templateUrl: './catalogo-gastos-fijos.component.html',
	styleUrls: ['./catalogo-gastos-fijos.component.css'],
})
export class CatalogoGastosFijosComponent implements OnInit {
	appData = environment;
	hlpApp = hlpApp;
	hlpPrimeNGTable = hlpPrimeNGTable;
	isDevelopment = isDevMode;

	// Tabla Tipos de Miembros
	// Columnas de la tabla
	GastosFijosCols: any[] = [
		{ header: 'Gasto fijo' },
		{ header: 'Estatus', width: '70px' },
		// Botones de acción
		{ textAlign: 'center', width: '50px' },
	];
	GastosFijosFilter: any[] = ['gasto_fijo'];

	GastosFijos: GastoFijoModel[] = [];
	GastoFijo: GastoFijoModel;

	frmGastoFijo: FormGroup;
	mostrarDialogoEdicionGastoFijo: boolean = false;

	constructor(private formBuilder: FormBuilder, private gastosFijosService: GastosFijosService) {}

	ngOnInit(): void {
		this.onActualizarInformacion();
	}

	private OrdenarGastosFijos(tiposMiembros: GastoFijoModel[]) {
		return tiposMiembros.sort((a, b) => (a.gasto_fijo > b.gasto_fijo ? 1 : -1));
	}

	public onActualizarInformacion() {
		this.GastosFijos = [];

		hlpSwal.Cargando();

		this.gastosFijosService
			.Listar()
			.toPromise()
			.then((r) => {
				this.GastosFijos = this.OrdenarGastosFijos(r['gastos_fijos']);
			})
			.catch(async (e) => {
				await hlpSwal.Error(e);
			})
			.finally(() => {
				hlpSwal.Cerrar();
			});
	}

	async onGastoFijoEditar(idGastoFijo: number = 0) {
		if (idGastoFijo > 0) {
			hlpSwal.Cargando();
			this.GastoFijo = await this.gastosFijosService
				.ListarGastoFijo(idGastoFijo)
				.toPromise()
				.then((r) => r['gasto_fijo'])
				.catch(async (e) => {
					await hlpSwal.Error(e).then(() => null);
				})
				.finally(() => hlpSwal.Cerrar());

			if (this.GastoFijo == null) return;
		} else {
			this.GastoFijo = new GastoFijoModel();
		}

		try {
			this.frmGastoFijo = this.formBuilder.group(this.GastoFijo);
			this.frmGastoFijo
				.get('gasto_fijo')
				.setValidators([Validators.required, Validators.minLength(3), Validators.maxLength(50)]);

			this.mostrarDialogoEdicionGastoFijo = true;
		} catch (e) {
			hlpSwal.Error(e);
		}
	}

	onGastoFijoGuardar() {
		if (!this.frmGastoFijo.valid) {
			this.frmGastoFijo.markAllAsTouched();
			hlpSwal.Error('Se detectaron errores en la información solicitada.');
			return;
		}

		let gastoFijo = this.frmGastoFijo.value;

		hlpSwal
			.Pregunta({
				html: '¿Deseas guardar la información?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.gastosFijosService.Guardar(gastoFijo).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err && r.value.gasto_fijo) {
					const c = r.value.gasto_fijo;
					if (gastoFijo.id_gasto_fijo == 0) {
						this.GastosFijos.push(c);
					} else {
						this.GastosFijos = this.GastosFijos.map((C) => (C.id_gasto_fijo === c.id_gasto_fijo ? c : C));
					}
					this.GastosFijos = this.OrdenarGastosFijos(this.GastosFijos);
					hlpSwal.ExitoToast(r.value.msg);
					this.mostrarDialogoEdicionGastoFijo = false;
				}
			});
	}

	onGastoFijoCancelar() {
		this.mostrarDialogoEdicionGastoFijo = false;
	}

	onGastoFijoAlternarEstatus(GastoFijo: GastoFijoModel) {
		hlpSwal
			.Pregunta({
				html: '¿Deseas ' + (GastoFijo.estatus == 1 ? 'des' : '') + 'habilitar el Gasto fijo?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.gastosFijosService.AlternarEstatus(GastoFijo.id_gasto_fijo).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err) {
					GastoFijo.estatus = GastoFijo.estatus == 1 ? 0 : 1;
					hlpSwal.ExitoToast(r.value.msg);
				}
			});
	}
}
