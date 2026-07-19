import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { environment } from '../../../../environments/environment';
import * as hlpApp from '../../../helpers/app-helper';
import * as hlpSwal from '../../../helpers/sweetalert2-helper';
import { CondominiosService } from '../../../services/condominios.service';
import { UsuariosAdministradoresService } from '../../../services/usuarios-administradores.service';
import { CondominioResumenModel, CondominioModel } from '../../../models/condominio.model';

@Component({
  selector: 'app-catalogo-condominios',
  templateUrl: './catalogo-condominios.component.html',
  styleUrls: ['./catalogo-condominios.component.css'],
})
export class CatalogoCondominiosComponent implements OnInit {
  appData = environment;
  hlpApp = hlpApp;

  Condominios: CondominioResumenModel[] = [];
  CondominioSeleccionado: any = null;
  Administradores: any[] = [];

  frmCondominio: FormGroup;
  mostrarDialogoEdicion: boolean = false;
  srcImagen: string = null;
  srcImagenPreview: string = null;
  mostrarImagenPreview: boolean = false;

  tiposCondominio = [
    { label: 'Residencial',      value: 'RESIDENCIAL' },
    { label: 'Fraccionamiento',  value: 'FRACCIONAMIENTO' },
    { label: 'Mixto',            value: 'MIXTO' },
    { label: 'Comercial',        value: 'COMERCIAL' },
    { label: 'Industrial',       value: 'INDUSTRIAL' },
  ];

  // tiposAdministracion removido - se define desde Administradores

  ModulosEditorReglamento = {
    imageResize: {
      handleStyles: { backgroundColor: 'black', border: 'none', color: 'white' },
      modules: ['DisplaySize', 'Resize'],
    },
  };

  idxSeleccionado: number = 0;

  // Vista Cards
  vistaActual: 'cards' | 'carrusel' = 'cards';
  Cards: any[] = [];
  CardsFiltradas: any[] = [];
  cargandoCards: boolean = false;
  filtroCardEstatus: any = null;
  filtroCardTipo: any = null;
  filtroCardBusqueda: string = '';
  fechaInicioCards: string = null;
  fechaFinCards: string = null;



  constructor(
    private condominiosService: CondominiosService,
    private administradoresService: UsuariosAdministradoresService,
    private formBuilder: FormBuilder,
    private router: Router,
    private cdr: ChangeDetectorRef,
  ) {}

  ngOnInit(): void {
    this.onActualizarInformacion();
    this.cargarCards();
    this.administradoresService.ListarSinAsignar().toPromise().then((r: any) => {
      this.AdminsSinAsignar = (r['administradores'] || []).map((a: any) => ({
        label: a.nombre + (a.email ? ' — ' + a.email : ''),
        value: +a.id_usuario
      }));
    }).catch(() => {});
  }

  private OrdenarCondominios(condominios: CondominioResumenModel[]) {
    return condominios.sort((a, b) => (a.condominio > b.condominio ? 1 : -1));
  }

  public onActualizarInformacion() {
    hlpSwal.Cargando();
    this.condominiosService.Listar().toPromise()
      .then((r) => {
        this.Condominios = this.OrdenarCondominios(r['condominios']);
        if (this.Condominios.length > 0) {
          this.onSeleccionarCondominio(0);
        }
      })
      .catch(async (e) => await hlpSwal.Error(e))
      .finally(() => hlpSwal.Cerrar());
  }

  onSeleccionarCondominio(idx: number) {
    this.idxSeleccionado = idx;
    const c = this.Condominios[idx];
    if (!c) return;
    hlpSwal.Cargando();
    this.condominiosService.ListarCondominio(c.id_condominio).toPromise()
      .then((r) => {
        this.CondominioSeleccionado = r['condominios'];
        this.srcImagen = this.CondominioSeleccionado?.imagen
          ? environment.urlBackendCondominiosFiles + this.CondominioSeleccionado.id_condominio + '/' + this.CondominioSeleccionado.imagen
          : null;
        this.cargarAdministradores(this.CondominioSeleccionado.id_condominio);
      })
      .catch(async (e) => await hlpSwal.Error(e))
      .finally(() => hlpSwal.Cerrar());
  }

  onAnterior() {
    const n = this.Condominios.length;
    this.onSeleccionarCondominio((this.idxSeleccionado - 1 + n) % n);
  }

  onSiguiente() {
    const n = this.Condominios.length;
    this.onSeleccionarCondominio((this.idxSeleccionado + 1) % n);
  }

  cargarAdministradores(idCondominio: number) {
    this.administradoresService.Listar().toPromise()
      .then((r: any) => {
        this.Administradores = (r['administradores'] || []).filter((a: any) => +a.fk_id_condominio === +idCondominio);
      }).catch(() => {});
  }

