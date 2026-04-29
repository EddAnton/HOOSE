import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { AppAuthGuard } from './app-auth.guard';

import { LayoutComponent } from './layout/layout.component';
import { LoginComponent } from './pages/login/login.component';

const routes: Routes = [
	{ path: 'inicio-sesion', component: LoginComponent },
	// Layout
	{
		path: '',
		component: LayoutComponent,
		canActivateChild: [AppAuthGuard],
		children: [
			{
				path: '',
				loadChildren: './layout/layout.module#LayoutModule',
				canActivate: [AppAuthGuard],
			},
		],
	},
	{ path: '**', redirectTo: '' },
];

@NgModule({
	imports: [RouterModule.forRoot(routes, { useHash: true })],
	exports: [RouterModule],
})
export class AppRoutingModule {}
