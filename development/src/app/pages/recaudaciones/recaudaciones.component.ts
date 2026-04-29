import { Component, OnInit, isDevMode } from '@angular/core';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';
import alasql from 'alasql';

import { environment } from '../../../environments/environment';
import * as hlpApp from '../../helpers/app-helper';
import * as hlpSwal from '../../helpers/sweetalert2-helper';
import * as hlpPrimeNGTable from '../../helpers/primeng-table-helper';
import {
  FilaTotalesModel,
  RecaudacionModel,
  RecaudacionRegistrarPagoModel,
  RecaudacionResumenModel,
} from '../../models/recaudacion.model';
import { UnidadParaRecaudacionesModel } from '../../models/unidad.model';
import { EdificioModel } from '../../models/edificio.model';
import { EstatusRecaudacionModel } from '../../models/estatus-recaudacion.model';
import { FormaPagoModel } from '../../models/forma-pago.model';
import { RecaudacionesService } from '../../services/recaudaciones.service';
import { UnidadesService } from '../../services/unidades.service';
import { EstatusRecaudacionesService } from '../../services/estatus-recaudaciones.service';
import { FormasPagoService } from '../../services/formas-pago.service';
import { SesionUsuarioService } from '../../services/sesion-usuario.service';

@Component({
  selector: 'app-recaudaciones',
  templateUrl: './recaudaciones.component.html',
  styleUrls: ['./recaudaciones.component.css'],
})
export class RecaudacionesComponent implements OnInit {
  appData = environment;
  hlpApp = hlpApp;
  hlpPrimeNGTable = hlpPrimeNGTable;
  isDevelopment = isDevMode;

  // Tabla Recaudaciones
  // Columnas de la tabla
  RecaudacionesCols: any[] = [
    { header: 'Folio', width: '70px' },
    { header: 'Edificio' },
    { header: 'Unidad' },
    { header: '¿Quién paga?' },
    { header: 'Usuario paga' },
    { header: 'Año' },
    { header: 'Mes' },
    { header: 'Total' },
    { header: 'Estatus' },
    // Botones de acción
    { textAlign: 'center' },
  ];
  RecaudacionesFilter: any[] = [
    'folio',
    'edificio',
    'unidad',
    'perfil_usuario_paga',
    'usuario_paga',
    'anio',
    'mes',
    'estatus_recaudacion',
  ];

  Recaudaciones: RecaudacionResumenModel[] = [];
  RecaudacionesIDsFiltered: any[] = [];
  FilaTotales: FilaTotalesModel;
  Recaudacion: RecaudacionModel;
  RecaudacionRegistrarPago: RecaudacionRegistrarPagoModel;
  Unidades: UnidadParaRecaudacionesModel[] = [];
  UnidadesEdificio: UnidadParaRecaudacionesModel[] = [];
  Edificios: EdificioModel[] = [];
  EstatusRecaudaciones: EstatusRecaudacionModel[] = [];
  FormasPago: FormaPagoModel[] = [];
  fechaPagoLimite: Date = new Date();

  frmRecaudacion: FormGroup;
  frmRecaudacionRegistrarPago: FormGroup;
  mostrarDialogoEdicionRecaudacion: boolean = false;
  mostrarDialogoRegistrarPagoRecaudacion: boolean = false;
  mostrarDialogoReciboPagoRecaudacion: boolean = false;
  permitirAgregarEditar: boolean = false;
  esUsuarioAdministrador: boolean = false;

  constructor(
    private formBuilder: FormBuilder,
    private sesionUsuarioService: SesionUsuarioService,
    private recaudacionesService: RecaudacionesService,
    private unidadesService: UnidadesService,
    private estatusRecaudacionesService: EstatusRecaudacionesService,
    private formasPagoService: FormasPagoService,
  ) { }

