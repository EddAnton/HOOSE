import { Component, OnInit, isDevMode } from '@angular/core';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';
import { DomSanitizer } from '@angular/platform-browser';
// import { DatePipe } from '@angular/common';

import { environment } from '../../../../environments/environment';
import * as hlpApp from '../../../helpers/app-helper';
import * as hlpSwal from '../../../helpers/sweetalert2-helper';
import * as hlpPrimeNGTable from '../../../helpers/primeng-table-helper';
import { CondominoResumenModel, CondominoModel } from '../../../models/usuario-condomino.model';
import { UsuariosCondominosService } from '../../../services/usuarios-condominos.service';
import { UnidadesService } from '../../../services/unidades.service';
import { CondominiosService } from '../../../services/condominios.service';
import { UnidadesEdificioModel } from '../../../models/unidad.model';
import { UsuariosService } from '../../../services/usuarios.service';
import { SesionUsuarioService } from '../../../services/sesion-usuario.service';

@Component({
  selector: 'app-catalogo-condominos',
  templateUrl: './catalogo-condominos.component.html',
  styleUrls: ['./catalogo-condominos.component.css'],
})
export class CatalogoCondominosComponent implements OnInit {
  appData = environment;
  hlpApp = hlpApp;
  hlpPrimeNGTable = hlpPrimeNGTable;
  isDevelopment = isDevMode;

  // Tabla Condominos
  // Columnas de la tabla
  CondominosCols: any[] = [
    { header: '', width: '80px' },
    { header: 'Nombre' },
    { header: 'Usuario' },
    { header: 'Email' },
    { header: 'Contacto' },
    { header: 'Unidad' },
    { header: 'Depósito', width: '90px' },
    { header: 'Renta', width: '90px' },
    { header: 'Condominio' },
    { header: 'Estatus', width: '70px' },
    // Botones de acción
    { textAlign: 'center', width: '90px' },
  ];
  CondominosFilter: any[] = ['nombre', 'usuario', 'email', 'unidad'];

  Condominos: CondominoResumenModel[] = [];
  Condomino: CondominoModel;
  UnidadesDisponiblesRenta: UnidadesEdificioModel[] = [];

  frmCondomino: FormGroup;
  frmCondominoDeshabilitar: FormGroup;
	mostrarDialogoEdicionCondomino: boolean = false;
	serviciosDisponibles: string[] = ['Agua', 'Energía Eléctrica', 'Gas', 'Limpia Pública', 'Otros'];
	serviciosSeleccionados: string[] = [];
	penalizacionOpciones: any[] = [{label: 'No', value: '0'}, {label: 'Sí', value: '1'}];
	cuotaMttoOpciones: any[] = [{label: 'No aplica', value: '0'}, {label: 'Sí, a cargo del Condómino', value: '1'}];
	diasPagoOpciones: any[] = [
		{label: 'Día 1', value: '1'}, {label: 'Día 5', value: '5'}, {label: 'Día 10', value: '10'},
		{label: 'Día 15', value: '15'}, {label: 'Día 20', value: '20'}, {label: 'Día 25', value: '25'},
		{label: 'Día 28', value: '28'}, {label: 'Último día del mes', value: '31'}
	];
	representacionOpciones: any[] = [{label: 'No tiene derecho', value: '0'}, {label: 'Sí, representa al Propietario', value: '1'}];
	serviciosOpciones: any[] = [{label: 'Agua', value: 'Agua'}, {label: 'Energía Eléctrica', value: 'Energía Eléctrica'}, {label: 'Gas', value: 'Gas'}, {label: 'Limpia Pública', value: 'Limpia Pública'}, {label: 'Otros', value: 'Otros'}];
  mostrarDialogoDeshabilitarCondomino: boolean = false;
  mostrarDialogoImagenCondomino: boolean = false;
  mostrarDialogoDetallesCondomino: boolean = false;
  mostrarDialogoContratoCondomino: boolean = false;
  srcImagen: string = null;
  srcIdentificacionAnverso: string = null;
  srcIdentificacionReverso: string = null;
  srcImagenMostrar: string = null;
  srcContrato: string = null;
  bImagenBorrar: boolean = false;
  bIdentificacionAnversoBorrar: boolean = false;
  bIdentificacionReversoBorrar: boolean = false;
  bContratoBorrar: boolean = false;
  permitirAgregarEditar: boolean = false;
	esSuperAdminSinCondominio: boolean = false;
	condominiosLista: any[] = [];
  esUsuarioAdministrador: boolean = false;

  constructor(
    private sesionUsuarioService: SesionUsuarioService,
    private condominosService: UsuariosCondominosService,
    private unidadesService: UnidadesService,
		private condominiosService: CondominiosService,
    private formBuilder: FormBuilder,
    private usuariosService: UsuariosService,
    private sanitizer: DomSanitizer,
  ) {
		this.frmCondomino = this.formBuilder.group(new CondominoModel());
		this.frmCondomino.addControl('apellidos', new FormControl(null));
		this.frmCondomino.addControl('fecha_limite_pago', new FormControl(1));
		this.frmCondomino.addControl('otros_servicios', new FormControl([]));
		this.frmCondomino.addControl('penalizacion_pago_tardio', new FormControl(0));
		this.frmCondomino.addControl('porcentaje_penalizacion', new FormControl(0));
		this.frmCondomino.addControl('cuota_mantenimiento_aplica', new FormControl(0));
		this.frmCondomino.addControl('representacion_asamblea', new FormControl(0));
		this.frmCondomino.addControl('contrato_fecha_firma', new FormControl(null));
		this.frmCondomino.addControl('contrato_fecha_vencimiento', new FormControl(null)); }

