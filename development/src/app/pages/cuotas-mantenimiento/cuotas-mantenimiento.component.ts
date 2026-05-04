import { Component, OnInit, isDevMode } from '@angular/core';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';
import alasql from 'alasql';

import { environment } from '../../../environments/environment';
import * as hlpApp from '../../helpers/app-helper';
import * as hlpSwal from '../../helpers/sweetalert2-helper';
import * as hlpPrimeNGTable from '../../helpers/primeng-table-helper';

import {
	CuotaMantenimientoMasivaModel,
	CuotaMantenimientoModel,
	CuotaMantenimientoRegistrarPagoModel,
	CuotaMantenimientoResumenModel,
	FilaTotalesModel,
} from '../../models/cuota-mantenimiento.model';
import { UnidadParaRecaudacionesModel } from '../../models/unidad.model';
import { EdificioModel } from '../../models/edificio.model';
import { EstatusRecaudacionModel } from '../../models/estatus-recaudacion.model';
import { FormaPagoModel } from '../../models/forma-pago.model';
import { CuotasMantenimientoService } from 'src/app/services/cuotas-mantenimiento.service';
import { UnidadesService } from 'src/app/services/unidades.service';
import { EstatusRecaudacionesService } from 'src/app/services/estatus-recaudaciones.service';
import { FormasPagoService } from 'src/app/services/formas-pago.service';
import { SesionUsuarioService } from '../../services/sesion-usuario.service';
import { FondosMonetariosService } from 'src/app/services/fondos-monetarios.service';
import { FondoMonetarioResumenModel } from 'src/app/models/fondo-monetario.model';
// import { X } from 'chart.js/dist/chunks/helpers.core';

@Component({
	selector: 'app-cuotas-mantenimiento',
	templateUrl: './cuotas-mantenimiento.component.html',
	styleUrls: ['./cuotas-mantenimiento.component.css'],
})
export class CuotasMantenimientoComponent implements OnInit {
	appData = environment;
	hlpApp = hlpApp;
	hlpPrimeNGTable = hlpPrimeNGTable;
	isDevelopment = isDevMode;

	// Tabla Cuotas Mantenimiento
	// Columnas de la tabla
	/* 	CuotasMantenimientoCols: any[] = [
		{ header: 'Folio', width: '70px' },
		{ header: 'Edificio', width: '200px' },
		{ header: 'Unidad', width: '200px' },
		{ header: '¿Quién paga?', width: '150px' },
		{ header: 'Usuario paga', width: '150px' },
		{ header: 'Año', width: '70px' },
		{ header: 'Mes', width: '100px' },
		{ header: 'Pagado', width: '120px' },
		{ header: 'Saldo', width: '120px' },
		{ header: 'Total', width: '120px' },
		{ header: 'Estatus', width: '130px' },
		// Botones de acción
		{ textAlign: 'center', width: '130px' },
	]; */
	CuotasMantenimientoCols: any[] = [
		{ header: 'Folio' },
		{ header: 'Edificio' },
		{ header: 'Unidad' },
		{ header: '¿Quién paga?' },
		{ header: 'Usuario paga' },
		{ header: 'Año' },
		{ header: 'Mes' },
		{ header: 'Pagado' },
		{ header: 'Saldo' },
		{ header: 'Total' },
		{ header: 'Estatus' },
		// Botones de acción
		{ textAlign: 'center' },
	];
	CuotasMantenimientoFilter: any[] = [
		'folio',
		'edificio',
		'unidad',
		'perfil_usuario_paga',
		'usuario_paga',
		'anio',
		'mes',
		'estatus_recaudacion',
	];

	// Tabla Cuotas Mantenimiento
	// Columnas de la tabla
	CuotaMantenimientoPagosCols: any[] = [
		{ header: 'Fecha', width: '150px' },
		{ header: 'Importe', width: '120px' },
		{ header: 'Forma pago' },
		{ header: 'Referencia' },
		{ header: 'Fondo afectado' },
		// Botones de acción
		{ textAlign: 'center' },
	];

