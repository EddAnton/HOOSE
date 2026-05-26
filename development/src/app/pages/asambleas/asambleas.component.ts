import { Component, OnInit, isDevMode, ViewChild } from '@angular/core';
import { FormArray, FormBuilder, FormGroup, Validators } from '@angular/forms';

import alasql from 'alasql';
import { environment } from '../../../environments/environment';
import * as hlpApp from '../../helpers/app-helper';
import * as hlpSwal from '../../helpers/sweetalert2-helper';
import * as hlpPrimeNGTable from '../../helpers/primeng-table-helper';
import { FormsValidator } from '../../validators/forms.validator';


import {
  ConvocatoriaResumenModel,
  ConvocatoriaModel,
  OrdenDelDiaModel,
  ActaModel,
  /* ActaOrdenDiaModel,
  ActaOrdenDiaVotacionModel,
  ActaOrdenDiaSentidoVotacionModel */
} from '../../models/asamblea.model';
// import { UsuarioActaAsamblea } from '../../models/usuario.model';
import { AsambleasService } from '../../services/asambleas.service';
import { UsuariosService } from '../../services/usuarios.service';
import { TiposAsambleasService } from '../../services/tipos-asambleas.service';
import { PropositoGeneralService } from '../../services/proposito-general.service';
import { ConfiguracionService } from '../../services/configuracion.service';
import { ComitesService } from '../../services/comites.service';
import { UsuariosAdministradoresService } from '../../services/usuarios-administradores.service';
import { CondominiosService } from '../../services/condominios.service';
import { PdfService } from '../../services/pdf.service';
import { SesionUsuarioService } from '../../services/sesion-usuario.service';
import { UnidadesService } from '../../services/unidades.service';
import { UsuariosCondominosService } from '../../services/usuarios-condominos.service';
import { UsuariosPropietariosService } from '../../services/usuarios-propietarios.service';

@Component({
  selector: 'app-asambleas',
  templateUrl: './asambleas.component.html',
  styleUrls: ['./asambleas.component.css'],
})
export class AsambleasComponent implements OnInit {
  appData = environment;
  hlpApp = hlpApp;
  hlpPrimeNGTable = hlpPrimeNGTable;
  isDevelopment = isDevMode;

  catSentidoVotacion: any[] = [
    { id: 0, sentido_votacion: 'Sin votar' },
    { id: 1, sentido_votacion: 'A favor' },
    { id: 2, sentido_votacion: 'En contra' },
    { id: 3, sentido_votacion: 'Abstención' },
  ];

  // Tabla Convocatorias
  // Columnas de la tabla

  opcionesTipoVotacion: any[] = [
    { label: 'Mayoría Simple', value: 'MAYORÍA SIMPLE' },
    { label: 'Mayoría Calificada (2/3 presentes)', value: 'MAYORÍA CALIFICADA' },
    { label: 'Por Indiviso (≥51% indiviso)', value: 'POR INDIVISO' },
  ];

  opcionesQuienConvoca: any[] = [
    { label: 'ADMINISTRADOR', value: 'ADMINISTRADOR' },
    { label: 'COMITÉ DE VIGILANCIA', value: 'COMITÉ DE VIGILANCIA' },
    { label: 'ADMINISTRADOR Y COMITÉ DE VIGILANCIA', value: 'ADMINISTRADOR Y COMITÉ DE VIGILANCIA' },
  ];
  ConvocatoriasCols: any[] = [
    { header: 'Fecha', width: '140px' },
    { header: 'Tipo' },
    { header: 'Lugar' },
    { header: 'Emite' },
    // Botones de acción
    { textAlign: 'center', width: '130px' },
  ];
  ConvocatoriasFilter: any[] = ['fecha', 'titulo'];

  // Convocatorias: ConvocatoriaModel[] = [];
  Convocatorias: ConvocatoriaResumenModel[] = [];
  ConvocatoriasFiltradas: ConvocatoriaResumenModel[] = [];

  // Filtros tabla
  mostrarFiltros: boolean = false;
  filtroAnio: number = null;
  filtroTipo: number = null;
  filtroEstatus: number = null;
  aniosDisponibles: any[] = [];
  tiposDisponibles: any[] = [
    { label: 'Ordinaria', value: 1 },
    { label: 'Extraordinaria', value: 2 },
  ];
  estatusDisponibles: any[] = [
    { label: 'Activa', value: 1 },
    { label: 'Cerrada', value: 0 },
  ];

  TiposAsambleas: any[] = [];
  quillEditors: any = {};
  ConvocatoriaPdf: any = null;
  mostrarPdfConvocatoria: boolean = false;
  imgLogoPdf: string = null;
  textosOriginalesConfig: any = {};
  textosOriginalesActa: any = {};
  private _updateTimer: any = null;
  idAsamblea: number = 0;
  Convocatoria: ConvocatoriaModel;
  fechaMinimaAsamblea: Date = new Date();
  fechaMinimaConvocatoria: Date = new Date();

  frmConvocatoria: FormGroup;
  mostrarDialogoConvocatoria: boolean = false;

  ModulosEditorDescripcion = {
    imageResize: {
      handleStyles: {
        backgroundColor: 'black',
        border: 'none',
        color: 'white',
      },
      modules: ['DisplaySize', 'Resize'],
    },
  };

  OrdenesDelDiaCols: any[] = [
    /* { header: '', width: '3rem' }, */
    { header: 'Descripción' },
    { header: 'Requiere votación', width: '120px' },
    // Botones de acción
    { textAlign: 'center', width: '90px' },
  ];
  OrdenesDelDia: OrdenDelDiaModel[] = [];
  idOrdenDelDia: number = 0;
  OrdenDelDia: OrdenDelDiaModel;
  frmOrdenDelDia: FormGroup;
  mostrarDialogoOrdenDelDia: boolean = false;

  PaseListaCols: any[] = [
    /* { header: '', width: '3rem' }, */
    { header: 'Unidad' },
    { header: 'Nombre' },
    /* { header: 'Perfil', width: '120px' }, */
    { header: 'Asiste', width: '120px' },
  ];
  // UsuariosActa: UsuarioActaAsamblea[] = [];
  idActa: number = 0;
  Acta: ActaModel;
  // ActaPaseLista: ActaPaseListaModel[] = [];
  // ActaPaseLista: any[] = [];
  // ActaOrdenDia: ActaOrdenDiaModel[] = [];
  // ActaOrdenDiaVotaciones: ActaOrdenDiaVotacionModel[] = [];
  bExisteQuorum: boolean;

  VotacionPuntoOrdenDiaCols: any[] = [
    /* { header: '', width: '3rem' }, */
    { header: 'Unidad' },
    { header: 'Nombre' },
    /* { header: 'Perfil', width: '120px' }, */
    { header: 'Sentido votación', width: '120px' },
  ];

  // frmActa: FormGroup = this.formBuilder.group(new ActaModel());
  frmActa: FormGroup;
  mostrarDialogoEdicionActa: boolean = false;
  mostrarDialogoEmisionActa: boolean = false;
  usuariosActaFlat: any[] = [];

  constructor(private formBuilder: FormBuilder,
    private tiposAsambleasService: TiposAsambleasService,
    private asambleasService: AsambleasService,
    private configuracionService: ConfiguracionService,
    private sesionUsuarioService: SesionUsuarioService,
    private pdfService: PdfService,
    private propositoGeneralService: PropositoGeneralService,
    private comitesService: ComitesService,
    private administradoresService: UsuariosAdministradoresService,
    private condominiosService: CondominiosService,
    private usuariosService: UsuariosService,
    private unidadesService: UnidadesService,
    private condominosService: UsuariosCondominosService,
    private propietariosService: UsuariosPropietariosService
  ) { }

  ngOnInit(): void {
    this.onActualizarInformacion();
    // Cargar domicilio del condominio para variables
    const idCond = this.sesionUsuarioService.obtenerIDCondominioUsuario();
    this.condominiosService.ListarCondominio(idCond).toPromise()
      .then((r: any) => { this.domicilioCondominioPdf = r?.['condominios']?.domicilio || ''; })
      .catch(() => {});
    // Cargar logo para PDFs como base64
    this.propositoGeneralService.LoginImagenes().toPromise().then((r: any) => {
      const data = r['data'] || [];
      const logo = data.find((d: any) => d.opcion === 'login_logo');
      if (logo) {
        const url = environment.urlBackendImagesFiles + logo.valor;
        fetch(url)
          .then(res => res.blob())
          .then(blob => {
            const reader = new FileReader();
            reader.onloadend = () => { this.imgLogoPdf = reader.result as string; };
            reader.readAsDataURL(blob);
          })
          .catch(() => {});
      }
    }).catch(() => {});
  }

  kpiTotalUnidades: number = 0;
  kpiTotalCondominos: number = 0;
  kpiTotalArrendatarios: number = 0;
  kpiTotalPropietarios: number = 0;
  get kpiPuntosDiscutidosTotal() { return this.ConvocatoriasFiltradas.reduce((acc, c) => acc + (+c.total_puntos || 0), 0); }

  get kpiTotalAsambleas() { return this.ConvocatoriasFiltradas.length; }
  get kpiOrdinarias() { return this.ConvocatoriasFiltradas.filter(c => String(c.id_tipo_asamblea) === '1').length; }
  get kpiExtraordinarias() { return this.ConvocatoriasFiltradas.filter(c => String(c.id_tipo_asamblea) === '2').length; }
  get kpiProximaAsamblea() {
    const futuras = this.Convocatorias.filter(c => new Date(c.fecha_hora) >= new Date() && +c.estatus === 1);
    if (!futuras.length) return 'Sin programar';
    futuras.sort((a, b) => new Date(a.fecha_hora).getTime() - new Date(b.fecha_hora).getTime());
    const f = new Date(futuras[0].fecha_hora);
    const meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    return f.getDate() + ' ' + meses[f.getMonth()] + ' ' + f.getFullYear();
  }
  get kpiProximaAsambleaHoras() {
    const futuras = this.Convocatorias.filter(c => new Date(c.fecha_hora) >= new Date() && +c.estatus === 1);
    if (!futuras.length) return null;
    futuras.sort((a, b) => new Date(a.fecha_hora).getTime() - new Date(b.fecha_hora).getTime());
    const c = futuras[0];
    const formatHora = (h: string) => {
      if (!h) return null;
      // Si viene como HH:MM:SS tomar solo HH:MM
      const partes = h.split(':');
      if (partes.length >= 2) return partes[0] + ':' + partes[1];
      return h;
    };
    const h1 = c.hora_primera_convocatoria ? '1a conv: ' + formatHora(c.hora_primera_convocatoria) : '';
    const h2 = c.hora_segunda_convocatoria ? ' | 2a conv: ' + formatHora(c.hora_segunda_convocatoria) : '';
    return (h1 + h2) || null;
  }

