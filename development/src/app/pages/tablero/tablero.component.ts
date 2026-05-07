import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { environment } from '../../../environments/environment';
import * as hlpSwal from '../../helpers/sweetalert2-helper';
import * as hlpPrimerCharts from '../../helpers/prime-charts-helper';
import { DashboardService } from '../../services/dashboard.service';
import { SesionUsuarioService } from '../../services/sesion-usuario.service';
import { TareasService } from '../../services/tareas.service';
import { MetricasService } from '../../services/metricas.service';
import { LayoutService } from '../../services/layout.service';

@Component({
  selector: 'app-home',
  templateUrl: './tablero.component.html',
  styleUrls: ['./tablero.component.css'],
})
export class TableroComponent implements OnInit {
  appData = environment;
  idCondominio: number = 0;
  cargando: boolean = false;
  cargandoTareas: boolean = false;
  fechaLimite: Date = new Date();
  fechaInicial: Date = new Date();
  fechaFinal: Date = new Date();
  hoy: string = new Date().toISOString().split('T')[0];
  data: any = null;
  charts: any[] = [];
  cardCobranza: any = null;
  tareasRecientes: any[] = [];
  totalTareasPendientes: number = 0;
  metricas: any = null;
  comparativo: string = 'mes_anterior';
  widgets: any[] = [];
  dragWidget: any = null;
  dragOverWidget: any = null;
  guardandoLayout = false;
  private layoutTimer: any;
  catalogoCards: any[] = [];
  finCards: any[] = [];

  iconMap: { [key: string]: string } = {
    'Condominios': 'pi-home',
    'Edificios': 'pi-building',
    'Unidades': 'pi-th-large',
    'Propietarios': 'pi-user',
    'Condóminos': 'pi-users',
    'Colaboradores': 'pi-id-card',
    'Áreas comunes': 'pi-star',
    'Avisos': 'pi-bell',
    'Quejas': 'pi-exclamation-circle',
    'Visitas': 'pi-car',
    'Proyectos': 'pi-chart-line',
    'Recaudaciones': 'pi-home',
    'Cuotas': 'pi-dollar',
    'Gastos': 'pi-minus-circle',
    'Nómina': 'pi-money-bill',
    'Egresos': 'pi-shopping-cart',
    'Saldo': 'pi-chart-bar',
    'Fondos': 'pi-wallet',
    'Arrendamientos': 'pi-key',
  };

  constructor(
    private router: Router,
    private sesionUsuarioService: SesionUsuarioService,
    private dashboardService: DashboardService,
    private tareasService: TareasService,
    private metricasService: MetricasService,
    private layoutService: LayoutService,
  ) {}

  ngOnInit() {
    this.idCondominio = this.sesionUsuarioService.obtenerIDCondominioUsuario();
    this.initWidgets();
    this.onActualizarInformacion();
    this.cargarTareas();
    this.cargarMetricas();
  }

  private generarGraphs() {
    this.charts = [];
    if (!this.data?.charts) return;
    this.data.charts.forEach(graph => {
      const g = hlpPrimerCharts.GenerateGraph(graph);
      if (g) this.charts.push(g);
    });
  }

  private calcularCobranza() {
    if (!this.data?.cards) return;
    const cardSaldo = this.data.cards.find(c => c.subtitle === 'Cuotas mantenimiento' && c.content === 'Saldo pendiente');
    const cardOrdinarias = this.data.cards.find(c => c.subtitle === 'Cuotas mantenimiento' && c.content === 'Orinarias');
    const cardMorosidad = this.data.cards.find(c => c.content === 'Morosidad');
    if (cardSaldo || cardOrdinarias) {
      const limpiar = (val: string) => val ? val.replace(/[$,]/g, '') : '0';
      const recaudado = parseFloat(limpiar(cardOrdinarias?.title));
      const pendiente = parseFloat(limpiar(cardSaldo?.title));
      const total = recaudado + pendiente;
      this.cardCobranza = {
        recaudado: cardOrdinarias?.title || '$0.00',
        pendiente: cardSaldo?.title || '$0.00',
        morosidad: cardMorosidad?.title || '0.00%',
        pctPendiente: total > 0 ? (pendiente / total * 100).toFixed(0) : '0',
      };
    }
  }