	CuotasMantenimiento: CuotaMantenimientoResumenModel[] = [];
	CuotasMantenimientoIDsFiltered: any[] = [];
	FilaTotales: FilaTotalesModel;
CuotasMantenimientoFiltradas: any[] = [];

// Filtros
filtroAnio: number = null;
filtroMes: number = null;
filtroEstatus: number = null;

aniosDisponibles: any[] = [];
mesesDisponibles: any[] = [
  { label: 'Enero', value: 1 }, { label: 'Febrero', value: 2 },
  { label: 'Marzo', value: 3 }, { label: 'Abril', value: 4 },
  { label: 'Mayo', value: 5 }, { label: 'Junio', value: 6 },
  { label: 'Julio', value: 7 }, { label: 'Agosto', value: 8 },
  { label: 'Septiembre', value: 9 }, { label: 'Octubre', value: 10 },
  { label: 'Noviembre', value: 11 }, { label: 'Diciembre', value: 12 },
];
estatusDisponibles: any[] = [
  { label: 'Pendiente pago', value: 1 },
  { label: 'Saldo pendiente', value: 2 },
  { label: 'Pagado', value: 3 },
];
	CuotaMantenimiento: CuotaMantenimientoModel;
	CuotaMantenimientoRegistrarPago: CuotaMantenimientoRegistrarPagoModel;
	Unidades: UnidadParaRecaudacionesModel[] = [];
	UnidadesEdificio: UnidadParaRecaudacionesModel[] = [];
	Edificios: EdificioModel[] = [];
	EstatusRecaudaciones: EstatusRecaudacionModel[] = [];
	FormasPago: FormaPagoModel[] = [];
	FondosMonetarios: FondoMonetarioResumenModel[] = [];
	fechaPagoLimite: Date = new Date();

	frmCuotaMantenimiento: FormGroup;
	frmCuotaMantenimientoMasiva: FormGroup;
	frmCuotaMantenimientoRegistrarPago: FormGroup;
	mostrarDialogoEdicionCuotaMantenimiento: boolean = false;
	mostrarDialogoCuotaMantenimientoMasiva: boolean = false;
	// puedeGenerarCuotaMantenimientoMasiva: boolean = false;
	totalGeneracionMasiva: number = undefined;
	mostrarDialogoRegistrarPagoCuotaMantenimiento: boolean = false;
	mostrarDialogoReciboPagoCuotaMantenimiento: boolean = false;
	permitirAgregarEditar: boolean = false;

	constructor(
		private formBuilder: FormBuilder,
		private sesionUsuarioService: SesionUsuarioService,
		private cuotasMantenimientoService: CuotasMantenimientoService,
		private unidadesService: UnidadesService,
		private estatusRecaudacionesService: EstatusRecaudacionesService,
		private formasPagoService: FormasPagoService,
		private fondosMonetariosService: FondosMonetariosService,
	) {}

	ngOnInit(): void {
		this.permitirAgregarEditar = [1, 2].includes(this.sesionUsuarioService.obtenerIDPerfilUsuario());
		this.onActualizarInformacion();
	}

	public aplicarFiltros() {
    let filtradas = this.CuotasMantenimiento;
    if (this.filtroAnio) filtradas = filtradas.filter(c => c.anio.toString() === this.filtroAnio.toString());
    if (this.filtroMes) filtradas = filtradas.filter(c => Number(c.mes) === this.filtroMes);
    if (this.filtroEstatus) filtradas = filtradas.filter(c => Number(c.id_estatus_recaudacion) === this.filtroEstatus);
    this.CuotasMantenimientoFiltradas = filtradas;
    this.onCalcularFilaTotales(this.CuotasMantenimientoFiltradas);
  }

  public limpiarFiltros() {
    this.filtroAnio = null;
    this.filtroMes = null;
    this.filtroEstatus = null;
    this.aplicarFiltros();
  }

  private OrdenarCuotasMantenimiento(recaudaciones: CuotaMantenimientoResumenModel[]) {
		return recaudaciones.sort((a, b) =>
			(Number(a.id_estatus_recaudacion) * -1).toString() +
				(9999 - Number(a.anio)).toString() +
				(99 - Number(a.mes)).toString() +
				a.edificio +
				a.unidad >
			(Number(b.id_estatus_recaudacion) * -1).toString() +
				(9999 - Number(b.anio)).toString() +
				(99 - Number(b.mes)).toString() +
				b.edificio +
				b.unidad
				? 1
				: -1,
		);
	}

	private onCalcularFilaTotales(registros: CuotaMantenimientoResumenModel[]) {
		this.FilaTotales = new FilaTotalesModel();
		if (registros.length < 1) {
			return;
		}
		this.FilaTotales.pagado = registros.reduce((a, c) => {
			return a + +c.pagado;
		}, 0);
		this.FilaTotales.saldo = registros.reduce((a, c) => {
			return a + +c.saldo;
		}, 0);
		this.FilaTotales.total = registros.reduce((a, c) => {
			return a + +c.total;
		}, 0);
	}

	private FiltrarUnidadesEdificio(idEdificio: number = 0) {
		this.UnidadesEdificio = this.UnidadesEdificio = this.Unidades.filter((u) => u.id_edificio == idEdificio);
	}

	private onCalcularTotalSaldo() {
		const total =
			Number(this.frmCuotaMantenimiento.get('ordinaria').value) +
			Number(this.frmCuotaMantenimiento.get('extraordinaria').value) +
			Number(this.frmCuotaMantenimiento.get('otros_servicios').value) -
			Number(this.frmCuotaMantenimiento.get('descuento_pronto_pago').value);
		const saldo = total - Number(this.frmCuotaMantenimiento.get('importe').value);

		this.frmCuotaMantenimiento.get('total').setValue(total);
		this.frmCuotaMantenimiento.get('saldo').setValue(saldo);
	}