  ngOnInit(): void {
    this.permitirAgregarEditar = [1, 2, 4].includes(this.sesionUsuarioService.obtenerIDPerfilUsuario());
		const perfil = this.sesionUsuarioService.obtenerIDPerfilUsuario();
		const idCond = this.sesionUsuarioService.obtenerIDCondominioUsuario();
		this.esSuperAdminSinCondominio = perfil === 1 && (!idCond || idCond === 0);
		if (this.esSuperAdminSinCondominio) {
			this.condominiosService.Listar(true).toPromise().then((r: any) => {
				this.condominiosLista = (r['condominios'] || []).map((c: any) => ({ label: c.condominio, value: +c.id_condominio }));
			});
		}
    this.esUsuarioAdministrador = [1, 2].includes(this.sesionUsuarioService.obtenerIDPerfilUsuario());
    this.onActualizarInformacion();
  }

  private OrdenarCondominos(condominos: CondominoResumenModel[]) {
    return condominos.sort((a, b) => (a.nombre > b.nombre ? 1 : -1));
  }

  onOrdenarUnidades(unidades: UnidadesEdificioModel[] = []) {
    unidades = unidades.sort((a, b) => (a.unidad > b.unidad ? 1 : -1));
  }

  public onActualizarInformacion() {
    this.Condominos = [];

    hlpSwal.Cargando();

    (this.esUsuarioAdministrador ? this.condominosService.Listar() : this.condominosService.ListarActivos())
      .toPromise()
      .then((r) => {
        this.Condominos = this.OrdenarCondominos(r['condominos']);
      })
      .catch(async (e) => {
        await hlpSwal.Error(e);
      })
      .finally(() => {
        hlpSwal.Cerrar();
      });
  }