  get kpiPuntosDiscutidos() {
    let total = 0;
    this.Convocatorias.forEach((c: any) => {
      if (c.orden_dia) {
        c.orden_dia.forEach((o: any) => {
          const n = String(o.orden_dia || '').toUpperCase();
          if (!n.includes('PASE DE LISTA') && !n.includes('MINUTA') && !n.includes('LECTURA')) total++;
        });
      }
    });
    return total;
  }

  private OrdenarConvocatorias(asambleas: ConvocatoriaResumenModel[]) {
    return asambleas.sort((a, b) => (a.fecha_hora.toString() > b.fecha_hora.toString() ? 1 : -1));
  }

  private reconstruirAniosDisponibles() {
    const anios = [...new Set(this.Convocatorias.map(c => new Date(c.fecha_hora).getFullYear()))].sort((a, b) => b - a);
    this.aniosDisponibles = anios.map(a => ({ label: a.toString(), value: a }));
  }

  public aplicarFiltros() {
    let filtradas = [...this.Convocatorias];
    if (this.filtroAnio) filtradas = filtradas.filter(c => new Date(c.fecha_hora).getFullYear() === this.filtroAnio);
    if (this.filtroTipo != null) filtradas = filtradas.filter(c => Number(c.id_tipo_asamblea) === this.filtroTipo);
    if (this.filtroEstatus != null) filtradas = filtradas.filter(c => Number(c.estatus) === this.filtroEstatus);
    this.ConvocatoriasFiltradas = filtradas;
  }

  public limpiarFiltros() {
    this.filtroAnio = null;
    this.filtroTipo = null;
    this.filtroEstatus = null;
    this.ConvocatoriasFiltradas = [...this.Convocatorias];
  }

  /* private getTextoPEditor(idxPunto: number, esApertura: boolean = true) {
    let texto = null;
    const pEditor = document.getElementById('ordenDia' + (esApertura ? 'Apertura' : 'Cierre') + idxPunto);
    if (!pEditor)
      return texto;

    const qlEditor = pEditor.getElementsByClassName('ql-editor');
    if (qlEditor.length != 1)
      return texto;

    texto = qlEditor[0].innerHTML;
    return texto;
  }
 */

  private calcularExistenciaQuorum() {
    const tipoConv = this.frmActa?.get('tipo_convocatoria')?.value || 'PRIMERA';
    const totalUnidades = this.actaPaseLista.controls.length;
    const asistentes = this.actaPaseLista.value.filter((p: any) => p.asistencia).length;
    this.bExisteQuorum = tipoConv === 'SEGUNDA' ? asistentes > 0 : asistentes > totalUnidades / 2;
    /*
    let bExisteQuorum = tipoConv === 'SEGUNDA' ? asistentes > 0 : asistentes > totalUnidades / 2;
    if (bExisteQuorum) {
      this.frmActa.controls['cierre'].enable();
      this.frmActa.controls['cierre'].setValue('<p><br></p>');
    } else {
      this.frmActa.controls['cierre'].disable();
      this.frmActa.controls['cierre'].setValue('<p>Esta asamblea no se llevó a cabo por falt ade quorum.</p><p>Por tal motivo se acordó realizar una nueva convocatoria.</p>');

    }
    this.frmActa.get('existe_quorum').setValue(bExisteQuorum);
    */
  }

  /*   existenVotosPendientes() {
      let puntosRequierenVotacion = this.actaOrdenDia.controls.filter((o) => o.get('requiere_votacion').value);
      if (puntosRequierenVotacion.length < 1) {
        return null;
      }
      for (const punto of puntosRequierenVotacion) {
        if ((punto.get('votacion') as FormArray).controls.filter(
          (v) => v.get('id_sentido_votacion').value == 0).length > 0) {
          return true;
        }
      }
      return false;
    } */

  /* private setTextosPuntosOrdenDia(puntosOrdenDia: any) {
    puntosOrdenDia.forEach((punto, idxPunto) => {
      let pEditor = document.getElementById('ordenDiaApertura' + idxPunto);
      if (pEditor) {
        const qlEditor = pEditor.getElementsByClassName('ql-editor');
        if (qlEditor.length == 1) {
          qlEditor[0].innerHTML = punto.apertura ? punto.apertura : '<p><br /></p>';
        }
      }

      pEditor = null;
      pEditor = document.getElementById('ordenDiaCierre' + idxPunto);
      if (pEditor) {
        const qlEditor = pEditor.getElementsByClassName('ql-editor');
        if (qlEditor.length == 1) {
          qlEditor[0].innerHTML = punto.cierre ? punto.cierre : '<p><br /></p>';
        }
      }
    });
  } */

  public onActualizarInformacion() {
    this.Convocatorias = [];
    this.TiposAsambleas = [];

    hlpSwal.Cargando();

    // Cargar KPIs de unidades, propietarios y condóminos
    this.unidadesService.Listar().toPromise()
      .then((r: any) => { this.kpiTotalUnidades = (r?.['unidades'] || []).length; })
      .catch(() => {});
    this.propietariosService.Listar().toPromise()
      .then((r: any) => { this.kpiTotalPropietarios = (r?.['propietarios'] || []).length; })
      .catch(() => {});
    this.condominosService.Listar().toPromise()
      .then((r: any) => { this.kpiTotalCondominos = (r?.['condominos'] || []).length; })
      .catch(() => {});
    // kpiPuntosDiscutidosTotal se calcula desde ConvocatoriasFiltradas

    this.tiposAsambleasService.ListarActivos().toPromise().then((r) => {
      this.TiposAsambleas = r['tipos_asambleas'];
    })
      .catch(async (e) => {
        await hlpSwal.Error(e);
      });

    this.asambleasService
      .ListarActivos()
      .toPromise()
      .then((r) => {
        this.Convocatorias = this.OrdenarConvocatorias(r['asambleas']);
        this.reconstruirAniosDisponibles();
        this.aplicarFiltros();
      })
      .catch(async (e) => {
        await hlpSwal.Error(e);
      })
      .finally(() => {
        if (hlpSwal.estaCargando) {
          hlpSwal.Cerrar()
        }
      });
  }

