import { Component, OnInit } from '@angular/core';
import * as hlpSwal from '../../helpers/sweetalert2-helper';
import { environment } from '../../../environments/environment';
import { SidebarOptionsInterface } from '../../interfaces/sidebar-options-interface';
import { SesionUsuarioService } from '../../services/sesion-usuario.service';

export let mnuOpciones: SidebarOptionsInterface[];

@Component({
  selector: 'app-sidebar',
  templateUrl: './sidebar.component.html',
  // styleUrls: ['./sidebar.component.css'],
})
export class SidebarComponent implements OnInit {
  appData = environment;
  public menuOptions: SidebarOptionsInterface[];

  constructor(private sesionUsuarioService: SesionUsuarioService) {
    mnuOpciones = [
      { path: '/tablero', title: 'Tablero Ejecutivo', visiblePerfilUsuario: [], visible: true, icon: 'bx bxs-dashboard' },
      /* Catálogos */
      {
        path: '/catalogos/condominios',
        title: 'Condominios',
        visiblePerfilUsuario: [1],
        visible: true,
        icon: 'bi bi-building',
      },
      {
        path: '/catalogos/edificios',
        title: 'Edificios / Pisos',
        visiblePerfilUsuario: [1, 2],
        visible: true,
        icon: 'bx bxs-building-house',
      },
      {
        path: '/catalogos/unidades',
        title: 'Unidades',
        visiblePerfilUsuario: [1, 2, 4],
        visible: true,
        icon: 'bi bi-house',
      },
      {
        path: '/catalogos/propietarios',
        title: 'Propietarios',
        visiblePerfilUsuario: [1, 2, 3],
        visible: true,
        icon: 'bx bxs-user-detail',
      },
      {
        path: '/catalogos/condominos',
        title: 'Condóminos',
        visiblePerfilUsuario: [1, 2, 3, 4],
        visible: true,
        icon: 'bi bi-person-video2',
      },
      {
        path: '/catalogos/colaboradores',
        title: 'Colaboradores',
        visiblePerfilUsuario: [1, 2, 4],
        visible: true,
        icon: 'bi bi-person-rolodex',
      },
      {
        path: '/catalogos/administradores',
        title: 'Administradores',
        visiblePerfilUsuario: [1],
        visible: true,
        icon: 'bi bi-person-workspace',
      },
      {
        path: '/catalogos/tipos-miembros',
        title: 'Catálogo Tipos de miembros',
        titleMenu: 'Tipos de miembros',
        visiblePerfilUsuario: [1],
        visible: true,
        icon: 'bx bxs-user-account',
      },
      {
        path: '/catalogos/gastos-fijos',
        title: 'Catálogo Gastos fijos',
        titleMenu: 'Gastos fijos',
        visiblePerfilUsuario: [1],
        visible: true,
        icon: 'bi bi-wallet',
      },
      {
        path: '/catalogos/areas-comunes',
        title: 'Catálogo Áreas comunes',
        titleMenu: 'Áreas comunes',
        visiblePerfilUsuario: [1, 2],
        visible: true,
        icon: 'bx bx-home-smile',
      },
      {
        path: '/colaboradores-solicitudes-ausencia',
        title: 'Solicitudes ausencia',
        visiblePerfilUsuario: [1, 2, 3],
        visible: false,
        icon: 'bi bi-person-rolodex',
      },
      { path: '|', title: '', visiblePerfilUsuario: [], visible: true },
      {
        path: '/recaudaciones',
        title: 'Recaudaciones',
        visiblePerfilUsuario: [1, 2, 4, 5],
        visible: true,
        icon: 'bx bx-money',
      },
      {
        path: '/nomina',
        title: 'Nómina',
        visiblePerfilUsuario: [1, 2, 3],
        visible: true,
        icon: 'bx bx-money-withdraw',
      },
      {
        path: '/gastos-mantenimiento',
        title: 'Gastos mantenimiento',
        visiblePerfilUsuario: [1, 2, 4],
        visible: true,
        icon: 'bx bx-wallet',
      },
      {
        path: '/cuotas-mantenimiento',
        title: 'Cuotas mantenimiento',
        visiblePerfilUsuario: [1, 2, 4, 5],
        visible: true,
        icon: 'bx bx-coin',
      },
      {
        path: '/fondos-monetarios',
        title: 'Fondos monetarios',
        visiblePerfilUsuario: [1, 2, 4],
        visible: true,
        icon: 'bx bxs-bank',
      },
      { path: '|', title: '', visiblePerfilUsuario: [1, 2], visible: true },
      {
        path: '/comite-administracion',
        title: 'Comité de administración',
        visiblePerfilUsuario: [1, 2, 3],
        visible: true,
        icon: 'bx bx-male-female',
      },
      { path: '/asambleas', title: 'Asambleas', visiblePerfilUsuario: [1, 2], visible: true, icon: 'bx bxs-receipt' },
      {
        path: '/reservar-areas-comunes',
        title: 'Reservar áreas comunes',
        visiblePerfilUsuario: [1, 2],
        visible: true,
        icon: 'bx bxs-party',
      },
      { path: '/visitas', title: 'Visitas', visiblePerfilUsuario: [1, 2, 3], visible: true, icon: 'bx bxs-car' },
      { path: '/proyectos', title: 'Proyectos', visiblePerfilUsuario: [], visible: true, icon: 'bx bxs-hard-hat' },
      { path: '|', title: '', visiblePerfilUsuario: [], visible: true },
      {
        path: '/tareas',
        title: 'Tareas',
        visiblePerfilUsuario: [1, 2, 3],
        visible: true,
        icon: 'bx bx-task',
      },
      { path: '|', title: '', visiblePerfilUsuario: [], visible: true },
      {
        path: '/tablero-avisos',
        title: 'Tablero avisos',
        visiblePerfilUsuario: [],
        visible: true,
        icon: 'bx bxs-envelope',
        messageCount: 2,
      },
      {
        path: '/notificaciones',
        title: 'Notificaciones',
        visiblePerfilUsuario: [1, 2],
        visible: true,
        icon: 'bx bxs-inbox',
        messageCount: 3,
      },
      {
        path: '/quejas',
        title: 'Quejas',
        visiblePerfilUsuario: [],
        visible: true,
        icon: 'bx bxs-message-rounded-error',
        messageCount: 1,
      },
      {
        path: '/encuestas',
        title: 'Encuestas *',
        visiblePerfilUsuario: [1, 2],
        visible: false,
        icon: 'bx bx-support',
        messageCount: 1,
      },
      {
        path: '/ponte-cloud',
        title: 'hooseCloud',
        visiblePerfilUsuario: [],
        visible: true,
        icon: 'bx bxl-google-cloud',
      },
      /* { path: '|', title: '', visiblePerfilUsuario: [], visible: true }, */
      /* { path: '/salir', title: 'Salir', visiblePerfilUsuario: [], visible: true, icon: 'bi bi-box-arrow-left' }, */
    ];
  }

  ngOnInit(): void {
    this.menuOptions = mnuOpciones.filter(
      (menuItem) =>
        menuItem.visible &&
        (menuItem.visiblePerfilUsuario.length == 0 ||
          menuItem.visiblePerfilUsuario.includes(this.sesionUsuarioService.obtenerIDPerfilUsuario())),
    );
    // console.log(this.menuOptions);
  }

  onSalir() {
    hlpSwal.Pregunta('¿Ya te vas?').then(async (r) => {
      if (r.isConfirmed) {
        // await hlpSwal.Info('Adios :(').then(() => this.sesionUsuarioService.cerrarSesion());
        this.sesionUsuarioService.cerrarSesion();
      }
    });
  }
}