  /* async onCondominoEditar(idUsuario: number = 0) {
    hlpSwal.Cargando();

    this.UnidadesDisponiblesRenta = await this.unidadesService
      .ListarUnidadesDisponiblesRenta(condomino?.['fk_id_condominio'] || null)
      .toPromise()
      .then((r) => r['unidades'])
      .catch(async (e) => {
        await hlpSwal.Error(e).then(() => null);
      });

    if (idUsuario == 0 && this.UnidadesDisponiblesRenta.length < 1) {
      hlpSwal.Advertencia('No se encontraron unidades disponibles para renta.');
      return;
    }

    if (idUsuario > 0) {
      this.Condomino = await this.condominosService
        .ListarCondomino(idUsuario)
        .toPromise()
        .then((r) => r['condomino'])
        .catch(async (e) => {
          await hlpSwal.Error(e).then(() => null);
        });
      if (this.Condomino == null) return;
      this.UnidadesDisponiblesRenta.push({
        id_unidad: this.Condomino.id_unidad,
        unidad: this.Condomino.unidad + ' (' + this.Condomino.edificio + ')',
      });
      this.Condomino.fecha_inicio = new Date(this.Condomino.fecha_inicio + 'T00:00:00');
                              this.onOrdenarUnidades(this.UnidadesDisponiblesRenta);
    } else {
      this.Condomino = new CondominoModel();
    }
    // Convertir TODAS las fechas fuera del if/else
    ['fecha_fin', 'contrato_fecha_firma', 'contrato_fecha_vencimiento'].forEach(f => {
      const v = this.Condomino[f];
      if (v && typeof v === 'string' && v.indexOf('0000') < 0) { this.Condomino[f] = new Date(v + 'T00:00:00'); }
      else if (typeof v === 'string') { this.Condomino[f] = null; }
    });
    hlpSwal.Cerrar();

    try {
      this.srcImagen = this.Condomino.imagen
        ? environment.urlBackendUsuariosFiles + this.Condomino.id_usuario + '/' + this.Condomino.imagen
        : null;
      this.srcIdentificacionAnverso = this.Condomino.identificacion_anverso
        ? environment.urlBackendUsuariosFiles + this.Condomino.id_usuario + '/' + this.Condomino.identificacion_anverso
        : null;
      this.srcIdentificacionReverso = this.Condomino.identificacion_anverso
        ? environment.urlBackendUsuariosFiles + this.Condomino.id_usuario + '/' + this.Condomino.identificacion_reverso
        : null;
      this.srcContrato = this.Condomino.contrato
        ? environment.urlBackendUsuariosFiles + this.Condomino.id_usuario + '/' + this.Condomino.contrato
        : null;
      this.frmCondomino = this.formBuilder.group(this.Condomino);
      // Forzar fechas como Date
      const fechasCampos = {fecha_inicio: this.Condomino.fecha_inicio, fecha_fin: this.Condomino.fecha_fin};
      for (const [k, v] of Object.entries(fechasCampos)) {
        if (v instanceof Date) { this.frmCondomino.get(k)?.setValue(v); }
        else if (v && typeof v === 'string' && v !== '0000-00-00') { this.frmCondomino.get(k)?.setValue(new Date(v + 'T00:00:00')); }
        else { this.frmCondomino.get(k)?.setValue(null); }
      }
      // Agregar controles que no están en el modelo
      if (!this.frmCondomino.get('apellidos')) this.frmCondomino.addControl('apellidos', new FormControl(this.Condomino['apellidos'] || null));
      if (!this.frmCondomino.get('fecha_limite_pago')) this.frmCondomino.addControl('fecha_limite_pago', new FormControl(this.Condomino['fecha_limite_pago'] || 1));
      if (!this.frmCondomino.get('otros_servicios')) this.frmCondomino.addControl('otros_servicios', new FormControl([]));
      if (!this.frmCondomino.get('penalizacion_pago_tardio')) this.frmCondomino.addControl('penalizacion_pago_tardio', new FormControl(this.Condomino['penalizacion_pago_tardio'] || 0));
      if (!this.frmCondomino.get('porcentaje_penalizacion')) this.frmCondomino.addControl('porcentaje_penalizacion', new FormControl(this.Condomino['porcentaje_penalizacion'] || 0));
      if (!this.frmCondomino.get('cuota_mantenimiento_aplica')) this.frmCondomino.addControl('cuota_mantenimiento_aplica', new FormControl(this.Condomino['cuota_mantenimiento_aplica'] || 0));
      if (!this.frmCondomino.get('representacion_asamblea')) this.frmCondomino.addControl('representacion_asamblea', new FormControl(this.Condomino['representacion_asamblea'] || 0));
      if (!this.frmCondomino.get('contrato_fecha_firma')) this.frmCondomino.addControl('contrato_fecha_firma', new FormControl(this.Condomino['contrato_fecha_firma'] || null));
      if (!this.frmCondomino.get('contrato_fecha_vencimiento')) this.frmCondomino.addControl('contrato_fecha_vencimiento', new FormControl(this.Condomino['contrato_fecha_vencimiento'] || null));
      this.frmCondomino
        .get('nombre')
        .setValidators([Validators.required, Validators.minLength(3), Validators.maxLength(255)]);
      this.frmCondomino
        .get('usuario')
        .setValidators([
          Validators.required,
          Validators.minLength(3),
          Validators.maxLength(25),
          Validators.pattern('^[a-z0-9.]+$'),
        ]);
      this.frmCondomino
        .get('email')
        .setValidators([Validators.required, Validators.pattern('^[a-z0-9._%+-]+@[a-z0-9.-]+\\.[a-z]{2,4}$')]);
      this.frmCondomino
        .get('telefono')
        .setValidators([Validators.required, Validators.minLength(10), Validators.maxLength(12)]);
      this.frmCondomino.get('domicilio').setValidators([Validators.maxLength(255)]);
      this.frmCondomino.get('identificacion_folio').setValidators([Validators.maxLength(50)]);
      this.frmCondomino.get('identificacion_domicilio').setValidators([Validators.maxLength(255)]);
      this.frmCondomino.get('id_unidad').setValidators([Validators.required, Validators.min(1)]);
      this.frmCondomino.get('deposito').setValidators([Validators.required, Validators.min(0)]);
      this.frmCondomino.get('renta').setValidators([Validators.required, Validators.min(0.01)]);
      this.frmCondomino.get('fecha_inicio').setValidators([Validators.required]);

      this.frmCondomino.addControl('fk_id_condominio', new FormControl(null));
			this.frmCondomino.addControl('archivo_imagen', new FormControl());
      this.frmCondomino.addControl('archivo_identificacion_anverso', new FormControl());
      this.frmCondomino.addControl('archivo_identificacion_reverso', new FormControl());
      this.frmCondomino.addControl('archivo_contrato', new FormControl());
      // Controles dinámicos
      if (!this.frmCondomino.get('apellidos')) this.frmCondomino.addControl('apellidos', new FormControl(this.Condomino['apellidos'] || null));
      if (!this.frmCondomino.get('fecha_limite_pago')) this.frmCondomino.addControl('fecha_limite_pago', new FormControl(this.Condomino['fecha_limite_pago'] || '1'));
      if (!this.frmCondomino.get('otros_servicios')) this.frmCondomino.addControl('otros_servicios', new FormControl([]));
      if (!this.frmCondomino.get('penalizacion_pago_tardio')) this.frmCondomino.addControl('penalizacion_pago_tardio', new FormControl(this.Condomino['penalizacion_pago_tardio'] || '0'));
      if (!this.frmCondomino.get('porcentaje_penalizacion')) this.frmCondomino.addControl('porcentaje_penalizacion', new FormControl(this.Condomino['porcentaje_penalizacion'] || '0'));
      if (!this.frmCondomino.get('cuota_mantenimiento_aplica')) this.frmCondomino.addControl('cuota_mantenimiento_aplica', new FormControl(this.Condomino['cuota_mantenimiento_aplica'] || '0'));
      if (!this.frmCondomino.get('representacion_asamblea')) this.frmCondomino.addControl('representacion_asamblea', new FormControl(this.Condomino['representacion_asamblea'] || '0'));
      if (!this.frmCondomino.get('contrato_fecha_firma')) this.frmCondomino.addControl('contrato_fecha_firma', new FormControl(null));
      if (!this.frmCondomino.get('contrato_fecha_vencimiento')) this.frmCondomino.addControl('contrato_fecha_vencimiento', new FormControl(null));
      // Forzar fechas como Date
      ['fecha_fin', 'contrato_fecha_firma', 'contrato_fecha_vencimiento'].forEach(campo => {
        const v = this.frmCondomino.get(campo)?.value;
        if (v && typeof v === 'string' && v !== '0000-00-00') { this.frmCondomino.get(campo).setValue(new Date(v + 'T00:00:00')); }
        else if (!v || v === '0000-00-00') { this.frmCondomino.get(campo)?.setValue(null); }
      });
      // Cargar servicios como array
      if (this.Condomino['otros_servicios']) {
        this.frmCondomino.patchValue({ otros_servicios: this.Condomino['otros_servicios'].split(',').filter(s => s.length > 1) });
      }
      this.frmCondomino.updateValueAndValidity();

      this.bImagenBorrar = false;
      this.bIdentificacionAnversoBorrar = false;
      this.bIdentificacionReversoBorrar = false;
      this.bContratoBorrar = false;
      // Cargar otros_servicios como array
			if (this.Condomino && this.Condomino['otros_servicios']) {
				const svcs = this.Condomino['otros_servicios'].split(',').filter(s => s.length > 1);
				this.frmCondomino.patchValue({ otros_servicios: svcs });
			}
			// Convertir fechas string a Date
			// Convertir fechas explícitamente
			const v_fecha_inicio = this.frmCondomino.get('fecha_inicio')?.value;
			if (v_fecha_inicio && typeof v_fecha_inicio === 'string' && v_fecha_inicio !== '0000-00-00') { this.frmCondomino.get('fecha_inicio').setValue(new Date(v_fecha_inicio + 'T12:00:00')); }
			else if (v_fecha_inicio === '0000-00-00') { this.frmCondomino.get('fecha_inicio').setValue(null); }
			const v_fecha_fin = this.frmCondomino.get('fecha_fin')?.value;
			if (v_fecha_fin && typeof v_fecha_fin === 'string' && v_fecha_fin !== '0000-00-00') { this.frmCondomino.get('fecha_fin').setValue(new Date(v_fecha_fin + 'T12:00:00')); }
			else if (v_fecha_fin === '0000-00-00') { this.frmCondomino.get('fecha_fin').setValue(null); }
			const v_contrato_fecha_firma = this.frmCondomino.get('contrato_fecha_firma')?.value;
			if (v_contrato_fecha_firma && typeof v_contrato_fecha_firma === 'string' && v_contrato_fecha_firma !== '0000-00-00') { this.frmCondomino.get('contrato_fecha_firma').setValue(new Date(v_contrato_fecha_firma + 'T12:00:00')); }
			else if (v_contrato_fecha_firma === '0000-00-00') { this.frmCondomino.get('contrato_fecha_firma').setValue(null); }
			const v_contrato_fecha_vencimiento = this.frmCondomino.get('contrato_fecha_vencimiento')?.value;
			if (v_contrato_fecha_vencimiento && typeof v_contrato_fecha_vencimiento === 'string' && v_contrato_fecha_vencimiento !== '0000-00-00') { this.frmCondomino.get('contrato_fecha_vencimiento').setValue(new Date(v_contrato_fecha_vencimiento + 'T12:00:00')); }
			else if (v_contrato_fecha_vencimiento === '0000-00-00') { this.frmCondomino.get('contrato_fecha_vencimiento').setValue(null); }
			this.mostrarDialogoEdicionCondomino = true;
    } catch (e) {
      hlpSwal.Error(e);
    }
  } */