  ngOnInit(): void {
    this.permitirAgregarEditar = [1, 2, 4].includes(this.sesionUsuarioService.obtenerIDPerfilUsuario());
    this.esUsuarioAdministrador = [1, 2].includes(this.sesionUsuarioService.obtenerIDPerfilUsuario());
    this.onActualizarInformacion();
  }

  private OrdenarRecaudaciones(recaudaciones: RecaudacionResumenModel[]) {
    return recaudaciones.sort((a, b) =>
      a.id_estatus_recaudacion.toString() + a.anio.toString() + a.mes.toString() + a.edificio + a.unidad >
        b.id_estatus_recaudacion.toString() + b.anio.toString() + b.mes.toString() + b.edificio + b.unidad
        ? 1
        : -1,
    );
  }

  private onCalcularFilaTotales(registros: RecaudacionResumenModel[]) {
    this.FilaTotales = new FilaTotalesModel();
    if (registros.length < 1) {
      return;
    }
    this.FilaTotales.total = registros.reduce((a, c) => {
      return a + +c.total;
    }, 0);
  }

  private FiltrarUnidadesEdificio(idEdificio: number = 0) {
    this.UnidadesEdificio = this.UnidadesEdificio = this.Unidades.filter((u) => u.id_edificio == idEdificio);
  }

  public onActualizarInformacion() {
    this.Recaudaciones = [];
    this.RecaudacionesIDsFiltered = [];

    hlpSwal.Cargando();

    this.recaudacionesService
      .ListarActivos()
      .toPromise()
      .then((r) => {
        this.Recaudaciones = this.OrdenarRecaudaciones(r['recaudaciones']);
        this.onCalcularFilaTotales(this.Recaudaciones);
      })
      .catch(async (e) => {
        await hlpSwal.Error(e);
      });

    this.estatusRecaudacionesService
      .ListarActivos()
      .toPromise()
      .then((r) => {
        // this.EstatusRecaudaciones = r['estatus_recaudaciones'].filter((r) => r.solo_recaudaciones == 1);
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
      })
      .finally(() => {
        hlpSwal.Cerrar();
      });
  }

  public onFilter(e) {
    this.RecaudacionesIDsFiltered = e.filteredValue.map((f) => f.id_recaudacion);
    this.onCalcularFilaTotales(e.filteredValue);
  }

  public onFilterReset(t) {
    this.RecaudacionesIDsFiltered = [];
    hlpPrimeNGTable.reset(t);
    this.onCalcularFilaTotales(this.Recaudaciones);
  }