  getImagenUrl(c: any): string {
    return c?.imagen
      ? environment.urlBackendCondominiosFiles + c.id_condominio + '/' + c.imagen
      : './assets/img/imagen_no_disponible.png';
  }

  getArchivoUrl(condominio: any, campo: string): string {
    if (!condominio?.[campo]) return null;
    return environment.urlBackendCondominiosFiles + condominio.id_condominio + '/' + condominio[campo];
  }

  async onCondominioEditar(idCondominio: number = 0) {
    let condominio: any;
    if (idCondominio > 0) {
      hlpSwal.Cargando();
      condominio = await this.condominiosService.ListarCondominio(idCondominio).toPromise()
        .then((r) => r['condominios'])
        .catch(async (e) => { await hlpSwal.Error(e); return null; })
        .finally(() => hlpSwal.Cerrar());
      if (!condominio) return;
    } else {
      condominio = new CondominioModel();
    }

    // Estado de documentos/archivos: refleja el registro actual (vacío si es nuevo)
    this.CondominioSeleccionado = condominio;

    this.srcImagen = condominio.imagen
      ? environment.urlBackendCondominiosFiles + condominio.id_condominio + '/' + condominio.imagen
      : null;

    this.frmCondominio = this.formBuilder.group({
      id_condominio:          [condominio.id_condominio],
      condominio:             [condominio.condominio, [Validators.required, Validators.minLength(3), Validators.maxLength(255)]],
      email:                  [condominio.email, [Validators.pattern('^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}$')]],
      telefono:               [condominio.telefono],
      domicilio:              [condominio.domicilio, [Validators.required, Validators.maxLength(255)]],
      tipo:                   [condominio.tipo],
      metros_cuadrados:       [condominio.metros_cuadrados],
      anio_construccion:      [condominio.anio_construccion, [Validators.pattern('^[0-9]{4}$')]],
      telefono_caseta:        [condominio.telefono_caseta],
      telefono_administracion: [condominio.telefono_administracion],
      constructora:           [condominio.constructora],
      constructora_telefono:  [condominio.constructora_telefono],
      constructora_domicilio: [condominio.constructora_domicilio],
      reglamento:             [condominio.reglamento],
      imagen:                 [condominio.imagen],
      archivo_imagen:         [null],
      archivo_acta_constitutiva:  [null],
      archivo_reglamento_interno: [null],
      archivo_poliza_seguro:      [null],
      archivo_planos:             [null],
      estatus:                [condominio.estatus],
    });

    this.mostrarDialogoEdicion = true;
  }

  async onImagenSeleccionada(event: any) {
    if (event.target.files.length != 1) return;
    const file = event.target.files[0];
    this.frmCondominio.patchValue({ archivo_imagen: file });
    this.srcImagen = await hlpApp.readFile(file).then((r: any) => r as string).catch(() => null);
  }

  onImagenSeleccionadaCancelar() {
    (<HTMLInputElement>document.getElementById('txtImagenArchivo')).value = '';
    this.frmCondominio.get('archivo_imagen').setValue(null);
    const id = this.frmCondominio.get('id_condominio').value;
    const img = this.frmCondominio.get('imagen').value;
    this.srcImagen = img ? environment.urlBackendCondominiosFiles + id + '/' + img : null;
  }

  onImagenEliminada() {
    this.frmCondominio.get('imagen').setValue(null);
    this.onImagenSeleccionadaCancelar();
  }

  onArchivoSeleccionado(event: any, campo: string) {
    if (event.target.files.length != 1) return;
    this.frmCondominio.patchValue({ [campo]: event.target.files[0] });
  }