	public onActualizarInformacion() {
		this.CuotasMantenimientoIDsFiltered = [];
		this.CuotasMantenimiento = [];
		this.Unidades = [];
		this.Edificios = [];
		this.EstatusRecaudaciones = [];
		this.FormasPago = [];
		this.FondosMonetarios = [];

		hlpSwal.Cargando();

		this.cuotasMantenimientoService
			.ListarActivos()
			.toPromise()
			.then((r) => {
				this.CuotasMantenimiento = this.OrdenarCuotasMantenimiento(r['cuotas_mantenimiento']);
				const anios = [...new Set(this.CuotasMantenimiento.map((c:any) => c.anio.toString()))].sort((a:any,b:any) => Number(b)-Number(a));
        this.aniosDisponibles = anios.map((a:any) => ({ label: a, value: a }));
        this.CuotasMantenimientoFiltradas = [...this.CuotasMantenimiento];
        this.onCalcularFilaTotales(this.CuotasMantenimientoFiltradas);
			})
			.catch(async (e) => {
				await hlpSwal.Error(e);
			});

		this.unidadesService
			.ListarParaRecaudaciones()
			.toPromise()
			.then((r) => {
				this.Unidades = r['unidades'].sort((a: UnidadParaRecaudacionesModel, b: UnidadParaRecaudacionesModel) =>
					a.edificio + a.unidad > b.edificio + b.unidad ? 1 : -1,
				);
				this.Edificios = alasql('SELECT DISTINCT id_edificio, edificio FROM ? ORDER BY edificio', [r['unidades']]).sort(
					(a: EdificioModel, b: EdificioModel) => (a.edificio > b.edificio ? 1 : -1),
				);
			})
			.catch(async (e) => {
				await hlpSwal.Error(e);
			});

		this.estatusRecaudacionesService
			.ListarActivos()
			.toPromise()
			.then((r) => {
				this.EstatusRecaudaciones = r['estatus_recaudaciones'];
			})
			.catch(async (e) => {
				await hlpSwal.Error(e);
			});

		this.formasPagoService
			.ListarActivos()
			.toPromise()
			.then((r) => {
				this.FormasPago = r['formas_pago'];
			})
			.catch(async (e) => {
				await hlpSwal.Error(e);
			});

		this.fondosMonetariosService
			.ListarActivos()
			.toPromise()
			.then((r) => {
				this.FondosMonetarios = r['fondos_monetarios'];
			})
			.catch(async (e) => {
				await hlpSwal.Error(e);
			})
			.finally(() => {
				hlpSwal.Cerrar();
			});
	}

	public onFilter(e) {
		this.CuotasMantenimientoIDsFiltered = e.filteredValue.map((f) => f.id_cuota_mantenimiento);
		this.onCalcularFilaTotales(e.filteredValue);
	}

	public onFilterReset(t) {
		this.CuotasMantenimientoIDsFiltered = [];
		hlpPrimeNGTable.reset(t);
		this.onCalcularFilaTotales(this.CuotasMantenimiento);
	}