  async onConvocatoriaEditar(idAsamblea: number = 0) {
    hlpSwal.Cargando();
    this.idAsamblea = idAsamblea;

    if (idAsamblea > 0) {
      this.Convocatoria = await this.asambleasService
        .ListarConvocatoria(idAsamblea)
        .toPromise()
        .then((r) => r['asamblea'])
        .catch(async (e) => {
          await hlpSwal.Error(e).then(() => null);
        });
      if (this.Convocatoria == null) return;

      // this.Convocatoria.fecha = new Date(this.Convocatoria.fecha + 'T00:00:00');
    } else {
      this.Convocatoria = new ConvocatoriaModel();
    }
    hlpSwal.Cerrar();

    try {
      this.fechaMinimaConvocatoria = new Date();
      this.fechaMinimaAsamblea = new Date();
      this.fechaMinimaAsamblea.setDate(this.fechaMinimaAsamblea.getDate() + 1);
      // Calcular hora redondeada a 30 min más cercano
      const ahora = new Date();
      const minutos = ahora.getMinutes();
      const horaRedondeada = new Date(ahora);
      if (minutos < 15) {
        horaRedondeada.setMinutes(0, 0, 0);
      } else if (minutos < 45) {
        horaRedondeada.setMinutes(30, 0, 0);
      } else {
        horaRedondeada.setHours(ahora.getHours() + 1, 0, 0, 0);
      }
      const horaSegunda = new Date(horaRedondeada);
      horaSegunda.setMinutes(horaRedondeada.getMinutes() + 30);

      const horaStringToDate = (hora: any): Date | null => {
        if (!hora) return null;
        if (hora instanceof Date) return hora;
        const partes = hora.toString().split(':');
        if (partes.length < 2) return null;
        const d = new Date();
        d.setHours(+partes[0], +partes[1], 0, 0);
        return d;
      };

      if (idAsamblea > 0) {
        this.Convocatoria.fecha_hora = new Date(this.Convocatoria.fecha_hora);
        this.Convocatoria.convocatoria_fecha = new Date(this.Convocatoria.convocatoria_fecha + ' 0000:00');
        this.Convocatoria.hora_primera_convocatoria = horaStringToDate(this.Convocatoria.hora_primera_convocatoria) as any;
        this.Convocatoria.hora_segunda_convocatoria = horaStringToDate(this.Convocatoria.hora_segunda_convocatoria) as any;
      } else {
        // Valores por default para nueva convocatoria
        const manana = new Date();
        manana.setDate(manana.getDate() + 1);
        this.Convocatoria.fecha_hora = manana;
        this.Convocatoria.convocatoria_fecha = new Date();
        // p-calendar timeOnly espera Date objects
        this.Convocatoria.hora_primera_convocatoria = horaRedondeada as any;
        this.Convocatoria.hora_segunda_convocatoria = horaSegunda as any;
      }

      this.frmConvocatoria = this.formBuilder.group(this.Convocatoria, {
        validators: FormsValidator.fechaMenorQue('convocatoria_fecha', 'fecha_hora'),
      });
      // Asegurar campos de hora si no están en el modelo
      if (!this.frmConvocatoria.get('hora_primera_convocatoria')) {
        this.frmConvocatoria.addControl('hora_primera_convocatoria', this.formBuilder.control(null));
      }
      if (!this.frmConvocatoria.get('hora_segunda_convocatoria')) {
        this.frmConvocatoria.addControl('hora_segunda_convocatoria', this.formBuilder.control(null));
      }
      this.frmConvocatoria.get('fecha_hora').setValidators([Validators.required]);
      this.frmConvocatoria.get('hora_primera_convocatoria').setValidators([Validators.required]);

      // Validador: hora segunda convocatoria >= hora primera + 30 min
      this.frmConvocatoria.get('hora_segunda_convocatoria').setValidators([
        (control) => {
          const segunda = control.value;
          const primera = this.frmConvocatoria?.get('hora_primera_convocatoria')?.value;
          if (!segunda || !primera) return null;
          const toMin = (t: any) => {
            const d = t instanceof Date ? t : new Date('1970-01-01T' + t);
            return d.getHours() * 60 + d.getMinutes();
          };
          if (toMin(segunda) < toMin(primera) + 30) {
            return { horaSegundaInvalida: true };
          }
          return null;
        }
      ]);
      this.frmConvocatoria.get('hora_primera_convocatoria').valueChanges.subscribe(() => {
        this.frmConvocatoria.get('hora_segunda_convocatoria').updateValueAndValidity();
      });

      this.frmConvocatoria.get('hora_primera_convocatoria').setValidators([Validators.required]);


      this.frmConvocatoria.get('id_tipo_asamblea').setValidators([Validators.min(1)]);
      this.frmConvocatoria
        .get('lugar')
        .setValidators([Validators.required, Validators.minLength(3), Validators.maxLength(250)]);
      this.frmConvocatoria.get('fundamento_legal').setValidators([Validators.required]);
      this.frmConvocatoria.get('convocatoria_cierre').setValidators([Validators.required]);
      this.frmConvocatoria.get('convocatoria_fecha').setValidators([Validators.required]);
      this.frmConvocatoria.get('convocatoria_ciudad').setValidators([Validators.required]);
      this.frmConvocatoria.get('convocatoria_quien_emite').setValidators([Validators.required]);

      // Precargar textos de configuración solo para convocatorias nuevas
      if (idAsamblea === 0) {
        try {
          const cfg: any = await this.configuracionService.Listar().toPromise();
          const config = cfg['config'] || {};
          // Valores disponibles al momento de precargar
          const valoresIniciales = {
            nombre_condominio: this.sesionUsuarioService.obtenerNombreCondominio(),
            domicilio_condominio: '',
            tipo_asamblea: '',
            fecha_asamblea: '',
            hora_primera_convocatoria: '',
            hora_segunda_convocatoria: '',
            lugar: '',
            ciudad_convocatoria: '',
            fecha_convocatoria: '',
            quien_convoca: '',
          };
          if (config['convocatoria_fundamento_legal']) {
            this.textosOriginalesConfig['fundamento_legal'] = config['convocatoria_fundamento_legal'];
            this.frmConvocatoria.get('fundamento_legal').setValue(
              this.reemplazarVariables(config['convocatoria_fundamento_legal'], valoresIniciales)
            );
          }
          if (config['convocatoria_disposiciones_generales']) {
            this.textosOriginalesConfig['disposiciones_generales'] = config['convocatoria_disposiciones_generales'];
            this.frmConvocatoria.get('disposiciones_generales').setValue(
              this.reemplazarVariables(config['convocatoria_disposiciones_generales'], valoresIniciales)
            );
          }
          if (config['convocatoria_cierre']) {
            this.textosOriginalesConfig['convocatoria_cierre'] = config['convocatoria_cierre'];
            this.frmConvocatoria.get('convocatoria_cierre').setValue(
              this.reemplazarVariables(config['convocatoria_cierre'], valoresIniciales)
            );
          }
          if (config['convocatoria_disposiciones_generales'] && this.frmConvocatoria.get('disposiciones_generales')) {
            this.frmConvocatoria.get('disposiciones_generales').setValue(config['convocatoria_disposiciones_generales']);
          }

          // Suscribir a cambios del formulario para actualizar variables en tiempo real
          const camposQueActualizan = ['lugar', 'fecha_hora', 'hora_primera_convocatoria',
            'hora_segunda_convocatoria', 'convocatoria_ciudad', 'convocatoria_fecha',
            'convocatoria_quien_emite', 'id_tipo_asamblea'];

          camposQueActualizan.forEach(campo => {
            this.frmConvocatoria.get(campo)?.valueChanges.subscribe(() => {
              this.actualizarVariablesConvocatoria();
            });
          });

        } catch(e) { console.error('Error cargando config:', e); }
      }

      this.OrdenesDelDia = [];
      if (this.Convocatoria.orden_dia) {
        this.OrdenesDelDia = this.Convocatoria.orden_dia.map((o) => {
          return {
            id_asamblea_orden_dia: o.id_asamblea_orden_dia,
            orden_dia: o.orden_dia,
            requiere_votacion: o.requiere_votacion == 1,
            tipo_votacion: o.tipo_votacion || 'MAYORÍA SIMPLE',
          }
        });
      }

      // Precargar puntos del orden del día para convocatoria nueva
      if (idAsamblea == 0 && this.OrdenesDelDia.length == 0) {
        try {
          const cfg: any = await this.configuracionService.Listar().toPromise();
          const config = cfg['config'] || {};
          this.OrdenesDelDia = [
            {
              id_asamblea_orden_dia: 1,
              orden_dia: 'PASE DE LISTA',
              requiere_votacion: false,
              tipo_votacion: 'MAYORÍA SIMPLE',
            },
            {
              id_asamblea_orden_dia: 2,
              orden_dia: 'LECTURA Y APROBACIÓN EN SU CASO DE LA MINUTA DE LA SESIÓN ANTERIOR',
              requiere_votacion: true,
              tipo_votacion: 'MAYORÍA SIMPLE',
            },
          ];
        } catch(e) { console.error('Error cargando orden del día por default:', e); }
      }

      this.idOrdenDelDia = this.OrdenesDelDia.length + 1;

      this.frmConvocatoria.updateValueAndValidity();

      this.mostrarDialogoConvocatoria = true;
    } catch (e) {
      hlpSwal.Error(e);
    }
  }

  onOrdenDelDiaEditar(ordenDelDia: OrdenDelDiaModel = null) {
    if (ordenDelDia == null) {
      ordenDelDia = new OrdenDelDiaModel();
    }
    // this.frmOrdenDelDia.patchValue(ordenDelDia);
    this.OrdenDelDia = Object.assign({}, ordenDelDia);
    this.frmOrdenDelDia = this.formBuilder.group(ordenDelDia);
    this.frmOrdenDelDia
      .get('orden_dia')
      .setValidators([Validators.required, Validators.minLength(3), Validators.maxLength(250)]);
    this.frmOrdenDelDia.updateValueAndValidity();

    this.mostrarDialogoOrdenDelDia = true;
  }

  onOrdenDelDiaEliminar(idOrdenDelDia: number) {
    hlpSwal.Pregunta('¿Deseas eliminar el orden del día?').then(async (r) => {
      if (r.isConfirmed) {
        this.OrdenesDelDia = this.OrdenesDelDia.filter((i: any) => i.id_asamblea_orden_dia !== idOrdenDelDia);
      }
    });
  }

  onGuardarOrdenDelDia() {
    if (!this.frmOrdenDelDia.valid) {
      this.frmOrdenDelDia.markAllAsTouched();
      hlpSwal.Error('Se detectaron errores en la información solicitada.');
      return;
    }

    let ordenDelDia = this.frmOrdenDelDia.value;
    hlpSwal.Pregunta('¿Deseas ' + (ordenDelDia.id_asamblea_orden_dia == 0 ? 'agregar' : 'actualizar') + ' la información?').then(async (r) => {
      if (r.isConfirmed) {
        ordenDelDia.orden_dia = ordenDelDia.orden_dia.toUpperCase();
        if (ordenDelDia.id_asamblea_orden_dia == 0) {
          ordenDelDia.id_asamblea_orden_dia = this.idOrdenDelDia++;
          this.OrdenesDelDia.push(ordenDelDia);
        } else {
          this.OrdenesDelDia = this.OrdenesDelDia.map((O) => (O.id_asamblea_orden_dia === ordenDelDia.id_asamblea_orden_dia ? ordenDelDia : O));
        }
        this.mostrarDialogoOrdenDelDia = false;
      }
    });
  }

  onCancelarEdicionOrdenDelDia() {
    /* hlpSwal.Pregunta('¿Deseas cancelar la ' +
      (this.frmOrdenDelDia.get('id_asamblea_orden_dia').value == 0 ? 'adición' : 'edición') +
      '?').then(async (r) => {
        if (r.isConfirmed) { */
    this.mostrarDialogoOrdenDelDia = false;
    /* }
  }); */
  }

  onConvocatoriaGuardar() {
    if (!this.frmConvocatoria.valid) {
      this.frmConvocatoria.markAllAsTouched();
      hlpSwal.Error('Se detectaron errores en la información solicitada.');
      return;
    }
    if (this.OrdenesDelDia.length < 1) {
      hlpSwal.Error('Se debe especificar al menos un punto en el orden del día.');
      return;
    }

    let asamblea = this.frmConvocatoria.value;
    asamblea.fecha_hora = hlpApp.formatDateToMySQL(asamblea.fecha_hora);
    asamblea.convocatoria_fecha = hlpApp.formatDateToMySQL(asamblea.convocatoria_fecha);
    const padH = (n: number) => n.toString().padStart(2, '0');
    const horaToString = (h: any): string => {
      if (!h) return '00:00';
      if (h instanceof Date) return padH(h.getHours()) + ':' + padH(h.getMinutes());
      if (typeof h === 'string' && h.includes(':')) return h.substring(0, 5);
      return '00:00';
    };
    asamblea.hora_primera_convocatoria = horaToString(asamblea.hora_primera_convocatoria);
    asamblea.hora_segunda_convocatoria = horaToString(asamblea.hora_segunda_convocatoria);
    asamblea.orden_dia = this.OrdenesDelDia.map((o) => {
      return {
        orden_dia: o.orden_dia,
        requiere_votacion: o.requiere_votacion ? 1 : 0,
        tipo_votacion: o.tipo_votacion || 'MAYORÍA SIMPLE',
      }
    });

    hlpSwal
      .Pregunta({
        html: '¿Deseas guardar la información?',
        showLoaderOnConfirm: true,
        preConfirm: async () => {
          try {
            return await this.asambleasService.GuardarConvocatoria(asamblea).toPromise();
          } catch (e) {
            return hlpSwal.Error(e).then(() => ({ err: true }));
          }
        },
        allowOutsideClick: () => !hlpSwal.estaCargando,
      })
      .then((r) => {
        if (r.value && !r.value.err && r.value.asamblea) {
          const c = r.value.asamblea;
          if (asamblea.id_asamblea == 0) {
            this.Convocatorias.push(c);
          } else {
            this.Convocatorias = this.Convocatorias.map((C) => (C.id_asamblea === c.id_asamblea ? c : C));
          }
          this.Convocatorias = this.OrdenarConvocatorias(this.Convocatorias);
          this.reconstruirAniosDisponibles();
          this.aplicarFiltros();
          hlpSwal.ExitoToast(r.value.msg);
          this.mostrarDialogoConvocatoria = false;
        }
      });
  }

