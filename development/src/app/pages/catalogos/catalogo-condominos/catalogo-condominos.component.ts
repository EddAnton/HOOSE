import { Component, OnInit, isDevMode } from '@angular/core';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';
import { DomSanitizer } from '@angular/platform-browser';
// import { DatePipe } from '@angular/common';

import { environment } from '../../../../environments/environment';
import * as hlpApp from '../../../helpers/app-helper';
import * as hlpSwal from '../../../helpers/sweetalert2-helper';
import * as hlpPrimeNGTable from '../../../helpers/primeng-table-helper';
import { CondominoResumenModel, CondominoModel } from '../../../models/usuario-condomino.model';
import { UsuariosCondominosService } from '../../../services/usuarios-condominos.service';
import { UnidadesService } from '../../../services/unidades.service';
import { UnidadesEdificioModel } from '../../../models/unidad.model';
import { UsuariosService } from '../../../services/usuarios.service';
import { SesionUsuarioService } from '../../../services/sesion-usuario.service';

@Component({
  selector: 'app-catalogo-condominos',
  templateUrl: './catalogo-condominos.component.html',
  styleUrls: ['./catalogo-condominos.component.css'],
})
export class CatalogoCondominosComponent implements OnInit {
  appData = environment;
  hlpApp = hlpApp;
  hlpPrimeNGTable = hlpPrimeNGTable;
  isDevelopment = isDevMode;

  // Tabla Condominos
  // Columnas de la tabla
  CondominosCols: any[] = [
    { header: '', width: '80px' },
    { header: 'Nombre' },
    { header: 'Usuario' },
    { header: 'Email' },
    { header: 'Contacto' },
    { header: 'Unidad' },
    { header: 'Depósito', width: '90px' },
    { header: 'Renta', width: '90px' },
    { header: 'Estatus', width: '70px' },
    // Botones de acción
    { textAlign: 'center', width: '90px' },
  ];
  CondominosFilter: any[] = ['nombre', 'usuario', 'email', 'unidad'];

  Condominos: CondominoResumenModel[] = [];
  Condomino: CondominoModel;
  UnidadesDisponiblesRenta: UnidadesEdificioModel[] = [];

  frmCondomino: FormGroup;
  frmCondominoDeshabilitar: FormGroup;
  mostrarDialogoEdicionCondomino: boolean = false;
  mostrarDialogoDeshabilitarCondomino: boolean = false;
  mostrarDialogoImagenCondomino: boolean = false;
  mostrarDialogoDetallesCondomino: boolean = false;
  mostrarDialogoContratoCondomino: boolean = false;
  srcImagen: string = null;
  srcIdentificacionAnverso: string = null;
  srcIdentificacionReverso: string = null;
  srcImagenMostrar: string = null;
  srcContrato: string = null;
  bImagenBorrar: boolean = false;
  bIdentificacionAnversoBorrar: boolean = false;
  bIdentificacionReversoBorrar: boolean = false;
  bContratoBorrar: boolean = false;
  permitirAgregarEditar: boolean = false;
  esUsuarioAdministrador: boolean = false;

  constructor(
    private sesionUsuarioService: SesionUsuarioService,
    private condominosService: UsuariosCondominosService,
    private unidadesService: UnidadesService,
    private formBuilder: FormBuilder,
    private usuariosService: UsuariosService,
    private sanitizer: DomSanitizer,
  ) { }

  ngOnInit(): void {
    this.permitirAgregarEditar = [1, 2, 4].includes(this.sesionUsuarioService.obtenerIDPerfilUsuario());
    this.esUsuarioAdministrador = [1, 2].includes(this.sesionUsuarioService.obtenerIDPerfilUsuario());
    this.onActualizarInformacion();
  }

  private OrdenarCondominos(condominos: CondominoResumenModel[]) {
    return condominos.sort((a, b) => (a.nombre > b.nombre ? 1 : -1));
  }

  onOrdenarUnidades(unidades: UnidadesEdificioModel[] = []) {
    unidades = unidades.sort((a, b) => (a.unidad > b.unidad ? 1 : -1));
  }

  public onActualizarInformacion() {
    this.Condominos = [];

    hlpSwal.Cargando();

    (this.esUsuarioAdministrador ? this.condominosService.Listar() : this.condominosService.ListarActivos())
      .toPromise()
      .then((r) => {
        this.Condominos = this.OrdenarCondominos(r['condominos']);
      })
      .catch(async (e) => {
        await hlpSwal.Error(e);
      })
      .finally(() => {
        hlpSwal.Cerrar();
      });
  }

