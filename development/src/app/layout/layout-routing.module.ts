import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { AppAuthGuard } from '../app-auth.guard';

import { TableroComponent } from '../pages/tablero/tablero.component';
import { CatalogoCondominiosComponent } from '../pages/catalogos/catalogo-condominios/catalogo-condominios.component';
import { CatalogoEdificiosComponent } from '../pages/catalogos/catalogo-edificios/catalogo-edificios.component';
import { CatalogoUnidadesComponent } from '../pages/catalogos/catalogo-unidades/catalogo-unidades.component';
import { CatalogoColaboradoresComponent } from '../pages/catalogos/catalogo-colaboradores/catalogo-colaboradores.component';
import { CatalogoCondominosComponent } from '../pages/catalogos/catalogo-condominos/catalogo-condominos.component';
import { CatalogoPropietariosComponent } from '../pages/catalogos/catalogo-propietarios/catalogo-propietarios.component';
import { TableroAvisosComponent } from '../pages/tablero-avisos/tablero-avisos.component';
import { PageNotFoundComponent } from '../pages/page-not-found/page-not-found.component';
import { CatalogoAdministradoresComponent } from '../pages/catalogos/catalogo-administradores/catalogo-administradores.component';
import { CatalogoTiposMiembrosComponent } from '../pages/catalogos/catalogo-tipos-miembros/catalogo-tipos-miembros.component';
import { CatalogoGastosFijosComponent } from '../pages/catalogos/catalogo-gastos-fijos/catalogo-gastos-fijos.component';
import { CatalogoAreasComunesComponent } from '../pages/catalogos/catalogo-areas-comunes/catalogo-areas-comunes.component';
import { RecaudacionesComponent } from '../pages/recaudaciones/recaudaciones.component';
import { NominaComponent } from '../pages/nomina/nomina.component';
import { GastosMantenimientoComponent } from '../pages/gastos-mantenimiento/gastos-mantenimiento.component';
import { CuotasMantenimientoComponent } from '../pages/cuotas-mantenimiento/cuotas-mantenimiento.component';
import { MiembrosComiteAdministracionComponent } from '../pages/miembros-comite-administracion/miembros-comite-administracion.component';
import { AsambleasComponent } from '../pages/asambleas/asambleas.component';
import { VisitasComponent } from '../pages/visitas/visitas.component';
import { PonteCloudComponent } from '../pages/ponte-cloud/ponte-cloud.component';
import { FondosMonetariosComponent } from '../pages/fondos-monetarios/fondos-monetarios.component';
import { ProyectosComponent } from '../pages/proyectos/proyectos.component';
import { ReservarAreasComunesComponent } from '../pages/reservar-areas-comunes/reservar-areas-comunes.component';
import { QuejasComponent } from '../pages/quejas/quejas.component';
import { TareasComponent } from '../pages/tareas/tareas.component';
import { NotificacionesComponent } from '../pages/notificaciones/notificaciones.component';