  onConvocatoriaCancelar() {
    this.mostrarDialogoConvocatoria = false;
  }

  AdministradoresPdf: any[] = [];
  ActaPdf: any = null;
  mostrarPdfActa: boolean = false;
  ComiteVigilanciaPdf: any[] = [];
  FirmasPdf: any[] = [];
  FirmasConvocatoriaPdf: any[] = [];
  domicilioCondominioPdf: string = '';
  ciudadConvocatoria: string = '';

  async onGenerarPdfConvocatoria(idAsamblea: number) {
    if (idAsamblea == 0) return;
    hlpSwal.Cargando();
    try {
      const idCondominio = this.sesionUsuarioService.obtenerIDCondominioUsuario();
      const [convR, adminR, comitesR, condR] = await Promise.all([
        this.asambleasService.ListarConvocatoria(idAsamblea).toPromise(),
        this.administradoresService.Listar().toPromise(),
        this.comitesService.Listar().toPromise(),
        this.condominiosService.ListarCondominio(idCondominio).toPromise(),
      ]);
      this.domicilioCondominioPdf = condR?.['condominios']?.domicilio || '';

      this.ConvocatoriaPdf = convR['asamblea'];
      const quienConvoca = this.ConvocatoriaPdf?.convocatoria_quien_emite || '';
      const comites = comitesR['comites'] || [];

      this.FirmasConvocatoriaPdf = [];

      // Administrador(es) o Comité de Administración
      if (quienConvoca.includes('ADMINISTRADOR')) {
        const comiteAdmin = comites.find((c: any) =>
          c.tipo_comite?.toUpperCase().includes('ADMINISTRACI')
        );
        if (comiteAdmin?.miembros?.length > 0) {
          comiteAdmin.miembros.forEach((m: any) => {
            this.FirmasConvocatoriaPdf.push({ nombre: m.usuario, cargo: m.cargo_comite + ' - COMITÉ DE ADMINISTRACIÓN' });
          });
        } else {
          const admins = adminR['administradores'] || [];
          admins.forEach((a: any) => {
            this.FirmasConvocatoriaPdf.push({ nombre: a.nombre, cargo: 'ADMINISTRADOR' });
          });
        }
      }

      // Comité de Vigilancia
      if (quienConvoca.includes('VIGILANCIA')) {
        const comiteVigilancia = comites.find((c: any) =>
          c.tipo_comite?.toUpperCase().includes('VIGILANCIA')
        );
        if (comiteVigilancia?.miembros) {
          comiteVigilancia.miembros.forEach((m: any) => {
            this.FirmasConvocatoriaPdf.push({ nombre: m.usuario, cargo: m.cargo_comite + ' - COMITÉ DE VIGILANCIA' });
          });
        }
      }

      this.mostrarPdfConvocatoria = true;
      hlpSwal.Cerrar();
      setTimeout(() => {
        hlpApp.imprimirElemento('pdfConvocatoria');
        setTimeout(() => { this.mostrarPdfConvocatoria = false; }, 2000);
      }, 1500);
    } catch(e) {
      hlpSwal.Cerrar();
      await hlpSwal.Error(e);
    }
  }

  async onConvocatoriaDetalles(idAsamblea: number = 0) {
    if (idAsamblea == 0) {
      return;
    }

    hlpSwal.Cargando();
    this.Convocatoria = await this.asambleasService
      // .ListarConvocatoriaDetalle(idAsamblea)
      .ListarConvocatoria(idAsamblea)
      .toPromise()
      .then((r) => r['asamblea'])
      .catch(async (e) => {
        await hlpSwal.Error(e).then(() => null);
      })
      .finally(() => {
        hlpSwal.Cerrar();
      });

    this.Convocatoria.fecha_hora = new Date(this.Convocatoria.fecha_hora);
    this.Convocatoria.convocatoria_fecha = new Date(this.Convocatoria.convocatoria_fecha);
    this.frmConvocatoria = this.formBuilder.group(this.Convocatoria);
    this.frmConvocatoria.disable();
    this.OrdenesDelDia = [];
    if (this.Convocatoria.orden_dia) {
      this.OrdenesDelDia = this.Convocatoria.orden_dia.map((o) => {
        return {
          id_asamblea_orden_dia: o.id_asamblea_orden_dia,
          orden_dia: o.orden_dia,
          requiere_votacion: o.requiere_votacion == 1,
          tipo_votacion: o.tipo_votacion || 'MAYORÍA SIMPLE',
        }
      });
    }

    this.mostrarDialogoConvocatoria = this.Convocatoria != null;
  }

  onConvocatoriaEliminar(idAsamblea: number = 0) {
    hlpSwal
      .Pregunta({
        html: '¿Deseas eliminar el registro?',
        showLoaderOnConfirm: true,
        preConfirm: async () => {
          try {
            return await this.asambleasService.EliminarConvocatoria(idAsamblea).toPromise();
          } catch (e) {
            return hlpSwal.Error(e).then(() => ({ err: true }));
          }
        },
        allowOutsideClick: () => !hlpSwal.estaCargando,
      })
      .then((r) => {
        if (r.value && !r.value.err) {
          this.Convocatorias = this.Convocatorias.filter((a) => a.id_asamblea != idAsamblea);
          hlpSwal.ExitoToast(r.value.msg);
        }
      });
  }

  onQuillInit(editor: any, campo: string) {
    this.quillEditors[campo] = editor;
    // Si es editor del acta y hay textos originales, actualizar variables
    if (['apertura', 'cierre'].includes(campo) && this.textosOriginalesActa?.[campo]) {
      setTimeout(() => this.actualizarVariablesActa(), 200);
    }
  }

  actualizarVariablesConvocatoria() {
    if (!this.frmConvocatoria) return;
    const f = this.frmConvocatoria.value;

    // Obtener tipo asamblea
    const tipoAsamblea = this.TiposAsambleas.find(t => +t.id_tipo_asamblea === +f.id_tipo_asamblea)?.tipo_asamblea || '';

    // Formatear fechas
    const formatFecha = (d: Date) => {
      if (!d) return '';
      const meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
      return `${d.getDate()} de ${meses[d.getMonth()]} de ${d.getFullYear()}`;
    };
    const formatHora = (d: Date) => {
      if (!d) return '';
      return `${d.getHours().toString().padStart(2,'0')}:${d.getMinutes().toString().padStart(2,'0')}`;
    };

    const valores = {
      nombre_condominio: this.sesionUsuarioService.obtenerNombreCondominio(),
      domicilio_condominio: this.domicilioCondominioPdf || '',
      tipo_asamblea: tipoAsamblea,
      fecha_asamblea: f.fecha_hora ? formatFecha(new Date(f.fecha_hora)) : '',
      hora_primera_convocatoria: f.hora_primera_convocatoria ? formatHora(new Date(f.hora_primera_convocatoria)) : '',
      hora_segunda_convocatoria: f.hora_segunda_convocatoria ? formatHora(new Date(f.hora_segunda_convocatoria)) : '',
      lugar: f.lugar || '',
      ciudad_convocatoria: f.convocatoria_ciudad || '',
      fecha_convocatoria: f.convocatoria_fecha ? formatFecha(new Date(f.convocatoria_fecha)) : '',
      quien_convoca: f.convocatoria_quien_emite || '',
    };

    // Actualizar FormControls inmediatamente
    ['fundamento_legal', 'disposiciones_generales', 'convocatoria_cierre'].forEach(campo => {
      const textoOriginal = this.textosOriginalesConfig[campo];
      if (!textoOriginal) return;
      const nuevoValor = this.reemplazarVariables(textoOriginal, valores);
      this.frmConvocatoria.get(campo)?.setValue(nuevoValor, { emitEvent: false });
    });

    // Actualizar editores Quill con debounce para no robar el foco mientras escribe
    if (this._updateTimer) clearTimeout(this._updateTimer);
    this._updateTimer = setTimeout(() => {
      ['fundamento_legal', 'disposiciones_generales', 'convocatoria_cierre'].forEach(campo => {
        const textoOriginal = this.textosOriginalesConfig[campo];
        if (!textoOriginal) return;
        const ctrl = this.frmConvocatoria.get(campo);
        if (ctrl && this.quillEditors[campo]) {
          const qlEditor = this.quillEditors[campo].root;
          if (qlEditor) qlEditor.innerHTML = ctrl.value;
        }
      });
    }, 800);


  }

  getIconoVotacion(sentido: string): string {
    const s = (sentido || '').toLowerCase();
    if (s.includes('favor')) return '✔';
    if (s.includes('contra')) return '✖';
    if (s.includes('abstencion') || s.includes('abstención')) return '–';
    return '·';
  }

  getClaseVotacion(sentido: string): string {
    const s = (sentido || '').toLowerCase();
    if (s.includes('favor')) return 'text-success';
    if (s.includes('contra')) return 'text-danger';
    if (s.includes('abstencion') || s.includes('abstención')) return 'text-warning';
    return 'text-secondary';
  }

  votarTodos(punto: any, sentido: number) {
    const votaciones = punto.get('votacion') as any;
    votaciones.controls.forEach((v: any) => {
      v.get('id_sentido_votacion').setValue(sentido);
    });
    setTimeout(() => this.actualizarVariablesActa(), 100);
  }

  getClaseResultado(resultado: string): string {
    if (resultado === 'NO SE APRUEBA') return 'text-danger';
    if (resultado.indexOf('SE APRUEBA') === 0) return 'text-success';
    return 'text-secondary';
  }

  getIconoResultado(resultado: string): string {
    if (resultado === 'NO SE APRUEBA') return '✖';
    if (resultado.indexOf('SE APRUEBA') === 0) return '✔';
    return '·';
  }

