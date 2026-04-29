import { Component, OnInit } from '@angular/core';
import { environment } from '../../../environments/environment';

import * as hlpSwal from '../../helpers/sweetalert2-helper';
import * as hlpPrimerCharts from '../../helpers/prime-charts-helper';

import { DashboardService } from '../../services/dashboard.service';
import { SesionUsuarioService } from '../../services/sesion-usuario.service';

@Component({
  selector: 'app-home',
  templateUrl: './tablero.component.html',
  styleUrls: ['./tablero.component.css'],
})
export class TableroComponent implements OnInit {
  appData = environment;
  idCondominio: number = 0;
  cargando: boolean = false;

  fechaLimite: Date = new Date();
  fechaInicial: Date = new Date();
  fechaFinal: Date = new Date();

  data: any = null;
  charts: any[] = [];
  cardCobranza: any = null;
  notificaciones: string[] = [
    'Revisar reporte de actividades de los colaboradores.',
    'Enviar la convocatoria para la Asamblea Extraordinaria.',
    'Reunión de trabajo con el Comité de Administración.',
    'Revisar cotización de la planta de tratamiento de aguas residuales.',
    'Reunión de capacitación con el equipo de HOOSE.',
  ];

  // Mapa de íconos por tipo de card
  private iconMap: { [key: string]: string } = {
    'Ingresos': 'pi-chart-line',
    'Egresos': 'pi-shopping-cart',
    'Proyectos': 'pi-building',
    'Fondos': 'pi-wallet',
    'Fondos monetarios': 'pi-wallet',
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
    'Morosidad': 'pi-exclamation-triangle',
    'Arrendamientos': 'pi-key',
  };

  private coloresGraficas: string[] = [
    '#1BC99A', '#e91e8c', '#3B82F6', '#F59E0B', '#8B5CF6', '#EF4444',
  ];

  constructor(
    private sesionUsuarioService: SesionUsuarioService,
    private dashboardService: DashboardService,
  ) {}

  private generarGraphs() {
    this.charts = [];
    if (!this.data?.charts) return;
    this.data.charts.forEach(graph => {
      this.charts.push(hlpPrimerCharts.GenerateGraph(graph));
    });
  }

  private calcularCobranza() {
    if (!this.data?.cards) return;

    // Buscar tarjetas de recaudación/saldo para calcular cobranza
    const cardSaldo = this.data.cards.find(c =>
      c.subtitle === 'Cuotas mantenimiento' && c.content === 'Saldo pendiente'
    );
    const cardOrdinarias = this.data.cards.find(c =>
      c.subtitle === 'Cuotas mantenimiento' && c.content === 'Orinarias'
    );
    const cardMorosidad = this.data.cards.find(c =>
      c.content === 'Morosidad'
    );

    if (cardSaldo || cardOrdinarias) {
      const limpiarMonto = (val: string) =>
        val ? val.replace(/[$,]/g, '') : '0';

      const recaudado = cardOrdinarias ? parseFloat(limpiarMonto(cardOrdinarias.title)) : 0;
      const pendiente = cardSaldo ? parseFloat(limpiarMonto(cardSaldo.title)) : 0;
      const total = recaudado + pendiente;
      const pctPendiente = total > 0 ? (pendiente / total * 100) : 0;

      this.cardCobranza = {
        recaudado: cardOrdinarias?.title || '$0.00',
        pendiente: cardSaldo?.title || '$0.00',
        morosidad: cardMorosidad?.title || '0.00%',
        pctPendiente: pctPendiente.toFixed(0),
      };
    }
  }

  ngOnInit() {
    this.idCondominio = this.sesionUsuarioService.obtenerIDCondominioUsuario();
    this.onActualizarInformacion();
  }

  async onActualizarInformacion() {
    if (this.fechaInicial.getTime() > this.fechaFinal.getTime()) {
      await hlpSwal.Error('La fecha inicial no puede ser mayor a la final');
      return;
    }

    this.cargando = true;
    hlpSwal.Cargando();

    let formData = new FormData();
    formData.append('anios[0]', this.fechaInicial.getFullYear().toString());
    formData.append('anios[1]', this.fechaFinal.getFullYear().toString());
    formData.append('meses[0]', (this.fechaInicial.getMonth() + 1).toString());
    formData.append('meses[1]', (this.fechaFinal.getMonth() + 1).toString());

    this.data = null;
    this.charts = [];
    this.cardCobranza = null;

    this.dashboardService
      .Listar(formData)
      .toPromise()
      .then((r) => {
        this.data = r['data'];
        this.generarGraphs();
        this.calcularCobranza();
      })
      .catch(async (e) => {
        await hlpSwal.Error(e);
      })
      .finally(() => {
        this.cargando = false;
        hlpSwal.Cerrar();
      });
  }

  // Obtener ícono según el subtítulo de la card
  getCardIcon(subtitle: string): string {
    if (!subtitle) return 'pi-info-circle';
    for (const key of Object.keys(this.iconMap)) {
      if (subtitle.toLowerCase().includes(key.toLowerCase())) {
        return this.iconMap[key];
      }
    }
    return 'pi-info-circle';
  }

  // Obtener keys de datasets para leyenda
  getDatasetKeys(chart: any): string[] {
    if (!chart?.datasets?.[0]) return [];
    return Object.keys(chart.datasets[0]).filter(k => k !== 'legend');
  }

  // Color de leyenda
  getLegendColor(index: number): string {
    return this.coloresGraficas[index % this.coloresGraficas.length];
  }

  // Total de gastos mantenimiento desde cards
  getGastosTotal(): string {
    if (!this.data?.cards) return '$0.00';
    const card = this.data.cards.find(c =>
      c.subtitle === 'Gastos mantenimiento' && c.content === 'Erogación'
    );
    return card?.title || '$0.00';
  }
}
