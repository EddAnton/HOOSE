export class TareaModel {
  id_tarea: number = 0;
  titulo: string = '';
  descripcion: string = '';
  fecha_limite: any = null;
  prioridad: number = 2;
  fk_id_usuario_responsable: number = 0;
  fk_id_estatus: number = 1;
  modulo_vinculado: string = '';
  id_registro_vinculado: number = null;
}
