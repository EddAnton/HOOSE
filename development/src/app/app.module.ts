import { LOCALE_ID, NgModule } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { registerLocaleData } from '@angular/common';
import localeEsMX from '@angular/common/locales/es-MX';
import { HttpClientModule, HTTP_INTERCEPTORS } from '@angular/common/http';
import { AppHttpInterceptor } from './app-http.interceptor';

import { InputTextModule } from 'primeng/inputtext';
import { ButtonModule } from 'primeng/button';

import { AppRoutingModule } from './app-routing.module';
import { AppComponent } from './app.component';
import { LayoutModule } from './layout/layout.module';
import { CryptoService } from './services/crypto.service';
import { LoginComponent } from './pages/login/login.component';

registerLocaleData(localeEsMX, 'es-MX');

@NgModule({
	declarations: [AppComponent, LoginComponent],
	imports: [
		BrowserModule,
		BrowserAnimationsModule,
		FormsModule,
		ReactiveFormsModule,
		HttpClientModule,
		InputTextModule,
		ButtonModule,
		AppRoutingModule,
		LayoutModule,
	],
	providers: [
		{
			provide: HTTP_INTERCEPTORS,
			useClass: AppHttpInterceptor,
			multi: true,
		},
		CryptoService,
		{ provide: LOCALE_ID, useValue: 'es-MX' },
	],
	exports: [],
	bootstrap: [AppComponent],
})
export class AppModule {}
