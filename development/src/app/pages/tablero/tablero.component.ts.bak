import { Component, OnInit } from '@angular/core';
import { environment } from '../../../environments/environment';

import * as hlpSwal from '../../helpers/sweetalert2-helper';
import * as hlpPrimerCharts from '../../helpers/prime-charts-helper';

import { DashboardService } from '../../services/dashboard.service';
import { SesionUsuarioService } from '../../services/sesion-usuario.service';
// import { PropositoGeneralService } from '../../services/proposito-general.service';

/* import '@fontsource/montserrat';
import { Chart } from 'chart.js'; */
// import 'chartjs-plugin-piechart-outlabels';

@Component({
  selector: 'app-home',
  templateUrl: './tablero.component.html',
  styleUrls: ['./tablero.component.css'],
})

export class TableroComponent implements OnInit {
  appData = environment;
  idCondominio: number = 0;

  fechaLimite: Date = new Date();
  fechaInicial: Date = new Date();
  fechaFinal: Date = new Date();

  data: any;
  charts: any = [];

  constructor(
    private sesionUsuarioService: SesionUsuarioService,
    private dashboardService: DashboardService,
  ) { }

  private generarGraphs() {
    this.data.charts.forEach(graph => {
      this.charts.push(
        hlpPrimerCharts.GenerateGraph(graph));
    });
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

    hlpSwal.Cargando();

    let data = new FormData();
    data.append('anios[0]', this.fechaInicial.getFullYear().toString());
    data.append('anios[1]', this.fechaFinal.getFullYear().toString());
    data.append('meses[0]', (this.fechaInicial.getMonth() + 1).toString());
    data.append('meses[1]', (this.fechaFinal.getMonth() + 1).toString());

    this.data = null;
    this.charts = [];

    /* 		await this.propositoGeneralService
      .LoginImagenes()
      .toPromise()
      .then((r) => {
        const data = r['data'];

        const imgLogo =
          data.filter((d) => {
            if (d.opcion == 'logo_dashboard') return d.valor;
          })[0] || null;
        if (imgLogo != null) {
          this.data.push({
            title: 'Tablero de indicadores',
            urlLogo: environment.urlBackendImagesFiles + 'logos/' + imgLogo.valor,
          });
        }
      })
      .finally(() => {
        hlpSwal.Cerrar();
      }); */

    /* let data = new FormData();
    data.append('anios[0]', '2023');
    data.append('anios[1]', '2023');
    data.append('meses[0]', '2');
    data.append('meses[1]', '7'); */
    /* data.append('meses[0]', '3');
    data.append('meses[1]', '4'); */

    this.dashboardService
      .Listar(data)
      .toPromise()
      .then((r) => {
        this.data = r['data'];
        // console.log(this.data);
        this.generarGraphs();
      })
      .catch(async (e) => {
        await hlpSwal.Error(e);
      })
      .finally(() => hlpSwal.Cerrar());
  }
}