  /* async onCondominoEditar(idUsuario: number = 0) {
    hlpSwal.Cargando();

    this.UnidadesDisponiblesRenta = await this.unidadesService
      .ListarUnidadesDisponiblesRenta()
      .toPromise()
      .then((r) => r['unidades'])
      .catch(async (e) => {
        await hlpSwal.Error(e).then(() => null);
      });

    if (idUsuario == 0 && this.UnidadesDisponiblesRenta.length < 1) {
      hlpSwal.Advertencia('No se encontraron unidades disponibles para renta.');
      return;
    }

    if (idUsuario > 0) {
      this.Condomino = await this.condominosService
        .ListarCondomino(idUsuario)
        .toPromise()
        .then((r) => r['condomino'])
        .catch(async (e) => {
          await hlpSwal.Error(e).then(() => null);
        });
      if (this.Condomino == null) return;
      this.UnidadesDisponiblesRenta.push({
        id_unidad: this.Condomino.id_unidad,
        unidad: this.Condomino.unidad + ' (' + this.Condomino.edificio + ')',
      });
      this.Condomino.fecha_inicio = new Date(this.Condomino.fecha_inicio + 'T00:00:00');
      this.onOrdenarUnidades(this.UnidadesDisponiblesRenta);
    } else {
      this.Condomino = new CondominoModel();
    }
    hlpSwal.Cerrar();

    try {
      this.srcImagen = this.Condomino.imagen
        ? environment.urlBackendUsuariosFiles + this.Condomino.id_usuario + '/' + this.Condomino.imagen
        : null;
      this.srcIdentificacionAnverso = this.Condomino.identificacion_anverso
        ? environment.urlBackendUsuariosFiles + this.Condomino.id_usuario + '/' + this.Condomino.identificacion_anverso
        : null;
      this.srcIdentificacionReverso = this.Condomino.identificacion_anverso
        ? environment.urlBackendUsuariosFiles + this.Condomino.id_usuario + '/' + this.Condomino.identificacion_reverso
        : null;
      this.srcContrato = this.Condomino.contrato
        ? environment.urlBackendUsuariosFiles + this.Condomino.id_usuario + '/' + this.Condomino.contrato
        : null;
      this.frmCondomino = this.formBuilder.group(this.Condomino);
      this.frmCondomino
        .get('nombre')
        .setValidators([Validators.required, Validators.minLength(3), Validators.maxLength(255)]);
      this.frmCondomino
        .get('usuario')
        .setValidators([
          Validators.required,
          Validators.minLength(3),
          Validators.maxLength(25),
          Validators.pattern('^[a-z0-9.]+$'),
        ]);
      this.frmCondomino
        .get('email')
        .setValidators([Validators.required, Validators.pattern('^[a-z0-9._%+-]+@[a-z0-9.-]+\\.[a-z]{2,4}$')]);
      this.frmCondomino
        .get('telefono')
        .setValidators([Validators.required, Validators.minLength(10), Validators.maxLength(12)]);
      this.frmCondomino.get('domicilio').setValidators([Validators.maxLength(255)]);
      this.frmCondomino.get('identificacion_folio').setValidators([Validators.maxLength(50)]);
      this.frmCondomino.get('identificacion_domicilio').setValidators([Validators.maxLength(255)]);
      this.frmCondomino.get('id_unidad').setValidators([Validators.required, Validators.min(1)]);
      this.frmCondomino.get('deposito').setValidators([Validators.required, Validators.min(0)]);
      this.frmCondomino.get('renta').setValidators([Validators.required, Validators.min(0.01)]);
      this.frmCondomino.get('fecha_inicio').setValidators([Validators.required]);

      this.frmCondomino.addControl('archivo_imagen', new FormControl());
      this.frmCondomino.addControl('archivo_identificacion_anverso', new FormControl());
      this.frmCondomino.addControl('archivo_identificacion_reverso', new FormControl());
      this.frmCondomino.addControl('archivo_contrato', new FormControl());
      this.frmCondomino.updateValueAndValidity();

      this.bImagenBorrar = false;
      this.bIdentificacionAnversoBorrar = false;
      this.bIdentificacionReversoBorrar = false;
      this.bContratoBorrar = false;
      this.mostrarDialogoEdicionCondomino = true;
    } catch (e) {
      hlpSwal.Error(e);
    }
  } */