  onCondominioGuardar() {
    if (!this.frmCondominio.valid) {
      this.frmCondominio.markAllAsTouched();
      hlpSwal.Error('Se detectaron errores en la información.');
      return;
    }
    const vals = this.frmCondominio.value;

    // Construir FormData con archivos
    const formData = new FormData();
    const camposArchivo = ['archivo_imagen', 'archivo_acta_constitutiva', 'archivo_reglamento_interno', 'archivo_poliza_seguro', 'archivo_planos'];
    const camposExcluir = [...camposArchivo, 'imagen'];

    // Campos de texto
    Object.keys(vals).forEach(k => {
      if (camposExcluir.includes(k)) return;
      if (vals[k] !== null && vals[k] !== undefined && vals[k] !== '') {
        formData.append(k, vals[k]);
      }
    });

    // Archivos — obtener directo del input HTML
    const inputImagen = document.getElementById('txtImagenArchivo') as HTMLInputElement;
    if (inputImagen?.files?.length > 0) formData.append('archivo_imagen', inputImagen.files[0]);

    const inputIds: any = {
      archivo_acta_constitutiva:  'fileActa',
      archivo_reglamento_interno: 'fileReglamento',
      archivo_poliza_seguro:      'filePoliza',
      archivo_planos:             'filePlanos',
    };
    Object.keys(inputIds).forEach(campo => {
      const el = document.getElementById(inputIds[campo]) as HTMLInputElement;
      if (el?.files?.length > 0) formData.append(campo, el.files[0]);
    });

    // DEBUG
const debugData: any = {};
formData.forEach((v, k) => debugData[k] = v instanceof File ? v.name : v);
hlpSwal.Pregunta({
      html: '¿Deseas guardar la información?',
      showLoaderOnConfirm: true,
      preConfirm: async () => {
        try { return await this.condominiosService.GuardarFormData(formData, vals.id_condominio).toPromise(); }
        catch (e) { return hlpSwal.Error(e).then(() => ({ err: true })); }
      },
      allowOutsideClick: () => !hlpSwal.estaCargando,
    }).then((r: any) => {
      if (r.value && !r.value.err && r.value.condominio) {
        const c = r.value.condominio;
        if (vals.id_condominio == 0) {
          this.Condominios.push(c);
        } else {
          this.Condominios = this.Condominios.map(C => C.id_condominio === c.id_condominio ? c : C);
        }
        this.Condominios = this.OrdenarCondominios(this.Condominios);
        const idx = this.Condominios.findIndex(C => C.id_condominio === c.id_condominio);
        this.onSeleccionarCondominio(idx >= 0 ? idx : 0);
        hlpSwal.ExitoToast(r.value.msg);
        this.mostrarDialogoEdicion = false;
      }
    });
  }

  onCondominioCancelar() {
    this.mostrarDialogoEdicion = false;
    this.srcImagen = null;
  }

  onCondominioAlternarEstatus(condominio: any) {
    hlpSwal.Pregunta({
      html: '¿Deseas ' + (condominio.estatus == 1 ? 'des' : '') + 'habilitar el Condominio?',
      showLoaderOnConfirm: true,
      preConfirm: async () => {
        try { return await this.condominiosService.AlternarEstatus(condominio.id_condominio).toPromise(); }
        catch (e) { return hlpSwal.Error(e).then(() => ({ err: true })); }
      },
      allowOutsideClick: () => !hlpSwal.estaCargando,
    }).then((r: any) => {
      if (r.value && !r.value.err) {
        condominio.estatus = condominio.estatus == 1 ? 0 : 1;
        if (this.CondominioSeleccionado?.id_condominio === condominio.id_condominio) {
          this.CondominioSeleccionado.estatus = condominio.estatus;
        }
        hlpSwal.ExitoToast(r.value.msg);
      }
    });
  }

  getItemStyle(i: number): any {
    const n = this.Condominios.length;
    // Calcular posición relativa al seleccionado (-2, -1, 0, 1, 2)
    let diff = i - this.idxSeleccionado;
    // Wrap around
    if (diff > n / 2) diff -= n;
    if (diff < -n / 2) diff += n;

    if (Math.abs(diff) > 2) return { display: 'none' };

    const configs: any = {
      '-2': { translateX: -480, translateY: 80, rotateY: 25, scale: 0.42, opacity: 0.25, zIndex: 1 },
      '-1': { translateX: -260, translateY: 40, rotateY: 15, scale: 0.62, opacity: 0.55, zIndex: 5 },
       '0': { translateX: 0,    translateY: 0,  rotateY: 0,  scale: 1,    opacity: 1,    zIndex: 10 },
       '1': { translateX: 260,  translateY: 40, rotateY: -15, scale: 0.62, opacity: 0.55, zIndex: 5 },
       '2': { translateX: 480,  translateY: 80, rotateY: -25, scale: 0.42, opacity: 0.25, zIndex: 1 },
    };

    const cfg = configs[diff.toString()] || { display: 'none' };
    const size = Math.abs(diff) === 0 ? 340 : Math.abs(diff) === 1 ? 210 : 160;
    return {
      transform: `translateX(${cfg.translateX}px) translateY(${cfg.translateY || 0}px) rotateY(${cfg.rotateY}deg) scale(${cfg.scale})`,
      opacity: cfg.opacity,
      zIndex: cfg.zIndex,
      display: 'flex',
      marginLeft: (-size / 2) + 'px',
      pointerEvents: diff === 0 ? 'none' : 'all',
      cursor: diff === 0 ? 'default' : 'pointer',
    };
  }

  getImgStyle(i: number): any {
    let diff = i - this.idxSeleccionado;
    const n = this.Condominios.length;
    if (diff > n / 2) diff -= n;
    if (diff < -n / 2) diff += n;
    const size = diff === 0 ? 340 : Math.abs(diff) === 1 ? 210 : 160;
    const brightness = diff === 0 ? 1 : Math.abs(diff) === 1 ? 0.55 : 0.3;
    return {
      width: size + 'px',
      height: size + 'px',
      filter: `brightness(${brightness}) drop-shadow(0 20px 40px rgba(0,0,0,0.5))`,
    };
  }

