import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { environment } from '../../../environments/environment';
import * as hlpSwal from '../../helpers/sweetalert2-helper';
import * as hlpPrimerCharts from '../../helpers/prime-charts-helper';
import { DashboardService } from '../../services/dashboard.service';
import { SesionUsuarioService } from '../../services/sesion-usuario.service';
import { TareasService } from '../../services/tareas.service';
import { MetricasService } from '../../services/metricas.service';

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
  catalogoCards: any[] = [];
  finCards: any[] = [];

  private iconMap: { [key: string]: string } = {
    'Ingresos': 'pi-chart-line',
    'Egresos': 'pi-shopping-cart',
    'Proyectos': 'pi-building',
    'Fondos monetarios': 'pi-wallet',
    'Fondos': 'pi-wallet',
    'Condominios': 'pi-home',
    'Edificios': 'pi-building',
    'Unidades': 'pi-th-large',
    'Propietarios': 'pi-user',
    'Condóminos': 'pi-users',
    'Colaboradores': 'pi-id-card',
    'Áreas comunes': 'pi-star',
    'Quejas': 'pi-exclamation-circle',
    'Avisos': 'pi-bell',
    'Visitas': 'pi-car',
    'Nómina': 'pi-money-bill',
    'Gastos mantenimiento': 'pi-wrench',
    'Cuotas mantenimiento': 'pi-file-edit',
    'Saldo periodo': 'pi-arrow-right-arrow-left',
    'Arrendamientos': 'pi-key',
  };

  constructor(
    private router: Router,
    private sesionUsuarioService: SesionUsuarioService,
    private dashboardService: DashboardService,
    private tareasService: TareasService,
    private metricasService: MetricasService,
  ) {}

  ngOnInit() {
    this.idCondominio = this.sesionUsuarioService.obtenerIDCondominioUsuario();
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
      { subtitle: 'Fondos monetarios',    content: 'Total',     label: 'Fondos Monetarios' },
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
