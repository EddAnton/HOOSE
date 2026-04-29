import { Component, OnInit, isDevMode } from '@angular/core';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';
import { DomSanitizer } from '@angular/platform-browser';

import { environment } from '../../../environments/environment';
import * as hlpApp from '../../helpers/app-helper';
import * as hlpSwal from '../../helpers/sweetalert2-helper';
import * as hlpPrimeNGTable from '../../helpers/primeng-table-helper';
import { SesionUsuarioService } from '../../services/sesion-usuario.service';
import { GastosMantenimientoService } from '../../services/gastos-mantenimiento.service';
import {
	GastoMantenimientoResumenModel,
	GastoMantenimientoModel,
	FilaTotalesModel,
} from '../../models/gasto-mantenimiento.model';
import { GastoFijoModel } from '../../models/gasto-fijo.model';
import { GastosFijosService } from '../../services/gastos-fijos.service';
import { FondoMonetarioResumenModel } from '../../models/fondo-monetario.model';
import { FondosMonetariosService } from '../../services/fondos-monetarios.service';

@Component({
	selector: 'app-gastos-mantenimiento',
	templateUrl: './gastos-mantenimiento.component.html',
	styleUrls: ['./gastos-mantenimiento.component.css'],
})
export class GastosMantenimientoComponent implements OnInit {
	appData = environment;
	hlpApp = hlpApp;
	hlpPrimeNGTable = hlpPrimeNGTable;
	isDevelopment = isDevMode;

	// Tabla Gastos de Mantenimiento
	// Columnas de la tabla
	GastosMantenimientoCols: any[] = [
		{ header: 'Concepto' },
		{ header: 'Fecha' },
		{ header: 'Importe' },
		{ header: 'Deducible' },
		// Botones de acción
		{ textAlign: 'center', width: '170px' },
	];
	GastosMantenimientoFilter: any[] = ['concepto', 'fecha', 'importe'];

	GastosMantenimiento: GastoMantenimientoResumenModel[] = [];
	GastosFijos: GastoFijoModel[] = [];
	GastosMantenimientoIDsFiltered: any[] = [];
	FilaTotales: FilaTotalesModel;
	GastoMantenimiento: GastoMantenimientoModel;
	FondosMonetarios: FondoMonetarioResumenModel[] = [];
	fechaRegistroLimite: Date = new Date();

	frmGastoMantenimiento: FormGroup;
	mostrarDialogoEdicionGastoMantenimiento: boolean = false;
	srcComprobante: string = null;
	bComprobanteBorrar: boolean = false;

	mostrarDialogoComprobanteGastoMantenimiento: boolean = false;
	mostrarDialogoDetallesGastoMantenimiento: boolean = false;

	permitirAgregarEditar: boolean = false;

	constructor(
		private formBuilder: FormBuilder,
		private sanitizer: DomSanitizer,
		private sesionUsuarioService: SesionUsuarioService,
		private gastosMantenimientoService: GastosMantenimientoService,
		private gastosFijosService: GastosFijosService,
		private fondosMonetariosService: FondosMonetariosService,
	) {}

	ngOnInit(): void {
		this.permitirAgregarEditar = [1, 2].includes(this.sesionUsuarioService.obtenerIDPerfilUsuario());
		this.onActualizarInformacion();
	}

	private OrdenarGastosMantenimiento(gastosMantenimiento: GastoMantenimientoResumenModel[]) {
		return gastosMantenimiento.sort((a, b) =>
			hlpApp.formatDateToMySQL(a.fecha) + a.concepto < hlpApp.formatDateToMySQL(b.fecha) + b.concepto ? 1 : -1,
		);
	}

	private onCalcularFilaTotales(registros: GastoMantenimientoResumenModel[]) {
		this.FilaTotales = new FilaTotalesModel();
		if (registros.length < 1) {
			return;
		}

		this.FilaTotales.total = registros.reduce((a, c) => {
			return a + +c.importe;
		}, 0);
	}

	public onActualizarInformacion() {
		this.FondosMonetarios = [];
		this.GastosFijos = [];
		this.GastosMantenimiento = [];
		this.GastosMantenimientoIDsFiltered = [];

		hlpSwal.Cargando();

		this.fondosMonetariosService
			.ListarActivos()
			.toPromise()
			.then((r) => {
				this.FondosMonetarios = r['fondos_monetarios'];
			})
			.catch(async (e) => {
				await hlpSwal.Error(e);
			});

		this.gastosFijosService
			.ListarActivos()
			.toPromise()
			.then((r) => {
				this.GastosFijos = r['gastos_fijos'];
			})
			.catch(async (e) => {
				await hlpSwal.Error(e);
			});

		this.gastosMantenimientoService
			.ListarActivos()
			.toPromise()
			.then((r) => {
				this.GastosMantenimiento = this.OrdenarGastosMantenimiento(r['gastos_mantenimiento']);
				this.onCalcularFilaTotales(this.GastosMantenimiento);
			})
			.catch(async (e) => {
				await hlpSwal.Error(e);
			})
			.finally(() => {
				hlpSwal.Cerrar();
			});
	}