  async onCondominoEditar(condomino: CondominoResumenModel = null) {
    hlpSwal.Cargando();

    // Obtener las unidades disponibles para renta
    this.UnidadesDisponiblesRenta = await this.unidadesService
      .ListarUnidadesDisponiblesRenta()
      .toPromise()
      .then((r) => r['unidades'])
      .catch(async (e) => {
        await hlpSwal.Error(e).then(() => null);
      });

    // Mensaje de error si no hay unidades disponibles para renta y
    // el condómino nuevo o reactivado después de finalización de contrato
    if (
      this.UnidadesDisponiblesRenta.length < 1 &&
      (condomino == null || (condomino.estatus == 0 && condomino.contrato_activo == 0))
    ) {
      hlpSwal.Advertencia('No se encontraron unidades disponibles para renta.');
      return;
    }

    // Determinar el id del usuario
    const idUsuario = condomino == null ? 0 : condomino.id_usuario;
    // Si el id del usuario es mayor a cero, obtiene la información a editar
    if (idUsuario > 0) {
      this.Condomino = await this.condominosService
        .ListarCondomino(idUsuario)
        .toPromise()
        .then((r) => r['condomino'])
        .catch(async (e) => {
          await hlpSwal.Error(e).then(() => null);
        });

      if (this.Condomino == null) return;

      if (condomino != null && condomino.contrato_activo == 1) {
        this.UnidadesDisponiblesRenta.push({
          id_unidad: this.Condomino.id_unidad,
          unidad: this.Condomino.unidad + ' (' + this.Condomino.edificio + ')',
        });
        this.Condomino.fecha_inicio = new Date(this.Condomino.fecha_inicio + 'T00:00:00');
      } else {
        // this.Condomino.edificio = null;
        this.Condomino.id_unidad = 0;
        // this.Condomino.unidad = null;
        // this.Condomino.unidad_edificio = null;
        this.Condomino.deposito = 0;
        this.Condomino.renta = 0;
        this.Condomino.fecha_inicio = null;
      }
      this.onOrdenarUnidades(this.UnidadesDisponiblesRenta);
    } else {
      this.Condomino = new CondominoModel();
    }
    hlpSwal.Cerrar();

    try {
      this.srcImagen = this.Condomino.imagen
        ? environment.urlBackendUsuariosFiles + this.Condomino.id_usuario + '/' + this.Condomino.imagen
        : null;
      this.srcIdentificacionAnverso = this.Condomino.identificacion_anverso
        ? environment.urlBackendUsuariosFiles + this.Condomino.id_usuario + '/' + this.Condomino.identificacion_anverso
        : null;
      this.srcIdentificacionReverso = this.Condomino.identificacion_anverso
        ? environment.urlBackendUsuariosFiles + this.Condomino.id_usuario + '/' + this.Condomino.identificacion_reverso
        : null;
      this.srcContrato = this.Condomino.contrato
        ? environment.urlBackendUsuariosFiles + this.Condomino.id_usuario + '/' + this.Condomino.contrato
        : null;
      this.frmCondomino = this.formBuilder.group(this.Condomino);
      this.frmCondomino
        .get('nombre')
        .setValidators([Validators.required, Validators.minLength(3), Validators.maxLength(255)]);
      this.frmCondomino
        .get('usuario')
        .setValidators([
          Validators.required,
          Validators.minLength(3),
          Validators.maxLength(25),
          Validators.pattern('^[a-z0-9.]+$'),
        ]);
      this.frmCondomino
        .get('email')
        .setValidators([Validators.required, Validators.pattern('^[a-z0-9._%+-]+@[a-z0-9.-]+\\.[a-z]{2,4}$')]);
      this.frmCondomino
        .get('telefono')
        .setValidators([Validators.required, Validators.minLength(10), Validators.maxLength(12)]);
      this.frmCondomino.get('domicilio').setValidators([Validators.maxLength(255)]);
      this.frmCondomino.get('identificacion_folio').setValidators([Validators.maxLength(50)]);
      this.frmCondomino.get('identificacion_domicilio').setValidators([Validators.maxLength(255)]);
      this.frmCondomino.get('id_unidad').setValidators([Validators.required, Validators.min(1)]);
      this.frmCondomino.get('deposito').setValidators([Validators.required, Validators.min(0)]);
      this.frmCondomino.get('renta').setValidators([Validators.required, Validators.min(0.01)]);
      this.frmCondomino.get('fecha_inicio').setValidators([Validators.required]);

      this.frmCondomino.addControl('archivo_imagen', new FormControl());
      this.frmCondomino.addControl('archivo_identificacion_anverso', new FormControl());
      this.frmCondomino.addControl('archivo_identificacion_reverso', new FormControl());
      this.frmCondomino.addControl('archivo_contrato', new FormControl());
      this.frmCondomino.updateValueAndValidity();

      this.bImagenBorrar = false;
      this.bIdentificacionAnversoBorrar = false;
      this.bIdentificacionReversoBorrar = false;
      this.bContratoBorrar = false;
      this.mostrarDialogoEdicionCondomino = true;
    } catch (e) {
      hlpSwal.Error(e);
    }
  }