  async onCondominoEditar(condomino: CondominoResumenModel = null) {
    hlpSwal.Cargando();

    // Obtener las unidades disponibles para renta
    this.UnidadesDisponiblesRenta = await this.unidadesService
      .ListarUnidadesDisponiblesRenta(condomino?.['fk_id_condominio'] || null)
      .toPromise()
      .then((r) => r['unidades'])
      .catch(async (e) => {
        await hlpSwal.Error(e).then(() => null);
      });

    // Mensaje de error si no hay unidades disponibles para renta y
    // el condómino nuevo o reactivado después de finalización de contrato
    if (
      this.UnidadesDisponiblesRenta.length < 1 &&
      (condomino == null || (condomino.estatus == 0 && condomino.contrato_activo == 0))
    ) {
      hlpSwal.Advertencia('No se encontraron unidades disponibles para renta.');
      return;
    }

    // Determinar el id del usuario
    const idUsuario = condomino == null ? 0 : condomino.id_usuario;
    // Si el id del usuario es mayor a cero, obtiene la información a editar
    if (idUsuario > 0) {
      this.Condomino = await this.condominosService
        .ListarCondomino(idUsuario)
        .toPromise()
        .then((r) => r['condomino'])
        .catch(async (e) => {
          await hlpSwal.Error(e).then(() => null);
        });

      if (this.Condomino == null) return;

      if (condomino != null && condomino.contrato_activo == 1) {
        this.UnidadesDisponiblesRenta.push({
          id_unidad: this.Condomino.id_unidad,
          unidad: this.Condomino.unidad + ' (' + this.Condomino.edificio + ')',
        });
        this.Condomino.fecha_inicio = new Date(this.Condomino.fecha_inicio + 'T00:00:00');
                              } else {
        // this.Condomino.edificio = null;
        this.Condomino.id_unidad = 0;
        // this.Condomino.unidad = null;
        // this.Condomino.unidad_edificio = null;
        this.Condomino.deposito = 0;
        this.Condomino.renta = 0;
        this.Condomino.fecha_inicio = null;
      }
      this.onOrdenarUnidades(this.UnidadesDisponiblesRenta);
    } else {
      this.Condomino = new CondominoModel();
    }
    // Convertir TODAS las fechas fuera del if/else
    ['fecha_fin', 'contrato_fecha_firma', 'contrato_fecha_vencimiento'].forEach(f => {
      const v = this.Condomino[f];
      if (v && typeof v === 'string' && v.indexOf('0000') < 0) { this.Condomino[f] = new Date(v + 'T00:00:00'); }
      else if (typeof v === 'string') { this.Condomino[f] = null; }
    });
    hlpSwal.Cerrar();

    try {
      this.srcImagen = this.Condomino.imagen
        ? environment.urlBackendUsuariosFiles + this.Condomino.id_usuario + '/' + this.Condomino.imagen
        : null;
      this.srcIdentificacionAnverso = this.Condomino.identificacion_anverso
        ? environment.urlBackendUsuariosFiles + this.Condomino.id_usuario + '/' + this.Condomino.identificacion_anverso
        : null;
      this.srcIdentificacionReverso = this.Condomino.identificacion_anverso
        ? environment.urlBackendUsuariosFiles + this.Condomino.id_usuario + '/' + this.Condomino.identificacion_reverso
        : null;
      this.srcContrato = this.Condomino.contrato
        ? environment.urlBackendUsuariosFiles + this.Condomino.id_usuario + '/' + this.Condomino.contrato
        : null;
      this.frmCondomino = this.formBuilder.group(this.Condomino);
      // Forzar fechas como Date
      const fechasCampos = {fecha_inicio: this.Condomino.fecha_inicio, fecha_fin: this.Condomino.fecha_fin};
      for (const [k, v] of Object.entries(fechasCampos)) {
        if (v instanceof Date) { this.frmCondomino.get(k)?.setValue(v); }
        else if (v && typeof v === 'string' && v !== '0000-00-00') { this.frmCondomino.get(k)?.setValue(new Date(v + 'T00:00:00')); }
        else { this.frmCondomino.get(k)?.setValue(null); }
      }
      // Agregar controles que no están en el modelo
      if (!this.frmCondomino.get('apellidos')) this.frmCondomino.addControl('apellidos', new FormControl(this.Condomino['apellidos'] || null));
      if (!this.frmCondomino.get('fecha_limite_pago')) this.frmCondomino.addControl('fecha_limite_pago', new FormControl(this.Condomino['fecha_limite_pago'] || 1));
      if (!this.frmCondomino.get('otros_servicios')) this.frmCondomino.addControl('otros_servicios', new FormControl([]));
      if (!this.frmCondomino.get('penalizacion_pago_tardio')) this.frmCondomino.addControl('penalizacion_pago_tardio', new FormControl(this.Condomino['penalizacion_pago_tardio'] || 0));
      if (!this.frmCondomino.get('porcentaje_penalizacion')) this.frmCondomino.addControl('porcentaje_penalizacion', new FormControl(this.Condomino['porcentaje_penalizacion'] || 0));
      if (!this.frmCondomino.get('cuota_mantenimiento_aplica')) this.frmCondomino.addControl('cuota_mantenimiento_aplica', new FormControl(this.Condomino['cuota_mantenimiento_aplica'] || 0));
      if (!this.frmCondomino.get('representacion_asamblea')) this.frmCondomino.addControl('representacion_asamblea', new FormControl(this.Condomino['representacion_asamblea'] || 0));
      if (!this.frmCondomino.get('contrato_fecha_firma')) this.frmCondomino.addControl('contrato_fecha_firma', new FormControl(this.Condomino['contrato_fecha_firma'] || null));
      if (!this.frmCondomino.get('contrato_fecha_vencimiento')) this.frmCondomino.addControl('contrato_fecha_vencimiento', new FormControl(this.Condomino['contrato_fecha_vencimiento'] || null));
      this.frmCondomino
        .get('nombre')
        .setValidators([Validators.required, Validators.minLength(3), Validators.maxLength(255)]);
      this.frmCondomino
        .get('usuario')
        .setValidators([
          Validators.required,
          Validators.minLength(3),
          Validators.maxLength(25),
          Validators.pattern('^[a-z0-9.]+$'),
        ]);
      this.frmCondomino
        .get('email')
        .setValidators([Validators.required, Validators.pattern('^[a-z0-9._%+-]+@[a-z0-9.-]+\\.[a-z]{2,4}$')]);
      this.frmCondomino
        .get('telefono')
        .setValidators([Validators.required, Validators.minLength(10), Validators.maxLength(12)]);
      this.frmCondomino.get('domicilio').setValidators([Validators.maxLength(255)]);
      this.frmCondomino.get('identificacion_folio').setValidators([Validators.maxLength(50)]);
      this.frmCondomino.get('identificacion_domicilio').setValidators([Validators.maxLength(255)]);
      this.frmCondomino.get('id_unidad').setValidators([Validators.required, Validators.min(1)]);
      this.frmCondomino.get('deposito').setValidators([Validators.required, Validators.min(0)]);
      this.frmCondomino.get('renta').setValidators([Validators.required, Validators.min(0.01)]);
      this.frmCondomino.get('fecha_inicio').setValidators([Validators.required]);

      this.frmCondomino.addControl('fk_id_condominio', new FormControl(null));
			this.frmCondomino.addControl('archivo_imagen', new FormControl());
      this.frmCondomino.addControl('archivo_identificacion_anverso', new FormControl());
      this.frmCondomino.addControl('archivo_identificacion_reverso', new FormControl());
      this.frmCondomino.addControl('archivo_contrato', new FormControl());
      // Controles dinámicos
      if (!this.frmCondomino.get('apellidos')) this.frmCondomino.addControl('apellidos', new FormControl(this.Condomino['apellidos'] || null));
      if (!this.frmCondomino.get('fecha_limite_pago')) this.frmCondomino.addControl('fecha_limite_pago', new FormControl(this.Condomino['fecha_limite_pago'] || '1'));
      if (!this.frmCondomino.get('otros_servicios')) this.frmCondomino.addControl('otros_servicios', new FormControl([]));
      if (!this.frmCondomino.get('penalizacion_pago_tardio')) this.frmCondomino.addControl('penalizacion_pago_tardio', new FormControl(this.Condomino['penalizacion_pago_tardio'] || '0'));
      if (!this.frmCondomino.get('porcentaje_penalizacion')) this.frmCondomino.addControl('porcentaje_penalizacion', new FormControl(this.Condomino['porcentaje_penalizacion'] || '0'));
      if (!this.frmCondomino.get('cuota_mantenimiento_aplica')) this.frmCondomino.addControl('cuota_mantenimiento_aplica', new FormControl(this.Condomino['cuota_mantenimiento_aplica'] || '0'));
      if (!this.frmCondomino.get('representacion_asamblea')) this.frmCondomino.addControl('representacion_asamblea', new FormControl(this.Condomino['representacion_asamblea'] || '0'));
      if (!this.frmCondomino.get('contrato_fecha_firma')) this.frmCondomino.addControl('contrato_fecha_firma', new FormControl(null));
      if (!this.frmCondomino.get('contrato_fecha_vencimiento')) this.frmCondomino.addControl('contrato_fecha_vencimiento', new FormControl(null));
      // Forzar fechas como Date
      ['fecha_fin', 'contrato_fecha_firma', 'contrato_fecha_vencimiento'].forEach(campo => {
        const v = this.frmCondomino.get(campo)?.value;
        if (v && typeof v === 'string' && v !== '0000-00-00') { this.frmCondomino.get(campo).setValue(new Date(v + 'T00:00:00')); }
        else if (!v || v === '0000-00-00') { this.frmCondomino.get(campo)?.setValue(null); }
      });
      // Cargar servicios como array
      if (this.Condomino['otros_servicios']) {
        this.frmCondomino.patchValue({ otros_servicios: this.Condomino['otros_servicios'].split(',').filter(s => s.length > 1) });
      }
      this.frmCondomino.updateValueAndValidity();
      
      this.bImagenBorrar = false;
      this.bIdentificacionAnversoBorrar = false;
      this.bIdentificacionReversoBorrar = false;
      this.bContratoBorrar = false;
      // Agregar controles dinámicos
      if (!this.frmCondomino.get('apellidos')) this.frmCondomino.addControl('apellidos', new FormControl(this.Condomino['apellidos'] || null));
      if (!this.frmCondomino.get('fecha_limite_pago')) this.frmCondomino.addControl('fecha_limite_pago', new FormControl(this.Condomino['fecha_limite_pago'] || '1'));
      if (!this.frmCondomino.get('otros_servicios')) this.frmCondomino.addControl('otros_servicios', new FormControl([]));
      if (!this.frmCondomino.get('penalizacion_pago_tardio')) this.frmCondomino.addControl('penalizacion_pago_tardio', new FormControl(this.Condomino['penalizacion_pago_tardio'] || '0'));
      if (!this.frmCondomino.get('porcentaje_penalizacion')) this.frmCondomino.addControl('porcentaje_penalizacion', new FormControl(this.Condomino['porcentaje_penalizacion'] || '0'));
      if (!this.frmCondomino.get('cuota_mantenimiento_aplica')) this.frmCondomino.addControl('cuota_mantenimiento_aplica', new FormControl(this.Condomino['cuota_mantenimiento_aplica'] || '0'));
      if (!this.frmCondomino.get('representacion_asamblea')) this.frmCondomino.addControl('representacion_asamblea', new FormControl(this.Condomino['representacion_asamblea'] || '0'));
      if (!this.frmCondomino.get('contrato_fecha_firma')) this.frmCondomino.addControl('contrato_fecha_firma', new FormControl(null));
      if (!this.frmCondomino.get('contrato_fecha_vencimiento')) this.frmCondomino.addControl('contrato_fecha_vencimiento', new FormControl(null));
      // Cargar servicios como array
      if (this.Condomino['otros_servicios']) {
        this.frmCondomino.patchValue({ otros_servicios: this.Condomino['otros_servicios'].split(',').filter(s => s.length > 1) });
      }
      // Convertir fechas
      // Convertir fechas explícitamente
      const v_fecha_inicio = this.frmCondomino.get('fecha_inicio')?.value;
      if (v_fecha_inicio && typeof v_fecha_inicio === 'string' && v_fecha_inicio !== '0000-00-00') { this.frmCondomino.get('fecha_inicio').setValue(new Date(v_fecha_inicio + 'T12:00:00')); }
      else if (v_fecha_inicio === '0000-00-00') { this.frmCondomino.get('fecha_inicio').setValue(null); }
      const v_fecha_fin = this.frmCondomino.get('fecha_fin')?.value;
      if (v_fecha_fin && typeof v_fecha_fin === 'string' && v_fecha_fin !== '0000-00-00') { this.frmCondomino.get('fecha_fin').setValue(new Date(v_fecha_fin + 'T12:00:00')); }
      else if (v_fecha_fin === '0000-00-00') { this.frmCondomino.get('fecha_fin').setValue(null); }
      const v_contrato_fecha_firma = this.frmCondomino.get('contrato_fecha_firma')?.value;
      if (v_contrato_fecha_firma && typeof v_contrato_fecha_firma === 'string' && v_contrato_fecha_firma !== '0000-00-00') { this.frmCondomino.get('contrato_fecha_firma').setValue(new Date(v_contrato_fecha_firma + 'T12:00:00')); }
      else if (v_contrato_fecha_firma === '0000-00-00') { this.frmCondomino.get('contrato_fecha_firma').setValue(null); }
      const v_contrato_fecha_vencimiento = this.frmCondomino.get('contrato_fecha_vencimiento')?.value;
      if (v_contrato_fecha_vencimiento && typeof v_contrato_fecha_vencimiento === 'string' && v_contrato_fecha_vencimiento !== '0000-00-00') { this.frmCondomino.get('contrato_fecha_vencimiento').setValue(new Date(v_contrato_fecha_vencimiento + 'T12:00:00')); }
      else if (v_contrato_fecha_vencimiento === '0000-00-00') { this.frmCondomino.get('contrato_fecha_vencimiento').setValue(null); }
      this.mostrarDialogoEdicionCondomino = true;
    } catch (e) {
      hlpSwal.Error(e);
    }
  }