  getIdx(offset: number): number {
    const n = this.Condominios.length;
    return ((this.idxSeleccionado + offset) % n + n) % n;
  }

  isVisible(i: number): boolean {
    const visibles = [-2, -1, 0, 1, 2].map(o => this.getIdx(o));
    return visibles.includes(i);
  }

  // Panel asignación administrador
  mostrarPanelAdmin: boolean = false;
  AdminsSinAsignar: any[] = [];
  adminSeleccionado: number = null;
  cargandoAdmins: boolean = false;

  onAbrirPanelAdmin() {
    this.adminSeleccionado = null;
    this.administradoresService.ListarSinAsignar().toPromise().then((r: any) => {
      this.AdminsSinAsignar = (r['administradores'] || []).map((a: any) => ({
        label: a.nombre + (a.email ? ' — ' + a.email : ''),
        value: +a.id_usuario
      }));
      this.mostrarPanelAdmin = true;
    }).catch(async (e) => await hlpSwal.Error(e));
  }

  onAsignarAdministrador() {
    if (!this.adminSeleccionado || !this.CondominioSeleccionado) return;
    hlpSwal.Pregunta({
      html: '¿Asignar este administrador al condominio?',
      showLoaderOnConfirm: true,
      preConfirm: async () => {
        try {
          return await this.administradoresService.AsignarCondominio(
            this.adminSeleccionado,
            this.CondominioSeleccionado.id_condominio
          ).toPromise();
        } catch(e) { return hlpSwal.Error(e).then(() => ({ err: true })); }
      },
      allowOutsideClick: () => !hlpSwal.estaCargando,
    }).then((r: any) => {
      if (r.value && !r.value.err) {
        hlpSwal.ExitoToast('Administrador asignado correctamente.');
        this.mostrarPanelAdmin = false;
        this.cargarAdministradores(this.CondominioSeleccionado.id_condominio);
      }
    });
  }
  onIrAdministradores() {
    this.router.navigateByUrl('/catalogos/administradores');
  }

  get kpiCardsActivos() { return (this.Cards || []).filter(c => c.estatus == 1).length; }

  cambiarVista(vista: 'cards' | 'carrusel') {
    this.vistaActual = vista;
    if (vista === 'cards' && this.Cards.length === 0) {
      this.cargarCards();
    }
  }

  async cargarCards() {
    this.cargandoCards = true;
    try {
      const hoy = new Date();
      const inicio = this.fechaInicioCards || hoy.getFullYear() + '-' + String(hoy.getMonth() + 1).padStart(2, '0') + '-01';
      const fin = this.fechaFinCards || new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0).toISOString().slice(0, 10);
      const r: any = await this.condominiosService.ResumenCards(inicio, fin).toPromise();
      this.Cards = r['cards'] || [];
      this.filtrarCards();
    } catch (e) {
      await hlpSwal.Error(e);
    } finally {
      this.cargandoCards = false;
    }
  }

  filtrarCards() {
    let resultado = [...this.Cards];
    if (this.filtroCardEstatus !== null && this.filtroCardEstatus !== undefined) {
      resultado = resultado.filter(c => c.estatus == this.filtroCardEstatus);
    }
    if (this.filtroCardTipo) {
      resultado = resultado.filter(c => c.tipo === this.filtroCardTipo);
    }
    if (this.filtroCardBusqueda) {
      const q = this.filtroCardBusqueda.toLowerCase();
      resultado = resultado.filter(c =>
        (c.condominio || '').toLowerCase().includes(q) ||
        (c.domicilio || '').toLowerCase().includes(q)
      );
    }
    this.CardsFiltradas = resultado;
  }

  limpiarFiltrosCards() {
    this.filtroCardEstatus = null;
    this.filtroCardTipo = null;
    this.filtroCardBusqueda = '';
    this.filtrarCards();
  }

  getImagenCard(card: any): string {
    if (!card?.imagen) return './assets/img/imagen_no_disponible.png';
    return environment.urlBackendCondominiosFiles + card.id_condominio + '/' + card.imagen;
  }

  getFotoAdmin(admin: any): string {
    if (!admin || !admin.imagen_archivo) return null;
    return environment.urlBackendUsuariosFiles + admin.id_usuario + '/' + admin.imagen_archivo;
  }

  onVerDetalleCard(card: any) {
    const idx = this.Condominios.findIndex(c => c.id_condominio === card.id_condominio);
    if (idx >= 0) {
      this.vistaActual = 'carrusel';
      this.onSeleccionarCondominio(idx);
    }
  }

  onMostrarImagenPreview(url: string) {
    this.srcImagenPreview = url;
    this.mostrarImagenPreview = true;
  }
}