  async onImagenSeleccionada(event, idImagen: number = 0) {
    if (event.target.files.length != 1 || idImagen == 0) return;

    let file: any = event.target.files[0];
    file.src = await hlpApp
      .readFile(file)
      .then((r) => r)
      .catch((e) => {
        idImagen = 0;
        hlpSwal.Error(e);
      });

    if (!file.src) return;

    switch (idImagen) {
      case 1:
        this.bImagenBorrar = false;
        this.srcImagen = file.src;
        this.frmCondomino.patchValue({ archivo_imagen: file });
        this.frmCondomino.get('archivo_imagen').updateValueAndValidity();
        break;
      case 2:
        this.bIdentificacionAnversoBorrar = false;
        this.srcIdentificacionAnverso = file.src;
        this.frmCondomino.patchValue({ archivo_identificacion_anverso: file });
        this.frmCondomino.get('archivo_identificacion_anverso').updateValueAndValidity();
        break;
      case 3:
        this.bIdentificacionReversoBorrar = false;
        this.srcIdentificacionReverso = file.src;
        this.frmCondomino.patchValue({ archivo_identificacion_reverso: file });
        this.frmCondomino.get('archivo_identificacion_reverso').updateValueAndValidity();
        break;
    }
  }

  onImagenSeleccionadaCancelar(idImagen: number = 0) {
    switch (idImagen) {
      case 1:
        (<HTMLInputElement>document.getElementById('txtImagenArchivo')).value = '';
        this.frmCondomino.get('archivo_imagen').setValue(null);

        this.srcImagen = this.frmCondomino.get('imagen').value
          ? environment.urlBackendUsuariosFiles + this.Condomino.id_usuario + '/' + this.Condomino.imagen
          : null;
        this.bImagenBorrar = !this.srcImagen;
        break;
      case 2:
        (<HTMLInputElement>document.getElementById('txtAnversoIdentificacionArchivo')).value = '';
        this.frmCondomino.get('archivo_identificacion_anverso').setValue(null);

        this.srcIdentificacionAnverso = this.frmCondomino.get('identificacion_anverso').value
          ? environment.urlBackendUsuariosFiles +
          this.Condomino.id_usuario +
          '/' +
          this.Condomino.identificacion_anverso
          : null;
        this.bIdentificacionAnversoBorrar = !this.srcIdentificacionAnverso;
        break;
      case 3:
        (<HTMLInputElement>document.getElementById('txtReversoIdentificacionArchivo')).value = '';
        this.frmCondomino.get('archivo_identificacion_reverso').setValue(null);

        this.srcIdentificacionReverso = this.frmCondomino.get('identificacion_reverso').value
          ? environment.urlBackendUsuariosFiles +
          this.Condomino.id_usuario +
          '/' +
          this.Condomino.identificacion_reverso
          : null;
        this.bIdentificacionReversoBorrar = !this.srcIdentificacionReverso;
        break;
    }
  }

  onImagenEliminar(idImagen: number = 0) {
    if (idImagen == 0) {
      return;
    }
    switch (idImagen) {
      case 1:
        this.frmCondomino.get('imagen').setValue(null);
        break;
      case 2:
        this.frmCondomino.get('identificacion_anverso').setValue(null);
        break;
      case 3:
        this.frmCondomino.get('identificacion_reverso').setValue(null);
        break;
    }
    this.onImagenSeleccionadaCancelar(idImagen);
  }

  onContratoSeleccionado(event) {
    if (event.target.files.length != 1) return;
    let file: any;
    file = event.target.files[0];

    this.frmCondomino.patchValue({ archivo_contrato: file });
    this.frmCondomino.get('archivo_contrato').updateValueAndValidity();

    let reader = new FileReader();

    reader.onload = (e) => {
      file.src = reader.result;
      this.srcContrato = file.src;
    };
    reader.readAsDataURL(file);
  }

