import { Component, OnInit, isDevMode } from '@angular/core';
import { FormArray, FormBuilder, FormGroup, Validators } from '@angular/forms';

import alasql from 'alasql';
import { environment } from '../../../environments/environment';
import * as hlpApp from '../../helpers/app-helper';
import * as hlpSwal from '../../helpers/sweetalert2-helper';
import * as hlpPrimeNGTable from '../../helpers/primeng-table-helper';
import { FormsValidator } from '../../validators/forms.validator';


import {
  ConvocatoriaResumenModel,
  ConvocatoriaModel,
  OrdenDelDiaModel,
  ActaModel,
  /* ActaOrdenDiaModel,
  ActaOrdenDiaVotacionModel,
  ActaOrdenDiaSentidoVotacionModel */
} from '../../models/asamblea.model';
// import { UsuarioActaAsamblea } from '../../models/usuario.model';
import { AsambleasService } from '../../services/asambleas.service';
import { UsuariosService } from '../../services/usuarios.service';
import { TiposAsambleasService } from '../../services/tipos-asambleas.service';

@Component({
  selector: 'app-asambleas',
  templateUrl: './asambleas.component.html',
  styleUrls: ['./asambleas.component.css'],
})
export class AsambleasComponent implements OnInit {
  appData = environment;
  hlpApp = hlpApp;
  hlpPrimeNGTable = hlpPrimeNGTable;
  isDevelopment = isDevMode;

  catSentidoVotacion: any[] = [
    { id: 0, sentido_votacion: 'Sin votar' },
    { id: 1, sentido_votacion: 'A favor' },
    { id: 2, sentido_votacion: 'En contra' },
    { id: 3, sentido_votacion: 'Abstención' },
  ];

  // Tabla Convocatorias
  // Columnas de la tabla
  ConvocatoriasCols: any[] = [
    { header: 'Fecha', width: '140px' },
    { header: 'Tipo' },
    { header: 'Lugar' },
    { header: 'Emite' },
    // Botones de acción
    { textAlign: 'center', width: '130px' },
  ];
  ConvocatoriasFilter: any[] = ['fecha', 'titulo'];

  // Convocatorias: ConvocatoriaModel[] = [];
  Convocatorias: ConvocatoriaResumenModel[] = [];
  TiposAsambleas: any[] = [];
  idAsamblea: number = 0;
  Convocatoria: ConvocatoriaModel;
  fechaMinimaAsamblea: Date = new Date();
  fechaMinimaConvocatoria: Date = new Date();

  frmConvocatoria: FormGroup;
  mostrarDialogoConvocatoria: boolean = false;

  ModulosEditorDescripcion = {
    imageResize: {
      handleStyles: {
        backgroundColor: 'black',
        border: 'none',
        color: 'white',
      },
      modules: ['DisplaySize', 'Resize'],
    },
  };

  OrdenesDelDiaCols: any[] = [
    /* { header: '', width: '3rem' }, */
    { header: 'Descripción' },
    { header: 'Requiere votación', width: '120px' },
    // Botones de acción
    { textAlign: 'center', width: '90px' },
  ];
  OrdenesDelDia: OrdenDelDiaModel[] = [];
  idOrdenDelDia: number = 0;
  OrdenDelDia: OrdenDelDiaModel;
  frmOrdenDelDia: FormGroup;
  mostrarDialogoOrdenDelDia: boolean = false;

  PaseListaCols: any[] = [
    /* { header: '', width: '3rem' }, */
    { header: 'Unidad' },
    { header: 'Nombre' },
    /* { header: 'Perfil', width: '120px' }, */
    { header: 'Asiste', width: '120px' },
  ];
  // UsuariosActa: UsuarioActaAsamblea[] = [];
  idActa: number = 0;
  Acta: ActaModel;
  // ActaPaseLista: ActaPaseListaModel[] = [];
  // ActaPaseLista: any[] = [];
  // ActaOrdenDia: ActaOrdenDiaModel[] = [];
  // ActaOrdenDiaVotaciones: ActaOrdenDiaVotacionModel[] = [];
  bExisteQuorum: boolean;

  VotacionPuntoOrdenDiaCols: any[] = [
    /* { header: '', width: '3rem' }, */
    { header: 'Unidad' },
    { header: 'Nombre' },
    /* { header: 'Perfil', width: '120px' }, */
    { header: 'Sentido votación', width: '120px' },
  ];

