import { Component, OnInit, isDevMode } from '@angular/core';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';

import { environment } from '../../../environments/environment';
import * as hlpApp from '../../helpers/app-helper';
import * as hlpSwal from '../../helpers/sweetalert2-helper';
import * as hlpPrimeNGTable from '../../helpers/primeng-table-helper';

import { SesionUsuarioService } from '../../services/sesion-usuario.service';
import { FondosMonetariosService } from '../../services/fondos-monetarios.service';
import { TiposMovimientosFondosService } from '../../services/tipos-movimientos-fondos.service';
import {
	FondoMonetarioModel,
	FondoMonetarioMovimientoModel,
	FondoMonetarioResumenModel,
	FondoMonetarioTraspasoModel,
} from '../../models/fondo-monetario.model';
import { TipoMovimientoFondoModel } from '../../models/tipo-movimiento-fondo.model';
import { DomSanitizer } from '@angular/platform-browser';
import { TiposFondosMonetariosService } from '../../services/tipos-fondos-monetarios.service';
import { TipoFondoMonetarioModel } from 'src/app/models/tipo-fondo-monetario.model';

@Component({
	selector: 'app-fondos-monetarios',
	templateUrl: './fondos-monetarios.component.html',
	styleUrls: ['./fondos-monetarios.component.css'],
})
export class FondosMonetariosComponent implements OnInit {
	appData = environment;
	hlpApp = hlpApp;
	hlpPrimeNGTable = hlpPrimeNGTable;
	isDevelopment = isDevMode;
	expandedRows = {};

	FondosMonetariosCols: any[] = [
		{ textAlign: 'center', width: '40px' },
		{ header: 'Tipo' },
		{ header: 'Nombre' },
		{ header: 'Banco' },
		{ header: 'Cuenta' },
		{ header: 'Clabe' },
		{ header: 'Saldo', width: '100px' },
		// Botones de acción
		{ textAlign: 'center', width: '170px' },
	];
	FondosMonetariosFilter: any[] = ['nombre', 'tipo_fondo', 'banco', 'numero_cuenta', 'clabe'];

	// Tabla Cuotas Mantenimiento
	// Columnas de la tabla
	MovimientosFondoMonetarioCols: any[] = [
		{ header: 'Fecha', width: '120px' },
		{ header: 'Concepto' },
		{ header: 'Importe', width: '100px' },
		{ header: 'Saldo actualizado', width: '100px' },
		// Botones de acción
		{ textAlign: 'center', width: '130px' },
	];
	FondosMonetariosMovimientosFilter: any[] = ['tipo_movimiento', 'fecha', 'concepto'];

	TiposFondosMonetarios: TipoFondoMonetarioModel[] = [];
	TiposMovimientos: TipoMovimientoFondoModel[] = [];

	FondosMonetarios: FondoMonetarioResumenModel[] = [];
	FondoMonetario: FondoMonetarioModel;
	FondoMonetarioExpandido: FondoMonetarioResumenModel = new FondoMonetarioResumenModel();
	FondoMonetarioMovimientos: FondoMonetarioResumenModel;
	MovimientosFondoMonetario: FondoMonetarioMovimientoModel[] = [];

	fechaMovimientoLimite: Date = new Date();

	frmFondoMonetario: FormGroup;
	mostrarDialogoEdicionFondoMonetario: boolean = false;

	FondosMonetariosDestino: FondoMonetarioResumenModel[] = [];
	FondoMonetarioTraspaso: FondoMonetarioTraspasoModel;
	frmFondoMonetarioTraspaso: FormGroup;
	mostrarDialogoRegistrarTraspaso: boolean = false;

	FondoMonetarioMovimiento: FondoMonetarioMovimientoModel;
	frmFondoMonetarioMovimiento: FormGroup;
	srcComprobante: string = null;
	mostrarDialogoRegistrarMovimiento: boolean = false;

	mostrarDialogoComprobante: boolean = false;

	permitirAgregarEditar: boolean = false;

	constructor(
		private formBuilder: FormBuilder,
		private sanitizer: DomSanitizer,
		private sesionUsuarioService: SesionUsuarioService,
		private fondosMonetariosService: FondosMonetariosService,
		private tiposFondosMonetariosService: TiposFondosMonetariosService,
		private tiposMovimientosFondosService: TiposMovimientosFondosService,
	) {}

