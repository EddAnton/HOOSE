import { Component, HostListener, isDevMode } from '@angular/core';
import { Title } from '@angular/platform-browser';
import { PrimeNGConfig } from 'primeng/api';
import { environment } from '../environments/environment';

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.css'],
})
export class AppComponent {
  title = 'hoose';
  @HostListener('contextmenu', ['$event'])
  onRightClick(e) {
    if (!isDevMode) {
      e.preventDefault();
    }
  }

  constructor(private primengConfig: PrimeNGConfig, private titleService: Title) {
    this.titleService.setTitle(environment.appName + ' :: ' + environment.appTitle);
  }

  ngOnInit() {
    this.primengConfig.ripple = true;
    this.primengConfig.setTranslation({
      startsWith: 'Comienza con',
      contains: 'Contiene',
      notContains: 'No contiene',
      endsWith: 'Termina con',
      equals: 'Igual',
      notEquals: 'Diferente',
      noFilter: 'Sin filtro',
      lt: 'Menor que',
      lte: 'Menor o igual a',
      gt: 'Mayor que',
      gte: 'Mayor o igual a',
      is: 'Es',
      isNot: 'No es',
      before: 'Antes',
      after: 'Después',
      clear: 'Limpiar',
      apply: 'Aplicar',
      matchAll: 'Conincidir todo',
      matchAny: 'Conincidir cualquiera',
      addRule: 'Agregar regla',
      removeRule: 'Quitar regla',
      accept: 'Si',
      reject: 'No',
      choose: 'Selecciona',
      upload: 'Subir',
      cancel: 'Cancelar',
      dayNames: ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'],
      dayNamesShort: ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'],
      dayNamesMin: ['D', 'L', 'Ma', 'Mi', 'J', 'V', 'S'],
      monthNames: [
        'Enero',
        'Febrero',
        'Marzo',
        'Abril',
        'Mayo',
        'Junio',
        'Julio',
        'Agosto',
        'Septiembre',
        'Octubre',
        'Noviembre',
        'Diciembre',
      ],
      monthNamesShort: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
      today: 'Hoy',
      weekHeader: 'Sm',
    });
  }
}