  async onImagenSeleccionada(event, idImagen: number = 0) {
    if (event.target.files.length != 1 || idImagen == 0) return;

    let file: any = event.target.files[0];
    file.src = await hlpApp
      .readFile(file)
      .then((r) => r)
      .catch((e) => {
        idImagen = 0;
        hlpSwal.Error(e);
      });

    if (!file.src) return;

    switch (idImagen) {
      case 1:
        this.bImagenBorrar = false;
        this.srcImagen = file.src;
        this.frmCondomino.patchValue({ archivo_imagen: file });
        this.frmCondomino.get('archivo_imagen').updateValueAndValidity();
        break;
      case 2:
        this.bIdentificacionAnversoBorrar = false;
        this.srcIdentificacionAnverso = file.src;
        this.frmCondomino.patchValue({ archivo_identificacion_anverso: file });
        this.frmCondomino.get('archivo_identificacion_anverso').updateValueAndValidity();
        break;
      case 3:
        this.bIdentificacionReversoBorrar = false;
        this.srcIdentificacionReverso = file.src;
        this.frmCondomino.patchValue({ archivo_identificacion_reverso: file });
        this.frmCondomino.get('archivo_identificacion_reverso').updateValueAndValidity();
        break;
    }
  }

  onImagenSeleccionadaCancelar(idImagen: number = 0) {
    switch (idImagen) {
      case 1:
        (<HTMLInputElement>document.getElementById('txtImagenArchivo')).value = '';
        this.frmCondomino.get('archivo_imagen').setValue(null);

        this.srcImagen = this.frmCondomino.get('imagen').value
          ? environment.urlBackendUsuariosFiles + this.Condomino.id_usuario + '/' + this.Condomino.imagen
          : null;
        this.bImagenBorrar = !this.srcImagen;
        break;
      case 2:
        (<HTMLInputElement>document.getElementById('txtAnversoIdentificacionArchivo')).value = '';
        this.frmCondomino.get('archivo_identificacion_anverso').setValue(null);

        this.srcIdentificacionAnverso = this.frmCondomino.get('identificacion_anverso').value
          ? environment.urlBackendUsuariosFiles +
          this.Condomino.id_usuario +
          '/' +
          this.Condomino.identificacion_anverso
          : null;
        this.bIdentificacionAnversoBorrar = !this.srcIdentificacionAnverso;
        break;
      case 3:
        (<HTMLInputElement>document.getElementById('txtReversoIdentificacionArchivo')).value = '';
        this.frmCondomino.get('archivo_identificacion_reverso').setValue(null);

        this.srcIdentificacionReverso = this.frmCondomino.get('identificacion_reverso').value
          ? environment.urlBackendUsuariosFiles +
          this.Condomino.id_usuario +
          '/' +
          this.Condomino.identificacion_reverso
          : null;
        this.bIdentificacionReversoBorrar = !this.srcIdentificacionReverso;
        break;
    }
  }