  async onRecaudacionEditar(idRecaudacion: number = 0) {
    this.Unidades = [];
    this.Edificios = [];
    this.UnidadesEdificio = [];

    hlpSwal.Cargando();

    await this.unidadesService
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

    if (this.Unidades.length < 1) {
      hlpSwal.Advertencia('No se obtuvieron unidades para registrar recaudaciones.');
      return;
    }

    if (idRecaudacion > 0) {
      this.Recaudacion = await this.recaudacionesService
        .ListarRecaudacion(idRecaudacion)
        .toPromise()
        .then((r) => r['recaudacion'])
        .catch(async (e) => {
          await hlpSwal.Error(e).then(() => null);
        });
      if (this.Recaudacion == null) return;
    } else {
      this.Recaudacion = new RecaudacionModel();
    }
    this.FiltrarUnidadesEdificio(this.Recaudacion.id_edificio);

    hlpSwal.Cerrar();

    try {
      const anioMes =
        idRecaudacion > 0 ? new Date(this.Recaudacion.mes.toString() + '/01/' + this.Recaudacion.anio.toString()) : '';

      this.Recaudacion.fecha_limite_pago =
        idRecaudacion > 0
          ? new Date(this.Recaudacion.fecha_limite_pago + 'T00:00:00')
          : this.Recaudacion.fecha_limite_pago;
      this.Recaudacion.fecha_pago =
        this.Recaudacion.id_estatus_recaudacion == 3 ? new Date(this.Recaudacion.fecha_pago + 'T00:00:00') : new Date();

      this.frmRecaudacion = this.formBuilder.group(this.Recaudacion);
      this.frmRecaudacion.get('id_edificio').setValidators([Validators.required, Validators.min(1)]);
      this.frmRecaudacion.get('id_unidad').setValidators([Validators.required, Validators.min(1)]);
      this.frmRecaudacion.get('renta').setValidators([Validators.required, Validators.min(0.0)]);
      this.frmRecaudacion.get('agua').setValidators([Validators.required, Validators.min(0.0)]);
      this.frmRecaudacion.get('id_estatus_recaudacion').setValidators([Validators.required, Validators.min(1)]);
      this.frmRecaudacion.get('notas').setValidators([Validators.maxLength(255)]);
      this.frmRecaudacion.addControl('anio_mes', new FormControl(anioMes, Validators.required));
      this.frmRecaudacion.addControl('total', new FormControl(0));
      this.frmRecaudacion.get('perfil_usuario_paga').disable();
      this.frmRecaudacion.get('usuario_paga').disable();
      this.frmRecaudacion.get('renta').disable();
      this.frmRecaudacion.get('total').disable();
      this.frmRecaudacion.updateValueAndValidity();

      this.onCalcularTotal();
      this.onEstatusRecaudacionSeleccionado(this.Recaudacion.id_estatus_recaudacion);

      this.mostrarDialogoEdicionRecaudacion = true;
    } catch (e) {
      hlpSwal.Error(e);
    }
  }

  public onEdificioSeleccionado(idEdificio: number) {
    this.FiltrarUnidadesEdificio(idEdificio);

    this.frmRecaudacion.get('id_unidad').setValue(0);
    this.frmRecaudacion.get('id_perfil_usuario_paga').setValue(0);
    this.frmRecaudacion.get('perfil_usuario_paga').setValue('');
    this.frmRecaudacion.get('id_usuario_paga').setValue(0);
    this.frmRecaudacion.get('usuario_paga').setValue('');
    this.frmRecaudacion.get('renta').setValue(0);
  }

  public onUnidadSeleccionada(idUnidad: number) {
    const unidad = this.Unidades.filter((u) => u.id_unidad == idUnidad)[0];

    if (unidad) {
      this.frmRecaudacion.get('id_perfil_usuario_paga').setValue(unidad.id_perfil_usuario_paga);
      this.frmRecaudacion.get('perfil_usuario_paga').setValue(unidad.perfil_usuario_paga);
      this.frmRecaudacion.get('id_usuario_paga').setValue(unidad.id_usuario_paga);
      this.frmRecaudacion.get('usuario_paga').setValue(unidad.perfil_usuario_paga + ' - ' + unidad.usuario_paga);
      this.frmRecaudacion.get('renta').setValue(unidad.renta);
    }
    this.onCalcularTotal();
  }

  public onAnioMesSeleccionado(fecha: Date) {
    this.frmRecaudacion.get('anio').setValue(fecha.getFullYear());
    this.frmRecaudacion.get('mes').setValue(fecha.getMonth() + 1);
  }

  public onCalcularTotal() {
    const total =
      Number(this.frmRecaudacion.get('renta').value) +
      Number(this.frmRecaudacion.get('agua').value) +
      Number(this.frmRecaudacion.get('energia_electrica').value) +
      Number(this.frmRecaudacion.get('gas').value) +
      Number(this.frmRecaudacion.get('seguridad').value) +
      Number(this.frmRecaudacion.get('servicios_publicos').value) +
      Number(this.frmRecaudacion.get('otros_servicios').value);
    this.frmRecaudacion.get('total').setValue(total);
  }