	async onCuotaMantenimientoEditar(idCuotaMantenimiento: number = 0) {
		// hlpSwal.Cargando();
		this.UnidadesEdificio = [];

		try {
			if (idCuotaMantenimiento > 0) {
				hlpSwal.Cargando();
				this.CuotaMantenimiento = await this.cuotasMantenimientoService
					.ListarCuotaMantenimiento(idCuotaMantenimiento)
					.toPromise()
					.then((r) => r['cuota_mantenimiento'])
					.catch(async (e) => {
						await hlpSwal.Error(e).then(() => null);
					})
					.finally(() => hlpSwal.Cerrar());
				if (this.CuotaMantenimiento == null) return;
				this.CuotaMantenimiento.importe = 0;
				this.CuotaMantenimiento.id_forma_pago = 0;
				this.CuotaMantenimiento.id_fondo_monetario = 0;
				this.CuotaMantenimiento.numero_referencia = null;
			} else {
				this.CuotaMantenimiento = new CuotaMantenimientoModel();
			}
			this.FiltrarUnidadesEdificio(this.CuotaMantenimiento.id_edificio);

			// hlpSwal.Cerrar();

			const anioMes =
				idCuotaMantenimiento > 0
					? new Date(this.CuotaMantenimiento.mes.toString() + '/01/' + this.CuotaMantenimiento.anio.toString())
					: '';

			this.CuotaMantenimiento.fecha_limite_pago =
				idCuotaMantenimiento > 0
					? new Date(this.CuotaMantenimiento.fecha_limite_pago + 'T00:00:00')
					: this.CuotaMantenimiento.fecha_limite_pago;
			this.CuotaMantenimiento.fecha_pago = new Date();

			this.frmCuotaMantenimiento = this.formBuilder.group(this.CuotaMantenimiento);
			this.frmCuotaMantenimiento.get('id_edificio').setValidators([Validators.required, Validators.min(1)]);
			this.frmCuotaMantenimiento.get('id_unidad').setValidators([Validators.required, Validators.min(1)]);
			this.frmCuotaMantenimiento.get('ordinaria').setValidators([Validators.required, Validators.min(0.0)]);
			this.frmCuotaMantenimiento.get('extraordinaria').setValidators([Validators.required, Validators.min(0.0)]);
			this.frmCuotaMantenimiento.get('otros_servicios').setValidators([Validators.required, Validators.min(0.0)]);
			this.frmCuotaMantenimiento.get('importe').setValidators([Validators.required, Validators.min(0.0)]);
			this.frmCuotaMantenimiento.get('notas').setValidators([Validators.maxLength(255)]);

			this.frmCuotaMantenimiento.addControl('anio_mes', new FormControl(anioMes, Validators.required));
			this.frmCuotaMantenimiento.addControl('total', new FormControl(0));
			this.frmCuotaMantenimiento.addControl('saldo', new FormControl(0));
			this.frmCuotaMantenimiento.get('perfil_usuario_paga').disable();
			this.frmCuotaMantenimiento.get('usuario_paga').disable();
			this.frmCuotaMantenimiento.get('total').disable();
			this.frmCuotaMantenimiento.get('saldo').disable();

			this.mostrarDialogoEdicionCuotaMantenimiento = true;
			this.onValidarImporte();
		} catch (e) {
			hlpSwal.Error(e);
		}
	}

	public onEdificioSeleccionado(idEdificio: number) {
		this.FiltrarUnidadesEdificio(idEdificio);

		this.frmCuotaMantenimiento.get('id_unidad').setValue(0);
		this.frmCuotaMantenimiento.get('id_perfil_usuario_paga').setValue(0);
		this.frmCuotaMantenimiento.get('perfil_usuario_paga').setValue('');
		this.frmCuotaMantenimiento.get('id_usuario_paga').setValue(0);
		this.frmCuotaMantenimiento.get('usuario_paga').setValue('');
	}

	public onUnidadSeleccionada(idUnidad: number) {
		const unidad = this.Unidades.filter((u) => u.id_unidad == idUnidad)[0];

		if (unidad) {
			this.frmCuotaMantenimiento.get('id_perfil_usuario_paga').setValue(unidad.id_perfil_usuario_paga);
			this.frmCuotaMantenimiento.get('perfil_usuario_paga').setValue(unidad.perfil_usuario_paga);
			this.frmCuotaMantenimiento.get('id_usuario_paga').setValue(unidad.id_usuario_paga);
			this.frmCuotaMantenimiento.get('usuario_paga').setValue(unidad.perfil_usuario_paga + ' - ' + unidad.usuario_paga);
		}
		// this.onCalcularTotal();
	}

	public onAnioMesSeleccionado(fecha: Date) {
		this.frmCuotaMantenimiento.get('anio').setValue(fecha.getFullYear());
		this.frmCuotaMantenimiento.get('mes').setValue(fecha.getMonth() + 1);
	}

	public onValidarImporte() {
		if (this.frmCuotaMantenimiento.get('importe').value > 0) {
			// Fecha de pago
			this.frmCuotaMantenimiento.get('fecha_pago').setValidators([Validators.required]);
			this.frmCuotaMantenimiento.get('fecha_pago').setValue(this.fechaPagoLimite);
			this.frmCuotaMantenimiento.get('fecha_pago').enable();
			// Forma de pago
			this.frmCuotaMantenimiento.get('id_forma_pago').setValidators([Validators.required, Validators.min(1)]);
			this.frmCuotaMantenimiento.get('id_forma_pago').setValue(0);
			this.frmCuotaMantenimiento.get('id_forma_pago').enable();
			// Fondo monetario
			this.frmCuotaMantenimiento.get('id_fondo_monetario').setValidators([Validators.required, Validators.min(1)]);
			this.frmCuotaMantenimiento.get('id_fondo_monetario').setValue(0);
			this.frmCuotaMantenimiento.get('id_fondo_monetario').enable();
		} else {
			// Fecha de pago
			this.frmCuotaMantenimiento.get('fecha_pago').clearValidators();
			this.frmCuotaMantenimiento.get('fecha_pago').setValue(null);
			this.frmCuotaMantenimiento.get('fecha_pago').disable();
			// Forma de pago
			this.frmCuotaMantenimiento.get('id_forma_pago').clearValidators();
			this.frmCuotaMantenimiento.get('id_forma_pago').setValue(0);
			this.frmCuotaMantenimiento.get('id_forma_pago').disable();
			// Fondo monetario
			this.frmCuotaMantenimiento.get('id_fondo_monetario').clearValidators();
			this.frmCuotaMantenimiento.get('id_fondo_monetario').setValue(0);
			this.frmCuotaMantenimiento.get('id_fondo_monetario').disable();
		}
		this.onFormaPagoSeleccionado(this.frmCuotaMantenimiento.get('id_forma_pago').value);
		this.onCalcularTotalSaldo();
		this.frmCuotaMantenimiento.updateValueAndValidity();
	}