  getResultadoVotacion(punto: any): string {
    const votaciones = punto.get('votacion')?.value || [];
    if (votaciones.length === 0) return 'SIN VOTAR';
    const sinVotar = votaciones.filter((v: any) => +v.id_sentido_votacion === 0).length;
    if (sinVotar === votaciones.length) return 'SIN VOTAR';

    const tipoVotacion = punto.get('tipo_votacion')?.value || 'MAYORÍA SIMPLE';
    const votosFavor = votaciones.filter((v: any) => +v.id_sentido_votacion === 1).length;
    const votosContra = votaciones.filter((v: any) => +v.id_sentido_votacion === 2).length;
    const totalVotos = votaciones.filter((v: any) => +v.id_sentido_votacion !== 0).length;

    const totalPresentes = votaciones.length; // todos los que pueden votar (presentes)
    let aprobado = false;
    if (tipoVotacion === 'MAYORÍA SIMPLE') {
      aprobado = votosFavor > votosContra;
    } else if (tipoVotacion === 'MAYORÍA CALIFICADA') {
      // 2/3 de los presentes (no solo de los que ya votaron)
      aprobado = totalPresentes > 0 && votosFavor >= Math.ceil((2/3) * totalPresentes);
    } else if (tipoVotacion === 'POR INDIVISO') {
      const pctFavor = votaciones
        .filter((v: any) => +v.id_sentido_votacion === 1)
        .reduce((sum: number, v: any) => sum + (+v.porcentaje_indiviso || 0), 0);
      aprobado = pctFavor > 50;
    }
    if (totalVotos === 0) return 'SIN VOTAR';
    const unanimidad = (votosFavor === totalPresentes && votosContra === 0) || 
                       (votosContra === totalPresentes && votosFavor === 0);
    const tipoAprobacion = unanimidad ? 'por unanimidad' : 'por mayoría';
    return aprobado ? 'SE APRUEBA ' + tipoAprobacion : 'NO SE APRUEBA';
  }

  actualizarVariablesActa() {
    if (!this.frmActa || !this.textosOriginalesActa) return;
    const meses = ['enero','febrero','marzo','abril','mayo','junio','julio',
      'agosto','septiembre','octubre','noviembre','diciembre'];
    const fechaActa = this.frmActa.get('fecha_hora')?.value;
    const fecha = fechaActa ? new Date(fechaActa) : null;
    const formatFecha = (d: Date) => d ? d.getDate() + ' de ' + meses[d.getMonth()] + ' de ' + d.getFullYear() : '';
    const formatHora = (d: Date) => d ? d.getHours().toString().padStart(2,'0') + ':' + d.getMinutes().toString().padStart(2,'0') : '';

    const presidente = this.frmActa.get('id_presidente')?.value;
    const secretario = this.frmActa.get('id_secretario')?.value;
    const escrutadoresArr = this.frmActa.get('id_escrutadores')?.value || [];
    const tipoConv = this.frmActa.get('tipo_convocatoria')?.value || 'PRIMERA';
    const totalAsistentes = this.actaPaseLista ? this.actaPaseLista.value.filter((p: any) => p.asistencia).length : 0;
    const totalUnidades = this.actaPaseLista?.controls?.length || 0;
    const pct = totalUnidades > 0 ? Math.round(totalAsistentes / totalUnidades * 100) : 0;

    const valores = {
      nombre_condominio: this.sesionUsuarioService.obtenerNombreCondominio(),
      domicilio_condominio: this.domicilioCondominioPdf || '',
      tipo_asamblea: this.TiposAsambleas.find((t: any) => +t.id_tipo_asamblea === +this.Convocatoria?.id_tipo_asamblea)?.tipo_asamblea || '',
      tipo_convocatoria: tipoConv === 'SEGUNDA' ? 'Segunda Convocatoria' : 'Primera Convocatoria',
      fecha_asamblea: fecha ? formatFecha(fecha) : (this.Convocatoria?.fecha_hora ? formatFecha(new Date(this.Convocatoria.fecha_hora)) : ''),
      hora_asamblea: fecha ? formatHora(fecha) : '',
      lugar: this.frmActa.get('lugar')?.value || this.Convocatoria?.lugar || '',
      ciudad_convocatoria: this.ciudadConvocatoria || '',
      presidente_asamblea: presidente?.usuario || '',
      secretario_asamblea: secretario?.usuario || '',
      escrutadores: Array.isArray(escrutadoresArr) ? escrutadoresArr.map((e: any) => e?.usuario || '').filter(Boolean).join(', ') : '',
      total_asistentes: totalAsistentes.toString(),
      total_unidades: (this.actaPaseLista?.controls?.length || 0).toString(),
      unidades: (this.actaPaseLista?.controls?.length || 0).toString(),
      porcentaje_quorum: pct + '%',
    };

    // Actualizar apertura y cierre del acta
    ['apertura', 'cierre'].forEach(campo => {
      const original = this.textosOriginalesActa[campo];
      if (!original) return;
      const nuevoValor = this.reemplazarVariablesActa(original, valores);
      this.frmActa.get(campo)?.setValue(nuevoValor, { emitEvent: false });
      if (this.quillEditors[campo]) {
        this.quillEditors[campo].root.innerHTML = nuevoValor;
      }
    });

    // Actualizar apertura/cierre de cada punto del orden del día
    this.actaOrdenDia?.controls?.forEach((punto: any, idx: number) => {
      const nombrePunto = (punto.get('orden_dia')?.value || '').toUpperCase();
      const requiereVotacion = punto.get('requiere_votacion')?.value;
      const esPaseLista = punto.get('es_pase_lista')?.value;
      const esMinuta = punto.get('es_minuta')?.value;

      // Calcular resultados de votación para este punto
      const votaciones = punto.get('votacion')?.value || [];
      const votosFavor = votaciones.filter((v: any) => v.id_sentido_votacion == 1).length;
      const votosContra = votaciones.filter((v: any) => v.id_sentido_votacion == 2).length;
      const votosAbstencion = votaciones.filter((v: any) => v.id_sentido_votacion == 3).length;
      const totalVotos = votosFavor + votosContra + votosAbstencion;
      const tipoVotacion = punto.get('tipo_votacion')?.value || 'MAYORÍA SIMPLE';
      let aprobado = false;
      if (tipoVotacion === 'MAYORÍA SIMPLE') {
        aprobado = votosFavor > votosContra;
      } else if (tipoVotacion === 'MAYORÍA CALIFICADA') {
        aprobado = totalVotos > 0 && votosFavor >= (2/3) * totalVotos;
      } else if (tipoVotacion === 'POR INDIVISO') {
        // Sumar % indiviso de unidades que votaron a favor
        const votacionesArray = punto.get('votacion')?.value || [];
        const pctFavor = votacionesArray
          .filter((v: any) => +v.id_sentido_votacion === 1)
          .reduce((sum: number, v: any) => sum + (+v.porcentaje_indiviso || 0), 0);
        aprobado = pctFavor > 50;
      }

      // Unanimidad: todos votan en el mismo sentido sin ninguno en contrario
      const unanimidad = totalVotos > 0 && (
        (votosFavor === totalVotos) || // todos a favor
        (votosContra === totalVotos)   // todos en contra
      );

      const tipoVotacionLabel = tipoVotacion === 'MAYORÍA SIMPLE'
        ? 'MAYORÍA SIMPLE (50% + 1)'
        : tipoVotacion === 'MAYORÍA CALIFICADA'
        ? 'MAYORÍA CALIFICADA (2/3 de los presentes)'
        : 'POR INDIVISO (% de la unidad)';

      const valoresPunto = {
        ...valores,
        nombre_punto: punto.get('orden_dia')?.value || '',
        votos_favor: votosFavor.toString(),
        votos_contra: votosContra.toString(),
        votos_abstencion: votosAbstencion.toString(),
        aprobacion: aprobado ? 'SE APRUEBA' : 'NO SE APRUEBA',
        tipo_aprobacion: unanimidad ? 'UNANIMIDAD' : 'MAYORÍA',
        tipo_votacion: tipoVotacionLabel,
      };

      let textoApertura = null;
      let textoCierre = null;

      if (esPaseLista) {
        textoApertura = this.textosOriginalesActa['orden_dia_pase_lista'];
      } else if (esMinuta) {
        textoCierre = this.textosOriginalesActa['orden_dia_minuta'];
      } else if (requiereVotacion) {
        textoCierre = this.textosOriginalesActa['orden_dia_votacion_cierre'];
      }

      if (textoApertura) {
        const nuevoValor = this.reemplazarVariablesActa(textoApertura, valoresPunto);
        punto.get('apertura')?.setValue(nuevoValor, { emitEvent: false });
        const editor = this.quillEditors['ordenDiaApertura' + idx];
        if (editor) editor.root.innerHTML = nuevoValor;
      }
      if (textoCierre) {
        const nuevoValor = this.reemplazarVariablesActa(textoCierre, valoresPunto);
        punto.get('cierre')?.setValue(nuevoValor, { emitEvent: false });
        const editor = this.quillEditors['ordenDiaCierre' + idx];
        if (editor) editor.root.innerHTML = nuevoValor;
      }
    });
  }

  limpiarHtmlPdf(html: string): string {
    if (!html) return '';
    // Eliminar solo párrafos vacíos del editor Quill, preservar negritas y demás formato
    return html
      .replace(/<p><br><\/p>/g, '')
      .replace(/<p>\s*<\/p>/g, '')
      .replace(/<h[1-6]><br><\/h[1-6]>/g, '')
      .trim();
  }

  reemplazarVariablesActa(texto: string, valores: any): string {
    if (!texto) return texto;
    return texto
      .replace(/{{nombre_condominio}}/g, valores.nombre_condominio || '<<Nombre del Condominio>>')
      .replace(/{{domicilio_condominio}}/g, valores.domicilio_condominio || '<<Dirección del Condominio>>')
      .replace(/{{tipo_asamblea}}/g, valores.tipo_asamblea || '<<Ordinaria/Extraordinaria>>')
      .replace(/{{tipo_convocatoria}}/g, valores.tipo_convocatoria || '<<Primera/Segunda>>')
      .replace(/{{fecha_asamblea}}/g, valores.fecha_asamblea || '<<DD de MMMM de YYYY>>')
      .replace(/{{hora_asamblea}}/g, valores.hora_asamblea || '<<HH:MM>>')
      .replace(/{{lugar}}/g, valores.lugar || '<<Lugar>>')
      .replace(/{{ciudad_convocatoria}}/g, valores.ciudad_convocatoria || '<<Ciudad>>')
      .replace(/{{unidades}}/g, valores.unidades || valores.total_unidades || '<<N>>')
      .replace(/{{nombre_punto}}/g, valores.nombre_punto || '<<Punto del Orden del Día>>')
      .replace(/{{tipo_votacion}}/g, valores.tipo_votacion || '<<Tipo de Votación>>')
      .replace(/{{tipo_votación}}/g, valores.tipo_votacion || '<<Tipo de Votación>>')
      .replace(/{{aprobacion}}/g, valores.aprobacion || '<<SE APRUEBA / NO SE APRUEBA>>')
      .replace(/{{aprobación}}/g, valores.aprobacion || '<<SE APRUEBA / NO SE APRUEBA>>')
      .replace(/{{tipo_aprobacion}}/g, valores.tipo_aprobacion || '<<UNANIMIDAD / MAYORÍA>>')
      .replace(/{{tipo_aprobación}}/g, valores.tipo_aprobacion || '<<UNANIMIDAD / MAYORÍA>>')
      .replace(/{{votos_favor}}/g, valores.votos_favor || '<<N>>')
      .replace(/{{votos_contra}}/g, valores.votos_contra || '<<N>>')
      .replace(/{{votos_abstencion}}/g, valores.votos_abstencion || '<<N>>')
      .replace(/{{votos_abstención}}/g, valores.votos_abstencion || '<<N>>')
      .replace(/{{presidente_asamblea}}/g, valores.presidente_asamblea || '<<Presidente>>')
      .replace(/{{secretario_asamblea}}/g, valores.secretario_asamblea || '<<Secretario>>')
      .replace(/{{escrutadores}}/g, valores.escrutadores || '<<Escrutadores>>')
      .replace(/{{porcentaje_quorum}}/g, valores.porcentaje_quorum || '<<XX%>>')
      .replace(/{{total_asistentes}}/g, valores.total_asistentes || '<<N>>')
      .replace(/{{unidades}}/g, valores.total_unidades || '<<Total Unidades>>');
  }

