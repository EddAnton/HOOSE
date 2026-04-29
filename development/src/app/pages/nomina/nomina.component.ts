import { Component, OnInit, isDevMode } from '@angular/core';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';

import { environment } from '../../../environments/environment';
import * as hlpApp from '../../helpers/app-helper';
import * as hlpSwal from '../../helpers/sweetalert2-helper';
import * as hlpPrimeNGTable from '../../helpers/primeng-table-helper';

import { SesionUsuarioService } from '../../services/sesion-usuario.service';
import { NominaService } from '../../services/nomina.service';
import {
	NominaResumenModel,
	NominaPagoModel,
	NominaPagoDetalleModel,
	FilaTotalesModel,
} from 'src/app/models/nomina.model';
import { UsuariosColaboradoresService } from '../../services/usuarios-colaboradores.service';
import { FondoMonetarioResumenModel } from '../../models/fondo-monetario.model';
import { FondosMonetariosService } from '../../services/fondos-monetarios.service';

@Component({
	selector: 'app-nomina',
	templateUrl: './nomina.component.html',
	styleUrls: ['./nomina.component.css'],
})
export class NominaComponent implements OnInit {
	appData = environment;
	hlpApp = hlpApp;
	hlpPrimeNGTable = hlpPrimeNGTable;
	isDevelopment = isDevMode;

	// Tabla Tipos de Miembros
	// Columnas de la tabla
	NominaCols: any[] = [
		{ header: 'Colaborador' },
		{ header: 'Puesto' },
		{ header: 'Año' },
		{ header: 'Mes' },
		{ header: 'Importe' },
		{ header: 'Fecha' },
		// Botones de acción
		{ textAlign: 'center' },
	];
	NominaFilter: any[] = ['colaborador', 'puesto', 'anio', 'mes', 'fecha_pago'];

	Nomina: NominaResumenModel[] = [];
	NominaIDsFiltered: any[] = [];
	FilaTotales: FilaTotalesModel;
	NominaPago: NominaPagoModel;
	Colaboradores: any[] = [];
	NominaPagoDetalle: NominaPagoDetalleModel;
	FondosMonetarios: FondoMonetarioResumenModel[] = [];
	fechaPagoLimite: Date = new Date();

	frmNominaPago: FormGroup;
	mostrarDialogoEdicionNominaPago: boolean = false;
	mostrarDialogoDetallesNominaPago: boolean = false;
	permitirAgregarEditar: boolean = false;

	constructor(
		private formBuilder: FormBuilder,
		private sesionUsuarioService: SesionUsuarioService,
		private nominaService: NominaService,
		private usuariosColaboradoresService: UsuariosColaboradoresService,
		private fondosMonetariosService: FondosMonetariosService,
	) {}

	ngOnInit(): void {
		this.permitirAgregarEditar = [1, 2].includes(this.sesionUsuarioService.obtenerIDPerfilUsuario());
		this.onActualizarInformacion();
	}

	private OrdenarNomina(nomina: NominaResumenModel[]) {
		return nomina.sort((a, b) =>
			a.anio.toString() + a.mes.toString() + a.colaborador > b.anio.toString() + b.mes.toString() + b.colaborador
				? 1
				: -1,
		);
	}

	private onCalcularFilaTotales(registros: NominaResumenModel[]) {
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
		this.Nomina = [];
		this.NominaIDsFiltered = [];

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

		this.nominaService
			.ListarActivos()
			.toPromise()
			.then((r) => {
				this.Nomina = this.OrdenarNomina(r['pagos']);
				this.onCalcularFilaTotales(this.Nomina);
			})
			.catch(async (e) => {
				await hlpSwal.Error(e);
			})
			.finally(() => {
				hlpSwal.Cerrar();
			});
	}

	public onFilter(e) {
		this.NominaIDsFiltered = e.filteredValue.map((f) => f.id_colaborador_nomina);
		this.onCalcularFilaTotales(e.filteredValue);
	}

	public onFilterReset(t) {
		this.NominaIDsFiltered = [];
		hlpPrimeNGTable.reset(t);
		this.onCalcularFilaTotales(this.Nomina);
	}