	ngOnInit(): void {
		this.permitirAgregarEditar = [1, 2].includes(this.sesionUsuarioService.obtenerIDPerfilUsuario());
		this.onActualizarFondosMonetarios();
	}

	private OrdenarFondosMonetarios(fondosMonetarios: FondoMonetarioResumenModel[]) {
		return fondosMonetarios.sort((a, b) =>
			a.tipo_fondo.toString() + a.fondo_monetario > b.tipo_fondo.toString() + b.fondo_monetario ? 1 : -1,
		);
	}

	private OrdenarMovimientosFondoMonetario(movimientos: FondoMonetarioMovimientoModel[]) {
		if (!movimientos) {
			return;
		}
		return movimientos.sort((a, b) =>
			a.fecha.toString() + a.fecha_registro.toString() + a.id_fondo_monetario_movimiento.toString() >
			b.fecha.toString() + b.fecha_registro.toString() + b.id_fondo_monetario_movimiento.toString()
				? -1
				: 1,
		);
	}

	public onActualizarFondosMonetarios() {
		this.FondosMonetarios = [];
		this.FondoMonetario = new FondoMonetarioModel();

		this.expandedRows = {};

		hlpSwal.Cargando();

		this.tiposFondosMonetariosService
			.ListarActivos()
			.toPromise()
			.then((r) => {
				this.TiposFondosMonetarios = r['tipos_fondos_monetarios'];
			})
			.catch(async (e) => {
				await hlpSwal.Error(e);
			});

		this.tiposMovimientosFondosService
			.ListarActivos()
			.toPromise()
			.then((r) => {
				this.TiposMovimientos = r['tipos_movimientos_fondos'];
			})
			.catch(async (e) => {
				await hlpSwal.Error(e);
			});

		this.fondosMonetariosService
			.ListarActivos()
			.toPromise()
			.then((r) => {
				this.FondosMonetarios = this.OrdenarFondosMonetarios(r['fondos_monetarios']);
			})
			.catch(async (e) => {
				await hlpSwal.Error(e);
			})
			.finally(() => hlpSwal.Cerrar());
	}

	async onFondoMonetarioEditar(fondoMonetario: FondoMonetarioResumenModel = null) {
		if (fondoMonetario) {
			hlpSwal.Cargando();
			this.FondoMonetario = await this.fondosMonetariosService
				.ListarFondoMonetario(fondoMonetario.id_fondo_monetario)
				.toPromise()
				.then((r) => {
					const f = r['fondo_monetario'];
					delete f.movimientos;
					return f;
				})
				.catch(async (e) => {
					await hlpSwal.Error(e).then(() => null);
				})
				.finally(() => hlpSwal.Cerrar());
			if (this.FondoMonetario == null) return;
		} else {
			this.FondoMonetario = new FondoMonetarioModel();
		}

		try {
			this.frmFondoMonetario = this.formBuilder.group(this.FondoMonetario);
			this.frmFondoMonetario
				.get('fondo_monetario')
				.setValidators([Validators.required, Validators.minLength(3), Validators.maxLength(50)]);
			this.frmFondoMonetario.get('id_tipo_fondo_monetario').setValidators([Validators.required, Validators.min(1)]);
			this.frmFondoMonetario
				.get('banco')
				.setValidators([Validators.required, Validators.minLength(3), Validators.maxLength(100)]);
			this.frmFondoMonetario
				.get('numero_cuenta')
				.setValidators([Validators.required, Validators.minLength(3), Validators.maxLength(50)]);
			this.frmFondoMonetario.get('clabe').setValidators([Validators.minLength(3), Validators.maxLength(50)]);
			if (this.FondoMonetario.id_fondo_monetario == 0) {
				this.frmFondoMonetario.get('saldo').setValidators([Validators.required, Validators.min(0)]);
			} else {
				this.frmFondoMonetario.get('saldo').disable();
			}

			if (this.FondoMonetario.requiere_datos_bancarios == 0) {
				this.frmFondoMonetario.get('banco').disable();
				this.frmFondoMonetario.get('numero_cuenta').disable();
				this.frmFondoMonetario.get('clabe').disable();
			}

			// this.onTipoFondoMonetarioSeleccionado(this.FondoMonetario.id_tipo_fondo_monetario);
			this.mostrarDialogoEdicionFondoMonetario = true;
		} catch (e) {
			hlpSwal.Error(e);
		}
	}

