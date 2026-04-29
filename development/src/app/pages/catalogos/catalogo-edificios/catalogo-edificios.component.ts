import { Component, OnInit, isDevMode } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';

import * as hlpSwal from '../../../helpers/sweetalert2-helper';
import * as hlpApp from '../../../helpers/app-helper';
import * as hlpPrimeNGTable from '../../../helpers/primeng-table-helper';
import { EdificioModel } from '../../../models/edificio.model';
import { EdificiosService } from '../../../services/edificios.service';

@Component({
	selector: 'app-catalogo-edificios',
	templateUrl: './catalogo-edificios.component.html',
	styleUrls: ['./catalogo-edificios.component.css'],
})
export class CatalogoEdificiosComponent implements OnInit {
	hlpApp = hlpApp;
	hlpPrimeNGTable = hlpPrimeNGTable;
	isDevelopment = isDevMode;

	// Tabla Edificio
	// Columnas de la tabla
	EdificiosCols: any[] = [
		{ header: 'Edificio' },
		{ header: 'Estatus', width: '70px' },
		// Botones de acción
		{ textAlign: 'center', width: '50px' },
	];
	EdificiosFilter: any[] = ['edificio'];

	Edificios: EdificioModel[] = [];
	Edificio: EdificioModel;
	frmEdificio: FormGroup;
	mostrarDialogoEdicionEdificio: boolean = false;

	constructor(private formBuilder: FormBuilder, private edificiosService: EdificiosService) {}

	ngOnInit(): void {
		this.onActualizarInformacion();
	}

	private OrdenarEdificios(edificio: EdificioModel[]) {
		return edificio.sort((a, b) => (a.edificio > b.edificio ? 1 : -1));
	}

	public onActualizarInformacion() {
		this.Edificios = [];

		hlpSwal.Cargando();

		this.edificiosService
			.Listar()
			.toPromise()
			.then((r) => {
				this.Edificios = this.OrdenarEdificios(r['edificios']);
			})
			.catch(async (e) => {
				await hlpSwal.Error(e);
			})
			.finally(() => {
				hlpSwal.Cerrar();
			});
	}

	async onEdificioEditar(idEdificio: number = 0) {
		if (idEdificio > 0) {
			hlpSwal.Cargando();
			this.Edificio = await this.edificiosService
				.ListarEdificio(idEdificio)
				.toPromise()
				.then((r) => r['edificios'])
				.catch(async (e) => {
					await hlpSwal.Error(e).then(() => null);
				})
				.finally(() => {
					hlpSwal.Cerrar();
				});
			if (this.Edificio == null) return;
		} else {
			this.Edificio = new EdificioModel();
		}

		try {
			this.frmEdificio = this.formBuilder.group(this.Edificio);
			this.frmEdificio.get('edificio').setValidators([Validators.minLength(3), Validators.maxLength(150)]);
			setTimeout(() => {
				document.getElementById('txtEdificio').focus();
			}, 500);
			this.frmEdificio.updateValueAndValidity();
			this.mostrarDialogoEdicionEdificio = true;
		} catch (e) {
			hlpSwal.Error(e);
		}
	}

	onEdificioGuardar() {
		if (!this.frmEdificio.valid) {
			this.frmEdificio.markAllAsTouched();
			hlpSwal.Error('Se detectaron errores en la información solicitada.');
			return;
		}

		let edificio = this.frmEdificio.value;

		hlpSwal
			.Pregunta({
				html: '¿Deseas guardar la información?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.edificiosService.Guardar(edificio).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err && r.value.edificio) {
					const c = r.value.edificio;
					if (edificio.id_edificio == 0) {
						this.Edificios.push(c);
					} else {
						this.Edificios = this.Edificios.map((C) => (C.id_edificio === c.id_edificio ? c : C));
					}
					this.Edificios = this.OrdenarEdificios(this.Edificios);
					hlpSwal.ExitoToast(r.value.msg);
					this.mostrarDialogoEdicionEdificio = false;
				}
			});
	}

	onEdificioCancelar() {
		this.mostrarDialogoEdicionEdificio = false;
	}

	/* onEdificioDeshabilitar(edificio: EdificioModel) {
		if (edificio.estatus == 0) {
			return;
		}

		hlpSwal
			.Pregunta({
				html: '¿Deseas eliminar el Edificio?<br /><p class="text-danger"><b>ESTE PROCESO ES IRREVERSIBLE</b></p>',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.edificiosService.Deshabilitar(edificio.id_edificio).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err) {
					this.Edificios = this.Edificios.filter(function (e) {
						return e.id_edificio !== edificio.id_edificio;
					});
					hlpSwal.ExitoToast(r.value.msg);
				}
			});
	} */

	onEdificioAlternarEstatus(edificio: EdificioModel) {
		hlpSwal
			.Pregunta({
				html: '¿Deseas ' + (edificio.estatus == 1 ? 'des' : '') + 'habilitar el Edificio?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.edificiosService.AlternarEstatus(edificio.id_edificio).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err) {
					edificio.estatus = edificio.estatus == 1 ? 0 : 1;
					hlpSwal.ExitoToast(r.value.msg);
				}
			});
	}
}
