import { Component, OnInit, isDevMode } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';

import { environment } from '../../../../environments/environment';
import * as hlpApp from '../../../helpers/app-helper';
import * as hlpSwal from '../../../helpers/sweetalert2-helper';
import * as hlpPrimeNGTable from '../../../helpers/primeng-table-helper';
import { TipoMiembroModel } from '../../../models/tipo-miembro.model';
import { TiposMiembrosService } from '../../../services/tipos-miembros.service';

@Component({
	selector: 'app-catalogo-tipos-miembros',
	templateUrl: './catalogo-tipos-miembros.component.html',
	styleUrls: ['./catalogo-tipos-miembros.component.css'],
})
export class CatalogoTiposMiembrosComponent implements OnInit {
	appData = environment;
	hlpApp = hlpApp;
	hlpPrimeNGTable = hlpPrimeNGTable;
	isDevelopment = isDevMode;

	// Tabla Tipos de Miembros
	// Columnas de la tabla
	TiposMiembrosCols: any[] = [
		{ header: 'Tipo' },
		{ header: 'Colaborador', width: '80px' },
		{ header: 'Estatus', width: '70px' },
		// Botones de acción
		{ textAlign: 'center', width: '50px' },
	];
	TiposMiembrosFilter: any[] = ['tipo_miembro'];

	TiposMiembros: TipoMiembroModel[] = [];
	TipoMiembro: TipoMiembroModel;

	frmTipoMiembro: FormGroup;
	mostrarDialogoEdicionTipoMiembro: boolean = false;

	constructor(private formBuilder: FormBuilder, private tiposMiembrosService: TiposMiembrosService) {}

	ngOnInit(): void {
		this.onActualizarInformacion();
	}

	private OrdenarTiposMiembros(tiposMiembros: TipoMiembroModel[]) {
		return tiposMiembros.sort((a, b) => (a.tipo_miembro > b.tipo_miembro ? 1 : -1));
	}

	public onActualizarInformacion() {
		this.TiposMiembros = [];

		hlpSwal.Cargando();

		this.tiposMiembrosService
			.Listar()
			.toPromise()
			.then((r) => {
				this.TiposMiembros = this.OrdenarTiposMiembros(r['tipos_miembros']);
			})
			.catch(async (e) => {
				await hlpSwal.Error(e);
			})
			.finally(() => {
				hlpSwal.Cerrar();
			});
	}

	async onTipoMiembroEditar(idUsuario: number = 0) {
		hlpSwal.Cargando();

		if (idUsuario > 0) {
			this.TipoMiembro = await this.tiposMiembrosService
				.ListarTipoMiembro(idUsuario)
				.toPromise()
				.then((r) => r['tipos_miembros'])
				.catch(async (e) => {
					await hlpSwal.Error(e).then(() => null);
				});
			if (this.TipoMiembro == null) return;
			this.TipoMiembro.es_colaborador = this.TipoMiembro.es_colaborador == 1 ? true : false;
		} else {
			this.TipoMiembro = new TipoMiembroModel();
		}
		hlpSwal.Cerrar();

		try {
			this.frmTipoMiembro = this.formBuilder.group(this.TipoMiembro);
			this.frmTipoMiembro
				.get('tipo_miembro')
				.setValidators([Validators.required, Validators.minLength(3), Validators.maxLength(50)]);
			this.frmTipoMiembro.get('es_colaborador').setValidators([Validators.required]);

			this.mostrarDialogoEdicionTipoMiembro = true;
		} catch (e) {
			hlpSwal.Error(e);
		}
	}

	onTipoMiembroGuardar() {
		if (!this.frmTipoMiembro.valid) {
			this.frmTipoMiembro.markAllAsTouched();
			hlpSwal.Error('Se detectaron errores en la información solicitada.');
			return;
		}

		let tipoMiembro = this.frmTipoMiembro.value;
		tipoMiembro.es_colaborador = tipoMiembro.es_colaborador ? 1 : 0;

		hlpSwal
			.Pregunta({
				html: '¿Deseas guardar la información?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.tiposMiembrosService.Guardar(tipoMiembro).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err && r.value.tipo_miembro) {
					const c = r.value.tipo_miembro;
					if (tipoMiembro.id_usuario == 0) {
						this.TiposMiembros.push(c);
					} else {
						this.TiposMiembros = this.TiposMiembros.map((C) => (C.id_tipo_miembro === c.id_tipo_miembro ? c : C));
					}
					this.TiposMiembros = this.OrdenarTiposMiembros(this.TiposMiembros);
					hlpSwal.ExitoToast(r.value.msg);
					this.mostrarDialogoEdicionTipoMiembro = false;
				}
			});
	}

	onTipoMiembroCancelar() {
		this.mostrarDialogoEdicionTipoMiembro = false;
	}

	onTipoMiembroAlternarEstatus(TipoMiembro: TipoMiembroModel) {
		hlpSwal
			.Pregunta({
				html: '¿Deseas ' + (TipoMiembro.estatus == 1 ? 'des' : '') + 'habilitar el Tipo de Miembro?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.tiposMiembrosService.AlternarEstatus(TipoMiembro.id_tipo_miembro).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err) {
					TipoMiembro.estatus = TipoMiembro.estatus == 1 ? 0 : 1;
					hlpSwal.ExitoToast(r.value.msg);
				}
			});
	}
}
