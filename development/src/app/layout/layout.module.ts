import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { NgbModule } from '@ng-bootstrap/ng-bootstrap';

import { PhoneFormatPipe } from '../pipes/phone-format.pipe';
// import { FormsValidator } from "../validators/forms.validator";

import { TableModule } from 'primeng/table';
import { SelectButtonModule } from 'primeng/selectbutton';
import { DropdownModule } from 'primeng/dropdown';
import { InputNumberModule } from 'primeng/inputnumber';
import { InputTextareaModule } from 'primeng/inputtextarea';
import { ButtonModule } from 'primeng/button';
import { TooltipModule } from 'primeng/tooltip';
import { DialogModule } from 'primeng/dialog';
import { InputTextModule } from 'primeng/inputtext';
import { FileUploadModule } from 'primeng/fileupload';
import { InputMaskModule } from 'primeng/inputmask';
import { EditorModule } from 'primeng/editor';
import { PickListModule } from 'primeng/picklist';
import { TagModule } from 'primeng/tag';
import { CalendarModule } from 'primeng/calendar';
import { InputSwitchModule } from 'primeng/inputswitch';
import { SplitButtonModule } from 'primeng/splitbutton';
import { GalleriaModule } from 'primeng/galleria';
import { TabViewModule } from 'primeng/tabview';
import { CarouselModule } from 'primeng/carousel';
import { ChartModule } from 'primeng/chart';
import { DividerModule } from 'primeng/divider';
import { FieldsetModule } from 'primeng/fieldset';
import { FullCalendarModule } from '@fullcalendar/angular'; // must go before plugins
import dayGridPlugin from '@fullcalendar/daygrid'; // a plugin!
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction'; // a plugin!

FullCalendarModule.registerPlugins([dayGridPlugin, timeGridPlugin, interactionPlugin]);
// import { FullCalendarModule } from 'primeng/fullcalendar';

/* import { ToolbarModule } from 'primeng/toolbar'; */
/* import { RatingModule } from 'primeng/rating'; */
/* import { RadioButtonModule } from 'primeng/radiobutton'; */
/* import { ConfirmDialogModule } from 'primeng/confirmdialog'; */
/* import { ConfirmationService } from 'primeng/api'; */
/* import { MessageService } from 'primeng/api'; */

import Quill from 'quill';
import ImageResize from 'quill-image-resize-module';
Quill.register('modules/imageResize', ImageResize);

// import { RatingModule } from 'primeng/rating';

import { LayoutRoutingModule } from './layout-routing.module';
import { LayoutComponent } from './layout.component';