  // frmActa: FormGroup = this.formBuilder.group(new ActaModel());
  frmActa: FormGroup;
  mostrarDialogoEdicionActa: boolean = false;
  mostrarDialogoEmisionActa: boolean = false;

  constructor(private formBuilder: FormBuilder,
    private tiposAsambleasService: TiposAsambleasService,
    private asambleasService: AsambleasService,
    private usuariosService: UsuariosService,
    // private unidadesService: UnidadesService
  ) { }

  ngOnInit(): void {
    this.onActualizarInformacion();
  }

  private OrdenarConvocatorias(asambleas: ConvocatoriaResumenModel[]) {
    return asambleas.sort((a, b) => (a.fecha_hora.toString() > b.fecha_hora.toString() ? 1 : -1));
  }

  /* private getTextoPEditor(idxPunto: number, esApertura: boolean = true) {
    let texto = null;
    const pEditor = document.getElementById('ordenDia' + (esApertura ? 'Apertura' : 'Cierre') + idxPunto);
    if (!pEditor)
      return texto;

    const qlEditor = pEditor.getElementsByClassName('ql-editor');
    if (qlEditor.length != 1)
      return texto;

    texto = qlEditor[0].innerHTML;
    return texto;
  }
 */

  private calcularExistenciaQuorum() {
    this.bExisteQuorum = this.actaPaseLista.value.filter((p) => p.asistencia).length > this.actaPaseLista.controls.length / 2;
    /*
    let bExisteQuorum = this.actaPaseLista.value.filter((p) => p.asistencia).length > this.actaPaseLista.controls.length / 2;
    if (bExisteQuorum) {
      this.frmActa.controls['cierre'].enable();
      this.frmActa.controls['cierre'].setValue('<p><br></p>');
    } else {
      this.frmActa.controls['cierre'].disable();
      this.frmActa.controls['cierre'].setValue('<p>Esta asamblea no se llevó a cabo por falt ade quorum.</p><p>Por tal motivo se acordó realizar una nueva convocatoria.</p>');

    }
    this.frmActa.get('existe_quorum').setValue(bExisteQuorum);
    */
  }

  /*   existenVotosPendientes() {
      let puntosRequierenVotacion = this.actaOrdenDia.controls.filter((o) => o.get('requiere_votacion').value);
      if (puntosRequierenVotacion.length < 1) {
        return null;
      }
      for (const punto of puntosRequierenVotacion) {
        if ((punto.get('votacion') as FormArray).controls.filter(
          (v) => v.get('id_sentido_votacion').value == 0).length > 0) {
          return true;
        }
      }
      return false;
    } */

  /* private setTextosPuntosOrdenDia(puntosOrdenDia: any) {
    puntosOrdenDia.forEach((punto, idxPunto) => {
      let pEditor = document.getElementById('ordenDiaApertura' + idxPunto);
      if (pEditor) {
        const qlEditor = pEditor.getElementsByClassName('ql-editor');
        if (qlEditor.length == 1) {
          qlEditor[0].innerHTML = punto.apertura ? punto.apertura : '<p><br /></p>';
        }
      }

      pEditor = null;
      pEditor = document.getElementById('ordenDiaCierre' + idxPunto);
      if (pEditor) {
        const qlEditor = pEditor.getElementsByClassName('ql-editor');
        if (qlEditor.length == 1) {
          qlEditor[0].innerHTML = punto.cierre ? punto.cierre : '<p><br /></p>';
        }
      }
    });
  } */

  public onActualizarInformacion() {
    this.Convocatorias = [];
    this.TiposAsambleas = [];

    hlpSwal.Cargando();

    this.tiposAsambleasService.ListarActivos().toPromise().then((r) => {
      this.TiposAsambleas = r['tipos_asambleas'];
    })
      .catch(async (e) => {
        await hlpSwal.Error(e);
      });

    this.asambleasService
      .ListarActivos()
      .toPromise()
      .then((r) => {
        this.Convocatorias = this.OrdenarConvocatorias(r['asambleas']);
      })
      .catch(async (e) => {
        await hlpSwal.Error(e);
      })
      .finally(() => {
        if (hlpSwal.estaCargando) {
          hlpSwal.Cerrar()
        }
      });
  }