  public onEstatusRecaudacionSeleccionado(idEstatusRecaudacion: number) {
    if (idEstatusRecaudacion == 3) {
      /* Fecha de pago */
      this.frmRecaudacion.get('fecha_pago').setValidators([Validators.required]);
      this.frmRecaudacion.get('fecha_pago').setValue(this.fechaPagoLimite);
      this.frmRecaudacion.get('fecha_pago').enable();
      /* Forma de pago */
      this.frmRecaudacion.get('id_forma_pago').setValidators([Validators.required, Validators.min(1)]);
      this.frmRecaudacion.get('id_forma_pago').setValue(0);
      this.frmRecaudacion.get('id_forma_pago').enable();
    } else {
      /* Fecha de pago */
      this.frmRecaudacion.get('fecha_pago').clearValidators();
      this.frmRecaudacion.get('fecha_pago').setValue(null);
      this.frmRecaudacion.get('fecha_pago').disable();
      /* Forma de pago */
      this.frmRecaudacion.get('id_forma_pago').clearValidators();
      this.frmRecaudacion.get('id_forma_pago').setValue(null);
      this.frmRecaudacion.get('id_forma_pago').disable();
    }
    this.onFormaPagoSeleccionado(this.frmRecaudacion.get('id_forma_pago').value);
    this.frmRecaudacion.updateValueAndValidity();
  }