  onImagenEliminar(idImagen: number = 0) {
    if (idImagen == 0) {
      return;
    }
    switch (idImagen) {
      case 1:
        this.frmCondomino.get('imagen').setValue(null);
        break;
      case 2:
        this.frmCondomino.get('identificacion_anverso').setValue(null);
        break;
      case 3:
        this.frmCondomino.get('identificacion_reverso').setValue(null);
        break;
    }
    this.onImagenSeleccionadaCancelar(idImagen);
  }

  onContratoSeleccionado(event) {
    if (event.target.files.length != 1) return;
    let file: any;
    file = event.target.files[0];

    this.frmCondomino.patchValue({ archivo_contrato: file });
    this.frmCondomino.get('archivo_contrato').updateValueAndValidity();

    let reader = new FileReader();

    reader.onload = (e) => {
      file.src = reader.result;
      this.srcContrato = file.src;
    };
    reader.readAsDataURL(file);
  }

  onContratoSeleccionadoCancelar() {
    (<HTMLInputElement>document.getElementById('txtContratoArchivo')).value = '';
    this.frmCondomino.get('archivo_contrato').setValue(null);

    this.srcContrato = this.frmCondomino.get('contrato').value
      ? environment.urlBackendUsuariosFiles + this.Condomino.id_usuario + '/' + this.Condomino.contrato
      : null;
    this.bContratoBorrar = !this.srcContrato;
  }