  defaultWidgets(): any[] {
    return [
      // Catálogo — altura fija compacta
      { id: 'condominios',   span: 2, rows: 2 },
      { id: 'edificios',     span: 2, rows: 2 },
      { id: 'unidades',      span: 2, rows: 2 },
      { id: 'propietarios',  span: 2, rows: 2 },
      { id: 'condominos',    span: 2, rows: 2 },
      { id: 'colaboradores', span: 2, rows: 2 },
      { id: 'areas_comunes', span: 2, rows: 2 },
      { id: 'avisos',        span: 2, rows: 2 },
      // Financieras
      { id: 'fin_recaudaciones', span: 4, rows: 2 },
      { id: 'fin_cuotas',        span: 8, rows: 2 },
      { id: 'fin_egresos',       span: 4, rows: 2 },
      { id: 'fin_saldo',         span: 4, rows: 2 },
      { id: 'fin_nomina',        span: 4, rows: 2 },
      { id: 'fin_gastos',        span: 4, rows: 2 },
      { id: 'fin_fondos',        span: 8, rows: 2 },
      // Gráficas
      { id: 'grafica_rec',   span: 8, rows: 5 },
      { id: 'cobranza',      span: 4, rows: 5 },
      // Operativas
      { id: 'op_ocupacion',  span: 3, rows: 2 },
      { id: 'op_quejas',     span: 3, rows: 2 },
      { id: 'op_asambleas',  span: 3, rows: 2 },
      { id: 'op_visitas',    span: 3, rows: 2 },
      { id: 'op_proyectos',  span: 3, rows: 2 },
      // Tareas y gastos
      { id: 'tareas',        span: 6, rows: 6 },
      { id: 'grafica_gastos',span: 6, rows: 6 },
    ];
  }

  initWidgets() {
    this.widgets = this.defaultWidgets();
    this.layoutService.getLayout().toPromise()
      .then((r: any) => {
        if (r.data && r.data.length > 0 && r.data[0].id) {
          this.widgets = r.data;
        }
      }).catch(() => {});
  }

  getWidget(id: string): any {
    return this.widgets.find(w => w.id === id) || { col: 1, span: 12, row: 99 };
  }

  getWidgetStyle(w: any): any {
    return {
      'grid-column': 'span ' + Math.min(w.span || 4, 12),
      'grid-row': 'span ' + Math.max(1, w.rows || 2),
    };
  }

  onDragStart(event: DragEvent, widget: any) {
    this.dragWidget = widget;
    if (event.dataTransfer) {
      event.dataTransfer.effectAllowed = 'move';
      event.dataTransfer.setData('text/plain', widget.id);
    }
    const el = event.currentTarget as HTMLElement;
    setTimeout(() => el.classList.add('dragging'), 0);
  }

  onDragOver(event: DragEvent, widget: any) {
    event.preventDefault();
    this.dragOverWidget = widget;
  }

  onDrop(event: DragEvent, targetWidget: any) {
    event.preventDefault();
    if (!this.dragWidget || this.dragWidget.id === targetWidget.id) {
      this.dragWidget = null;
      this.dragOverWidget = null;
      return;
    }

    const fromIdx = this.widgets.findIndex(w => w.id === this.dragWidget.id);
    let toIdx = this.widgets.findIndex(w => w.id === targetWidget.id);

    // Insertar después del target si se arrastra hacia adelante
    if (fromIdx < toIdx) toIdx = toIdx;
    else toIdx = toIdx;

    const [moved] = this.widgets.splice(fromIdx, 1);
    this.widgets.splice(toIdx, 0, moved);

    // Forzar re-render
    this.widgets = [...this.widgets];

    this.dragWidget = null;
    this.dragOverWidget = null;
    this.onLayoutChanged();
  }

  onDragEnd() {
    document.querySelectorAll('.dragging').forEach(el => el.classList.remove('dragging'));
    this.dragWidget = null;
    this.dragOverWidget = null;
  }

  changeSpan(widget: any, newSpan: number) {
    widget.span = newSpan;
    this.onLayoutChanged();
  }

  onResizeStart(event: MouseEvent, widget: any) {
    event.preventDefault();
    const startX = event.clientX;
    const startY = event.clientY;
    const startSpan = widget.span || 4;
    const startRows = widget.rows || 2;
    const gridEl = (event.target as HTMLElement).closest('.tb-grid') as HTMLElement;
    const gridWidth = gridEl?.clientWidth || 1200;
    const colWidth = gridWidth / 12;

    const onMove = (e: MouseEvent) => {
      // Resize horizontal (span)
      const diffX = e.clientX - startX;
      const spanDiff = Math.round(diffX / colWidth);
      widget.span = Math.max(1, Math.min(12, startSpan + spanDiff));

      // Resize vertical (rows) - usar startRows como base fija
      const diffY = e.clientY - startY;
      const rowsDiff = Math.round(diffY / 80);
      widget.rows = Math.max(1, startRows + rowsDiff);
    };

    const onUp = () => {
      document.removeEventListener('mousemove', onMove);
      document.removeEventListener('mouseup', onUp);
      this.onLayoutChanged();
    };

    document.addEventListener('mousemove', onMove);
    document.addEventListener('mouseup', onUp);
  }

  onLayoutChanged() {
    clearTimeout(this.layoutTimer);
    this.layoutTimer = setTimeout(() => this.guardarLayout(), 1500);
  }

  guardarLayout() {
    if (!this.widgets.length || this.guardandoLayout) return;
    this.guardandoLayout = true;
    this.layoutService.saveLayout(this.widgets).toPromise()
      .then(() => { this.guardandoLayout = false; })
      .catch(() => { this.guardandoLayout = false; });
  }

  getCardValue(subtitle: string): string {
    if (!this.data?.cards) return '0';
    return this.data.cards.find(c => c.subtitle === subtitle)?.title || '0';
  }

