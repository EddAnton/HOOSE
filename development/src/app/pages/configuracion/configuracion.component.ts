import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup } from '@angular/forms';
import * as hlpSwal from '../../helpers/sweetalert2-helper';
import { ConfiguracionService } from '../../services/configuracion.service';
import { environment } from '../../../environments/environment';

@Component({
  selector: 'app-configuracion',
  templateUrl: './configuracion.component.html',
  styleUrls: ['./configuracion.component.css']
})
export class ConfiguracionComponent implements OnInit {

  frmConfig: FormGroup;

  claves = [
    { clave: 'convocatoria_fundamento_legal', label: 'Fundamento Legal', seccion: 'Convocatoria' },
    { clave: 'convocatoria_disposiciones_generales', label: 'Disposiciones Generales', seccion: 'Convocatoria' },
    { clave: 'convocatoria_cierre', label: 'Cierre de Convocatoria', seccion: 'Convocatoria' },
    { clave: 'convocatoria_orden_dia_intro', label: 'Introducción Orden del Día', seccion: 'Convocatoria' },
    { clave: 'acta_apertura', label: 'Apertura del Acta', seccion: 'Acta' },
    { clave: 'orden_dia_pase_lista_apertura', label: 'Orden del Día - Pase de Lista (Quórum)', seccion: 'Acta' },
    { clave: 'orden_dia_minuta_apertura', label: 'Orden del Día - Lectura de Minuta', seccion: 'Acta' },
    { clave: 'acta_cierre', label: 'Cierre del Acta', seccion: 'Acta' },
  ];

  secciones = ['Convocatoria', 'Acta'];

  variablesConvocatoria = [
    { variable: '{{nombre_condominio}}', descripcion: 'Nombre del condominio' },
    { variable: '{{domicilio_condominio}}', descripcion: 'Dirección del condominio' },
    { variable: '{{total_asistentes}}', descripcion: 'Total de asistentes' },
    { variable: '{{unidades}}', descripcion: 'Total de unidades del condominio' },
    { variable: '{{porcentaje_quorum}}', descripcion: 'Porcentaje de quórum' },
    { variable: '{{tipo_convocatoria}}', descripcion: 'Primera o Segunda Convocatoria' },
    { variable: '{{domicilio_condominio}}', descripcion: 'Dirección del condominio' },
    { variable: '{{total_asistentes}}', descripcion: 'Total de asistentes' },
    { variable: '{{porcentaje_quorum}}', descripcion: 'Porcentaje de quórum' },
    { variable: '{{tipo_convocatoria}}', descripcion: 'Primera o Segunda Convocatoria' },
    { variable: '{{tipo_asamblea}}', descripcion: 'Tipo (Ordinaria/Extraordinaria)' },
    { variable: '{{fecha_asamblea}}', descripcion: 'Fecha de la asamblea' },
    { variable: '{{hora_primera_convocatoria}}', descripcion: 'Hora 1ª convocatoria' },
    { variable: '{{hora_segunda_convocatoria}}', descripcion: 'Hora 2ª convocatoria' },
    { variable: '{{lugar}}', descripcion: 'Lugar de celebración' },
    { variable: '{{ciudad_convocatoria}}', descripcion: 'Ciudad de expedición' },
    { variable: '{{fecha_convocatoria}}', descripcion: 'Fecha de expedición' },
    { variable: '{{quien_convoca}}', descripcion: 'Quién convoca' },
  ];

  variablesActa = [
    { variable: '{{nombre_condominio}}', descripcion: 'Nombre del condominio' },
    { variable: '{{domicilio_condominio}}', descripcion: 'Dirección del condominio' },
    { variable: '{{total_asistentes}}', descripcion: 'Total de asistentes' },
    { variable: '{{porcentaje_quorum}}', descripcion: 'Porcentaje de quórum' },
    { variable: '{{tipo_convocatoria}}', descripcion: 'Primera o Segunda Convocatoria' },
    { variable: '{{domicilio_condominio}}', descripcion: 'Dirección del condominio' },
    { variable: '{{total_asistentes}}', descripcion: 'Total de asistentes' },
    { variable: '{{porcentaje_quorum}}', descripcion: 'Porcentaje de quórum' },
    { variable: '{{tipo_convocatoria}}', descripcion: 'Primera o Segunda Convocatoria' },
    { variable: '{{tipo_asamblea}}', descripcion: 'Tipo (Ordinaria/Extraordinaria)' },
    { variable: '{{tipo_convocatoria}}', descripcion: 'Primera/Segunda convocatoria' },
    { variable: '{{fecha_asamblea}}', descripcion: 'Fecha de la asamblea' },
    { variable: '{{hora_asamblea}}', descripcion: 'Hora de inicio' },
    { variable: '{{lugar}}', descripcion: 'Lugar de celebración' },
    { variable: '{{presidente_asamblea}}', descripcion: 'Presidente de la asamblea' },
    { variable: '{{secretario_asamblea}}', descripcion: 'Secretario de la asamblea' },
    { variable: '{{escrutadores}}', descripcion: 'Escrutadores' },
    { variable: '{{porcentaje_quorum}}', descripcion: '% de indivisos presentes' },
    { variable: '{{total_asistentes}}', descripcion: 'Total de asistentes' },
  ];

