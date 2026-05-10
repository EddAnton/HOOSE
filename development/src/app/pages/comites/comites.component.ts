import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { environment } from '../../../environments/environment';
import * as hlpSwal from '../../helpers/sweetalert2-helper';
import { ComitesService } from '../../services/comites.service';
import { UsuariosService } from '../../services/usuarios.service';

@Component({
  selector: 'app-comites',
  templateUrl: './comites.component.html',
  styleUrls: ['./comites.component.css']
})
export class ComitesComponent implements OnInit {
  appData = environment;

  Comites: any[] = [];
  TiposComites: any[] = [];
  CargosComite: any[] = [];
  UsuariosDisponibles: any[] = [];

  mostrarDialogo = false;
  mostrarDialogoTipo = false;
  MiembroSeleccionado: any = null;
  TipoSeleccionado: any = null;
  esAdminUnico = false;

  tiposPersona = [
    { label: 'Persona Física', value: 'FISICA' },
    { label: 'Persona Moral', value: 'MORAL' },
  ];

  ColsMiembros: any[] = [
    { header: 'Cargo', width: '180px' },
    { header: 'Nombre' },
    { header: 'Perfil' },
    { header: 'Desde', width: '110px' },
    { textAlign: 'center', width: '90px' },
  ];

  frmMiembro: FormGroup;
  frmTipo: FormGroup;

  constructor(
    private fb: FormBuilder,
    private comitesService: ComitesService,
    private usuariosService: UsuariosService,
  ) {}

  ngOnInit() { 
    this.cargarDatos(); 
    this.frmTipo = this.fb.group({
      tipo_comite: [null, [Validators.required, Validators.minLength(3)]],
    });
  }

  async cargarDatos() {
    hlpSwal.Cargando();
    try {
      const [t, c, u] = await Promise.all([
        this.comitesService.ListarTipos().toPromise(),
        this.comitesService.Listar().toPromise(),
        this.usuariosService.ListarUsuariosActaAsambleas().toPromise(),
      ]);
      this.TiposComites = t['tipos_comites'] || [];
      this.Comites = c['comites'] || [];
      const grupos = u['usuarios'] || [];
      this.UsuariosDisponibles = grupos.reduce((acc: any[], g: any) =>
        acc.concat((g.usuarios || []).map((u: any) => ({
          ...u, label: u.usuario + ' — ' + u.perfil_usuario
        }))), []);
    } catch(e) { await hlpSwal.Error(e); }
    finally { hlpSwal.Cerrar(); }
  }

  onActualizarInformacion() { this.cargarDatos(); }

  // ── Miembros ──────────────────────────────────────────
  initForm() {
    this.frmMiembro = this.fb.group({
      id_tipo_comite:    [null, Validators.required],
      id_cargo_comite:   [null, Validators.required],
      id_usuario:        [null],
      es_unico:          [0],
      tipo_persona:      [null],
      razon_social:      [null],
      representante_legal: [null],
      fecha_inicio:      [new Date(), Validators.required],
      fecha_fin:         [null],
    });
  }

  onNuevoMiembro() {
    this.MiembroSeleccionado = null;
    this.CargosComite = [];
    this.esAdminUnico = false;
    this.initForm();
    this.mostrarDialogo = true;
  }

  onEditarMiembro(m: any) {
    this.MiembroSeleccionado = m;
    this.esAdminUnico = +m.es_unico === 1;
    this.cargarCargos(+m.fk_id_tipo_comite);
    this.frmMiembro = this.fb.group({
      id_tipo_comite:    [+m.fk_id_tipo_comite, Validators.required],
      id_cargo_comite:   [+m.fk_id_cargo_comite, Validators.required],
      id_usuario:        [m.fk_id_usuario ? +m.fk_id_usuario : null],
      es_unico:          [+m.es_unico || 0],
      tipo_persona:      [m.tipo_persona || null],
      razon_social:      [m.razon_social || null],
      representante_legal: [m.representante_legal || null],
      fecha_inicio:      [new Date(m.fecha_inicio + 'T00:00:00'), Validators.required],
      fecha_fin:         [m.fecha_fin ? new Date(m.fecha_fin + 'T00:00:00') : null],
    });
    this.mostrarDialogo = true;
  }

  async cargarCargos(idTipo: number) {
    try {
      const r = await this.comitesService.ListarCargos(idTipo).toPromise();
      this.CargosComite = r['cargos'] || [];
    } catch(e) { console.error(e); }
  }

  onTipoChange(val: any) {
    console.log('onTipoChange val:', val, typeof val);
    const id = +val;
    this.frmMiembro.patchValue({ id_cargo_comite: null, es_unico: 0 });
    this.esAdminUnico = false;
    if (id) this.cargarCargos(id);
  }