  onContratoEliminado() {
    this.frmCondomino.get('contrato').setValue(null);
    this.onContratoSeleccionadoCancelar();
  }

  onImagenMostrar(imagen: string = null) {
    if (!imagen) {
      return;
    }
    this.srcImagenMostrar = imagen;
    this.mostrarDialogoImagenCondomino = true;
  }

  onCondominoGuardar() {
    if (!this.frmCondomino.valid) {
      this.frmCondomino.markAllAsTouched();
      hlpSwal.Error('Se detectaron errores en la información solicitada.');
      return;
    }

    let condomino = this.frmCondomino.value;
		if (this.esSuperAdminSinCondominio && this.frmCondomino.get('fk_id_condominio')) {
			condomino.fk_id_condominio = this.frmCondomino.get('fk_id_condominio').value;
		}

    condomino.borrar_imagen = this.bImagenBorrar ? 1 : 0;
    condomino.borrar_identificacion_anverso = this.bIdentificacionAnversoBorrar ? 1 : 0;
    condomino.borrar_identificacion_reverso = this.bIdentificacionReversoBorrar ? 1 : 0;
    condomino.borrar_contrato = this.bContratoBorrar ? 1 : 0;
    condomino.fecha_inicio = hlpApp.formatDateToMySQL(condomino.fecha_inicio);
    condomino.fecha_fin = hlpApp.formatDateToMySQL(condomino.fecha_fin);
    condomino.contrato_fecha_firma = hlpApp.formatDateToMySQL(condomino.contrato_fecha_firma);
    condomino.contrato_fecha_vencimiento = hlpApp.formatDateToMySQL(condomino.contrato_fecha_vencimiento);
    delete condomino.imagen;
    delete condomino.identificacion_anverso;
    delete condomino.identificacion_reverso;
    delete condomino.contrato;

    hlpSwal
      .Pregunta({
        html: '¿Deseas guardar la información?',
        showLoaderOnConfirm: true,
        preConfirm: async () => {
          try {
            return await this.condominosService.Guardar(condomino).toPromise();
          } catch (e) {
            return hlpSwal.Error(e).then(() => ({ err: true }));
          }
        },
        allowOutsideClick: () => !hlpSwal.estaCargando,
      })
      .then((r) => {
        if (r.value && !r.value.err && r.value.condomino) {
          const c = r.value.condomino;
          if (condomino.id_usuario == 0) {
            this.Condominos.push(c);
          } else {
            this.Condominos = this.Condominos.map((C) => (C.id_usuario === c.id_usuario ? c : C));
          }
          this.Condominos = this.OrdenarCondominos(this.Condominos);
          hlpSwal.ExitoToast(r.value.msg);
	this.mostrarDialogoEdicionCondomino = false;
          this.onActualizarInformacion();
        }
      });
  }

  onCondominoCancelar() {
    this.srcImagen = null;
    this.srcIdentificacionAnverso = null;
    this.srcIdentificacionReverso = null;
    this.srcContrato = null;
	this.mostrarDialogoEdicionCondomino = false;
  }

  onCondominoAlternarEstatus(condomino: CondominoResumenModel = null) {
    if (condomino.estatus == 0 && condomino.contrato_activo == 0) {
      hlpSwal
        .Pregunta({
          html: '<span class="text-danger"><b>El Condómino no tiene contrato activo.</b></span><br />¿Deseas reactivar a el Condómino en un nuevo contrato?',
        })
        .then((r) => {
          if (r.isConfirmed) {
            this.onCondominoEditar(condomino);
          }
        });
    } else {
      hlpSwal
        .Pregunta({
          html: '¿Deseas ' + (condomino.estatus == 1 ? 'des' : '') + 'habilitar el Condomino?',
          showLoaderOnConfirm: true,
          preConfirm: async () => {
            try {
              return await this.usuariosService.AlternarEstatus(condomino.id_usuario).toPromise();
            } catch (e) {
              return hlpSwal.Error(e).then(() => ({ err: true }));
            }
          },
          allowOutsideClick: () => !hlpSwal.estaCargando,
        })
        .then((r) => {
          if (r.value && !r.value.err) {
            condomino.estatus = condomino.estatus == 1 ? 0 : 1;
            hlpSwal.ExitoToast(r.value.msg);
          }
        });
    }
  }

