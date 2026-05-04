import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import * as hlpSwal from '../../helpers/sweetalert2-helper';
import * as hlpApp from '../../helpers/app-helper';
import { TareasService } from '../../services/tareas.service';
import { SesionUsuarioService } from '../../services/sesion-usuario.service';

@Component({
  selector: 'app-tareas',
  templateUrl: './tareas.component.html',
  styleUrls: ['./tareas.component.css'],
})
export class TareasComponent implements OnInit {
  hlpApp = hlpApp;
  Tareas: any[] = [];
  TareasFiltradas: any[] = [];
  UsuariosAsignables: any[] = [];
  filtroEstatus: number = 0;
  idPerfilUsuario: number = 0;
  idUsuario: number = 0;
  permitirAgregar: boolean = false;
  frmTarea: FormGroup;
  mostrarDialogo: boolean = false;
  tareaEditando: any = null;
  hoy: string = new Date().toISOString().split('T')[0];

  prioridades = [
    { label: '🔴 Alta',   value: 1 },
    { label: '🟡 Media',  value: 2 },
    { label: '🟢 Baja',   value: 3 },
  ];

  estatusOpciones = [
    { label: 'Pendiente',  value: 1 },
    { label: 'En proceso', value: 2 },
    { label: 'Completada', value: 3 },
  ];

  recordatorioOpciones = [
    { label: 'Sin recordatorio',  value: null },
    { label: '1 hora antes',      value: 60 },
    { label: '3 horas antes',     value: 180 },
    { label: '1 día antes',       value: 1440 },
    { label: '2 días antes',      value: 2880 },
    { label: '1 semana antes',    value: 10080 },
  ];

  modulosVinculados = [
    { label: 'Sin vínculo',           value: '' },
    { label: 'Quejas',                value: 'quejas' },
    { label: 'Asambleas',             value: 'asambleas' },
    { label: 'Proyectos',             value: 'proyectos' },
    { label: 'Áreas Comunes',         value: 'areas-comunes' },
    { label: 'Gastos Mantenimiento',  value: 'gastos-mantenimiento' },
    { label: 'Recaudaciones',         value: 'recaudaciones' },
    { label: 'Notificaciones',        value: 'notificaciones' },
  ];

  constructor(
    private formBuilder: FormBuilder,
    private tareasService: TareasService,
    private sesionUsuarioService: SesionUsuarioService,
  ) {}

  ngOnInit(): void {
    this.idPerfilUsuario = this.sesionUsuarioService.obtenerIDPerfilUsuario();
    this.idUsuario = this.sesionUsuarioService.obtenerIDUsuario();
    this.permitirAgregar = [1, 2].includes(this.idPerfilUsuario);
    this.cargarUsuarios();
    this.onActualizarInformacion();
  }

  cargarUsuarios() {
    this.tareasService.ListarUsuariosAsignables().toPromise()
      .then((r: any) => {
        const perfilLabel = {1: 'Super Admin', 2: 'Administrador', 3: 'Colaborador'};
        this.UsuariosAsignables = (r.data || []).map(u => ({
          label: u.nombre + ' (' + (perfilLabel[u.fk_id_perfil_usuario] || 'Usuario') + ')',
          value: parseInt(u.id_usuario),
        }));
      })
      .catch(() => {});
  }

  onActualizarInformacion() {
    hlpSwal.Cargando();
    this.Tareas = [];
    this.tareasService.Listar().toPromise()
      .then((r: any) => {
        this.Tareas = (r.data || []).map(t => ({
          ...t,
          fk_id_estatus: parseInt(t.fk_id_estatus),
          prioridad: parseInt(t.prioridad),
          recordatorio_tiempo: t.recordatorio_tiempo ? parseInt(t.recordatorio_tiempo) : null,
        }));
        this.aplicarFiltros();
      })
      .catch(async (e) => await hlpSwal.Error(e))
      .finally(() => hlpSwal.Cerrar());
  }

  aplicarFiltros() {
    this.TareasFiltradas = this.Tareas.filter(t =>
      this.filtroEstatus === 0 || t.fk_id_estatus === this.filtroEstatus
    );
  }

  onNuevaTarea() {
    this.tareaEditando = { id_tarea: 0 };
    this.frmTarea = this.formBuilder.group({
      titulo:                    ['', [Validators.required, Validators.minLength(3)]],
      descripcion:               [''],
      fecha_limite:              [null],
      prioridad:                 [2, Validators.required],
      fk_id_usuario_responsable: [this.idUsuario, [Validators.required, Validators.min(1)]],
      recordatorio_activo:       [0],
      recordatorio_tiempo:       [null],
      modulo_vinculado:          [''],
      id_registro_vinculado:     [null],
    });
    this.mostrarDialogo = true;
  }