	public onFilter(e) {
		this.GastosMantenimientoIDsFiltered = e.filteredValue.map((f) => f.id_gasto_mantenimiento);
		this.onCalcularFilaTotales(e.filteredValue);
	}

	public onFilterReset(t) {
		this.GastosMantenimientoIDsFiltered = [];
		hlpPrimeNGTable.reset(t);
		this.onCalcularFilaTotales(this.GastosMantenimiento);
	}

	async onGastoMantenimientoEditar(idGastoMantenimiento: number = 0) {
		if (idGastoMantenimiento > 0) {
			hlpSwal.Cargando();
			this.GastoMantenimiento = await this.gastosMantenimientoService
				.ListarGastoMantenimiento(idGastoMantenimiento)
				.toPromise()
				.then((r) => r['gasto_mantenimiento'])
				.catch(async (e) => {
					await hlpSwal.Error(e).then(() => null);
				})
				.finally(() => hlpSwal.Cerrar());
			if (this.GastoMantenimiento == null) return;
			this.GastoMantenimiento.es_deducible = this.GastoMantenimiento.es_deducible == 1 ? true : false;
		} else {
			this.GastoMantenimiento = new GastoMantenimientoModel();
		}

		try {
			this.GastoMantenimiento.fecha =
				idGastoMantenimiento > 0 ? new Date(this.GastoMantenimiento.fecha + 'T00:00:00') : new Date();
			this.srcComprobante = this.GastoMantenimiento.comprobante
				? environment.urlBackendGastosMantenimientoFiles +
				  this.sesionUsuarioService.obtenerIDCondominioUsuario() +
				  '/' +
				  this.GastoMantenimiento.comprobante
				: null;

			this.frmGastoMantenimiento = this.formBuilder.group(this.GastoMantenimiento);
			this.frmGastoMantenimiento.get('id_gasto_fijo').setValidators([Validators.required, Validators.min(0)]);
			// this.frmGastoMantenimiento.get('concepto').setValidators([Validators.minLength(3), Validators.maxLength(150)]);
			this.frmGastoMantenimiento
				.get('descripcion')
				.setValidators([Validators.minLength(0), Validators.maxLength(65500)]);
			this.frmGastoMantenimiento.get('importe').setValidators([Validators.required, Validators.min(0.1)]);
			this.frmGastoMantenimiento.get('fecha').setValidators([Validators.required]);
			this.frmGastoMantenimiento.get('es_deducible').setValidators([Validators.required]);
			this.frmGastoMantenimiento.get('id_fondo_monetario').setValidators([Validators.required, Validators.min(1)]);
			this.frmGastoMantenimiento.addControl('archivo_comprobante', new FormControl());

			// this.frmGastoMantenimiento.updateValueAndValidity();
			this.onGastoFijoSeleccionado(this.GastoMantenimiento.id_gasto_fijo);

			this.mostrarDialogoEdicionGastoMantenimiento = true;
		} catch (e) {
			hlpSwal.Error(e);
		}
	}

	public onGastoFijoSeleccionado(idGastoFijo: number = 0) {
		if (!this.mostrarDialogoEdicionGastoMantenimiento) {
			return;
		}
		if (idGastoFijo != 0) {
			this.frmGastoMantenimiento.get('concepto').clearValidators();
			this.frmGastoMantenimiento.get('concepto').setValue(null);
			this.frmGastoMantenimiento.get('concepto').disable();
		} else {
			this.frmGastoMantenimiento
				.get('concepto')
				.setValidators([Validators.required, Validators.minLength(3), Validators.maxLength(150)]);
			this.frmGastoMantenimiento.get('concepto').setValue(null);
			this.frmGastoMantenimiento.get('concepto').enable();
		}

		this.frmGastoMantenimiento.updateValueAndValidity();
	}

	onComprobanteSeleccionado(event) {
		if (event.target.files.length != 1) return;
		let file: any;
		file = event.target.files[0];

		this.frmGastoMantenimiento.patchValue({ archivo_comprobante: file });
		this.frmGastoMantenimiento.get('archivo_comprobante').updateValueAndValidity();

		let reader = new FileReader();

		reader.onload = (e) => {
			file.src = reader.result;
			this.srcComprobante = file.src;
		};
		reader.readAsDataURL(file);
	}

	onComprobanteSeleccionadoCancelar() {
		(<HTMLInputElement>document.getElementById('txtComprobanteArchivo')).value = '';
		this.frmGastoMantenimiento.get('archivo_comprobante').setValue(null);

		this.srcComprobante = this.frmGastoMantenimiento.get('comprobante').value
			? environment.urlBackendGastosMantenimientoFiles +
			  this.sesionUsuarioService.obtenerIDCondominioUsuario() +
			  '/' +
			  this.GastoMantenimiento.comprobante
			: null;
		this.bComprobanteBorrar = !this.srcComprobante;
	}