	async onNominaPagoEditar(idColaboradorNomina: number = 0) {
		hlpSwal.Cargando();
		this.Colaboradores = await this.usuariosColaboradoresService
			.ListarActivos()
			.toPromise()
			.then((r) =>
				r['colaboradores'].map((c) => {
					return {
						id_colaborador: c.id_usuario,
						colaborador: c.nombre,
						puesto: c.tipo_miembro,
						importe: c.salario,
					};
				}),
			)
			.catch(async (e) => {
				await hlpSwal.Error(e).then(() => null);
			});

		if (!this.Colaboradores) {
			hlpSwal.Error('No se pudieron obtener Colaboradores');
			return;
		}

		if (idColaboradorNomina > 0) {
			this.NominaPago = await this.nominaService
				.ListarPago(idColaboradorNomina)
				.toPromise()
				.then((r) => r['pago'])
				.catch(async (e) => {
					await hlpSwal.Error(e).then(() => null);
				});
			if (this.NominaPago == null) return;
		} else {
			this.NominaPago = new NominaPagoModel();
		}

		hlpSwal.Cerrar();

		try {
			const anioMes =
				idColaboradorNomina > 0
					? new Date(this.NominaPago.mes.toString() + '/01/' + this.NominaPago.anio.toString())
					: '';

			if (idColaboradorNomina > 0) {
				this.NominaPago.fecha_pago = new Date(this.NominaPago.fecha_pago + 'T00:00:00');
			}

			this.frmNominaPago = this.formBuilder.group(this.NominaPago);
			this.frmNominaPago.get('id_colaborador').setValidators([Validators.required, Validators.min(1)]);
			this.frmNominaPago.get('importe').setValidators([Validators.required, Validators.min(0.1)]);
			this.frmNominaPago.get('fecha_pago').setValidators([Validators.required]);
			this.frmNominaPago.get('id_fondo_monetario').setValidators([Validators.required, Validators.min(1)]);
			this.frmNominaPago.addControl('anio_mes', new FormControl(anioMes, Validators.required));
			this.frmNominaPago.addControl('puesto', new FormControl(this.NominaPago.puesto));
			this.frmNominaPago.get('puesto').disable();

			this.mostrarDialogoEdicionNominaPago = true;
		} catch (e) {
			hlpSwal.Error(e);
		}
	}

	public onColaboradorSeleccionado(idColaborador: number) {
		const c = this.Colaboradores.filter((c) => c.id_colaborador == idColaborador)[0];

		if (c) {
			this.frmNominaPago.get('puesto').setValue(c.puesto);
			this.frmNominaPago.get('importe').setValue(c.importe);
		}
	}

	public onAnioMesSeleccionado(fecha: Date) {
		this.frmNominaPago.get('anio').setValue(fecha.getFullYear());
		this.frmNominaPago.get('mes').setValue(fecha.getMonth() + 1);
	}

	onNominaPagoEditarGuardar() {
		if (!this.frmNominaPago.valid) {
			this.frmNominaPago.markAllAsTouched();
			hlpSwal.Error('Se detectaron errores en la información solicitada.');
			return;
		}

		let nominaPago = this.frmNominaPago.value;

		nominaPago.fecha_pago = hlpApp.formatDateToMySQL(nominaPago.fecha_pago);
		delete nominaPago.anio_mes;
		delete nominaPago.colaborador;

		hlpSwal
			.Pregunta({
				html: '¿Deseas guardar la información?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.nominaService.Guardar(nominaPago).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err && r.value.pago) {
					const cm = r.value.pago;
					if (nominaPago.id_colaborador_nomina == 0) {
						this.Nomina.push(cm);
					} else {
						this.Nomina = this.Nomina.map((C) => (C.id_colaborador_nomina === cm.id_colaborador_nomina ? cm : C));
					}
					this.Nomina = this.OrdenarNomina(this.Nomina);
					this.FilaTotales.total += +nominaPago.importe - +this.NominaPago.importe;

					hlpSwal.ExitoToast(r.value.msg);
					this.mostrarDialogoEdicionNominaPago = false;
				}
			});
	}

	onNominaPagoEditarCancelar() {
		this.mostrarDialogoEdicionNominaPago = false;
	}

	async onNominaPagoDetalles(idColaboradorNomina: number = 0) {
		if (idColaboradorNomina == 0) {
			return;
		}
		hlpSwal.Cargando();
		this.NominaPagoDetalle = await this.nominaService
			.ListarPago(idColaboradorNomina)
			.toPromise()
			.then((r) => r['pago'])
			.catch(async (e) => {
				await hlpSwal.Error(e).then(() => null);
			})
			.finally(() => {
				hlpSwal.Cerrar();
			});

		this.mostrarDialogoDetallesNominaPago = this.NominaPagoDetalle != null;
	}

	onNominaPagoEliminar(NominaPago: NominaResumenModel = null) {
		if (NominaPago == null) {
			return;
		}

		hlpSwal
			.Pregunta({
				html: '¿Deseas eliminar el registro?<br><p class="pt-2 text-danger fw-bold">ESTE PROCESO ES IRREVERSIBLE.</p>',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.nominaService.Eliminar(NominaPago.id_colaborador_nomina).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err) {
					this.Nomina = this.Nomina.filter(
						(n: NominaResumenModel) => n.id_colaborador_nomina != NominaPago.id_colaborador_nomina,
					);

					this.NominaIDsFiltered = this.NominaIDsFiltered.filter((cm) => cm != NominaPago.id_colaborador_nomina);
					this.FilaTotales.total -= +NominaPago.importe;
					hlpSwal.ExitoToast(r.value.msg);
				}
			});
	}
}