  // Reemplazar variables en texto con valores del formulario
  reemplazarVariables(texto: string, valores: any): string {
    if (!texto) return texto;
    return texto
      .replace(/{{nombre_condominio}}/g, valores.nombre_condominio || '<<Nombre del Condominio>>')
      .replace(/{{domicilio_condominio}}/g, valores.domicilio_condominio || '<<Dirección del Condominio>>')
      .replace(/{{tipo_asamblea}}/g, valores.tipo_asamblea || '<<Ordinaria/Extraordinaria>>')
      .replace(/{{fecha_asamblea}}/g, valores.fecha_asamblea || '<<DD de MMMM de YYYY>>')
      .replace(/{{hora_primera_convocatoria}}/g, valores.hora_primera_convocatoria || '<<HH:MM>>')
      .replace(/{{hora_segunda_convocatoria}}/g, valores.hora_segunda_convocatoria || '<<HH:MM>>')
      .replace(/{{lugar}}/g, valores.lugar || '<<Lugar>>')
      .replace(/{{ciudad_convocatoria}}/g, valores.ciudad_convocatoria || '<<Ciudad>>')
      .replace(/{{fecha_convocatoria}}/g, valores.fecha_convocatoria || '<<DD de MMMM de YYYY>>')
      .replace(/{{quien_convoca}}/g, valores.quien_convoca || '<<Quien Convoca>>');
  }

  // get existeQuorum() { return this.frmActa.get('existe_quorum') }
  // get votosPendientes() { return this.frmActa.get('votos_pendientes') }
  get actaPaseLista() { return this.frmActa?.get('actaPaseLista') as FormArray; }
  get actaOrdenDia() { return this.frmActa.get('actaOrdenDia') as FormArray; }

  // Usuarios con asistencia confirmada en el pase de lista
  get usuariosConAsistencia(): any[] {
    if (!this.actaPaseLista) return [];
    const idsConAsistencia = new Set<string>();
    this.actaPaseLista.controls.forEach((ctrl: any) => {
      if (ctrl.get('asistencia')?.value) {
        const idUsuario = ctrl.get('id_usuario')?.value;
        if (idUsuario) idsConAsistencia.add(String(idUsuario));
      }
    });
    return this.usuariosActaFlat.filter(u => idsConAsistencia.has(String(u.id_usuario)));
  }

  // Opciones filtradas para la mesa de asamblea (solo asistentes, excluye ya seleccionados)
  get opcionesPresidente() {
    const excluir = [
      this.frmActa?.get('id_secretario')?.value?.id_usuario,
      ...(this.frmActa?.get('id_escrutadores')?.value || []).map((e: any) => e?.id_usuario)
    ].filter(v => v);
    return this.usuariosConAsistencia.filter(u => !excluir.includes(u.id_usuario));
  }

  get opcionesSecretario() {
    const excluir = [
      this.frmActa?.get('id_presidente')?.value?.id_usuario,
      ...(this.frmActa?.get('id_escrutadores')?.value || []).map((e: any) => e?.id_usuario)
    ].filter(v => v);
    return this.usuariosConAsistencia.filter(u => !excluir.includes(u.id_usuario));
  }

  get opcionesEscrutadores() {
    const excluir = [
      this.frmActa?.get('id_presidente')?.value?.id_usuario,
      this.frmActa?.get('id_secretario')?.value?.id_usuario,
    ].filter(v => v);
    return this.usuariosConAsistencia.filter(u => !excluir.includes(u.id_usuario));
  }

  async onGenerarPdfActa(idAsamblea: number) {
    if (!idAsamblea || idAsamblea == 0) return;
    hlpSwal.Cargando();
    try {
      const [actaR, adminR, comitesR, condR] = await Promise.all([
        this.asambleasService.ListarActa(idAsamblea).toPromise(),
        this.administradoresService.Listar().toPromise(),
        this.comitesService.Listar().toPromise(),
        this.condominiosService.ListarCondominio(this.sesionUsuarioService.obtenerIDCondominioUsuario()).toPromise(),
      ]);

      this.ActaPdf = actaR['acta'];
      this.domicilioCondominioPdf = condR?.['condominios']?.domicilio || '';

      const quienEmite = this.ActaPdf?.quien_emite || '';
      const comites = comitesR['comites'] || [];
      this.FirmasPdf = [];

      // 1. Mesa de la asamblea
      const idPresidente = this.ActaPdf?.fk_id_presidente;
      const idSecretario = this.ActaPdf?.fk_id_secretario;
      const idEscrutadores = this.ActaPdf?.id_escrutadores
        ? JSON.parse(this.ActaPdf.id_escrutadores) : [];

      // Buscar usuarios en pase lista y votaciones para encontrar nombres
      const paseLista = this.ActaPdf?.pase_lista || [];
      const todosUsuariosActa = [...paseLista];
      // Agregar usuarios de votaciones
      (this.ActaPdf?.orden_dia || []).forEach((od: any) => {
        (od.votaciones || []).forEach((v: any) => {
          if (!todosUsuariosActa.find((u: any) => +u.id_usuario === +v.id_usuario)) {
            todosUsuariosActa.push(v);
          }
        });
      });
      const findUsuarioActa = (id: any) => todosUsuariosActa.find((u: any) => +u.id_usuario === +id);

      if (idPresidente) {
        const u = findUsuarioActa(idPresidente);
        if (u) this.FirmasPdf.push({ nombre: u.usuario, cargo: 'PRESIDENTE(A) DE LA ASAMBLEA' });
      }
      if (idSecretario) {
        const u = findUsuarioActa(idSecretario);
        if (u) this.FirmasPdf.push({ nombre: u.usuario, cargo: 'SECRETARIO(A) DE LA ASAMBLEA' });
      }
      // Escrutadores vienen con datos completos en el JSON
      if (Array.isArray(idEscrutadores)) {
        idEscrutadores.forEach((e: any) => {
          const nombre = e?.usuario || findUsuarioActa(e?.id_usuario || e)?.usuario || '________________';
          this.FirmasPdf.push({ nombre, cargo: 'ESCRUTADOR(A)' });
        });
      }

      // 2. Administrador(es) o Comité de Administración
      if (quienEmite.includes('ADMINISTRADOR')) {
        const comiteAdmin = comites.find((c: any) => c.tipo_comite?.toUpperCase().includes('ADMINISTRACI'));
        if (comiteAdmin?.miembros?.length > 0) {
          comiteAdmin.miembros.forEach((m: any) => {
            this.FirmasPdf.push({ nombre: m.usuario, cargo: m.cargo_comite + ' - COMITÉ DE ADMINISTRACIÓN' });
          });
        } else {
          const admins = adminR['administradores'] || [];
          admins.forEach((a: any) => {
            this.FirmasPdf.push({ nombre: a.nombre, cargo: 'ADMINISTRADOR' });
          });
        }
      }

      // 3. Comité de Vigilancia
      if (quienEmite.includes('VIGILANCIA')) {
        const comiteV = comites.find((c: any) => c.tipo_comite?.toUpperCase().includes('VIGILANCIA'));
        if (comiteV?.miembros) {
          comiteV.miembros.forEach((m: any) => {
            this.FirmasPdf.push({ nombre: m.usuario, cargo: m.cargo_comite + ' - COMITÉ DE VIGILANCIA' });
          });
        }
      }

      this.mostrarPdfActa = true;
      hlpSwal.Cerrar();
      setTimeout(() => {
        hlpApp.imprimirElemento('pdfActa');
        setTimeout(() => { this.mostrarPdfActa = false; }, 2000);
      }, 1500);
    } catch(e) {
      hlpSwal.Cerrar();
      await hlpSwal.Error(e);
    }
  }