  cargarMetricas() {
    this.metricasService.Tablero(this.comparativo).toPromise()
      .then((r: any) => { this.metricas = r.data; })
      .catch(() => {});
  }

  private procesarCards() {
    if (!this.data?.cards) return;
    const cards = this.data.cards;

    // Cards catálogo
    const catalogoSubtitles = ['Condominios', 'Edificios / Pisos', 'Unidades', 'Propietarios', 'Condóminos', 'Colaboradores', 'Áreas comunes', 'Avisos'];
    this.catalogoCards = catalogoSubtitles.map(s => cards.find(c => c.subtitle === s)).filter(c => c !== undefined);

    // Cards financieras — orden y selección exacta
    const finDefs = [
      { subtitle: 'Recaudaciones',        content: null,        label: 'Recaudaciones' },
      { subtitle: 'Cuotas mantenimiento', content: 'Orinarias', label: 'Cuotas Mantenimiento' },
      { subtitle: 'Gastos mantenimiento', content: 'Erogación', label: 'Gastos Mantenimiento' },
      { subtitle: 'Nómina',               content: 'Erogación', label: 'Nómina' },
      { subtitle: 'Egresos',              content: null,        label: 'Egresos' },
      { subtitle: 'Saldo periodo',        content: null,        label: 'Saldo del Período' },
    ];
    this.finCards = finDefs.map(def => {
      const found = cards.find(c =>
        c.subtitle === def.subtitle && (def.content === null || c.content === def.content)
      );
      return found ? { ...found, label: def.label } : { subtitle: def.label, label: def.label, title: '$0.00', content: null, path: null };
    });
  }

  private cargarTareas() {
    this.cargandoTareas = true;
    this.tareasService.Listar().toPromise()
      .then((r: any) => {
        const todas = (r.data || []).map(t => ({
          ...t,
          fk_id_estatus: parseInt(t.fk_id_estatus),
          prioridad: parseInt(t.prioridad),
        }));
        this.totalTareasPendientes = todas.filter(t => t.fk_id_estatus !== 3).length;
        this.tareasRecientes = todas.slice(0, 5);
      })
      .catch(() => {})
      .finally(() => this.cargandoTareas = false);
  }

  async onActualizarInformacion() {
    if (this.fechaInicial.getTime() > this.fechaFinal.getTime()) {
      await hlpSwal.Error('La fecha inicial no puede ser mayor a la final');
      return;
    }
    this.cargando = true;
    hlpSwal.Cargando();
    const formData = new FormData();
    formData.append('anios[0]', this.fechaInicial.getFullYear().toString());
    formData.append('anios[1]', this.fechaFinal.getFullYear().toString());
    formData.append('meses[0]', (this.fechaInicial.getMonth() + 1).toString());
    formData.append('meses[1]', (this.fechaFinal.getMonth() + 1).toString());
    this.data = null;
    this.charts = [];
    this.cardCobranza = null;
    this.dashboardService.Listar(formData).toPromise()
      .then((r) => {
        this.data = r['data'];
        this.generarGraphs();
        this.calcularCobranza();
        this.procesarCards();
      })
      .catch(async (e) => await hlpSwal.Error(e))
      .finally(() => { this.cargando = false; hlpSwal.Cerrar(); });
  }

  onNavegar(path: string) {
    if (!path) return;
    this.router.navigateByUrl(path);
  }

  onCambiarEstatusTarea(tarea: any) {
    const nuevoEstatus = tarea.fk_id_estatus === 3 ? 1 : 3;
    const data = new FormData();
    data.append('fk_id_estatus', nuevoEstatus.toString());
    this.tareasService.CambiarEstatus(tarea.id_tarea, data).toPromise()
      .then((r: any) => {
        if (!r.err) {
          tarea.fk_id_estatus = nuevoEstatus;
          hlpSwal.ExitoToast(r.msg);
          this.cargarTareas();
        }
      }).catch(() => {});
  }

  getCardTitle(subtitle: string): string {
    if (!this.data?.cards) return '$0.00';
    const card = this.data.cards.find(c => c.subtitle === subtitle);
    return card?.title || '$0.00';
  }

  getCardIcon(subtitle: string): string {
    if (!subtitle) return 'pi-info-circle';
    for (const key of Object.keys(this.iconMap)) {
      if (subtitle.toLowerCase().includes(key.toLowerCase())) return this.iconMap[key];
    }
    return 'pi-info-circle';
  }

  getDatasetKeys(chart: any): string[] {
    if (!chart?.datasets?.[0]) return [];
    return Object.keys(chart.datasets[0]).filter(k => k !== 'legend');
  }

  getLegendColor(index: number): string {
    return ['#1BC99A', '#e91e8c', '#3B82F6', '#F59E0B'][index % 4];
  }

  getGastosTotal(): string {
    if (!this.data?.cards) return '$0.00';
    const card = this.data.cards.find(c => c.subtitle === 'Gastos mantenimiento' && c.content === 'Erogación');
    return card?.title || '$0.00';
  }

  getPrioridadClass(p: number): string {
    return p === 1 ? 'alta' : p === 2 ? 'media' : 'baja';
  }
}