  async onConvocatoriaEditar(idAsamblea: number = 0) {
    hlpSwal.Cargando();
    this.idAsamblea = idAsamblea;

    if (idAsamblea > 0) {
      this.Convocatoria = await this.asambleasService
        .ListarConvocatoria(idAsamblea)
        .toPromise()
        .then((r) => r['asamblea'])
        .catch(async (e) => {
          await hlpSwal.Error(e).then(() => null);
        });
      if (this.Convocatoria == null) return;

      // this.Convocatoria.fecha = new Date(this.Convocatoria.fecha + 'T00:00:00');
    } else {
      this.Convocatoria = new ConvocatoriaModel();
    }
    hlpSwal.Cerrar();

    try {
      this.fechaMinimaAsamblea = new Date();
      this.fechaMinimaConvocatoria = new Date();
      this.fechaMinimaAsamblea.setDate(this.fechaMinimaConvocatoria.getDate() + 1);
      this.Convocatoria.fecha_hora =
        idAsamblea > 0 ? new Date(this.Convocatoria.fecha_hora) : this.Convocatoria.fecha_hora;
      this.Convocatoria.convocatoria_fecha =
        idAsamblea > 0 ? new Date(this.Convocatoria.convocatoria_fecha + ' 0000:00') : this.Convocatoria.convocatoria_fecha;

      this.frmConvocatoria = this.formBuilder.group(this.Convocatoria, {
        validators: FormsValidator.fechaMenorQue('convocatoria_fecha', 'fecha_hora'),
      });
      this.frmConvocatoria.get('fecha_hora').setValidators([Validators.required]);
      this.frmConvocatoria.get('id_tipo_asamblea').setValidators([Validators.min(1)]);
      this.frmConvocatoria
        .get('lugar')
        .setValidators([Validators.required, Validators.minLength(3), Validators.maxLength(250)]);
      this.frmConvocatoria.get('fundamento_legal').setValidators([Validators.required]);
      this.frmConvocatoria.get('convocatoria_cierre').setValidators([Validators.required]);
      this.frmConvocatoria.get('convocatoria_fecha').setValidators([Validators.required]);
      this.frmConvocatoria.get('convocatoria_ciudad').setValidators([Validators.required]);
      this.frmConvocatoria.get('convocatoria_quien_emite').setValidators([Validators.required]);
      this.OrdenesDelDia = [];
      if (this.Convocatoria.orden_dia) {
        this.OrdenesDelDia = this.Convocatoria.orden_dia.map((o) => {
          return {
            id_asamblea_orden_dia: o.id_asamblea_orden_dia,
            orden_dia: o.orden_dia,
            requiere_votacion: o.requiere_votacion == 1,
          }
        });
      }
      this.idOrdenDelDia = this.OrdenesDelDia.length + 1;

      this.frmConvocatoria.updateValueAndValidity();

      this.mostrarDialogoConvocatoria = true;
    } catch (e) {
      hlpSwal.Error(e);
    }
  }

  onOrdenDelDiaEditar(ordenDelDia: OrdenDelDiaModel = null) {
    if (ordenDelDia == null) {
      ordenDelDia = new OrdenDelDiaModel();
    }
    // this.frmOrdenDelDia.patchValue(ordenDelDia);
    this.OrdenDelDia = Object.assign({}, ordenDelDia);
    this.frmOrdenDelDia = this.formBuilder.group(ordenDelDia);
    this.frmOrdenDelDia
      .get('orden_dia')
      .setValidators([Validators.required, Validators.minLength(3), Validators.maxLength(250)]);
    this.frmOrdenDelDia.updateValueAndValidity();

    this.mostrarDialogoOrdenDelDia = true;
  }

  onOrdenDelDiaEliminar(idOrdenDelDia: number) {
    hlpSwal.Pregunta('¿Deseas eliminar el orden del día?').then(async (r) => {
      if (r.isConfirmed) {
        this.OrdenesDelDia = this.OrdenesDelDia.filter((i: any) => i.id_asamblea_orden_dia !== idOrdenDelDia);
      }
    });
  }