	public onTipoFondoMonetarioSeleccionado(idTipoFondoMonetario: number) {
		const tf = this.TiposFondosMonetarios.filter((tf) => tf.id_tipo_fondo_monetario == idTipoFondoMonetario)[0];

		if (!tf) {
			return;
		}
		this.FondoMonetario.requiere_datos_bancarios = tf.requiere_datos_bancarios;

		if (tf.requiere_datos_bancarios == 1) {
			this.frmFondoMonetario.get('banco').enable();
			this.frmFondoMonetario.get('numero_cuenta').enable();
			this.frmFondoMonetario.get('clabe').enable();
		} else {
			this.frmFondoMonetario.get('banco').disable();
			this.frmFondoMonetario.get('numero_cuenta').disable();
			this.frmFondoMonetario.get('clabe').disable();

			this.frmFondoMonetario.get('banco').setValue(null);
			this.frmFondoMonetario.get('numero_cuenta').setValue(null);
			this.frmFondoMonetario.get('clabe').setValue(null);
		}
	}

	onFondoMonetarioEditarGuardar() {
		if (!this.frmFondoMonetario.valid) {
			this.frmFondoMonetario.markAllAsTouched();
			hlpSwal.Error('Se detectaron errores en la información solicitada.');
			return;
		}

		if (this.frmFondoMonetario.get('saldo').value < 0) {
			hlpSwal.Error('El saldo no puede ser inferior a cero.');
			return;
		}

		let fondoMonetario = this.frmFondoMonetario.value;
		fondoMonetario.requiere_datos_bancarios = this.FondoMonetario.requiere_datos_bancarios;

		hlpSwal
			.Pregunta({
				html: '¿Deseas guardar la información?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.fondosMonetariosService.Guardar(fondoMonetario).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err && r.value.fondo_monetario) {
					const fm = r.value.fondo_monetario;

					if (fondoMonetario.id_fondo_monetario == 0) {
						this.FondosMonetarios.push(fm);
					} else {
						this.FondosMonetarios = this.FondosMonetarios.map((f) =>
							f.id_fondo_monetario === fm.id_fondo_monetario ? fm : f,
						);
					}

					delete fm.movimientos;
					this.FondoMonetario = fm;
					this.FondosMonetarios = this.OrdenarFondosMonetarios(this.FondosMonetarios);

					hlpSwal.ExitoToast(r.value.msg);
					this.mostrarDialogoEdicionFondoMonetario = false;
				}
			});
	}

	onFondoMonetarioEditarCancelar() {
		this.mostrarDialogoEdicionFondoMonetario = false;
	}

	onFondoMonetarioEliminar(fondoMonetario: FondoMonetarioModel = null) {
		if (fondoMonetario == null) {
			return;
		}

		hlpSwal
			.Pregunta({
				html: '¿Deseas eliminar el Fondo monetario?<br><p class="pt-2 text-danger fw-bold">ESTE PROCESO ES IRREVERSIBLE.</p>',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.fondosMonetariosService.Eliminar(fondoMonetario.id_fondo_monetario).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err) {
					this.FondosMonetarios = this.FondosMonetarios.filter(
						(cm: FondoMonetarioResumenModel) => cm.id_fondo_monetario != fondoMonetario.id_fondo_monetario,
					);
					hlpSwal.ExitoToast(r.value.msg);
				}
			});
	}

	onTraspasoRegistrar(fondoMonetario: FondoMonetarioModel = null) {
		try {
			this.FondoMonetario = new FondoMonetarioModel();
			this.FondosMonetariosDestino = this.FondosMonetarios.filter(
				(fm) => fm.id_fondo_monetario != fondoMonetario.id_fondo_monetario,
			);
			if (this.FondosMonetariosDestino.length < 1) {
				hlpSwal.Error('No existen otros Fondos monetarios para traspaso.');
				return;
			}
			this.FondoMonetario = fondoMonetario;

			this.frmFondoMonetarioTraspaso = this.formBuilder.group(new FondoMonetarioTraspasoModel());
			this.frmFondoMonetarioTraspaso
				.get('id_fondo_monetario_destino')
				.setValidators([Validators.required, Validators.min(1)]);
			this.frmFondoMonetarioTraspaso.get('fecha').setValidators([Validators.required]);
			this.frmFondoMonetarioTraspaso
				.get('importe')
				.setValidators([Validators.required, Validators.min(0.01), Validators.max(this.FondoMonetario.saldo)]);
			this.frmFondoMonetarioTraspaso.addControl('archivo_comprobante', new FormControl());

			this.mostrarDialogoRegistrarTraspaso = true;
		} catch (e) {
			hlpSwal.Error(e);
		}
	}

	onTraspasoRegistrarGuardar() {
		if (!this.frmFondoMonetarioTraspaso.valid) {
			this.frmFondoMonetarioTraspaso.markAllAsTouched();
			hlpSwal.Error('Se detectaron errores en la información solicitada.');
			return;
		}

		if (this.frmFondoMonetarioTraspaso.get('importe').value < 0) {
			hlpSwal.Error('El importe no puede ser inferior a cero.');
			return;
		}

		let traspaso = this.frmFondoMonetarioTraspaso.value;
		traspaso.fecha = hlpApp.formatDateToMySQL(traspaso.fecha);

		hlpSwal
			.Pregunta({
				html: '¿Deseas registrar el traspaso?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.fondosMonetariosService
							.RegistrarTraspaso(this.FondoMonetario.id_fondo_monetario, traspaso)
							.toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err && r.value.movimientos) {
					const m = r.value.movimientos;
					if (this.FondoMonetarioExpandido) {
						if (this.FondoMonetarioExpandido.id_fondo_monetario == m.origen.id_fondo_monetario) {
							this.MovimientosFondoMonetario.push(m.origen);
						} else if (this.FondoMonetarioExpandido.id_fondo_monetario == m.destino.id_fondo_monetario) {
							this.MovimientosFondoMonetario.push(m.destino);
						}
						this.MovimientosFondoMonetario = this.OrdenarMovimientosFondoMonetario(this.MovimientosFondoMonetario);
					}
					let fmo = this.FondosMonetarios.filter((fm) => fm.id_fondo_monetario == m.origen.id_fondo_monetario);
					let fmd = this.FondosMonetarios.filter((fm) => fm.id_fondo_monetario == m.destino.id_fondo_monetario);
					if (fmo.length == 1) {
						fmo[0].saldo = Number(fmo[0].saldo) - Number(traspaso.importe);
					}
					if (fmd.length == 1) {
						fmd[0].saldo = Number(fmd[0].saldo) + Number(traspaso.importe);
					}
					this.mostrarDialogoRegistrarTraspaso = false;
					hlpSwal.ExitoToast(r.value.msg);
				}
			});
	}

	onTraspasoRegistrarCancelar() {
		this.mostrarDialogoRegistrarTraspaso = false;
	}

	public onMostrarMovimientos(e) {
		if (this.FondoMonetarioExpandido.id_fondo_monetario == e.dataid_fondo_monetario) {
			return;
		}
		this.FondoMonetarioExpandido = e.data;
		this.onActualizarMovimientos();
	}

	public onOcultarMovimientos(e) {
		this.FondoMonetarioExpandido = new FondoMonetarioResumenModel();
	}

	public onActualizarMovimientos() {
		this.MovimientosFondoMonetario = [];
		if (this.FondoMonetarioExpandido.id_fondo_monetario < 1) {
			return;
		}
		hlpSwal.Cargando();

		this.fondosMonetariosService
			.ListarMovimientosFondoMonetario(this.FondoMonetarioExpandido.id_fondo_monetario)
			.toPromise()
			.then((r) => {
				this.MovimientosFondoMonetario = this.OrdenarMovimientosFondoMonetario(r['movimientos']);
			})
			.catch(async (e) => {
				await hlpSwal.Error(e);
			})
			.finally(() => hlpSwal.Cerrar());
	}

	async onMovimientoRegistrar() {
		try {
			this.srcComprobante = null;
			this.FondoMonetarioMovimiento = new FondoMonetarioMovimientoModel();
			this.frmFondoMonetarioMovimiento = this.formBuilder.group(this.FondoMonetarioMovimiento);
			this.frmFondoMonetarioMovimiento
				.get('id_tipo_movimiento')
				.setValidators([Validators.required, Validators.min(1)]);
			this.frmFondoMonetarioMovimiento.get('fecha').setValidators([Validators.required]);
			this.frmFondoMonetarioMovimiento
				.get('concepto')
				.setValidators([Validators.required, Validators.minLength(3), Validators.maxLength(150)]);
			this.frmFondoMonetarioMovimiento.get('importe').setValidators([Validators.required, Validators.min(0.01)]);
			this.frmFondoMonetarioMovimiento.addControl('archivo_comprobante', new FormControl());

			this.mostrarDialogoRegistrarMovimiento = true;
		} catch (e) {
			hlpSwal.Error(e);
		}
	}

	onComprobanteSeleccionado(event) {
		let frm = event?.srcElement.form;
		if (event.target.files.length != 1) return;
		let file: any;
		file = event.target.files[0];

		if (this.mostrarDialogoRegistrarMovimiento) {
			this.frmFondoMonetarioMovimiento.patchValue({ archivo_comprobante: file });
			this.frmFondoMonetarioMovimiento.get('archivo_comprobante').updateValueAndValidity();
		} else if (this.mostrarDialogoRegistrarTraspaso) {
			this.frmFondoMonetarioTraspaso.patchValue({ archivo_comprobante: file });
			this.frmFondoMonetarioTraspaso.get('archivo_comprobante').updateValueAndValidity();
		}

		let reader = new FileReader();

		reader.onload = (e) => {
			file.src = reader.result;
			this.srcComprobante = file.src;
		};
		reader.readAsDataURL(file);
	}

	onComprobanteSeleccionadoCancelar() {
		(<HTMLInputElement>document.getElementById('txtComprobanteArchivo')).value = '';
		if (this.mostrarDialogoRegistrarMovimiento) {
		} else if (this.mostrarDialogoRegistrarTraspaso) {
			this.frmFondoMonetarioTraspaso.get('archivo_comprobante').setValue(null);
		}
		this.srcComprobante = null;
	}

	async onComprobanteMostrar(fondoMonetarioMovimiento: FondoMonetarioMovimientoModel = null) {
		if (fondoMonetarioMovimiento != null) {
			this.srcComprobante = fondoMonetarioMovimiento.comprobante_archivo
				? environment.urlBackendFondosMonetariosFiles +
				  this.sesionUsuarioService.obtenerIDCondominioUsuario() +
				  '/' +
				  fondoMonetarioMovimiento.comprobante_archivo
				: null;
		}

		this.mostrarDialogoComprobante = this.srcComprobante != null;
		if (this.mostrarDialogoComprobante) {
			hlpSwal.Cargando();
		}
	}

	comprobanteURL() {
		return this.sanitizer.bypassSecurityTrustResourceUrl(this.srcComprobante + '#toolbar=0&view=fitH');
	}

	onComprobanteMostrado() {
		hlpSwal.Cerrar();
	}

	onMovimientoRegistrarGuardar() {
		if (!this.frmFondoMonetarioMovimiento.valid) {
			this.frmFondoMonetarioMovimiento.markAllAsTouched();
			hlpSwal.Error('Se detectaron errores en la información solicitada.');
			return;
		}

		if (this.frmFondoMonetarioMovimiento.get('importe').value < 0) {
			hlpSwal.Error('El nuevo saldo no puede ser inferior a cero.');
			return;
		}

		let fondoMonetario = this.frmFondoMonetarioMovimiento.value;
		fondoMonetario.fecha = hlpApp.formatDateToMySQL(fondoMonetario.fecha);

		hlpSwal
			.Pregunta({
				html: '¿Deseas registrar el movimiento?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.fondosMonetariosService
							.RegistrarMovimiento(this.FondoMonetarioExpandido.id_fondo_monetario, fondoMonetario)
							.toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err && r.value.movimiento) {
					const m = r.value.movimiento;

					this.MovimientosFondoMonetario.push(m);
					this.MovimientosFondoMonetario = this.OrdenarMovimientosFondoMonetario(this.MovimientosFondoMonetario);

					this.FondoMonetarioExpandido.saldo = m.saldo_nuevo;
					this.mostrarDialogoRegistrarMovimiento = false;
					hlpSwal.ExitoToast(r.value.msg);
				}
			});
	}

	onMovimientoRegistrarCancelar() {
		this.mostrarDialogoRegistrarMovimiento = false;
	}

	onMovimientoEliminar(fondoMonetarioMovimiento: FondoMonetarioMovimientoModel) {
		hlpSwal
			.Pregunta({
				html: '¿Deseas eliminar el movimiento?<br><p class="pt-2 text-danger fw-bold">ESTE PROCESO ES IRREVERSIBLE.</p>',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.fondosMonetariosService
							.EliminarMovimiento(fondoMonetarioMovimiento.id_fondo_monetario_movimiento)
							.toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err) {
					this.FondoMonetarioExpandido.saldo -= fondoMonetarioMovimiento.importe;
					fondoMonetarioMovimiento.estatus = 0;
					hlpSwal.ExitoToast(r.value.msg);
				}
			});
	}
}
