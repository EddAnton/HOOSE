import { Component, OnInit, OnDestroy } from '@angular/core';
import { SidebarService } from '../services/sidebar.service';
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
  sidebarOpen: boolean = false;
  private sidebarSub: any;
  componenteTitulo: string;
  componenteTituloSubs$: Subscription;

  constructor(private router: Router, private sidebarService: SidebarService) {
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
    this.sidebarSub = this.sidebarService.visible$.subscribe(v => {
      setTimeout(() => { this.sidebarOpen = (window.innerWidth < 992) && v; }, 50);
    });
  }

  onCloseSidebar() {
    const sidebarSection = document.getElementsByClassName('sidebar-section')[0] as HTMLElement;
    sidebarSection.style.width = '0';
    sidebarSection.style.display = 'none';
    this.sidebarService.setVisible(false);
  }


  ngOnDestroy(): void {
    this.componenteTituloSubs$.unsubscribe();
    if (this.sidebarSub) this.sidebarSub.unsubscribe();
  }
}