  onGuardarOrdenDelDia() {
    if (!this.frmOrdenDelDia.valid) {
      this.frmOrdenDelDia.markAllAsTouched();
      hlpSwal.Error('Se detectaron errores en la información solicitada.');
      return;
    }

    let ordenDelDia = this.frmOrdenDelDia.value;
    hlpSwal.Pregunta('¿Deseas ' + (ordenDelDia.id_asamblea_orden_dia == 0 ? 'agregar' : 'actualizar') + ' la información?').then(async (r) => {
      if (r.isConfirmed) {
        ordenDelDia.orden_dia = ordenDelDia.orden_dia.toUpperCase();
        if (ordenDelDia.id_asamblea_orden_dia == 0) {
          ordenDelDia.id_asamblea_orden_dia = this.idOrdenDelDia++;
          this.OrdenesDelDia.push(ordenDelDia);
        } else {
          this.OrdenesDelDia = this.OrdenesDelDia.map((O) => (O.id_asamblea_orden_dia === ordenDelDia.id_asamblea_orden_dia ? ordenDelDia : O));
        }
        this.mostrarDialogoOrdenDelDia = false;
      }
    });
  }

  onCancelarEdicionOrdenDelDia() {
    /* hlpSwal.Pregunta('¿Deseas cancelar la ' +
      (this.frmOrdenDelDia.get('id_asamblea_orden_dia').value == 0 ? 'adición' : 'edición') +
      '?').then(async (r) => {
        if (r.isConfirmed) { */
    this.mostrarDialogoOrdenDelDia = false;
    /* }
  }); */
  }

  onConvocatoriaGuardar() {
    if (!this.frmConvocatoria.valid) {
      this.frmConvocatoria.markAllAsTouched();
      hlpSwal.Error('Se detectaron errores en la información solicitada.');
      return;
    }
    if (this.OrdenesDelDia.length < 1) {
      hlpSwal.Error('Se debe especificar al menos un punto en el orden del día.');
      return;
    }

    let asamblea = this.frmConvocatoria.value;
    asamblea.fecha_hora = hlpApp.formatDateToMySQL(asamblea.fecha_hora);
    asamblea.convocatoria_fecha = hlpApp.formatDateToMySQL(asamblea.convocatoria_fecha);
    asamblea.orden_dia = this.OrdenesDelDia.map((o) => {
      return {
        orden_dia: o.orden_dia,
        requiere_votacion: o.requiere_votacion ? 1 : 0,
      }
    });

    hlpSwal
      .Pregunta({
        html: '¿Deseas guardar la información?',
        showLoaderOnConfirm: true,
        preConfirm: async () => {
          try {
            return await this.asambleasService.GuardarConvocatoria(asamblea).toPromise();
          } catch (e) {
            return hlpSwal.Error(e).then(() => ({ err: true }));
          }
        },
        allowOutsideClick: () => !hlpSwal.estaCargando,
      })
      .then((r) => {
        if (r.value && !r.value.err && r.value.asamblea) {
          const c = r.value.asamblea;
          if (asamblea.id_asamblea == 0) {
            this.Convocatorias.push(c);
          } else {
            this.Convocatorias = this.Convocatorias.map((C) => (C.id_asamblea === c.id_asamblea ? c : C));
          }
          this.Convocatorias = this.OrdenarConvocatorias(this.Convocatorias);
          hlpSwal.ExitoToast(r.value.msg);
          this.mostrarDialogoConvocatoria = false;
        }
      });
  }

  onConvocatoriaCancelar() {
    this.mostrarDialogoConvocatoria = false;
  }

  async onConvocatoriaDetalles(idAsamblea: number = 0) {
    if (idAsamblea == 0) {
      return;
    }

    hlpSwal.Cargando();
    this.Convocatoria = await this.asambleasService
      // .ListarConvocatoriaDetalle(idAsamblea)
      .ListarConvocatoria(idAsamblea)
      .toPromise()
      .then((r) => r['asamblea'])
      .catch(async (e) => {
        await hlpSwal.Error(e).then(() => null);
      })
      .finally(() => {
        hlpSwal.Cerrar();
      });

    this.Convocatoria.fecha_hora = new Date(this.Convocatoria.fecha_hora);
    this.Convocatoria.convocatoria_fecha = new Date(this.Convocatoria.convocatoria_fecha);
    this.frmConvocatoria = this.formBuilder.group(this.Convocatoria);
    this.frmConvocatoria.disable();
    this.OrdenesDelDia = [];
    if (this.Convocatoria.orden_dia) {
      this.OrdenesDelDia = this.Convocatoria.orden_dia.map((o) => {
        return {
          id_asamblea_orden_dia: o.id_asamblea_orden_dia,
          orden_dia: o.orden_dia,
          requiere_votacion: o.requiere_votacion == 1,
        }
      });
    }

    this.mostrarDialogoConvocatoria = this.Convocatoria != null;
  }