  onEditarTarea(tarea: any) {
    this.tareaEditando = { ...tarea };
    this.frmTarea = this.formBuilder.group({
      titulo:                    [tarea.titulo, [Validators.required, Validators.minLength(3)]],
      descripcion:               [tarea.descripcion || ''],
      fecha_limite:              [tarea.fecha_limite ? new Date(tarea.fecha_limite) : null],
      prioridad:                 [tarea.prioridad, Validators.required],
      fk_id_usuario_responsable: [parseInt(tarea.responsable_id), [Validators.required, Validators.min(1)]],
      recordatorio_activo:       [tarea.recordatorio_activo || 0],
      recordatorio_tiempo:       [tarea.recordatorio_tiempo || null],
      modulo_vinculado:          [tarea.modulo_vinculado || ''],
      id_registro_vinculado:     [tarea.id_registro_vinculado],
    });
    this.mostrarDialogo = true;
  }

  onGuardarTarea() {
    if (!this.frmTarea.valid) {
      this.frmTarea.markAllAsTouched();
      hlpSwal.Error('Revisa los campos requeridos.');
      return;
    }
    const valores = this.frmTarea.value;
    const data = new FormData();
    Object.keys(valores).forEach(k => {
      if (valores[k] !== null && valores[k] !== undefined && valores[k] !== '') {
        if (valores[k] instanceof Date) {
          data.append(k, hlpApp.formatDateToMySQL(valores[k]));
        } else {
          data.append(k, valores[k]);
        }
      }
    });

    hlpSwal.Pregunta({
      html: '¿Deseas guardar la tarea?',
      showLoaderOnConfirm: true,
      preConfirm: async () => {
        try {
          if (this.tareaEditando.id_tarea > 0) {
            return await this.tareasService.Actualizar(this.tareaEditando.id_tarea, data).toPromise();
          } else {
            return await this.tareasService.Insertar(data).toPromise();
          }
        } catch (e) {
          return hlpSwal.Error(e).then(() => ({ err: true }));
        }
      },
      allowOutsideClick: () => !hlpSwal.estaCargando,
    }).then((r: any) => {
      if (r.value && !r.value.err) {
        this.onActualizarInformacion();
        hlpSwal.ExitoToast(r.value.msg);
        this.mostrarDialogo = false;
      }
    });
  }

  onCambiarEstatus(tarea: any, nuevoEstatus: number) {
    const data = new FormData();
    data.append('fk_id_estatus', nuevoEstatus.toString());
    this.tareasService.CambiarEstatus(tarea.id_tarea, data).toPromise()
      .then((r: any) => {
        if (!r.err) {
          tarea.fk_id_estatus = nuevoEstatus;
          tarea.estatus_label = this.estatusOpciones.find(e => e.value === nuevoEstatus)?.label;
          this.aplicarFiltros();
          hlpSwal.ExitoToast(r.msg);
        }
      })
      .catch(async (e) => await hlpSwal.Error(e));
  }

  onEliminarTarea(id: number) {
    hlpSwal.Pregunta({
      html: '¿Deseas eliminar esta tarea?',
      showLoaderOnConfirm: true,
      preConfirm: async () => {
        try {
          return await this.tareasService.Eliminar(id).toPromise();
        } catch (e) {
          return hlpSwal.Error(e).then(() => ({ err: true }));
        }
      },
      allowOutsideClick: () => !hlpSwal.estaCargando,
    }).then((r: any) => {
      if (r.value && !r.value.err) {
        this.Tareas = this.Tareas.filter(t => t.id_tarea !== id);
        this.aplicarFiltros();
        hlpSwal.ExitoToast(r.value.msg);
      }
    });
  }

  onRecordatorioChange() {
    const tiempo = this.frmTarea.get('recordatorio_tiempo').value;
    this.frmTarea.get('recordatorio_activo').setValue(tiempo ? 1 : 0);
  }

  onCancelar() { this.mostrarDialogo = false; }

  getPrioridadClass(p: number): string {
    return p === 1 ? 'alta' : p === 2 ? 'media' : 'baja';
  }

  getEstatusClass(e: number): string {
    return e === 1 ? 'pendiente' : e === 2 ? 'en-proceso' : 'completada';
  }

  getModuloLabel(m: string): string {
    return this.modulosVinculados.find(x => x.value === m)?.label || '';
  }

  getModuloRuta(tarea: any): string {
    return tarea.modulo_vinculado ? '/' + tarea.modulo_vinculado : null;
  }

  getRecordatorioLabel(minutos: number): string {
    return this.recordatorioOpciones.find(r => r.value === minutos)?.label || '';
  }
}