  async onActaEditar(Convocatoria: ConvocatoriaResumenModel) {
    if (Convocatoria.id_acta > 0) {
      return;
    }
    this.idAsamblea = Convocatoria.id_asamblea;

    /*  hlpSwal.Cargando();
 
     this.Acta = await this.asambleasService
       .ListarActa(this.idAsamblea)
       .toPromise()
       .then((r) => r['acta'])
       .catch(async (e) => {
         await hlpSwal.Error(e).then(() => null);
       });
 
     if (!this.Acta) { */
    this.Acta = new ActaModel();
    this.idActa = 0;
    this.bExisteQuorum = false;
    this.textosOriginalesActa = {};

    /* } else {
      this.idActa = this.Acta.id_acta;
      // this.Acta.finalizada = this.Acta.finalizada == 1;
    } */

    // hlpSwal.Cerrar();

    try {
      this.fechaMinimaAsamblea = new Date();
      this.Acta.fecha_hora = new Date(this.Acta.fecha_hora);

      this.frmActa = this.formBuilder.group({
        id_acta: [0],
        tipo_convocatoria: ['PRIMERA'],
        fecha_hora: [new Date()],
        lugar: [null],
        apertura: [null],
        cierre: [null],
        quien_emite: [null],
        total_unidades: [0],
        estatus: [0],
        id_presidente: [null],
        id_secretario: [null],
        id_escrutadores: [[]],
        existe_quorum: [false],
        actaPaseLista: this.formBuilder.array([]),
        actaOrdenDia: this.formBuilder.array([]),
      });
      this.frmActa.patchValue(this.Acta);

      this.frmActa.get('fecha_hora').setValidators([Validators.required]);
      this.frmActa
        .get('lugar')
        .setValidators([Validators.required, Validators.minLength(3), Validators.maxLength(250)]);
      this.frmActa.get('apertura').setValidators([Validators.required]);
      this.frmActa.get('cierre').setValidators([Validators.required]);
      this.frmActa.get('quien_emite').setValidators([Validators.required, Validators.minLength(3), Validators.maxLength(250)]);

      // Cargar detalle completo de la convocatoria para tener todos los datos
      try {
        const detalleR: any = await this.asambleasService.ListarConvocatoria(this.idAsamblea).toPromise();
        if (detalleR?.asamblea) {
          this.Convocatoria = { ...this.Convocatoria, ...detalleR.asamblea };
          this.ciudadConvocatoria = detalleR.asamblea.convocatoria_ciudad || '';
        }
      } catch(e) { console.error('Error cargando detalle convocatoria:', e); }

      // Cargar detalle completo de la convocatoria para tener todos los datos
      try {
        const detalleR: any = await this.asambleasService.ListarConvocatoria(this.idAsamblea).toPromise();
        if (detalleR?.asamblea) {
          this.Convocatoria = { ...this.Convocatoria, ...detalleR.asamblea };
          this.ciudadConvocatoria = detalleR.asamblea.convocatoria_ciudad || '';
        }
      } catch(e) { console.error('Error cargando detalle convocatoria:', e); }

      // Precargar textos de configuración para acta nueva
      try {
        const cfg: any = await this.configuracionService.Listar().toPromise();
        const config = cfg['config'] || {};
        const meses = ['enero','febrero','marzo','abril','mayo','junio','julio',
          'agosto','septiembre','octubre','noviembre','diciembre'];
        const fechaAsamblea = this.Convocatoria?.fecha_hora ? new Date(this.Convocatoria.fecha_hora) : null;
        const formatFecha = (d: Date) => d ? d.getDate() + ' de ' + meses[d.getMonth()] + ' de ' + d.getFullYear() : '';
        const formatHora = (d: Date) => d ? d.getHours().toString().padStart(2,'0') + ':' + d.getMinutes().toString().padStart(2,'0') : '';

        const valoresActa = {
          nombre_condominio: this.sesionUsuarioService.obtenerNombreCondominio(),
          domicilio_condominio: this.domicilioCondominioPdf || '',
          tipo_asamblea: this.TiposAsambleas.find((t: any) => +t.id_tipo_asamblea === +this.Convocatoria?.id_tipo_asamblea)?.tipo_asamblea || '',
          tipo_convocatoria: '',
          fecha_asamblea: fechaAsamblea ? formatFecha(fechaAsamblea) : '',
          hora_asamblea: fechaAsamblea ? formatHora(fechaAsamblea) : '',
          lugar: this.Convocatoria?.lugar || '',
          ciudad_convocatoria: this.ciudadConvocatoria || '',
          presidente_asamblea: '',
          secretario_asamblea: '',
          escrutadores: '',
          porcentaje_quorum: '',
          total_asistentes: '',
        };
        if (config['acta_apertura']) {
          this.textosOriginalesActa['apertura'] = config['acta_apertura'];
          this.frmActa.get('apertura').setValue(this.reemplazarVariablesActa(config['acta_apertura'], valoresActa));
        }
        if (config['acta_cierre']) {
          this.textosOriginalesActa['cierre'] = config['acta_cierre'];
          this.frmActa.get('cierre').setValue(this.reemplazarVariablesActa(config['acta_cierre'], valoresActa));
        }
        if (config['orden_dia_pase_lista_apertura']) {
          this.textosOriginalesActa['orden_dia_pase_lista'] = config['orden_dia_pase_lista_apertura'];
        }
        if (config['orden_dia_minuta_apertura']) {
          this.textosOriginalesActa['orden_dia_minuta'] = config['orden_dia_minuta_apertura'];
        }
        if (config['orden_dia_votacion_cierre']) {
          this.textosOriginalesActa['orden_dia_votacion_cierre'] = config['orden_dia_votacion_cierre'];
        }
        // Actualizar con datos reales después de un momento
        setTimeout(() => this.actualizarVariablesActa(), 500);
      } catch(e) { console.error('Error cargando config acta:', e); }

      const usuariosActa = await this.usuariosService.ListarUsuariosActaAsambleas().toPromise()
        .then((r) => r['usuarios'])
        .catch(async (e) => {
          await hlpSwal.Error(e).then(() => null);
        });

      // Lista plana de todos los usuarios para la mesa de asamblea
      this.usuariosActaFlat = usuariosActa
        ? usuariosActa.reduce((acc: any[], unidad: any) => {
            return acc.concat(unidad.usuarios.map((u: any) => ({
              ...u,
              label: u.usuario + ' - ' + u.perfil_usuario,
            })));
          }, [])
        : [];

      // Reiniciamos selecciones de mesa
      this.frmActa.get('id_presidente').setValue(null);
      this.frmActa.get('id_secretario').setValue(null);
      this.frmActa.get('id_escrutadores').setValue([]);

      // Suscribir a cambios para actualizar variables del acta
      ['id_presidente', 'id_secretario', 'id_escrutadores', 'tipo_convocatoria', 'fecha_hora', 'lugar'].forEach(campo => {
        this.frmActa.get(campo)?.valueChanges.subscribe(() => {
          setTimeout(() => this.actualizarVariablesActa(), 100);
        });
      });

      const ordenDiaActa = await this.asambleasService.ListarOrdenDiaConvocatoria(this.idAsamblea).toPromise()
        .then((r) => r['orden_dia'])
        .catch(async (e) => {
          await hlpSwal.Error(e).then(() => null);
        });

      this.actaPaseLista.clear();
      this.actaPaseLista.clear();

      // Este fragmento de código debería ser utilizado incluso si se está editando el acta
      this.frmActa.get('total_unidades').setValue(usuariosActa.length);
      usuariosActa.map((u) => {
        const usuarios = Array.isArray(u.usuarios) ? u.usuarios : Object.keys(u.usuarios).map(key => (u.usuarios[key]));
        this.actaPaseLista.push(this.formBuilder.group({
          id_unidad: u.id_unidad,
          unidad: u.unidad,
          total_usuarios: usuarios.length,
          usuarios: [usuarios],
          id_usuario: usuarios.length == 1 ? usuarios[0].id_usuario : 0,
          usuario: usuarios.length == 1 ? usuarios[0].usuario + ' - ' + usuarios[0].perfil_usuario : null,
          asistencia: false,
        }));

        let paseListaUnidad = this.actaPaseLista.controls[this.actaPaseLista.length - 1];
        if (usuarios.length > 1) {
          paseListaUnidad.get('asistencia').disable();
        }

        return {
          id_unidad: u.id_unidad,
          unidad: u.unidad,
          asistencia: false,
          usuarios: usuarios,
          id_usuario: usuarios.length == 1 ? usuarios[0].id_usuario : 0,
        }
      });

      // Este fragmento de código debería ser utilizado incluso si se está editando el acta
      this.actaOrdenDia.clear();
      this.actaOrdenDia.clear();

      // Cargar textos de configuración para apertura de puntos del orden del día
      let cfgOrdenDia: any = {};
      try {
        const cfgR: any = await this.configuracionService.Listar().toPromise();
        cfgOrdenDia = cfgR['config'] || {};
      } catch(e) {}

      let requiere_votacion = false;
      ordenDiaActa.map((o) => {
        if (!requiere_votacion && o.requiere_votacion == 1) {
          requiere_votacion = true;
        }

        // Precargar según tipo de punto
        const nombrePunto = (o.orden_dia || '').toUpperCase();
        const esPaseLista = nombrePunto.includes('PASE DE LISTA');
        const esMinuta = nombrePunto.includes('MINUTA') || nombrePunto.includes('LECTURA');
        const requiereVotacion = o.requiere_votacion == 1;

        let aperturaDefault = this.idActa == 0 ? null : (o.apertura || null);
        let cierreDefault = this.idActa == 0 ? null : (o.cierre || null);

        if (this.idActa == 0) {
          if (esPaseLista) {
            aperturaDefault = cfgOrdenDia['orden_dia_pase_lista_apertura'] || null;
          } else if (esMinuta) {
            cierreDefault = cfgOrdenDia['orden_dia_minuta_apertura'] || null;
          } else if (requiereVotacion) {
            cierreDefault = cfgOrdenDia['orden_dia_votacion_cierre'] || null;
          }
        }

        this.actaOrdenDia.push(this.formBuilder.group({
          id_asamblea_orden_dia: o.id_asamblea_orden_dia,
          orden_dia: o.orden_dia,
          requiere_votacion: requiereVotacion,
          tipo_votacion: o.tipo_votacion || 'MAYORÍA SIMPLE',
          es_pase_lista: esPaseLista,
          es_minuta: esMinuta,
          apertura: [aperturaDefault, [Validators.required, Validators.min(1)]],
          cierre: cierreDefault,
          votacion: this.formBuilder.array([])
        }));
      });


      this.bExisteQuorum = !requiere_votacion;
      // this.votosPendientes.setValue(requiere_votacion);

      this.frmActa.updateValueAndValidity();
      this.mostrarDialogoEdicionActa = true;

    } catch (e) {
      hlpSwal.Error(e);
    }
  }

  // Se ejecuta sólo cuando el acta es editada
  /*   onMostrarEdicionActa() {
      this.calcularExistenciaQuorum();
  
      if (this.idActa == 0) {
        return;
      }
  
      const puntosOrdenDia = this.actaOrdenDia.value;
      if (!puntosOrdenDia)
        return;
  
      // Procesar la información de cada punto del orden del día
      puntosOrdenDia.forEach((punto, idxPunto) => {
        let pEditor = document.getElementById('ordenDiaApertura' + idxPunto);
        if (pEditor) {
          const qlEditor = pEditor.getElementsByClassName('ql-editor');
          if (qlEditor.length == 1) {
            qlEditor[0].innerHTML = punto.apertura ? punto.apertura : '<p><br /></p>';
          }
        }
  
        pEditor = null;
        pEditor = document.getElementById('ordenDiaCierre' + idxPunto);
        if (pEditor) {
          const qlEditor = pEditor.getElementsByClassName('ql-editor');
          if (qlEditor.length == 1) {
            qlEditor[0].innerHTML = punto.cierre ? punto.cierre : '<p><br /></p>';
          }
        }
  
        punto.votaciones.forEach((votacion, idxVotacion) => {
          const cmbVotacion = <HTMLSelectElement>document.getElementById('votacionP' + idxPunto + 'I' + idxVotacion);
          if (cmbVotacion) {
            cmbVotacion.selectedIndex = Number(votacion.votacion);
          }
        });
      });
    } */