  onConvocatoriaEliminar(idAsamblea: number = 0) {
    hlpSwal
      .Pregunta({
        html: '¿Deseas eliminar el registro?',
        showLoaderOnConfirm: true,
        preConfirm: async () => {
          try {
            return await this.asambleasService.EliminarConvocatoria(idAsamblea).toPromise();
          } catch (e) {
            return hlpSwal.Error(e).then(() => ({ err: true }));
          }
        },
        allowOutsideClick: () => !hlpSwal.estaCargando,
      })
      .then((r) => {
        if (r.value && !r.value.err) {
          this.Convocatorias = this.Convocatorias.filter((a) => a.id_asamblea != idAsamblea);
          hlpSwal.ExitoToast(r.value.msg);
        }
      });
  }

  // get existeQuorum() { return this.frmActa.get('existe_quorum') }
  // get votosPendientes() { return this.frmActa.get('votos_pendientes') }
  get actaPaseLista() { return this.frmActa.get('actaPaseLista') as FormArray; }
  get actaOrdenDia() { return this.frmActa.get('actaOrdenDia') as FormArray; }

  async onActaEditar(Convocatoria: ConvocatoriaResumenModel) {
    if (Convocatoria.id_acta > 0) {
      return;
    }
    this.idAsamblea = Convocatoria.id_asamblea;

    /*  hlpSwal.Cargando();
 
     this.Acta = await this.asambleasService
       .ListarActa(this.idAsamblea)
       .toPromise()
       .then((r) => r['acta'])
       .catch(async (e) => {
         await hlpSwal.Error(e).then(() => null);
       });
 
     if (!this.Acta) { */
    this.Acta = new ActaModel();
    this.idActa = 0;
    this.bExisteQuorum = false;

    /* } else {
      this.idActa = this.Acta.id_acta;
      // this.Acta.finalizada = this.Acta.finalizada == 1;
    } */

    // hlpSwal.Cerrar();

    try {
      this.fechaMinimaAsamblea = new Date();
      this.Acta.fecha_hora = new Date(this.Acta.fecha_hora);

      this.frmActa = this.formBuilder.group(new ActaModel());
      this.frmActa.patchValue(this.Acta);

      this.frmActa.get('fecha_hora').setValidators([Validators.required]);
      this.frmActa
        .get('lugar')
        .setValidators([Validators.required, Validators.minLength(3), Validators.maxLength(250)]);
      this.frmActa.get('apertura').setValidators([Validators.required]);
      this.frmActa.get('cierre').setValidators([Validators.required]);
      this.frmActa.get('quien_emite').setValidators([Validators.required, Validators.minLength(3), Validators.maxLength(250)]);

      const usuariosActa = await this.usuariosService.ListarUsuariosActaAsambleas().toPromise()
        .then((r) => r['usuarios'])
        .catch(async (e) => {
          await hlpSwal.Error(e).then(() => null);
        });
      const ordenDiaActa = await this.asambleasService.ListarOrdenDiaConvocatoria(this.idAsamblea).toPromise()
        .then((r) => r['orden_dia'])
        .catch(async (e) => {
          await hlpSwal.Error(e).then(() => null);
        });

      this.frmActa.addControl('actaPaseLista', this.formBuilder.array([]));
      this.actaPaseLista.clear();

      // Este fragmento de código debería ser utilizado incluso si se está editando el acta
      this.frmActa.get('total_unidades').setValue(usuariosActa.length);
      usuariosActa.map((u) => {
        const usuarios = Object.keys(u.usuarios).map(key => (u.usuarios[key]));
        this.actaPaseLista.push(this.formBuilder.group({
          id_unidad: u.id_unidad,
          unidad: u.unidad,
          total_usuarios: usuarios.length,
          usuarios: this.formBuilder.group(usuarios),
          id_usuario: usuarios.length == 1 ? usuarios[0].id_usuario : 0,
          usuario: usuarios.length == 1 ? usuarios[0].usuario + ' - ' + usuarios[0].perfil_usuario : null,
          asistencia: false,
        }));

        let paseListaUnidad = this.actaPaseLista.controls[this.actaPaseLista.length - 1];
        if (usuarios.length > 1) {
          paseListaUnidad.get('asistencia').disable();
        }

        return {
          id_unidad: u.id_unidad,
          unidad: u.unidad,
          asistencia: false,
          usuarios: usuarios,
          id_usuario: usuarios.length == 1 ? usuarios[0].id_usuario : 0,
        }
      });

      // Este fragmento de código debería ser utilizado incluso si se está editando el acta
      this.frmActa.addControl('actaOrdenDia', this.formBuilder.array([]));
      this.actaOrdenDia.clear();
      let requiere_votacion = false;
      ordenDiaActa.map((o) => {
        if (!requiere_votacion && o.requiere_votacion == 1) {
          requiere_votacion = true;
          /* if (!this.bExisteQuorum) {
            this.bExisteQuorum = false;
          } */
        }
        this.actaOrdenDia.push(this.formBuilder.group({
          id_asamblea_orden_dia: o.id_asamblea_orden_dia,
          orden_dia: o.orden_dia,
          requiere_votacion: o.requiere_votacion == 1,
          apertura: [null, [Validators.required, Validators.min(1)]],
          cierre: null,
          votacion: this.formBuilder.array([])
        }));
      });

      this.bExisteQuorum = !requiere_votacion;
      // this.votosPendientes.setValue(requiere_votacion);

      this.frmActa.updateValueAndValidity();
      this.mostrarDialogoEdicionActa = true;

    } catch (e) {
      hlpSwal.Error(e);
    }
  }