	/* public onEstatusPagoSeleccionado(idEstatusRecaudacion: number) {
		if (idEstatusRecaudacion == 2) {
			// Fecha de pago
			this.frmCuotaMantenimiento.get('fecha_pago').setValidators([Validators.required]);
			this.frmCuotaMantenimiento.get('fecha_pago').setValue(this.fechaPagoLimite);
			this.frmCuotaMantenimiento.get('fecha_pago').enable();
			// Forma de pago
			this.frmCuotaMantenimiento.get('id_forma_pago').setValidators([Validators.required, Validators.min(1)]);
			this.frmCuotaMantenimiento.get('id_forma_pago').setValue(0);
			this.frmCuotaMantenimiento.get('id_forma_pago').enable();
		} else {
			// Fecha de pago
			this.frmCuotaMantenimiento.get('fecha_pago').clearValidators();
			this.frmCuotaMantenimiento.get('fecha_pago').setValue(null);
			this.frmCuotaMantenimiento.get('fecha_pago').disable();
			// Forma de pago
			this.frmCuotaMantenimiento.get('id_forma_pago').clearValidators();
			this.frmCuotaMantenimiento.get('id_forma_pago').setValue(null);
			this.frmCuotaMantenimiento.get('id_forma_pago').disable();
		}
		this.onFormaPagoSeleccionado(this.frmCuotaMantenimiento.get('id_forma_pago').value);
		this.frmCuotaMantenimiento.updateValueAndValidity();
	} */

	public onFormaPagoSeleccionado(idFormaPago: number = 0) {
		const frmFormularioUtilizar: FormGroup = this.mostrarDialogoEdicionCuotaMantenimiento
			? this.frmCuotaMantenimiento
			: this.mostrarDialogoRegistrarPagoCuotaMantenimiento
			? this.frmCuotaMantenimientoRegistrarPago
			: null;
		if (!frmFormularioUtilizar) {
			return;
		}

		const requiereNumeroReferencia =
			idFormaPago && idFormaPago != 0
				? this.FormasPago.filter((f) => f.id_forma_pago == idFormaPago)[0].requiere_numero_referencia == 1
				: false;
		if (requiereNumeroReferencia) {
			frmFormularioUtilizar.get('numero_referencia').setValidators([Validators.required, Validators.maxLength(30)]);
			frmFormularioUtilizar.get('numero_referencia').setValue(null);
			frmFormularioUtilizar.get('numero_referencia').enable();
		} else {
			frmFormularioUtilizar.get('numero_referencia').clearValidators();
			frmFormularioUtilizar.get('numero_referencia').setValue(null);
			frmFormularioUtilizar.get('numero_referencia').disable();
		}
	}