  onContratoSeleccionadoCancelar() {
    (<HTMLInputElement>document.getElementById('txtContratoArchivo')).value = '';
    this.frmCondomino.get('archivo_contrato').setValue(null);

    this.srcContrato = this.frmCondomino.get('contrato').value
      ? environment.urlBackendUsuariosFiles + this.Condomino.id_usuario + '/' + this.Condomino.contrato
      : null;
    this.bContratoBorrar = !this.srcContrato;
  }

  onContratoEliminado() {
    this.frmCondomino.get('contrato').setValue(null);
    this.onContratoSeleccionadoCancelar();
  }

  onImagenMostrar(imagen: string = null) {
    if (!imagen) {
      return;
    }
    this.srcImagenMostrar = imagen;
    this.mostrarDialogoImagenCondomino = true;
  }

  onCondominoGuardar() {
    if (!this.frmCondomino.valid) {
      this.frmCondomino.markAllAsTouched();
      hlpSwal.Error('Se detectaron errores en la información solicitada.');
      return;
    }

    let condomino = this.frmCondomino.value;

    condomino.borrar_imagen = this.bImagenBorrar ? 1 : 0;
    condomino.borrar_identificacion_anverso = this.bIdentificacionAnversoBorrar ? 1 : 0;
    condomino.borrar_identificacion_reverso = this.bIdentificacionReversoBorrar ? 1 : 0;
    condomino.borrar_contrato = this.bContratoBorrar ? 1 : 0;
    condomino.fecha_inicio = hlpApp.formatDateToMySQL(condomino.fecha_inicio);
    delete condomino.imagen;
    delete condomino.identificacion_anverso;
    delete condomino.identificacion_reverso;
    delete condomino.contrato;

    hlpSwal
      .Pregunta({
        html: '¿Deseas guardar la información?',
        showLoaderOnConfirm: true,
        preConfirm: async () => {
          try {
            return await this.condominosService.Guardar(condomino).toPromise();
          } catch (e) {
            return hlpSwal.Error(e).then(() => ({ err: true }));
          }
        },
        allowOutsideClick: () => !hlpSwal.estaCargando,
      })
      .then((r) => {
        if (r.value && !r.value.err && r.value.condomino) {
          const c = r.value.condomino;
          if (condomino.id_usuario == 0) {
            this.Condominos.push(c);
          } else {
            this.Condominos = this.Condominos.map((C) => (C.id_usuario === c.id_usuario ? c : C));
          }
          this.Condominos = this.OrdenarCondominos(this.Condominos);
          hlpSwal.ExitoToast(r.value.msg);
          this.mostrarDialogoEdicionCondomino = false;
        }
      });
  }

  onCondominoCancelar() {
    this.srcImagen = null;
    this.srcIdentificacionAnverso = null;
    this.srcIdentificacionReverso = null;
    this.srcContrato = null;
    this.mostrarDialogoEdicionCondomino = false;
  }

  onCondominoAlternarEstatus(condomino: CondominoResumenModel = null) {
    if (condomino.estatus == 0 && condomino.contrato_activo == 0) {
      hlpSwal
        .Pregunta({
          html: '<span class="text-danger"><b>El Condómino no tiene contrato activo.</b></span><br />¿Deseas reactivar a el Condómino en un nuevo contrato?',
        })
        .then((r) => {
          if (r.isConfirmed) {
            this.onCondominoEditar(condomino);
          }
        });
    } else {
      hlpSwal
        .Pregunta({
          html: '¿Deseas ' + (condomino.estatus == 1 ? 'des' : '') + 'habilitar el Condomino?',
          showLoaderOnConfirm: true,
          preConfirm: async () => {
            try {
              return await this.usuariosService.AlternarEstatus(condomino.id_usuario).toPromise();
            } catch (e) {
              return hlpSwal.Error(e).then(() => ({ err: true }));
            }
          },
          allowOutsideClick: () => !hlpSwal.estaCargando,
        })
        .then((r) => {
          if (r.value && !r.value.err) {
            condomino.estatus = condomino.estatus == 1 ? 0 : 1;
            hlpSwal.ExitoToast(r.value.msg);
          }
        });
    }
  }