  // Se ejecuta sólo cuando el acta es editada
  /*   onMostrarEdicionActa() {
      this.calcularExistenciaQuorum();
  
      if (this.idActa == 0) {
        return;
      }
  
      const puntosOrdenDia = this.actaOrdenDia.value;
      if (!puntosOrdenDia)
        return;
  
      // Procesar la información de cada punto del orden del día
      puntosOrdenDia.forEach((punto, idxPunto) => {
        let pEditor = document.getElementById('ordenDiaApertura' + idxPunto);
        if (pEditor) {
          const qlEditor = pEditor.getElementsByClassName('ql-editor');
          if (qlEditor.length == 1) {
            qlEditor[0].innerHTML = punto.apertura ? punto.apertura : '<p><br /></p>';
          }
        }
  
        pEditor = null;
        pEditor = document.getElementById('ordenDiaCierre' + idxPunto);
        if (pEditor) {
          const qlEditor = pEditor.getElementsByClassName('ql-editor');
          if (qlEditor.length == 1) {
            qlEditor[0].innerHTML = punto.cierre ? punto.cierre : '<p><br /></p>';
          }
        }
  
        punto.votaciones.forEach((votacion, idxVotacion) => {
          const cmbVotacion = <HTMLSelectElement>document.getElementById('votacionP' + idxPunto + 'I' + idxVotacion);
          if (cmbVotacion) {
            cmbVotacion.selectedIndex = Number(votacion.votacion);
          }
        });
      });
    } */

  // Cuando es seleccionado una persona de la unidad para que vote
  onPaseListaUsuarioChange(idUsuario: number, usuario: string, idUnidad: number) {
    this.calcularExistenciaQuorum();

    let paseListaUnidad: any;
    paseListaUnidad = this.actaPaseLista.controls.filter((c) => c.get('id_unidad').value == idUnidad);
    if (paseListaUnidad.length != 1) {
      let msg = '';
      switch (paseListaUnidad.length) {
        case 0:
          msg = 'No se encontró unidad para la persona seleccionada.';
          break;
        default:
          msg = 'Se encontró más de una unidad para la persona seleccionada.';
          break;
      };
      hlpSwal.Error(msg);
      return;
    }
    paseListaUnidad = paseListaUnidad[0];
    paseListaUnidad.get('id_usuario').setValue(idUsuario);
    paseListaUnidad.get('usuario').setValue(usuario);
    if (idUsuario == 0) {
      paseListaUnidad.get('asistencia').setValue(false);
      paseListaUnidad.get('asistencia').disable();
    } else {
      paseListaUnidad.get('asistencia').enable();
    }
  }

