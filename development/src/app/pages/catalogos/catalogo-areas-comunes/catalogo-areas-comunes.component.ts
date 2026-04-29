import { Component, OnInit, isDevMode } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';

import { environment } from '../../../../environments/environment';
import * as hlpApp from '../../../helpers/app-helper';
import * as hlpSwal from '../../../helpers/sweetalert2-helper';
import * as hlpPrimeNGTable from '../../../helpers/primeng-table-helper';
import { AreaComunModel, AreaComunResumenModel } from '../../../models/area-comun.model';
import { AreasComunesService } from '../../../services/areas-comunes.service';

@Component({
	selector: 'app-catalogo-areas-comunes',
	templateUrl: './catalogo-areas-comunes.component.html',
	styleUrls: ['./catalogo-areas-comunes.component.css'],
})
export class CatalogoAreasComunesComponent implements OnInit {
	appData = environment;
	hlpApp = hlpApp;
	hlpPrimeNGTable = hlpPrimeNGTable;
	isDevelopment = isDevMode;

	// Tabla Áreas comunes
	// Columnas de la tabla
	AreasComunesCols: any[] = [
		{ header: 'Area' },
		{ header: 'Descripción' },
		{ header: 'Importe por hora', width: '80px' },
		// Botones de acción
		{ textAlign: 'center', width: '90px' },
	];
	AreasComunesFilter: any[] = ['nombre'];

	AreasComunes: AreaComunResumenModel[] = [];
	AreaComun: AreaComunModel;

	frmAreaComun: FormGroup;
	mostrarDialogoEdicionAreaComun: boolean = false;

	constructor(private formBuilder: FormBuilder, private areasComunesService: AreasComunesService) {}

	ngOnInit(): void {
		this.onActualizarInformacion();
	}

	private OrdenarAreasComunes(areasComunes: AreaComunResumenModel[]) {
		return areasComunes.sort((a, b) => (a.nombre > b.nombre ? 1 : -1));
	}

	public onActualizarInformacion() {
		this.AreasComunes = [];

		hlpSwal.Cargando();

		this.areasComunesService
			.Listar()
			.toPromise()
			.then((r) => {
				this.AreasComunes = this.OrdenarAreasComunes(r['areas_comunes']);
			})
			.catch(async (e) => {
				await hlpSwal.Error(e);
			})
			.finally(() => {
				hlpSwal.Cerrar();
			});
	}

	async onAreaComunEditar(idAreaComun: number = 0) {
		hlpSwal.Cargando();

		if (idAreaComun > 0) {
			this.AreaComun = await this.areasComunesService
				.ListarAreaComun(idAreaComun)
				.toPromise()
				.then((r) => r['area_comun'])
				.catch(async (e) => {
					await hlpSwal.Error(e).then(() => null);
				});
			if (this.AreaComun == null) return;
		} else {
			this.AreaComun = new AreaComunModel();
		}
		hlpSwal.Cerrar();

		try {
			this.frmAreaComun = this.formBuilder.group(this.AreaComun);
			this.frmAreaComun
				.get('nombre')
				.setValidators([Validators.required, Validators.minLength(3), Validators.maxLength(150)]);
			this.frmAreaComun.get('descripcion').setValidators([Validators.maxLength(65500)]);
			this.frmAreaComun.get('importe_hora').setValidators([Validators.required, Validators.min(0.1)]);

			this.mostrarDialogoEdicionAreaComun = true;
		} catch (e) {
			hlpSwal.Error(e);
		}
	}

	onAreaComunGuardar() {
		if (!this.frmAreaComun.valid) {
			this.frmAreaComun.markAllAsTouched();
			hlpSwal.Error('Se detectaron errores en la información solicitada.');
			return;
		}

		let areaComun = this.frmAreaComun.value;

		hlpSwal
			.Pregunta({
				html: '¿Deseas guardar la información?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.areasComunesService.Guardar(areaComun).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err && r.value.area_comun) {
					const c = r.value.area_comun;
					if (areaComun.id_area_comun == 0) {
						this.AreasComunes.push(c);
					} else {
						this.AreasComunes = this.AreasComunes.map((C) => (C.id_area_comun === c.id_area_comun ? c : C));
					}
					this.AreasComunes = this.OrdenarAreasComunes(this.AreasComunes);
					hlpSwal.ExitoToast(r.value.msg);
					this.mostrarDialogoEdicionAreaComun = false;
				}
			});
	}

	onAreaComunCancelar() {
		this.mostrarDialogoEdicionAreaComun = false;
	}

	onAreaComunAlternarEstatus(AreaComun: AreaComunModel) {
		hlpSwal
			.Pregunta({
				html: '¿Deseas ' + (AreaComun.estatus == 1 ? 'des' : '') + 'habilitar el Área común?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.areasComunesService.AlternarEstatus(AreaComun.id_area_comun).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err) {
					AreaComun.estatus = AreaComun.estatus == 1 ? 0 : 1;
					hlpSwal.ExitoToast(r.value.msg);
				}
			});
	}
}