  async onCondominoDeshabilitar(idUsuario: number = 0) {
    if (idUsuario == 0) {
      return;
    }

    try {
      /* this.frmCondominoDeshabilitar.updateValueAndValidity(); */
      this.frmCondominoDeshabilitar = this.formBuilder.group({
        id_usuario: [idUsuario, Validators.required],
        fecha_fin: ['', Validators.required],
      });

      this.mostrarDialogoDeshabilitarCondomino = true;
    } catch (e) {
      hlpSwal.Error(e);
    }
  }

  async onReiniciarContrasenia(idUsuario: number = 0) {
    if (idUsuario < 1) {
      return;
    }

    hlpSwal
      .Pregunta({
        html: '¿Deseas reiniciar la contraseña del Condómino?',
        showLoaderOnConfirm: true,
        preConfirm: async () => {
          try {
            return await this.usuariosService.ReiniciarContrasenia(idUsuario).toPromise();
          } catch (e) {
            return hlpSwal.Error(e).then(() => ({ err: true }));
          }
        },
        allowOutsideClick: () => !hlpSwal.estaCargando,
      })
      .then((r) => {
        if (r.value && !r.value.err) {
          hlpSwal.ExitoToast(r.value.msg);
        }
      });
  }

  onCondominoDeshabilitarGuardar() {
    let id_usuario = this.frmCondominoDeshabilitar.get('id_usuario').value;
    let condomino = {
      fecha_fin: hlpApp.formatDateToMySQL(this.frmCondominoDeshabilitar.get('fecha_fin').value),
    };

    hlpSwal
      .Pregunta({
        html: '¿Deseas finalizar el contrato con el Condómino?<br /><p class="text-danger"><b>ESTE PROCESO ES IRREVERSIBLE</b></p>',
        showLoaderOnConfirm: true,
        preConfirm: async () => {
          try {
            return await this.condominosService.FinalizarContrato(id_usuario, condomino).toPromise();
          } catch (e) {
            return hlpSwal.Error(e).then(() => ({ err: true }));
          }
        },
        allowOutsideClick: () => !hlpSwal.estaCargando,
      })
      .then((r) => {
        if (r.value && !r.value.err) {
          this.mostrarDialogoDeshabilitarCondomino = false;
          this.Condominos = this.Condominos.map((C) => (C.id_usuario === id_usuario ? r.value.condomino : C));
          hlpSwal.ExitoToast(r.value.msg);
        }
      });
  }

  onCondominoDeshabilitarCancelar() {
    this.mostrarDialogoDeshabilitarCondomino = false;
  }

  contratoURL() {
    return this.sanitizer.bypassSecurityTrustResourceUrl(this.srcContrato + '#toolbar=0&view=fitH');
  }

  async onContratoMostrar(Condomino: CondominoResumenModel = null) {
    if (Condomino != null) {
      this.srcContrato = this.Condomino.contrato
        ? environment.urlBackendUsuariosFiles + this.Condomino.id_usuario + '/' + this.Condomino.contrato
        : null;
    }

    this.mostrarDialogoContratoCondomino = this.srcContrato != null;
    if (this.mostrarDialogoContratoCondomino) {
      hlpSwal.Cargando();
    }
  }

  onContratoMostrado() {
    hlpSwal.Cerrar();
  }

  async onCondominoDetalles(idUsuario: number = 0) {
    if (idUsuario == 0) {
      return;
    }
    hlpSwal.Cargando();
    this.Condomino = await this.condominosService
      .ListarCondomino(idUsuario)
      .toPromise()
      .then((r) => r['condomino'])
      .catch(async (e) => {
        await hlpSwal.Error(e).then(() => null);
      })
      .finally(() => {
        hlpSwal.Cerrar();
      });
    this.srcImagen = this.Condomino.imagen
      ? environment.urlBackendUsuariosFiles + this.Condomino.id_usuario + '/' + this.Condomino.imagen
      : null;
    this.srcIdentificacionAnverso = this.Condomino.identificacion_anverso
      ? environment.urlBackendUsuariosFiles + this.Condomino.id_usuario + '/' + this.Condomino.identificacion_anverso
      : null;
    this.srcIdentificacionReverso = this.Condomino.identificacion_reverso
      ? environment.urlBackendUsuariosFiles + this.Condomino.id_usuario + '/' + this.Condomino.identificacion_reverso
      : null;

    this.mostrarDialogoDetallesCondomino = this.Condomino != null;
  }
}