  // Cuando se cambia el valor de la asistencia
  onPaseListaAsistenciaChange(e: any, paseListaUsuario: any) {
    this.calcularExistenciaQuorum();
    let puntosConVotacion = this.actaOrdenDia.controls.filter((o) => o.get('requiere_votacion').value == true);
    puntosConVotacion.forEach((punto) => {
      if (e.checked) {
        /* this.ActaOrdenDiaVotaciones.push({
          id_asamblea_orden_dia: punto.get('id_asamblea_orden_dia').value,
          id_unidad: paseListaUsuario.id_unidad.value,
          unidad: paseListaUsuario.unidad.value,
          id_usuario: paseListaUsuario.id_usuario.value,
          usuario: paseListaUsuario.usuario.value,
          // perfil_usuario: paseListaUsuario.perfil_usuario.value,
          id_sentido_votacion: 0
        });
        this.ActaOrdenDiaVotaciones = this.ActaOrdenDiaVotaciones.sort((a, b) => (a.usuario > b.usuario ? 1 : -1)); */

        (punto.get('votacion') as FormArray).push(this.formBuilder.group({
          id_asamblea_orden_dia: punto.get('id_asamblea_orden_dia').value,
          id_unidad: Number(paseListaUsuario.id_unidad.value),
          unidad: paseListaUsuario.unidad.value,
          id_usuario: paseListaUsuario.id_usuario.value,
          usuario: paseListaUsuario.usuario.value,
          id_sentido_votacion: [0, [Validators.required, Validators.min(1)]]
          // id_sentido_votacion: 0
        }));

        punto.get('votacion').setValue(
          (punto.get('votacion') as FormArray).value.sort((a, b) => (a.usuario > b.usuario ? 1 : -1))
        );
      } else {
        const votaciones = punto.get('votacion') as FormArray;
        let votacion = punto.get('votacion').value.filter((v) => v.id_unidad != paseListaUsuario.id_unidad.value);
        votaciones.clear();
        votacion.forEach(v => {
          votaciones.push(this.formBuilder.group({
            id_asamblea_orden_dia: v.id_asamblea_orden_dia,
            id_unidad: v.id_unidad,
            unidad: v.unidad,
            id_usuario: v.id_usuario,
            usuario: v.usuario,
            id_sentido_votacion: v.id_sentido_votacion
          }));
        });
      }
    });
  }

  /* actaOrdenDiaVotaciones(idAsambleaOrdenDia: number) {
    return this.ActaOrdenDiaVotaciones.filter((v) => v.id_asamblea_orden_dia == idAsambleaOrdenDia);
  } */

  /*  onSentidoVotacionChange(e: any, votacion: any) {
     votacion.id_sentido_votacion = e;
   } */

  getTotalesVotacionPuntoOrdenDia(idAsambleaOrdenDia: number) {
    let ordenDia = this.actaOrdenDia.controls.filter((o) => o.get('id_asamblea_orden_dia').value == idAsambleaOrdenDia);
    if (ordenDia.length < 1) {
      return null;
    }
    let votaciones = ordenDia[0].get('votacion').value;
    if (votaciones.length < 1) {
      return null;
    }
    /* if (this.frmActa.get('votos_pendientes').value == false) {
      this.frmActa.get('votos_pendientes').setValue(votaciones.filter((v) => v.id_sentido_votacion == 0).length > 0);
    } */

    votaciones = alasql(
      'SELECT sv.id AS id_sentido_votacion, sv.sentido_votacion, SUM(IF(IFNULL(v.id_sentido_votacion, -1) >= 0, 1, 0)) AS total FROM ? AS sv ' +
      'LEFT JOIN ? AS v ON v.id_sentido_votacion = sv.id ' +
      'GROUP BY sv.id, sv.sentido_votacion ' +
      'ORDER BY sv.id',
      [this.catSentidoVotacion, votaciones],
    );

    return votaciones;
  }

  /* onFinalizarActaChange(event) {
    this.frmActa.get('finalizada').setValue(event.checked);
  } */