const routes: Routes = [
	{
		path: 'tablero',
		component: TableroComponent,
		canActivate: [AppAuthGuard],
	},
	{
		path: '',
		redirectTo: 'tablero',
		pathMatch: 'full',
	},
	/* Catálogos */
	{
		path: 'catalogos',
		canActivateChild: [AppAuthGuard],
		children: [
			{
				path: 'condominios',
				component: CatalogoCondominiosComponent,
				canActivate: [AppAuthGuard],
				data: { perfilesUsuarioPermitidos: [1] },
			},
			{
				path: 'edificios',
				component: CatalogoEdificiosComponent,
				canActivate: [AppAuthGuard],
				data: { perfilesUsuarioPermitidos: [1, 2] },
			},
			{
				path: 'unidades',
				component: CatalogoUnidadesComponent,
				canActivate: [AppAuthGuard],
				data: { perfilesUsuarioPermitidos: [1, 2, 4] },
			},
			{
				path: 'propietarios',
				component: CatalogoPropietariosComponent,
				canActivate: [AppAuthGuard],
				data: { perfilesUsuarioPermitidos: [1, 2, 3] },
			},
			{
				path: 'condominos',
				component: CatalogoCondominosComponent,
				canActivate: [AppAuthGuard],
				data: { perfilesUsuarioPermitidos: [1, 2, 3, 4] },
			},
			{
				path: 'colaboradores',
				component: CatalogoColaboradoresComponent,
				canActivate: [AppAuthGuard],
				data: { perfilesUsuarioPermitidos: [1, 2, 4] },
			},
			{
				path: 'administradores',
				component: CatalogoAdministradoresComponent,
				canActivate: [AppAuthGuard],
				data: { perfilesUsuarioPermitidos: [1] },
			},
			{
				path: 'tipos-miembros',
				component: CatalogoTiposMiembrosComponent,
				canActivate: [AppAuthGuard],
				data: { perfilesUsuarioPermitidos: [1] },
			},
			{
				path: 'gastos-fijos',
				component: CatalogoGastosFijosComponent,
				canActivate: [AppAuthGuard],
				data: { perfilesUsuarioPermitidos: [1] },
			},
			{
				path: 'areas-comunes',
				component: CatalogoAreasComunesComponent,
				canActivate: [AppAuthGuard],
				data: { perfilesUsuarioPermitidos: [1, 2] },
			},
		],
	},
	{
		path: 'colaboradores-solicitudes-ausencia',
		component: PageNotFoundComponent,
		canActivate: [AppAuthGuard],
		data: { perfilesUsuarioPermitidos: [3] },
	},
	{
		path: 'recaudaciones',
		component: RecaudacionesComponent,
		canActivate: [AppAuthGuard],
		data: { perfilesUsuarioPermitidos: [1, 2, 4, 5] },
	},
	{
		path: 'nomina',
		component: NominaComponent,
		canActivate: [AppAuthGuard],
		data: { perfilesUsuarioPermitidos: [1, 2, 3] },
	},
	{
		path: 'gastos-mantenimiento',
		component: GastosMantenimientoComponent,
		canActivate: [AppAuthGuard],
		data: { perfilesUsuarioPermitidos: [1, 2, 4] },
	},
	{
		path: 'cuotas-mantenimiento',
		component: CuotasMantenimientoComponent,
		canActivate: [AppAuthGuard],
		data: { perfilesUsuarioPermitidos: [1, 2, 4, 5] },
	},
	{
		path: 'fondos-monetarios',
		component: FondosMonetariosComponent,
		canActivate: [AppAuthGuard],
		data: { perfilesUsuarioPermitidos: [1, 2] },
	},
	{
		path: 'comite-administracion',
		component: MiembrosComiteAdministracionComponent,
		canActivate: [AppAuthGuard],
		data: { perfilesUsuarioPermitidos: [1, 2, 3] },
	},
	{
		path: 'asambleas',
		component: AsambleasComponent,
		canActivate: [AppAuthGuard],
		data: { perfilesUsuarioPermitidos: [1, 2] },
	},
	{
		path: 'reservar-areas-comunes',
		component: ReservarAreasComunesComponent,
		canActivate: [AppAuthGuard],
		data: { perfilesUsuarioPermitidos: [1, 2] },
	},
	{
		path: 'visitas',
		component: VisitasComponent,
		canActivate: [AppAuthGuard],
		data: { perfilesUsuarioPermitidos: [1, 2, 3] },
	},
	{
		path: 'proyectos',
		component: ProyectosComponent,
		canActivate: [AppAuthGuard],
	},
	{
		path: 'tablero-avisos',
		component: TableroAvisosComponent,
		canActivate: [AppAuthGuard],
	},
	{
		path: 'notificaciones',
		component: NotificacionesComponent,
		canActivate: [AppAuthGuard],
	},
	{
		path: 'tareas',
		component: TareasComponent,
		canActivate: [AppAuthGuard],
		data: { perfilesUsuarioPermitidos: [1, 2, 3] },
	},
	{
		path: 'quejas',
		component: QuejasComponent,
		canActivate: [AppAuthGuard],
	},
	{
		path: 'encuestas',
		component: PageNotFoundComponent,
		canActivate: [AppAuthGuard],
	},
	{
		path: 'ponte-cloud',
		component: PonteCloudComponent,
		canActivate: [AppAuthGuard],
	},
	{ path: '**', pathMatch: 'full', component: PageNotFoundComponent },
];

@NgModule({
	imports: [RouterModule.forChild(routes)],
	exports: [RouterModule],
})
export class LayoutRoutingModule {}