  public onFormaPagoSeleccionado(idFormaPago: number = 0) {
    const frmFormularioUtilizar: FormGroup = this.mostrarDialogoEdicionRecaudacion
      ? this.frmRecaudacion
      : this.mostrarDialogoRegistrarPagoRecaudacion
        ? this.frmRecaudacionRegistrarPago
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

  onRecaudacionEditarGuardar() {
    if (!this.frmRecaudacion.valid) {
      this.frmRecaudacion.markAllAsTouched();
      hlpSwal.Error('Se detectaron errores en la información solicitada.');
      return;
    }

    let recaudacion = this.frmRecaudacion.getRawValue();

    recaudacion.fecha_limite_pago = hlpApp.formatDateToMySQL(recaudacion.fecha_limite_pago);
    recaudacion.fecha_pago = hlpApp.formatDateToMySQL(recaudacion.fecha_pago);
    delete recaudacion.anio_mes;
    delete recaudacion.condomino;
    delete recaudacion.edificio;
    delete recaudacion.estatus;
    delete recaudacion.estatus_recaudacion;
    delete recaudacion.id_edificio;
    delete recaudacion.perfil_usuario_paga;
    delete recaudacion.usuario_paga;
    delete recaudacion.unidad;
    delete recaudacion.total;

    hlpSwal
      .Pregunta({
        html: '¿Deseas guardar la información?',
        showLoaderOnConfirm: true,
        preConfirm: async () => {
          try {
            return await this.recaudacionesService.Guardar(recaudacion).toPromise();
          } catch (e) {
            return hlpSwal.Error(e).then(() => ({ err: true }));
          }
        },
        allowOutsideClick: () => !hlpSwal.estaCargando,
      })
      .then((r) => {
        if (r.value && !r.value.err && r.value.recaudacion) {
          const re = r.value.recaudacion;
          if (recaudacion.id_recaudacion == 0) {
            this.Recaudaciones.push(re);
          } else {
            this.Recaudaciones = this.Recaudaciones.map((C) => (C.id_recaudacion === re.id_recaudacion ? re : C));
          }
          this.Recaudaciones = this.OrdenarRecaudaciones(this.Recaudaciones);
          this.FilaTotales.total += +this.frmRecaudacion.get('total').value - +this.Recaudacion.total;
          if (re.id_estatus_recaudacion == 3) {
            this.onRecaudacionReciboPago(re.id_recaudacion);
          }
          hlpSwal.ExitoToast(r.value.msg);
          this.mostrarDialogoEdicionRecaudacion = false;
        }
      });
  }

  onRecaudacionEditarCancelar() {
    this.mostrarDialogoEdicionRecaudacion = false;
  }

  async onRecaudacionRegistrarPago(idRecaudacion: number = 0) {
    if (idRecaudacion == 0) {
      return;
    }

    try {
      this.frmRecaudacionRegistrarPago = this.formBuilder.group(new RecaudacionRegistrarPagoModel());
      this.frmRecaudacionRegistrarPago.get('id_recaudacion').setValue(idRecaudacion);
      this.frmRecaudacionRegistrarPago.get('fecha_pago').setValidators([Validators.required]);
      this.frmRecaudacionRegistrarPago.get('id_forma_pago').setValidators([Validators.required, Validators.min(1)]);

      this.mostrarDialogoRegistrarPagoRecaudacion = true;
    } catch (e) {
      hlpSwal.Error(e);
    }
  }

  onRecaudacionRegistrarPagoGuardar() {
    let id_recaudacion = this.frmRecaudacionRegistrarPago.get('id_recaudacion').value;
    let recaudacion = this.frmRecaudacionRegistrarPago.value;
    delete recaudacion.id_recaudacion;
    recaudacion.fecha_pago = hlpApp.formatDateToMySQL(recaudacion.fecha_pago);

    hlpSwal
      .Pregunta({
        html: '¿Deseas registrar el pago de la recaudación?',
        showLoaderOnConfirm: true,
        preConfirm: async () => {
          try {
            return await this.recaudacionesService.RegistrarPago(id_recaudacion, recaudacion).toPromise();
          } catch (e) {
            return hlpSwal.Error(e).then(() => ({ err: true }));
          }
        },
        allowOutsideClick: () => !hlpSwal.estaCargando,
      })
      .then((r) => {
        if (r.value && !r.value.err) {
          this.mostrarDialogoRegistrarPagoRecaudacion = false;
          this.Recaudaciones = this.OrdenarRecaudaciones(
            this.Recaudaciones.map((re) => (re.id_recaudacion === id_recaudacion ? r.value.recaudacion : re)),
          );
          this.onRecaudacionReciboPago(id_recaudacion);
          hlpSwal.ExitoToast(r.value.msg);
        }
      });
  }

  onRecaudacionRegistrarPagoCancelar() {
    this.mostrarDialogoRegistrarPagoRecaudacion = false;
  }

  async onRecaudacionReciboPago(idRecaudacion: number = 0) {
    hlpSwal.Cargando();

    this.Recaudacion = await this.recaudacionesService
      .ListarReciboPago(idRecaudacion)
      .toPromise()
      .then((r) => r['recibo_pago'])
      .catch(async (e) => {
        await hlpSwal.Error(e).then(() => null);
      });

    hlpSwal.Cerrar();

    this.mostrarDialogoReciboPagoRecaudacion = this.Recaudacion != null;
  }

  onRecaudacionEliminar(Recaudacion: RecaudacionResumenModel = null) {
    if (Recaudacion == null) {
      return;
    }

    hlpSwal
      .Pregunta({
        html: '¿Deseas eliminar la recaudación?<br><p class="pt-2 text-danger fw-bold">ESTE PROCESO ES IRREVERSIBLE.</p>',
        showLoaderOnConfirm: true,
        preConfirm: async () => {
          try {
            return await this.recaudacionesService.Eliminar(Recaudacion.id_recaudacion).toPromise();
          } catch (e) {
            return hlpSwal.Error(e).then(() => ({ err: true }));
          }
        },
        allowOutsideClick: () => !hlpSwal.estaCargando,
      })
      .then((r) => {
        if (r.value && !r.value.err) {
          this.Recaudaciones = this.Recaudaciones.filter(
            (r: RecaudacionResumenModel) => r.id_recaudacion != Recaudacion.id_recaudacion,
          );
          this.RecaudacionesIDsFiltered = this.RecaudacionesIDsFiltered.filter(
            (cm) => cm != Recaudacion.id_recaudacion,
          );
          this.FilaTotales.total -= +Recaudacion.total;
          hlpSwal.ExitoToast(r.value.msg);
        }
      });
  }
}