  onActaGuardar() {
    let acta = this.frmActa.getRawValue()

    let actaPaseLista = acta.actaPaseLista;
    let actaOrdenDia = acta.actaOrdenDia;
    acta.fecha_hora = hlpApp.formatDateToMySQL(acta.fecha_hora);
    // acta.finalizada = acta.finalizada ? 1 : 0;
    /* delete acta.existe_quorum;
    delete acta.votos_pendientes; */
    delete acta.actaPaseLista;
    delete acta.actaOrdenDia;

    acta.pase_lista = Object.assign({}, actaPaseLista.filter((o) => o.asistencia).map(p => {
      return {
        id_unidad: Number(p.id_unidad),
        id_usuario: Number(p.id_usuario),
        // id_usuario: p.asistencia == 0 ? null : Number(p.id_usuario),
        // id_usuario: Number(p.asistencia == 0 ? p.usuarios[0].id_usuario : p.id_usuario),
        // asistencia: p.asistencia ? 1 : 0
      }
    }));

    acta.orden_dia = [];
    // acta.orden_dia_votacion = [];
    for (let i = 0; i < actaOrdenDia.length; i++) {
      acta.orden_dia.push({
        id_asamblea_orden_dia: Number(actaOrdenDia[i].id_asamblea_orden_dia),
        /* apertura: this.getTextoPEditor(i),
        cierre: this.getTextoPEditor(i, false), */
        apertura: actaOrdenDia[i].apertura,
        cierre: actaOrdenDia[i].cierre,
        votacion: actaOrdenDia[i].requiere_votacion
          ? Object.assign({}, actaOrdenDia[i].votacion.map((v) => {
            return {
              // id_asamblea_orden_dia: Number(v.id_asamblea_orden_dia),
              id_unidad: Number(v.id_unidad),
              id_usuario: Number(v.id_usuario),
              id_sentido_votacion: Number(v.id_sentido_votacion)
            }
          }))
          : null
      });

      /* if (actaOrdenDia[i].requiere_votacion) {
        acta.orden_dia_votacion.push(
          actaOrdenDia[i].votacion.map((v) => {
            return {
              id_asamblea_orden_dia: Number(v.id_asamblea_orden_dia),
              id_unidad: Number(v.id_unidad),
              id_usuario: Number(v.id_usuario),
              id_sentido_votacion: Number(v.id_sentido_votacion)
            }
          }));
      } */
    }
    /* if (acta.orden_dia_votacion.length == 0) {
      delete acta.orden_dia_votacion;
    } */

    console.log('Acta :>> ', acta);

    hlpSwal
      .Pregunta({
        html: '¿Deseas guardar la información?',
        showLoaderOnConfirm: true,
        preConfirm: async () => {
          try {
            if (!this.bExisteQuorum) {
              acta.cierre = '<p>Esta asamblea no se llevó a cabo por falta de quorum.</p><p>Por tal motivo se acordó realizar una nueva convocatoria.</p>';
            }
            return await this.asambleasService.GuardarActa(this.idAsamblea, acta).toPromise();
          } catch (e) {
            return hlpSwal.Error(e).then(() => ({ err: true }));
          }
        },
        allowOutsideClick: () => !hlpSwal.estaCargando,
      })
      .then((r) => {
        if (r.value && !r.value.err && r.value.asamblea) {
          const c = r.value.asamblea;
          this.Convocatorias = this.Convocatorias.map((C) => (C.id_asamblea === c.id_asamblea ? c : C));
          this.Convocatorias = this.OrdenarConvocatorias(this.Convocatorias);
          hlpSwal.ExitoToast(r.value.msg);
          this.mostrarDialogoEdicionActa = false;
        }
      });
  }

  onActaCancelar() {
    this.mostrarDialogoEdicionActa = false;
  }

  onActaDetalles(idActa) {
    this.Acta = null;

    hlpSwal.Cargando();

    this.asambleasService
      .ListarActa(idActa)
      .toPromise()
      .then((r) => {
        this.Acta = r['acta'];
        this.mostrarDialogoEmisionActa = true;
        hlpSwal.Cerrar();
      })
      .catch(async (e) => {
        await hlpSwal.Error(e).then(() => null);
      });
  }
}