  async onCondominoDeshabilitar(idUsuario: number = 0) {
    if (idUsuario == 0) {
      return;
    }

    try {
      /* this.frmCondominoDeshabilitar.updateValueAndValidity(); */
      this.frmCondominoDeshabilitar = this.formBuilder.group({
        id_usuario: [idUsuario, Validators.required],
        fecha_fin: ['', Validators.required],
      });

      this.mostrarDialogoDeshabilitarCondomino = true;
    } catch (e) {
      hlpSwal.Error(e);
    }
  }

  async onReiniciarContrasenia(idUsuario: number = 0) {
    if (idUsuario < 1) {
      return;
    }

    hlpSwal
      .Pregunta({
        html: '¿Deseas reiniciar la contraseña del Condómino?',
        showLoaderOnConfirm: true,
        preConfirm: async () => {
          try {
            return await this.usuariosService.ReiniciarContrasenia(idUsuario).toPromise();
          } catch (e) {
            return hlpSwal.Error(e).then(() => ({ err: true }));
          }
        },
        allowOutsideClick: () => !hlpSwal.estaCargando,
      })
      .then((r) => {
        if (r.value && !r.value.err) {
          hlpSwal.ExitoToast(r.value.msg);
        }
      });
  }

  onCondominoDeshabilitarGuardar() {
    let id_usuario = this.frmCondominoDeshabilitar.get('id_usuario').value;
    let condomino = {
      fecha_fin: hlpApp.formatDateToMySQL(this.frmCondominoDeshabilitar.get('fecha_fin').value),
    };

    hlpSwal
      .Pregunta({
        html: '¿Deseas finalizar el contrato con el Condómino?<br /><p class="text-danger"><b>ESTE PROCESO ES IRREVERSIBLE</b></p>',
        showLoaderOnConfirm: true,
        preConfirm: async () => {
          try {
            return await this.condominosService.FinalizarContrato(id_usuario, condomino).toPromise();
          } catch (e) {
            return hlpSwal.Error(e).then(() => ({ err: true }));
          }
        },
        allowOutsideClick: () => !hlpSwal.estaCargando,
      })
      .then((r) => {
        if (r.value && !r.value.err) {
          this.mostrarDialogoDeshabilitarCondomino = false;
          this.Condominos = this.Condominos.map((C) => (C.id_usuario === id_usuario ? r.value.condomino : C));
          hlpSwal.ExitoToast(r.value.msg);
        }
      });
  }

  onCondominoDeshabilitarCancelar() {
    this.mostrarDialogoDeshabilitarCondomino = false;
  }

  contratoURL() {
    return this.sanitizer.bypassSecurityTrustResourceUrl(this.srcContrato + '#toolbar=0&view=fitH');
  }

  async onContratoMostrar(Condomino: CondominoResumenModel = null) {
    if (Condomino != null) {
      this.srcContrato = this.Condomino.contrato
        ? environment.urlBackendUsuariosFiles + this.Condomino.id_usuario + '/' + this.Condomino.contrato
        : null;
    }

    this.mostrarDialogoContratoCondomino = this.srcContrato != null;
    if (this.mostrarDialogoContratoCondomino) {
      hlpSwal.Cargando();
    }
  }

  onContratoMostrado() {
    hlpSwal.Cerrar();
  }

  async onCondominoDetalles(idUsuario: number = 0) {
    if (idUsuario == 0) {
      return;
    }
    hlpSwal.Cargando();
    this.Condomino = await this.condominosService
      .ListarCondomino(idUsuario)
      .toPromise()
      .then((r) => r['condomino'])
      .catch(async (e) => {
        await hlpSwal.Error(e).then(() => null);
      })
      .finally(() => {
        hlpSwal.Cerrar();
      });
    this.srcImagen = this.Condomino.imagen
      ? environment.urlBackendUsuariosFiles + this.Condomino.id_usuario + '/' + this.Condomino.imagen
      : null;
    this.srcIdentificacionAnverso = this.Condomino.identificacion_anverso
      ? environment.urlBackendUsuariosFiles + this.Condomino.id_usuario + '/' + this.Condomino.identificacion_anverso
      : null;
    this.srcIdentificacionReverso = this.Condomino.identificacion_reverso
      ? environment.urlBackendUsuariosFiles + this.Condomino.id_usuario + '/' + this.Condomino.identificacion_reverso
      : null;

    this.mostrarDialogoDetallesCondomino = this.Condomino != null;
  }

	getCondominioColor(nombre: string): string {
		if (!nombre) return '#8a8f9e';
		const colores = [
			'#e91e8c', '#3B82F6', '#1BC99A', '#f59e0b', '#8b5cf6',
			'#ef4444', '#06b6d4', '#84cc16', '#f97316', '#ec4899',
		];
		let hash = 0;
		for (let i = 0; i < nombre.length; i++) {
			hash = nombre.charCodeAt(i) + ((hash << 5) - hash);
		}
		return colores[Math.abs(hash) % colores.length];
	}


	onGenerarUsuario() {
		const nombre = (this.frmCondomino.get('nombre').value || '').trim();
		const apellidos = (this.frmCondomino.get('apellidos')?.value || '').trim();
		if (!nombre || nombre.length < 2) return;
		let usuario = nombre.toLowerCase().split(/\s+/)[0];
		if (apellidos) {
			usuario += '.' + apellidos.toLowerCase().split(/\s+/)[0];
		}
		usuario = usuario.normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9.]/g, '');
		this.frmCondomino.patchValue({ usuario: usuario });
		}
	

	onServicioToggle(event, servicio: string) {
		if (event.target.checked) {
			this.serviciosSeleccionados.push(servicio);
		} else {
			this.serviciosSeleccionados = this.serviciosSeleccionados.filter(s => s !== servicio);
		}
		this.frmCondomino.patchValue({ otros_servicios: this.serviciosSeleccionados.join(',') });
	}


	async onCondominioChangeCond(event) {
		const condId = event?.value || null;
		if (condId) {
			this.UnidadesDisponiblesRenta = await this.unidadesService
				.ListarUnidadesDisponiblesRenta(condId)
				.toPromise()
				.then((r) => r['unidades'] || [])
				.catch(() => []);
		}
	}

}