  // Cuando es seleccionado una persona de la unidad para que vote
  onPaseListaUsuarioChange(idUsuario: number, usuario: string, idUnidad: number) {
    this.calcularExistenciaQuorum();

    let paseListaUnidad: any;
    paseListaUnidad = this.actaPaseLista.controls.filter((c) => c.get('id_unidad').value == idUnidad);
    if (paseListaUnidad.length != 1) {
      let msg = '';
      switch (paseListaUnidad.length) {
        case 0:
          msg = 'No se encontró unidad para la persona seleccionada.';
          break;
        default:
          msg = 'Se encontró más de una unidad para la persona seleccionada.';
          break;
      };
      hlpSwal.Error(msg);
      return;
    }
    paseListaUnidad = paseListaUnidad[0];
    paseListaUnidad.get('id_usuario').setValue(idUsuario);
    paseListaUnidad.get('usuario').setValue(usuario);
    if (idUsuario == 0) {
      paseListaUnidad.get('asistencia').setValue(false);
      paseListaUnidad.get('asistencia').disable();
    } else {
      paseListaUnidad.get('asistencia').enable();
    }
  }

  // Cuando se cambia el valor de la asistencia
  onPaseListaAsistenciaChange(e: any, paseListaUsuario: any) {
    // Actualizar variables del acta cuando cambia asistencia
    setTimeout(() => this.actualizarVariablesActa(), 200);
    this.calcularExistenciaQuorum();
    let puntosConVotacion = this.actaOrdenDia.controls.filter((o) => o.get('requiere_votacion').value == true);
    puntosConVotacion.forEach((punto) => {
      if (e.checked) {
        /* this.ActaOrdenDiaVotaciones.push({
          id_asamblea_orden_dia: punto.get('id_asamblea_orden_dia').value,
          id_unidad: paseListaUsuario.id_unidad.value,
          unidad: paseListaUsuario.unidad.value,
          id_usuario: paseListaUsuario.id_usuario.value,
          usuario: paseListaUsuario.usuario.value,
          // perfil_usuario: paseListaUsuario.perfil_usuario.value,
          id_sentido_votacion: 0
        });
        this.ActaOrdenDiaVotaciones = this.ActaOrdenDiaVotaciones.sort((a, b) => (a.usuario > b.usuario ? 1 : -1)); */

        (punto.get('votacion') as FormArray).push(this.formBuilder.group({
          id_asamblea_orden_dia: punto.get('id_asamblea_orden_dia').value,
          id_unidad: Number(paseListaUsuario.id_unidad.value),
          unidad: paseListaUsuario.unidad.value,
          id_usuario: paseListaUsuario.id_usuario.value,
          usuario: paseListaUsuario.usuario.value,
          id_sentido_votacion: [0, [Validators.required, Validators.min(1)]]
          // id_sentido_votacion: 0
        }));

        punto.get('votacion').setValue(
          (punto.get('votacion') as FormArray).value.sort((a, b) => (a.usuario > b.usuario ? 1 : -1))
        );
      } else {
        const votaciones = punto.get('votacion') as FormArray;
        let votacion = punto.get('votacion').value.filter((v) => v.id_unidad != paseListaUsuario.id_unidad.value);
        votaciones.clear();
        votacion.forEach(v => {
          votaciones.push(this.formBuilder.group({
            id_asamblea_orden_dia: v.id_asamblea_orden_dia,
            id_unidad: v.id_unidad,
            unidad: v.unidad,
            id_usuario: v.id_usuario,
            usuario: v.usuario,
            id_sentido_votacion: v.id_sentido_votacion
          }));
        });
      }
    });
  }

  /* actaOrdenDiaVotaciones(idAsambleaOrdenDia: number) {
    return this.ActaOrdenDiaVotaciones.filter((v) => v.id_asamblea_orden_dia == idAsambleaOrdenDia);
  } */

  /*  onSentidoVotacionChange(e: any, votacion: any) {
     votacion.id_sentido_votacion = e;
   } */

  getTotalesVotacionPuntoOrdenDia(idAsambleaOrdenDia: number) {
    let ordenDia = this.actaOrdenDia.controls.filter((o) => o.get('id_asamblea_orden_dia').value == idAsambleaOrdenDia);
    if (ordenDia.length < 1) {
      return null;
    }
    let votaciones = ordenDia[0].get('votacion').value;
    if (votaciones.length < 1) {
      return null;
    }
    /* if (this.frmActa.get('votos_pendientes').value == false) {
      this.frmActa.get('votos_pendientes').setValue(votaciones.filter((v) => v.id_sentido_votacion == 0).length > 0);
    } */

    votaciones = alasql(
      'SELECT sv.id AS id_sentido_votacion, sv.sentido_votacion, SUM(IF(IFNULL(v.id_sentido_votacion, -1) >= 0, 1, 0)) AS total FROM ? AS sv ' +
      'LEFT JOIN ? AS v ON v.id_sentido_votacion = sv.id ' +
      'GROUP BY sv.id, sv.sentido_votacion ' +
      'ORDER BY sv.id',
      [this.catSentidoVotacion, votaciones],
    );

    return votaciones;
  }

  /* onFinalizarActaChange(event) {
    this.frmActa.get('finalizada').setValue(event.checked);
  } */

  onActaGuardar() {
    let acta = this.frmActa.getRawValue()

    let actaPaseLista = acta.actaPaseLista;
    let actaOrdenDia = acta.actaOrdenDia;
    acta.fecha_hora = hlpApp.formatDateToMySQL(acta.fecha_hora);
    // acta.finalizada = acta.finalizada ? 1 : 0;
    /* delete acta.existe_quorum;
    delete acta.votos_pendientes; */
    delete acta.actaPaseLista;
    delete acta.actaOrdenDia;

    // Extraer solo el id_usuario del presidente y secretario
    acta.id_presidente = acta.id_presidente?.id_usuario || acta.id_presidente || null;
    acta.id_secretario = acta.id_secretario?.id_usuario || acta.id_secretario || null;
    acta.id_escrutadores = Array.isArray(acta.id_escrutadores) ? acta.id_escrutadores : [];

    acta.pase_lista = Object.assign({}, actaPaseLista.filter((o) => o.asistencia).map(p => {
      return {
        id_unidad: Number(p.id_unidad),
        id_usuario: Number(p.id_usuario),
        // id_usuario: p.asistencia == 0 ? null : Number(p.id_usuario),
        // id_usuario: Number(p.asistencia == 0 ? p.usuarios[0].id_usuario : p.id_usuario),
        // asistencia: p.asistencia ? 1 : 0
      }
    }));

    acta.orden_dia = [];
    // acta.orden_dia_votacion = [];
    for (let i = 0; i < actaOrdenDia.length; i++) {
      acta.orden_dia.push({
        id_asamblea_orden_dia: Number(actaOrdenDia[i].id_asamblea_orden_dia),
        /* apertura: this.getTextoPEditor(i),
        cierre: this.getTextoPEditor(i, false), */
        apertura: actaOrdenDia[i].apertura,
        cierre: actaOrdenDia[i].cierre,
        votacion: actaOrdenDia[i].requiere_votacion
          ? Object.assign({}, actaOrdenDia[i].votacion.map((v) => {
            return {
              // id_asamblea_orden_dia: Number(v.id_asamblea_orden_dia),
              id_unidad: Number(v.id_unidad),
              id_usuario: Number(v.id_usuario),
              id_sentido_votacion: Number(v.id_sentido_votacion)
            }
          }))
          : null
      });

      /* if (actaOrdenDia[i].requiere_votacion) {
        acta.orden_dia_votacion.push(
          actaOrdenDia[i].votacion.map((v) => {
            return {
              id_asamblea_orden_dia: Number(v.id_asamblea_orden_dia),
              id_unidad: Number(v.id_unidad),
              id_usuario: Number(v.id_usuario),
              id_sentido_votacion: Number(v.id_sentido_votacion)
            }
          }));
      } */
    }
    /* if (acta.orden_dia_votacion.length == 0) {
      delete acta.orden_dia_votacion;
    } */

    console.log('Acta :>> ', acta);

    hlpSwal
      .Pregunta({
        html: '¿Deseas guardar la información?',
        showLoaderOnConfirm: true,
        preConfirm: async () => {
          try {
            if (!this.bExisteQuorum) {
              acta.cierre = '<p>Esta asamblea no se llevó a cabo por falta de quorum.</p><p>Por tal motivo se acordó realizar una nueva convocatoria.</p>';
            }
            return await this.asambleasService.GuardarActa(this.idAsamblea, acta).toPromise();
          } catch (e) {
            return hlpSwal.Error(e).then(() => ({ err: true }));
          }
        },
        allowOutsideClick: () => !hlpSwal.estaCargando,
      })
      .then((r) => {
        if (r.value && !r.value.err && r.value.asamblea) {
          const c = r.value.asamblea;
          this.Convocatorias = this.Convocatorias.map((C) => (C.id_asamblea === c.id_asamblea ? c : C));
          this.Convocatorias = this.OrdenarConvocatorias(this.Convocatorias);
          this.reconstruirAniosDisponibles();
          this.aplicarFiltros();
          hlpSwal.ExitoToast(r.value.msg);
          this.mostrarDialogoEdicionActa = false;
        }
      });
  }

  onActaCancelar() {
    this.mostrarDialogoEdicionActa = false;
  }

  onActaDetalles(idAsamblea) {
    this.Acta = null;

    hlpSwal.Cargando();

    this.asambleasService
      .ListarActa(idAsamblea)
      .toPromise()
      .then((r) => {
        this.Acta = r['acta'];
        this.mostrarDialogoEmisionActa = true;
        hlpSwal.Cerrar();
      })
      .catch(async (e) => {
        await hlpSwal.Error(e).then(() => null);
      });
  }
}
// domingo, 10 de mayo de 2026, 23:54:34 CST
// domingo, 10 de mayo de 2026, 23:55:32 CST