	onComprobanteEliminado() {
		this.frmGastoMantenimiento.get('comprobante').setValue(null);
		this.onComprobanteSeleccionadoCancelar();
	}

	async onComprobanteMostrar(GastoMantenimiento: GastoMantenimientoModel = null) {
		if (GastoMantenimiento != null) {
			this.srcComprobante = GastoMantenimiento.comprobante
				? environment.urlBackendGastosMantenimientoFiles +
				  this.sesionUsuarioService.obtenerIDCondominioUsuario() +
				  '/' +
				  GastoMantenimiento.comprobante
				: null;
		}

		this.mostrarDialogoComprobanteGastoMantenimiento = this.srcComprobante != null;
		if (this.mostrarDialogoComprobanteGastoMantenimiento) {
			hlpSwal.Cargando();
		}
	}

	onComprobanteMostrado() {
		hlpSwal.Cerrar();
	}

	onGastoMantenimientoGuardar() {
		if (!this.frmGastoMantenimiento.valid) {
			this.frmGastoMantenimiento.markAllAsTouched();
			hlpSwal.Error('Se detectaron errores en la información solicitada.');
			return;
		}

		let gastoMantenimiento = this.frmGastoMantenimiento.getRawValue();
		gastoMantenimiento.borrar_comprobante = this.bComprobanteBorrar ? 1 : 0;
		gastoMantenimiento.fecha = hlpApp.formatDateToMySQL(gastoMantenimiento.fecha);
		gastoMantenimiento.es_deducible = gastoMantenimiento.es_deducible ? 1 : 0;
		delete gastoMantenimiento.comprobante;
		delete gastoMantenimiento.archivo_comprobante?.src;
		console.log(gastoMantenimiento);

		hlpSwal
			.Pregunta({
				html: '¿Deseas guardar la información?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.gastosMantenimientoService.Guardar(gastoMantenimiento).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err && r.value.gasto_mantenimiento) {
					const re = r.value.gasto_mantenimiento;
					if (gastoMantenimiento.id_gasto_mantenimiento == 0) {
						this.GastosMantenimiento.push(re);
					} else {
						this.GastosMantenimiento = this.GastosMantenimiento.map((C) =>
							C.id_gasto_mantenimiento === re.id_gasto_mantenimiento ? re : C,
						);
					}
					this.GastosMantenimiento = this.OrdenarGastosMantenimiento(this.GastosMantenimiento);
					this.FilaTotales.total += +gastoMantenimiento.importe - +this.GastoMantenimiento.importe;
					hlpSwal.ExitoToast(r.value.msg);
					this.mostrarDialogoEdicionGastoMantenimiento = false;
				}
			});
	}

	onGastoMantenimientoCancelar() {
		this.mostrarDialogoEdicionGastoMantenimiento = false;
	}

	comprobanteURL() {
		return this.sanitizer.bypassSecurityTrustResourceUrl(this.srcComprobante + '#toolbar=0&view=fitH');
	}

	async onGastoMantenimientoDetalles(idGastoMantenimiento: number = 0) {
		if (idGastoMantenimiento == 0) {
			return;
		}
		hlpSwal.Cargando();
		this.GastoMantenimiento = await this.gastosMantenimientoService
			.ListarGastoMantenimiento(idGastoMantenimiento)
			.toPromise()
			.then((r) => r['gasto_mantenimiento'])
			.catch(async (e) => {
				await hlpSwal.Error(e).then(() => null);
			})
			.finally(() => {
				hlpSwal.Cerrar();
			});

		this.mostrarDialogoDetallesGastoMantenimiento = this.GastoMantenimiento != null;
	}

	onGastoMantenimientoEliminar(GastoMantenimiento: GastoMantenimientoResumenModel = null) {
		if (GastoMantenimiento == null) {
			return;
		}

		hlpSwal
			.Pregunta({
				html: '¿Deseas eliminar el gasto de mantenimiento?<br><p class="pt-2 text-danger fw-bold">ESTE PROCESO ES IRREVERSIBLE.</p>',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.gastosMantenimientoService
							.Eliminar(GastoMantenimiento.id_gasto_mantenimiento)
							.toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err) {
					this.GastosMantenimiento = this.GastosMantenimiento.filter(
						(r: GastoMantenimientoResumenModel) =>
							r.id_gasto_mantenimiento != GastoMantenimiento.id_gasto_mantenimiento,
					);

					this.GastosMantenimientoIDsFiltered = this.GastosMantenimientoIDsFiltered.filter(
						(cm) => cm != GastoMantenimiento.id_gasto_mantenimiento,
					);
					this.FilaTotales.total -= +GastoMantenimiento.importe;
					hlpSwal.ExitoToast(r.value.msg);
				}
			});
	}
}