  onCargoChange(val: any) {
    const cargo = this.CargosComite.find(c => +c.id_cargo_comite === +val);
    this.esAdminUnico = cargo ? +cargo.orden === 0 : false;
    this.frmMiembro.patchValue({ es_unico: this.esAdminUnico ? 1 : 0 });
    if (!this.esAdminUnico) {
      this.frmMiembro.patchValue({ tipo_persona: null, razon_social: null, representante_legal: null });
    }
  }

  get esMoral() { return this.frmMiembro?.get('tipo_persona')?.value === 'MORAL'; }

  async onGuardar() {
    const v = this.frmMiembro.value;
    // Validación manual
    if (!v.id_tipo_comite || !v.id_cargo_comite || !v.fecha_inicio) {
      hlpSwal.Advertencia('Por favor completa los campos requeridos: Comité, Cargo y Fecha inicio.');
      return;
    }
    if (!this.esAdminUnico && !v.id_usuario) {
      hlpSwal.Advertencia('Por favor selecciona un propietario o condómino.');
      return;
    }
    if (this.esAdminUnico && !v.tipo_persona) {
      hlpSwal.Advertencia('Por favor selecciona el tipo de persona.');
      return;
    }
    if (this.esAdminUnico && v.tipo_persona === 'MORAL' && !v.razon_social) {
      hlpSwal.Advertencia('Por favor ingresa la razón social.');
      return;
    }

    const r = await hlpSwal.Pregunta(this.MiembroSeleccionado ? '¿Actualizar miembro?' : '¿Agregar miembro?');
    if (!r.isConfirmed) return;

    const data = { ...v };
    data.fecha_inicio = v.fecha_inicio instanceof Date ? v.fecha_inicio.toISOString().split('T')[0] : v.fecha_inicio;
    data.fecha_fin = v.fecha_fin instanceof Date ? v.fecha_fin.toISOString().split('T')[0] : (v.fecha_fin || null);

    hlpSwal.Cargando();
    try {
      if (this.MiembroSeleccionado) {
        await this.comitesService.Actualizar(this.MiembroSeleccionado.id_miembro, data).toPromise();
      } else {
        await this.comitesService.Insertar(data).toPromise();
      }
      await hlpSwal.Exito('Operación realizada correctamente.');
      this.mostrarDialogo = false;
      this.cargarDatos();
    } catch(e) { await hlpSwal.Error(e); }
  }

  async onEliminarMiembro(id: number) {
    const r = await hlpSwal.Pregunta('¿Eliminar este miembro?');
    if (!r.isConfirmed) return;
    hlpSwal.Cargando();
    try {
      await this.comitesService.Eliminar(id).toPromise();
      await hlpSwal.Exito('Miembro eliminado.');
      this.cargarDatos();
    } catch(e) { await hlpSwal.Error(e); }
  }

  // ── Tipos de comité ───────────────────────────────────
  onNuevoTipo() {
    this.TipoSeleccionado = null;
    this.frmTipo = this.fb.group({
      tipo_comite: [null, [Validators.required, Validators.minLength(3)]],
    });
    this.mostrarDialogoTipo = true;
  }

  onEditarTipo(tipo: any) {
    this.TipoSeleccionado = tipo;
    this.frmTipo = this.fb.group({
      tipo_comite: [tipo.tipo_comite, [Validators.required, Validators.minLength(3)]],
    });
    this.mostrarDialogoTipo = true;
  }

  async onGuardarTipo() {
    if (!this.frmTipo.value.tipo_comite) {
      hlpSwal.Advertencia('Ingresa el nombre del comité.');
      return;
    }
    const r = await hlpSwal.Pregunta(this.TipoSeleccionado ? '¿Actualizar tipo?' : '¿Crear tipo?');
    if (!r.isConfirmed) return;
    hlpSwal.Cargando();
    try {
      const data = { tipo_comite: this.frmTipo.value.tipo_comite.toUpperCase() };
      if (this.TipoSeleccionado) {
        await this.comitesService.ActualizarTipo(this.TipoSeleccionado.id_tipo_comite, data).toPromise();
      } else {
        await this.comitesService.InsertarTipo(data).toPromise();
      }
      await hlpSwal.Exito('Operación realizada correctamente.');
      this.TipoSeleccionado = null;
      this.frmTipo.reset();
      this.cargarDatos();
    } catch(e) { await hlpSwal.Error(e); }
  }

  async onEliminarTipo(id: number) {
    const r = await hlpSwal.Pregunta('¿Eliminar este tipo de comité?');
    if (!r.isConfirmed) return;
    hlpSwal.Cargando();
    try {
      await this.comitesService.EliminarTipo(id).toPromise();
      await hlpSwal.Exito('Tipo eliminado.');
      this.cargarDatos();
    } catch(e) { await hlpSwal.Error(e); }
  }
}