import { SidebarComponent } from '../components/sidebar/sidebar.component';
import { NavbarComponent } from '../components/navbar/navbar.component';
import { TableroComponent } from '../pages/tablero/tablero.component';
import { DashboardCardComponent } from '../components/shared/dashboard/dashboard-card/dashboard-card.component';
import { CatalogoCondominiosComponent } from '../pages/catalogos/catalogo-condominios/catalogo-condominios.component';
import { CatalogoEdificiosComponent } from '../pages/catalogos/catalogo-edificios/catalogo-edificios.component';
import { CatalogoUnidadesComponent } from '../pages/catalogos/catalogo-unidades/catalogo-unidades.component';
import { CatalogoPropietariosComponent } from '../pages/catalogos/catalogo-propietarios/catalogo-propietarios.component';
import { CatalogoCondominosComponent } from '../pages/catalogos/catalogo-condominos/catalogo-condominos.component';
import { CatalogoColaboradoresComponent } from '../pages/catalogos/catalogo-colaboradores/catalogo-colaboradores.component';
import { CatalogoAdministradoresComponent } from '../pages/catalogos/catalogo-administradores/catalogo-administradores.component';
import { CatalogoTiposMiembrosComponent } from '../pages/catalogos/catalogo-tipos-miembros/catalogo-tipos-miembros.component';
import { CatalogoGastosFijosComponent } from '../pages/catalogos/catalogo-gastos-fijos/catalogo-gastos-fijos.component';
import { RecaudacionesComponent } from '../pages/recaudaciones/recaudaciones.component';
import { PageNotFoundComponent } from '../pages/page-not-found/page-not-found.component';
import { GastosMantenimientoComponent } from '../pages/gastos-mantenimiento/gastos-mantenimiento.component';
import { CuotasMantenimientoComponent } from '../pages/cuotas-mantenimiento/cuotas-mantenimiento.component';
import { MiembrosComiteAdministracionComponent } from '../pages/miembros-comite-administracion/miembros-comite-administracion.component';
import { AsambleasComponent } from '../pages/asambleas/asambleas.component';
import { VisitasComponent } from '../pages/visitas/visitas.component';
import { TableroAvisosComponent } from '../pages/tablero-avisos/tablero-avisos.component';
import { PonteCloudComponent } from '../pages/ponte-cloud/ponte-cloud.component';
import { NominaComponent } from '../pages/nomina/nomina.component';
import { FondosMonetariosComponent } from '../pages/fondos-monetarios/fondos-monetarios.component';
import { ProyectosComponent } from '../pages/proyectos/proyectos.component';
import { ReservarAreasComunesComponent } from '../pages/reservar-areas-comunes/reservar-areas-comunes.component';
import { CatalogoAreasComunesComponent } from '../pages/catalogos/catalogo-areas-comunes/catalogo-areas-comunes.component';
import { QuejasComponent } from '../pages/quejas/quejas.component';
import { TareasComponent } from '../pages/tareas/tareas.component';
import { NotificacionesComponent } from '../pages/notificaciones/notificaciones.component';
import { DashboardChartComponent } from '../components/shared/dashboard/dashboard-chart/dashboard-chart.component';
import { ComitesComponent } from '../pages/comites/comites.component';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    ReactiveFormsModule,
    NgbModule,
    TableModule,
    SelectButtonModule,
    DropdownModule,
    InputNumberModule,
    InputTextareaModule,
    ButtonModule,
    TooltipModule,
    DialogModule,
    InputTextModule,
    InputTextareaModule,
    FileUploadModule,
    InputNumberModule,
    InputMaskModule,
    EditorModule,
    PickListModule,
    TagModule,
    CalendarModule,
    InputSwitchModule,
    SplitButtonModule,
    GalleriaModule,
    TabViewModule,
    CarouselModule,
    ChartModule,
    DividerModule,
    FieldsetModule,
    FullCalendarModule,
    LayoutRoutingModule,
  ],
  declarations: [
    LayoutComponent,
    PhoneFormatPipe,
    SidebarComponent,
    NavbarComponent,
    TableroComponent,
    DashboardCardComponent,
    CatalogoCondominiosComponent,
    CatalogoEdificiosComponent,
    CatalogoUnidadesComponent,
    CatalogoPropietariosComponent,
    CatalogoCondominosComponent,
    CatalogoColaboradoresComponent,
    CatalogoAdministradoresComponent,
    CatalogoTiposMiembrosComponent,
    CatalogoGastosFijosComponent,
    RecaudacionesComponent,
    TableroAvisosComponent,
    PageNotFoundComponent,
    GastosMantenimientoComponent,
    CuotasMantenimientoComponent,
    MiembrosComiteAdministracionComponent,
    AsambleasComponent,
    VisitasComponent,
    PonteCloudComponent,
    NominaComponent,
    FondosMonetariosComponent,
    ProyectosComponent,
    ReservarAreasComunesComponent,
    CatalogoAreasComunesComponent,
    QuejasComponent,
    TareasComponent,
    NotificacionesComponent,
    DashboardChartComponent,
    ComitesComponent,
  ],
  exports: [LayoutComponent, NavbarComponent],
  bootstrap: [TableroComponent],
})
export class LayoutModule { }
