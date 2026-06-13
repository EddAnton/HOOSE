import { Component, OnInit, isDevMode } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';

import * as hlpSwal from '../../../helpers/sweetalert2-helper';
import * as hlpApp from '../../../helpers/app-helper';
import * as hlpPrimeNGTable from '../../../helpers/primeng-table-helper';
import { EdificioModel } from '../../../models/edificio.model';
import { UnidadesService } from '../../../services/unidades.service';
import { EdificiosService } from '../../../services/edificios.service';
import { CondominiosService } from '../../../services/condominios.service';
import { SesionUsuarioService } from '../../../services/sesion-usuario.service';

@Component({
	selector: 'app-catalogo-edificios',
	templateUrl: './catalogo-edificios.component.html',
	styleUrls: ['./catalogo-edificios.component.css'],
})
export class CatalogoEdificiosComponent implements OnInit {
	hlpApp = hlpApp;
	hlpPrimeNGTable = hlpPrimeNGTable;
	isDevelopment = isDevMode;

	// Tabla Edificio
	// Columnas de la tabla
	EdificiosCols: any[] = [
		{ header: 'Condominio' },
		{ header: 'Tipo', width: '120px' },
		{ header: 'Nombre' },
		{ header: 'Estatus', width: '70px' },
		// Botones de acción
		{ textAlign: 'center', width: '50px' },
	];
	EdificiosFilter: any[] = ['edificio'];

	Edificios: EdificioModel[] = [];
  EdificioDetalle: any = null;
  UnidadesDelEdificio: any[] = [];
  mostrarDialogoDetallesEdificio: boolean = false;
	Edificio: EdificioModel;
	frmEdificio: FormGroup;
	mostrarDialogoEdicionEdificio: boolean = false;
	esSuperAdminSinCondominio: boolean = false;
	condominiosLista: any[] = [];
	tiposEdificio: string[] = ['Torre', 'Edificio', 'Sección', 'Etapa', 'Manzana'];
	permitirAgregarEditar: boolean = false;

	constructor(private formBuilder: FormBuilder, private edificiosService: EdificiosService,
		private condominiosService: CondominiosService,
private unidadesService: UnidadesService,
		private sesionUsuarioService: SesionUsuarioService) {
		this.frmEdificio = this.formBuilder.group(new EdificioModel());}

	ngOnInit(): void {
		const perfil = this.sesionUsuarioService.obtenerIDPerfilUsuario();
		const idCond = this.sesionUsuarioService.obtenerIDCondominioUsuario();
		this.permitirAgregarEditar = [1, 2].includes(perfil);
		this.esSuperAdminSinCondominio = perfil === 1 && (!idCond || idCond === 0);
		if (this.esSuperAdminSinCondominio) {
			this.condominiosService.Listar(true).toPromise().then((r: any) => {
				this.condominiosLista = (r['condominios'] || []).map((c: any) => ({ label: c.condominio, value: +c.id_condominio }));
			});
		}
		this.onActualizarInformacion();
	}

	private OrdenarEdificios(edificio: EdificioModel[]) {
		return edificio.sort((a, b) => (a.edificio > b.edificio ? 1 : -1));
	}

	public onActualizarInformacion() {
		this.Edificios = [];

		hlpSwal.Cargando();

		this.edificiosService
			.Listar()
			.toPromise()
			.then((r) => {
				this.Edificios = this.OrdenarEdificios(r['edificios']);
			})
			.catch(async (e) => {
				await hlpSwal.Error(e);
			})
			.finally(() => {
				hlpSwal.Cerrar();
			});
	}

	async onEdificioEditar(idEdificio: number = 0) {
		if (idEdificio > 0) {
			hlpSwal.Cargando();
			this.Edificio = await this.edificiosService
				.ListarEdificio(idEdificio)
				.toPromise()
				.then((r) => ({ ...new EdificioModel(), ...r['edificios'] }))
				.catch(async (e) => {
					await hlpSwal.Error(e).then(() => null);
				})
				.finally(() => {
					hlpSwal.Cerrar();
				});
			if (this.Edificio == null) return;
		} else {
			this.Edificio = new EdificioModel();
		}

		try {
			this.frmEdificio = this.formBuilder.group(this.Edificio);
			this.frmEdificio.get('edificio').setValidators([Validators.minLength(3), Validators.maxLength(150)]);
			setTimeout(() => {
				document.getElementById('txtEdificio').focus();
			}, 500);
			this.frmEdificio.updateValueAndValidity();
			this.mostrarDialogoEdicionEdificio = true;
		} catch (e) {
			hlpSwal.Error(e);
		}
	}

