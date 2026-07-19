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
    this.toggleOverlay(false);
    this.sidebarSub = this.sidebarService.visible$.subscribe(v => {
      setTimeout(() => this.toggleOverlay(v), 100);
    });
  }

  toggleOverlay(visible: boolean) {
    // Crear overlay en body si no existe
    let overlay = document.getElementById('sidebar-overlay-global');
    if (!overlay) {
      overlay = document.createElement('div');
      overlay.id = 'sidebar-overlay-global';
      overlay.style.cssText = 'display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.5);z-index:10;cursor:pointer;';
      overlay.addEventListener('click', () => this.onCloseSidebar());
      document.body.appendChild(overlay);
    }
    if (visible && window.innerWidth < 992) {
      overlay.style.display = 'block';
    } else {
      overlay.style.display = 'none';
    }
  }

  onCloseSidebar() {
    const sidebarSection = document.getElementsByClassName('sidebar-section')[0] as HTMLElement;
    sidebarSection.style.width = '0';
    sidebarSection.style.display = 'none';
    this.toggleOverlay(false);
    this.sidebarService.setVisible(false);
  }


  ngOnDestroy(): void {
    this.componenteTituloSubs$.unsubscribe();
    if (this.sidebarSub) this.sidebarSub.unsubscribe();
  }
}