  editorActivo: string = null;
  quillEditors: any = {};

  // Imágenes
  srcLogo: string = null;
  srcLogoDashboard: string = null;
  srcFondo: string = null;
  urlImages = environment.urlBackendImagesFiles;

  constructor(
    private fb: FormBuilder,
    private configuracionService: ConfiguracionService,
  ) {}

  ngOnInit() { this.cargarConfig(); }

  async cargarConfig() {
    hlpSwal.Cargando();
    try {
      const r: any = await this.configuracionService.Listar().toPromise();

      // Cargar imágenes por separado para no afectar los textos
      try {
        const imgs: any = await this.configuracionService.ListarImagenes().toPromise();
        console.log('Imágenes:', imgs);
        const imagenes = imgs['data'] || [];
        console.log('imagenes array:', imagenes);
        const logo = imagenes.find((i: any) => i.opcion === 'login_logo');
        console.log('logo encontrado:', logo);
        const logoDash = imagenes.find((i: any) => i.opcion === 'logo_dashboard');
        const fondo = imagenes.find((i: any) => i.opcion === 'login_background');
        this.srcLogo = logo ? this.urlImages + logo.valor : null;
        this.srcLogoDashboard = logoDash ? this.urlImages + logoDash.valor : null;
        this.srcFondo = fondo ? this.urlImages + fondo.valor : null;
        console.log('srcLogo:', this.srcLogo);
        console.log('urlImages:', this.urlImages);
      } catch(e) { console.error('Error cargando imágenes:', e); }
      const config = r['config'] || {};
      const controls: any = {};
      this.claves.forEach(c => { controls[c.clave] = [config[c.clave] || '']; });
      this.frmConfig = this.fb.group(controls);
    } catch(e) { await hlpSwal.Error(e); }
    finally { hlpSwal.Cerrar(); }
  }

  clavesSeccion(seccion: string) {
    return this.claves.filter(c => c.seccion === seccion);
  }

  variablesSeccion(seccion: string) {
    return seccion === 'Convocatoria' ? this.variablesConvocatoria : this.variablesActa;
  }

  onEditorInit(editor: any, clave: string) {
    this.quillEditors[clave] = editor;
  }

  onEditorFocus(clave: string) {
    this.editorActivo = clave;
  }

  insertarVariable(variable: string) {
    if (!this.editorActivo) {
      hlpSwal.Advertencia('Haz clic primero en el editor donde deseas insertar la variable.');
      return;
    }
    const editor = this.quillEditors[this.editorActivo];
    if (!editor) return;
    const range = editor.getSelection(true);
    editor.insertText(range ? range.index : editor.getLength(), variable, 'user');
  }

  async onSubirImagen(event: any, opcion: string, carpeta: string) {
    const file = event.target.files[0];
    if (!file) return;
    const r = await hlpSwal.Pregunta('¿Deseas actualizar esta imagen?');
    if (!r.isConfirmed) return;
    hlpSwal.Cargando();
    try {
      const res: any = await this.configuracionService.GuardarImagen(opcion, carpeta, file).toPromise();
      await hlpSwal.Exito('Imagen actualizada correctamente.');
      await this.cargarConfig();
    } catch(e) { await hlpSwal.Error(e); }
  }

  async onGuardar() {
    const r = await hlpSwal.Pregunta('¿Guardar configuración?');
    if (!r.isConfirmed) return;
    hlpSwal.Cargando();
    try {
      await this.configuracionService.Guardar(this.frmConfig.value).toPromise();
      await hlpSwal.Exito('Configuración guardada correctamente.');
    } catch(e) { await hlpSwal.Error(e); }
  }
}
