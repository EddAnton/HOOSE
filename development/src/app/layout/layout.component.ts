import { Component, OnInit, OnDestroy } from '@angular/core';
import { Router, ActivationEnd } from '@angular/router';
import { filter, map } from 'rxjs/operators';
import { Subscription } from 'rxjs';

import '@fontsource/montserrat';
import { Chart } from 'chart.js';
// import 'chartjs-plugin-piechart-outlabels';

@Component({
  selector: 'app-layout',
  templateUrl: './layout.component.html',
  styleUrls: ['./layout.component.css'],
})
export class LayoutComponent implements OnDestroy {
  componenteTitulo: string;
  componenteTituloSubs$: Subscription;

  constructor(private router: Router) {
    this.componenteTituloSubs$ = this.ObtenerTituloComponente().subscribe(({ componenteTitulo }) => {
      this.componenteTitulo = componenteTitulo;
    });
  }

  private ObtenerTituloComponente() {
    return this.router.events.pipe(
      filter((event) => event instanceof ActivationEnd),
      filter((event: ActivationEnd) => event.snapshot.firstChild === null),
      map((event: ActivationEnd) => event.snapshot.data),
    );
  }

  ngOnInit(): void {
    Chart.defaults.global.defaultFontFamily = 'Montserrat';
    Chart.defaults.global.defaultFontSize = 14;
    Chart.defaults.global.title.fontSize = 25;
    Chart.defaults.global.legend.position = 'top';
  }

  ngOnDestroy(): void {
    this.componenteTituloSubs$.unsubscribe();
  }
}