	public onCuotaMantenimientoEditarGuardar() {
		if (!this.frmCuotaMantenimiento.valid) {
			this.frmCuotaMantenimiento.markAllAsTouched();
			hlpSwal.Error('Se detectaron errores en la información solicitada.');
			return;
		}

		if (this.frmCuotaMantenimiento.get('total').value <= 0) {
			hlpSwal.Error('La cuota de mantenimiento no tiene un importe válido.');
			return;
		}
		if (this.frmCuotaMantenimiento.get('saldo').value < 0) {
			hlpSwal.Error('El saldo no puede ser inferior a cero.');
			return;
		}
		if (this.frmCuotaMantenimiento.get('importe').value > this.frmCuotaMantenimiento.get('total').value) {
			hlpSwal.Error('El importe no puede ser mayor al total.');
			return;
		}

		let cuotaMantenimiento = this.frmCuotaMantenimiento.getRawValue();

		cuotaMantenimiento.fecha_limite_pago = hlpApp.formatDateToMySQL(cuotaMantenimiento.fecha_limite_pago);
		cuotaMantenimiento.fecha_pago = hlpApp.formatDateToMySQL(cuotaMantenimiento.fecha_pago);
		delete cuotaMantenimiento.anio_mes;
		delete cuotaMantenimiento.condomino;
		delete cuotaMantenimiento.edificio;
		delete cuotaMantenimiento.estatus;
		delete cuotaMantenimiento.estatus_recaudacion;
		delete cuotaMantenimiento.id_edificio;
		delete cuotaMantenimiento.perfil_usuario_paga;
		delete cuotaMantenimiento.usuario_paga;
		delete cuotaMantenimiento.unidad;
		delete cuotaMantenimiento.total;
		delete cuotaMantenimiento.saldo;
		if (cuotaMantenimiento.importe == 0) {
			delete cuotaMantenimiento.fecha_pago;
			delete cuotaMantenimiento.id_forma_pago;
		}

		hlpSwal
			.Pregunta({
				html: '¿Deseas guardar la información?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.cuotasMantenimientoService.Guardar(cuotaMantenimiento).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err && r.value.cuota_mantenimiento) {
					const cm = r.value.cuota_mantenimiento;
					if (cuotaMantenimiento.id_cuota_mantenimiento == 0) {
						this.CuotasMantenimiento.push(cm);
					} else {
						this.CuotasMantenimiento = this.CuotasMantenimiento.map((C) =>
							C.id_cuota_mantenimiento === cm.id_cuota_mantenimiento ? cm : C,
						);
					}
					this.CuotasMantenimiento = this.OrdenarCuotasMantenimiento(this.CuotasMantenimiento);
					if (cm.id_estatus_recaudacion == 2) {
						this.onCuotaMantenimientoReciboPago(cm.id_cuota_mantenimiento);
					}
					if (cuotaMantenimiento.importe > 0) {
						this.FilaTotales.pagado += +cuotaMantenimiento.importe;
						this.FilaTotales.saldo -= +cuotaMantenimiento.importe;
					}
					hlpSwal.ExitoToast(r.value.msg);
					this.mostrarDialogoEdicionCuotaMantenimiento = false;
				}
			});
	}

	public onCuotaMantenimientoEditarCancelar() {
		this.mostrarDialogoEdicionCuotaMantenimiento = false;
	}

	public onCuotasMantenimientoMasivas() {
		this.totalGeneracionMasiva = null;
		try {
			this.frmCuotaMantenimientoMasiva = new FormGroup({
				anio_mes: new FormControl(null, Validators.required),
			});

			this.mostrarDialogoCuotaMantenimientoMasiva = true;
		} catch (e) {
			hlpSwal.Error(e);
		}
	}

	public onAnioMesSeleccionadoMasiva(fecha: Date) {
		// this.puedeGenerarCuotaMantenimientoMasiva = false;
		this.totalGeneracionMasiva = undefined;
		const fechaActual = new Date();

		if (
			fecha.getFullYear() < fechaActual.getFullYear() ||
			(fecha.getFullYear() == fechaActual.getFullYear() && fecha.getMonth() < fechaActual.getMonth())
		) {
			hlpSwal.ErrorToast('La fecha seleccionada no puede ser menor a la actual.');
			this.frmCuotaMantenimientoMasiva.get('anio_mes').setValue(null);
			return;
		}

		let cuotaMantenimientoMasiva = new CuotaMantenimientoMasivaModel();
		cuotaMantenimientoMasiva.anio = fecha.getFullYear();
		cuotaMantenimientoMasiva.mes = fecha.getMonth() + 1;

		hlpSwal.Cargando();

		this.cuotasMantenimientoService
			.GeneracionMasiva(cuotaMantenimientoMasiva)
			.toPromise()
			.then((r: any) => {
				this.totalGeneracionMasiva = r.total_generar;
			})
			.catch(async (e) => {
				await hlpSwal.Error(e).then(() => null);
			})
			.finally(() => hlpSwal.Cerrar());

		/* if (this.totalGeneracionMasiva <= 0) {
			hlpSwal.Error('No existen cuotas de mantenimiento pendientes de generar para la información especificada.');
			return;
		} */
	}

	public onCuotaMantenimientoMasivasGenerar() {
		const f = this.frmCuotaMantenimientoMasiva.get('anio_mes').value;
		console.log(f);

		let cuotaMantenimientoMasiva = new CuotaMantenimientoMasivaModel();
		cuotaMantenimientoMasiva.anio = f.getFullYear();
		cuotaMantenimientoMasiva.mes = f.getMonth() + 1;

		hlpSwal
			.Pregunta({
				html: '¿Deseas generación masiva de las cuotas de mantenimiento?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.cuotasMantenimientoService.GeneracionMasiva(cuotaMantenimientoMasiva, false).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err) {
					hlpSwal.Exito(r.value.msg).then(() => {
						this.onActualizarInformacion();
						this.mostrarDialogoCuotaMantenimientoMasiva = false;
					});
				}
			});
	}

	public onCuotaMantenimientoMasivasCancelar() {
		this.mostrarDialogoCuotaMantenimientoMasiva = false;
	}

	async onCuotaMantenimientoRegistrarPago(idCuotaMantenimiento: number = 0) {
		if (idCuotaMantenimiento == 0) {
			return;
		}

		try {
			hlpSwal.Cargando();
			this.CuotaMantenimiento = await this.cuotasMantenimientoService
				.ListarCuotaMantenimiento(idCuotaMantenimiento)
				.toPromise()
				.then((r) => r['cuota_mantenimiento'])
				.catch(async (e) => {
					await hlpSwal.Error(e).then(() => null);
				})
				.finally(() => hlpSwal.Cerrar());

			this.CuotaMantenimiento.saldo = Number(this.CuotaMantenimiento.saldo);
			this.CuotaMantenimiento.pagado = Number(this.CuotaMantenimiento.pagado);
			this.frmCuotaMantenimientoRegistrarPago = this.formBuilder.group(new CuotaMantenimientoRegistrarPagoModel());
			this.frmCuotaMantenimientoRegistrarPago.get('notas').setValue(this.CuotaMantenimiento.notas);
			this.frmCuotaMantenimientoRegistrarPago.get('notas').setValidators([Validators.maxLength(255)]);
			this.frmCuotaMantenimientoRegistrarPago.get('total').setValue(this.CuotaMantenimiento.total);
			this.frmCuotaMantenimientoRegistrarPago.get('saldo').setValue(this.CuotaMantenimiento.saldo);
			this.frmCuotaMantenimientoRegistrarPago.get('saldo_nuevo').setValue(this.CuotaMantenimiento.saldo);
			this.frmCuotaMantenimientoRegistrarPago.get('pagado').setValue(this.CuotaMantenimiento.pagado);
			this.frmCuotaMantenimientoRegistrarPago
				.get('importe')
				.setValidators([Validators.required, Validators.min(0.01), Validators.max(this.CuotaMantenimiento.saldo)]);
			this.frmCuotaMantenimientoRegistrarPago.get('fecha_pago').setValidators([Validators.required]);
			this.frmCuotaMantenimientoRegistrarPago
				.get('id_forma_pago')
				.setValidators([Validators.required, Validators.min(1)]);
			this.frmCuotaMantenimientoRegistrarPago
				.get('id_fondo_monetario')
				.setValidators([Validators.required, Validators.min(1)]);

			this.frmCuotaMantenimientoRegistrarPago.get('total').disable();
			this.frmCuotaMantenimientoRegistrarPago.get('pagado').disable();
			this.frmCuotaMantenimientoRegistrarPago.get('saldo').disable();
			this.frmCuotaMantenimientoRegistrarPago.get('saldo_nuevo').disable();

			this.mostrarDialogoRegistrarPagoCuotaMantenimiento = true;
		} catch (e) {
			hlpSwal.Error(e);
		}
	}

	public onValidarImportePago() {
		const saldoNuevo =
			this.CuotaMantenimiento.saldo - Number(this.frmCuotaMantenimientoRegistrarPago.get('importe').value);

		this.frmCuotaMantenimientoRegistrarPago.get('saldo_nuevo').setValue(saldoNuevo);
		this.frmCuotaMantenimientoRegistrarPago.updateValueAndValidity();
	}

	onCuotaMantenimientoRegistrarPagoGuardar() {
		if (!this.frmCuotaMantenimientoRegistrarPago.valid) {
			this.frmCuotaMantenimientoRegistrarPago.markAllAsTouched();
			hlpSwal.Error('Se detectaron errores en la información solicitada.');
			return;
		}

		if (this.frmCuotaMantenimientoRegistrarPago.get('saldo_nuevo').value < 0) {
			hlpSwal.Error('El nuevo saldo no puede ser inferior a cero.');
			return;
		}
		if (this.frmCuotaMantenimientoRegistrarPago.get('importe').value > Number(this.CuotaMantenimiento.saldo)) {
			hlpSwal.Error('El importe no puede ser mayor al total.');
			return;
		}

		let cuotaMantenimiento = this.frmCuotaMantenimientoRegistrarPago.value;
		cuotaMantenimiento.fecha_pago = hlpApp.formatDateToMySQL(cuotaMantenimiento.fecha_pago);

		hlpSwal
			.Pregunta({
				html: '¿Deseas registrar el pago de la cuota de mantenimiento?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.cuotasMantenimientoService
							.RegistrarPago(this.CuotaMantenimiento.id_cuota_mantenimiento, cuotaMantenimiento)
							.toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err) {
					this.CuotasMantenimiento = this.OrdenarCuotasMantenimiento(
						this.CuotasMantenimiento.map((cm) =>
							cm.id_cuota_mantenimiento === this.CuotaMantenimiento.id_cuota_mantenimiento
								? r.value.cuota_mantenimiento
								: cm,
						),
					);
					this.FilaTotales.pagado += +cuotaMantenimiento.importe;
					this.FilaTotales.saldo -= +cuotaMantenimiento.importe;
					// this.onCuotaMantenimientoReciboPago(this.CuotaMantenimiento.id_cuota_mantenimiento);
					this.mostrarDialogoRegistrarPagoCuotaMantenimiento = false;
					hlpSwal.ExitoToast(r.value.msg);
				}
			});
	}

	onCuotaMantenimientoRegistrarPagoCancelar() {
		this.mostrarDialogoRegistrarPagoCuotaMantenimiento = false;
	}

	onCuotaMantenimientoPagoEliminar(pago: any, cuotaMantenimiento: any) {
		hlpSwal
			.Pregunta({
				html: '¿Deseas eliminar el pago?<br><p class="pt-2 text-danger fw-bold">ESTE PROCESO ES IRREVERSIBLE.</p>',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.cuotasMantenimientoService.EliminarPago(pago.id_cuota_mantenimiento_pago).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err) {
					this.CuotaMantenimiento.pagado -= +pago.importe;
					this.CuotaMantenimiento.saldo += +pago.importe;
					this.frmCuotaMantenimientoRegistrarPago.get('saldo').setValue(this.CuotaMantenimiento.saldo);
					this.frmCuotaMantenimientoRegistrarPago.get('saldo_nuevo').setValue(this.CuotaMantenimiento.saldo);
					this.frmCuotaMantenimientoRegistrarPago.get('pagado').setValue(this.CuotaMantenimiento.pagado);
					let cuotaMantenimientoResumen = this.CuotasMantenimiento.filter(
						(cm) => cm.id_cuota_mantenimiento == this.CuotaMantenimiento.id_cuota_mantenimiento,
					)[0];
					if (cuotaMantenimientoResumen != undefined) {
						cuotaMantenimientoResumen.pagado = this.CuotaMantenimiento.pagado;
						cuotaMantenimientoResumen.saldo = this.CuotaMantenimiento.saldo;
						if (cuotaMantenimientoResumen.pagado == 0) {
							cuotaMantenimientoResumen.estatus_recaudacion = 'PENDIENTE PAGO';
							cuotaMantenimientoResumen.id_estatus_recaudacion = 1;
						}
						cuotaMantenimiento.pagos = cuotaMantenimiento.pagos.filter(
							(p) => p.id_cuota_mantenimiento_pago != pago.id_cuota_mantenimiento_pago,
						);
						this.CuotaMantenimiento = cuotaMantenimiento;
						/* if (
							this.CuotasMantenimientoIDsFiltered.length > 0 &&
							this.CuotasMantenimientoIDsFiltered.filter((cm) => cm == this.CuotaMantenimiento.id_cuota_mantenimiento)
								.length > 0
						) { */
						this.FilaTotales.pagado -= +pago.importe;
						this.FilaTotales.saldo += +pago.importe;
						// }
					}
					hlpSwal.ExitoToast(r.value.msg);
				}
			});
	}

	async onCuotaMantenimientoReciboPago(idCuotaMantenimiento: number = 0) {
		hlpSwal.Cargando();

		this.CuotaMantenimiento = await this.cuotasMantenimientoService
			.ListarReciboPago(idCuotaMantenimiento)
			.toPromise()
			.then((r) => r['recibo_pago'])
			.catch(async (e) => {
				await hlpSwal.Error(e).then(() => null);
			});

		hlpSwal.Cerrar();

		this.mostrarDialogoReciboPagoCuotaMantenimiento = this.CuotaMantenimiento != null;
	}

	onCuotaMantenimientoEliminar(CuotaMantenimiento: CuotaMantenimientoResumenModel = null) {
		if (CuotaMantenimiento == null) {
			return;
		}

		hlpSwal
			.Pregunta({
				html: '¿Deseas eliminar la Cuota de mantenimiento?<br><p class="pt-2 text-danger fw-bold">ESTE PROCESO ES IRREVERSIBLE.</p>',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.cuotasMantenimientoService
							.Eliminar(CuotaMantenimiento.id_cuota_mantenimiento)
							.toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err) {
					this.CuotasMantenimiento = this.CuotasMantenimiento.filter(
						(cm: CuotaMantenimientoResumenModel) =>
							cm.id_cuota_mantenimiento != CuotaMantenimiento.id_cuota_mantenimiento,
					);

					this.CuotasMantenimientoIDsFiltered = this.CuotasMantenimientoIDsFiltered.filter(
						(cm) => cm != CuotaMantenimiento.id_cuota_mantenimiento,
					);
					this.FilaTotales.total -= +CuotaMantenimiento.total;
					this.FilaTotales.saldo -= +CuotaMantenimiento.saldo;
					this.FilaTotales.pagado -= +CuotaMantenimiento.pagado;
					hlpSwal.ExitoToast(r.value.msg);
				}
			});
	}
}