	onEdificioGuardar() {
		if (!this.frmEdificio.valid) {
			this.frmEdificio.markAllAsTouched();
			hlpSwal.Error('Se detectaron errores en la información solicitada.');
			return;
		}

		let edificio = this.frmEdificio.value;

		hlpSwal
			.Pregunta({
				html: '¿Deseas guardar la información?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.edificiosService.Guardar(edificio).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && r.value.err && r.value.msg) {
					hlpSwal.Error(r.value.msg);
				} else if (r.value && !r.value.err && r.value.edificio) {
					const c = r.value.edificio;
					if (edificio.id_edificio == 0) {
						this.Edificios.push(c);
					} else {
						this.Edificios = this.Edificios.map((C) => (C.id_edificio === c.id_edificio ? c : C));
					}
					this.Edificios = this.OrdenarEdificios(this.Edificios);
					hlpSwal.ExitoToast(r.value.msg);
					this.mostrarDialogoEdicionEdificio = false;
				}
			});
	}

	onEdificioCancelar() {
		this.mostrarDialogoEdicionEdificio = false;
	}

	/* onEdificioDeshabilitar(edificio: EdificioModel) {
		if (edificio.estatus == 0) {
			return;
		}

		hlpSwal
			.Pregunta({
				html: '¿Deseas eliminar el Edificio?<br /><p class="text-danger"><b>ESTE PROCESO ES IRREVERSIBLE</b></p>',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.edificiosService.Deshabilitar(edificio.id_edificio).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err) {
					this.Edificios = this.Edificios.filter(function (e) {
						return e.id_edificio !== edificio.id_edificio;
					});
					hlpSwal.ExitoToast(r.value.msg);
				}
			});
	} */

	onEdificioAlternarEstatus(edificio: EdificioModel) {
		hlpSwal
			.Pregunta({
				html: '¿Deseas ' + (edificio.estatus == 1 ? 'des' : '') + 'habilitar el Edificio?',
				showLoaderOnConfirm: true,
				preConfirm: async () => {
					try {
						return await this.edificiosService.AlternarEstatus(edificio.id_edificio).toPromise();
					} catch (e) {
						return hlpSwal.Error(e).then(() => ({ err: true }));
					}
				},
				allowOutsideClick: () => !hlpSwal.estaCargando,
			})
			.then((r) => {
				if (r.value && !r.value.err) {
					edificio.estatus = edificio.estatus == 1 ? 0 : 1;
					hlpSwal.ExitoToast(r.value.msg);
				}
			});
	}

	onPlanoSeleccionado(event: any) {
		if (event.target.files.length !== 1) return;
		const file = event.target.files[0];
		this.frmEdificio.patchValue({ archivo_plano: file });
	}

	getCondominioColor(nombre: string): string {
		if (!nombre) return '#8a8f9e';
		const colores = [
			'#e91e8c', '#3B82F6', '#1BC99A', '#f59e0b', '#8b5cf6',
			'#ef4444', '#06b6d4', '#84cc16', '#f97316', '#ec4899',
		];
		let hash = 0;
		for (let i = 0; i < nombre.length; i++) {
			hash = nombre.charCodeAt(i) + ((hash << 5) - hash);
		}
		return colores[Math.abs(hash) % colores.length];
	}

	async onEdificioEliminar(edificio: any) {
		const result = await hlpSwal.Pregunta({
			html: '¿Estás seguro de eliminar este registro? Esta acción no se puede deshacer.',
			showLoaderOnConfirm: true,
			preConfirm: async () => {
				try {
					return await this.edificiosService.Eliminar(edificio.id_edificio).toPromise();
				} catch (e) {
					return hlpSwal.Error(e || 'Error al eliminar').then(() => ({ err: true }));
				}
			},
			allowOutsideClick: () => false,
		});
		if (result.value && !result.value.err && !result.value.error) {
			hlpSwal.ExitoToast('Registro eliminado correctamente.');
			this.onActualizarInformacion();
		}
	}


  async onEdificioDetalles(edificio: any) {
    this.EdificioDetalle = edificio;
    // Cargar unidades del edificio
    try {
      const r: any = await this.unidadesService.Listar().toPromise();
      const unidades = r['unidades'] || [];
      this.UnidadesDelEdificio = unidades.filter((u: any) => u.fk_id_edificio == edificio.id_edificio || u.edificio === edificio.edificio);
    } catch (e) { this.UnidadesDelEdificio = []; }
    this.mostrarDialogoDetallesEdificio = true;
  }
}
