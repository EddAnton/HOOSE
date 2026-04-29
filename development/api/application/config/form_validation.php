<?php
if (!defined('BASEPATH')) {
  exit('No direct script access allowed');
}

$config = [
  // Usuario - Iniciar sesión
  'usuarioIniciarSesion' => [
    [
      'field' => 'usuario',
      'label' => 'Usuario',
      'rules' => 'required|trim',
      // 'rules' => 'required|trim|emailValido',
    ],
    [
      'field' => 'contrasenia',
      'label' => 'Contraseña',
      'rules' => 'required',
    ],
  ],
  // Usuario - Cambiar contraseña
  'usuarioCambiarContrasenia' => [
    [
      'field' => 'contrasenia_actual',
      'label' => 'Contraseña actual',
      'rules' => 'required',
    ],
    [
      'field' => 'contrasenia_nueva',
      'label' => 'Nueva contraseña',
      'rules' => 'required',
    ],
  ],
  // Usuario - Cambiar condominio
  'usuarioCambiarCondominio' => [
    [
      'field' => 'id_condominio',
      'label' => 'Condominio',
      'rules' => 'required|is_natural_no_zero',
    ],
  ],

  // Usuario - Insertar
  // Regla de uso general para Administradores y Propietarios
  'usuarioInsertar' => [
    [
      'field' => 'usuario',
      'label' => 'Usuario',
      'rules' => 'required|trim|min_length[3]|max_length[25]',
    ],
    [
      'field' => 'contrasenia',
      'label' => 'Contraseña',
      'rules' => 'trim',
    ],
    [
      'field' => 'nombre',
      'label' => 'Nombre',
      'rules' => 'required|trim|min_length[3]|max_length[255]',
    ],
    [
      'field' => 'email',
      'label' => 'Correo electrónico',
      'rules' => 'required|trim|emailValido',
    ],
    [
      'field' => 'telefono',
      'label' => 'Teléfono',
      'rules' => 'required|trim|numeric|min_length[10]|max_length[14]',
    ],
    [
      'field' => 'domicilio',
      'label' => 'Domicilio',
      'rules' => 'trim|min_length[3]|max_length[255]',
    ],
    [
      'field' => 'identificacion_folio',
      'label' => 'Folio Identificación',
      'rules' => 'trim|min_length[3]|max_length[50]',
    ],
    [
      'field' => 'identificacion_domicilio',
      'label' => 'Domicilio Identificación',
      'rules' => 'trim|min_length[3]|max_length[255]',
    ],
  ],

  // Condominio - Insertar
  'condominioInsertar' => [
    [
      'field' => 'condominio',
      'label' => 'Condominio',
      'rules' => 'required|trim|min_length[3]|max_length[255]',
    ],
    [
      'field' => 'email',
      'label' => 'Correo electrónico',
      'rules' => 'trim|emailValido',
    ],
    [
      'field' => 'telefono',
      'label' => 'Teléfono',
      'rules' => 'trim|numeric|min_length[10]|max_length[14]',
    ],
    [
      'field' => 'domicilio',
      'label' => 'Domicilio',
      'rules' => 'required|trim|min_length[3]|max_length[255]',
    ],
    [
      'field' => 'telefono_guardia',
      'label' => 'Teléfono Guardia',
      'rules' => 'trim|numeric|min_length[10]|max_length[14]',
    ],
    [
      'field' => 'telefono_moderador',
      'label' => 'Teléfono Moderador',
      'rules' => 'trim|numeric|min_length[10]|max_length[14]',
    ],
    [
      'field' => 'telefono_secretaria',
      'label' => 'Teléfono Secretaria',
      'rules' => 'trim|numeric|min_length[10]|max_length[14]',
    ],
    [
      'field' => 'anio_construccion',
      'label' => 'Año construcción',
      'rules' => 'trim|numeric|exact_length[4]',
    ],
    [
      'field' => 'constructora',
      'label' => 'Constructora',
      'rules' => 'trim|min_length[3]|max_length[255]',
    ],
    [
      'field' => 'telefono_constructora',
      'label' => 'Teléfono Constructora',
      'rules' => 'trim|numeric|min_length[10]|max_length[14]',
    ],
    [
      'field' => 'domicilio_constructora',
      'label' => 'Domicilio Constructora',
      'rules' => 'trim|min_length[3]|max_length[255]',
    ],
  ],

  // Edificio - Insertar
  'edificioInsertar' => [
    [
      'field' => 'edificio',
      'label' => 'Edificio',
      'rules' => 'required|trim|min_length[3]|max_length[150]',
    ],
  ],

  // Unidad - Insertar
  'unidadInsertar' => [
    [
      'field' => 'unidad',
      'label' => 'Unidad',
      'rules' => 'required|trim|min_length[3]|max_length[150]',
    ],
    [
      'field' => 'id_edificio',
      'label' => 'Edificio',
      'rules' => 'required|is_natural_no_zero',
    ],
    [
      'field' => 'cuota_mantenimiento_ordinaria',
      'label' => 'Cuota mantenimiento ordinaria',
      'rules' => 'numeric|greater_than_equal_to[0]',
    ],
  ],

  // Tipo Miembro - Insertar
  'tipoMiembroInsertar' => [
    [
      'field' => 'tipo_miembro',
      'label' => 'Tipo de miembro',
      'rules' => 'required|trim|min_length[3]|max_length[50]',
    ],
    [
      'field' => 'es_colaborador',
      'label' => 'Es colaborador',
      'rules' => 'required|regex_match[/^[01]$/]',
    ],
  ],

  // Propietario - Insertar - Unidades
  'propietarioInsertarUnidades' => [
    [
      'field' => 'id_unidad',
      'label' => 'Unidad',
      'rules' => 'required|is_natural_no_zero',
    ],
  ],

  // Condomino
  // Condomino - Insertar
  'condominoInsertar' => [
    [
      'field' => 'id_unidad',
      'label' => 'Unidad',
      'rules' => 'required|is_natural_no_zero',
    ],
    [
      'field' => 'deposito',
      'label' => 'Depósito',
      'rules' => 'required|numeric|greater_than_equal_to[0]',
    ],
    [
      'field' => 'renta',
      'label' => 'Renta',
      'rules' => 'required|numeric|greater_than[0]',
    ],
    [
      'field' => 'fecha_inicio',
      'label' => 'Fecha de inicio',
      'rules' => 'required|fechaFormatoValido',
    ],
  ],
  // Condomino - Deshabilitar
  'condominoFinalizarContrato' => [
    [
      'field' => 'fecha_fin',
      'label' => 'Fecha final',
      'rules' => 'required|fechaFormatoValido',
    ],
  ],

  // Colaborador
  // Colaborador - Insertar
  'colaboradorInsertar' => [
    [
      'field' => 'id_tipo_miembro',
      'label' => 'Tipo miembro',
      'rules' => 'required|is_natural_no_zero',
    ],
    [
      'field' => 'fecha_inicio',
      'label' => 'Fecha de inicio',
      'rules' => 'required|fechaFormatoValido',
    ],
    [
      'field' => 'salario',
      'label' => 'Salario',
      'rules' => 'required|numeric|greater_than[0]',
    ],
  ],
  // Colaborador - Eliminar
  'colaboradorEliminar' => [
    [
      'field' => 'fecha_fin',
      'label' => 'Fecha final',
      'rules' => 'required|fechaFormatoValido',
    ],
  ],

  // Gasto Fijo - Insertar
  'gastoFijoInsertar' => [
    [
      'field' => 'gasto_fijo',
      'label' => 'Gasto Fijo',
      'rules' => 'required|trim|min_length[3]|max_length[50]',
    ],
  ],

  // Área común - Insertar
  'areaComunInsertar' => [
    [
      'field' => 'nombre',
      'label' => 'Nombre',
      'rules' => 'required|trim|min_length[3]|max_length[150]',
    ],
    [
      'field' => 'descripcion',
      'label' => 'Descripción',
      'rules' => 'trim|min_length[3]|max_length[1000]',
    ],
    [
      'field' => 'importe_hora',
      'label' => 'Importe por hora',
      'rules' => 'required|numeric|greater_than[0]',
    ],
  ],

  // Área común - Insertar Reservación
  'areaComunInsertarReservacion' => [
    [
      'field' => 'id_usuario',
      'label' => 'Persona reserva',
      'rules' => 'required|is_natural_no_zero',
    ],
    [
      'field' => 'fecha_inicio',
      'label' => 'Fecha inicio',
      'rules' => 'required|fechaHoraValidaNoMenorActual',
    ],
    [
      'field' => 'fecha_fin',
      'label' => 'Fecha fin',
      'rules' => 'required|fechaHoraValidaNoMenorActual',
    ],
    [
      'field' => 'importe_total',
      'label' => 'Importe total',
      'rules' => 'required|numeric|greater_than[0]',
    ],
  ],

  // Área común - Registrar pago Reservación
  /* 'areaComunRegistrarPagoReservacion' => [
     [
       'field' => 'fecha_pago',
       'label' => 'Fecha pago',
       'rules' => 'required|fechaValidaNoMayorActual',
     ],
   ], */

  // Recaudación
  // Recaudación - Insertar
  'recaudacionInsertar' => [
    [
      'field' => 'id_unidad',
      'label' => 'Unidad',
      'rules' => 'required|is_natural_no_zero',
    ],
    [
      'field' => 'id_perfil_usuario_paga',
      'label' => 'Perfil usuario pagará',
      'rules' => 'required|is_natural_no_zero',
    ],
    [
      'field' => 'id_usuario_paga',
      'label' => 'Usuario pagará',
      'rules' => 'required|is_natural_no_zero',
    ],
    [
      'field' => 'anio',
      'label' => 'Año',
      'rules' => 'required|is_natural_no_zero',
    ],
    [
      'field' => 'mes',
      'label' => 'Mes',
      'rules' => 'required|numeric|greater_than_equal_to[1]|less_than_equal_to[12]',
    ],
    [
      'field' => 'renta',
      'label' => 'Renta',
      'rules' => 'required|numeric|greater_than_equal_to[0]',
    ],
    [
      'field' => 'agua',
      'label' => 'Agua',
      'rules' => 'required|numeric|greater_than_equal_to[0]',
    ],
    [
      'field' => 'energia_electrica',
      'label' => 'Energía eléctrica',
      'rules' => 'required|numeric|greater_than_equal_to[0]',
    ],
    [
      'field' => 'gas',
      'label' => 'Gas',
      'rules' => 'required|numeric|greater_than_equal_to[0]',
    ],
    [
      'field' => 'seguridad',
      'label' => 'Seguridad',
      'rules' => 'required|numeric|greater_than_equal_to[0]',
    ],
    [
      'field' => 'servicios_publicos',
      'label' => 'Servicios públicos',
      'rules' => 'required|numeric|greater_than_equal_to[0]',
    ],
    [
      'field' => 'otros_servicios',
      'label' => 'Otros servicios',
      'rules' => 'required|numeric|greater_than_equal_to[0]',
    ],
    [
      'field' => 'fecha_limite_pago',
      'label' => 'Fecha límite pago',
      'rules' => 'required|fechaFormatoValido',
    ],
    [
      'field' => 'id_estatus_recaudacion',
      'label' => 'Estatus recaudación',
      'rules' => 'required|is_natural_no_zero',
    ],
    [
      'field' => 'fecha_pago',
      'label' => 'Fecha pago',
      'rules' => 'fechaFormatoValido',
    ],
    [
      'field' => 'numero_referencia',
      'label' => 'Número referencia',
      'rules' => 'trim',
    ],
    [
      'field' => 'notas',
      'label' => 'Notas',
      'rules' => 'trim|max_length[255]',
    ],
  ],
  // Recaudación - Registrar pago
  'recaudacionRegistrarPago' => [
    [
      'field' => 'fecha_pago',
      'label' => 'Fecha de pago',
      'rules' => 'required|fechaFormatoValido',
    ],
    [
      'field' => 'id_forma_pago',
      'label' => 'Forma de pago',
      'rules' => 'required|is_natural_no_zero',
    ],
    [
      'field' => 'numero_referencia',
      'label' => 'Número referencia',
      'rules' => 'trim',
    ],
  ],

  // Nómina - Registrar
  'nominaRegistrar' => [
    [
      'field' => 'id_colaborador',
      'label' => 'Colaborador',
      'rules' => 'required|is_natural_no_zero',
    ],
    [
      'field' => 'anio',
      'label' => 'Año',
      'rules' => 'required|is_natural_no_zero',
    ],
    [
      'field' => 'mes',
      'label' => 'Mes',
      'rules' => 'required|numeric|greater_than_equal_to[1]|less_than_equal_to[12]',
    ],
    [
      'field' => 'importe',
      'label' => 'Importe',
      'rules' => 'required|numeric|greater_than[0]',
    ],
    [
      'field' => 'fecha_pago',
      'label' => 'Fecha pago',
      'rules' => 'fechaFormatoValido',
    ],
  ],

  // Gasto mantenimiento
  // Gasto mantenimiento - Insertar
  'gastoMantenimientoInsertar' => [
    [
      'field' => 'id_gasto_fijo',
      'label' => 'Gasto fijo',
      'rules' => 'required|greater_than_equal_to[0]',
    ],
    [
      'field' => 'concepto',
      'label' => 'Concepto',
      'rules' => 'trim|min_length[3]|max_length[150]',
    ],
    [
      'field' => 'descripcion',
      'label' => 'Descripción',
      'rules' => 'trim|min_length[3]|max_length[1000]',
    ],
    [
      'field' => 'importe',
      'label' => 'Importe',
      'rules' => 'required|numeric|greater_than[0]',
    ],
    [
      'field' => 'fecha',
      'label' => 'Fecha',
      'rules' => 'required|fechaFormatoValido',
    ],
    [
      'field' => 'es_deducible',
      'label' => 'Es deducible',
      'rules' => 'required|regex_match[/^[01]$/]',
    ],
  ],

  // Cuota mantenimiento
  // Cuota mantenimiento - Insertar
  'cuotaMantenimientoInsertar' => [
    [
      'field' => 'id_unidad',
      'label' => 'Unidad',
      'rules' => 'required|is_natural_no_zero',
    ],
    [
      'field' => 'id_perfil_usuario_paga',
      'label' => 'Perfil usuario pagará',
      'rules' => 'required|is_natural_no_zero',
    ],
    [
      'field' => 'id_usuario_paga',
      'label' => 'Usuario pagará',
      'rules' => 'required|is_natural_no_zero',
    ],
    [
      'field' => 'anio',
      'label' => 'Año',
      'rules' => 'required|is_natural_no_zero',
    ],
    [
      'field' => 'mes',
      'label' => 'Mes',
      'rules' => 'required|numeric|greater_than_equal_to[1]|less_than_equal_to[12]',
    ],
    [
      'field' => 'ordinaria',
      'label' => 'Cuota ordinaria',
      'rules' => 'required|numeric|greater_than_equal_to[0]',
    ],
    [
      'field' => 'extraordinaria',
      'label' => 'Cuota extra ordinaria',
      'rules' => 'required|numeric|greater_than_equal_to[0]',
    ],
    [
      'field' => 'otros_servicios',
      'label' => 'Otros servicios',
      'rules' => 'required|numeric|greater_than_equal_to[0]',
    ],
    [
      'field' => 'descuento_pronto_pago',
      'label' => 'Descuento pronto pago',
      'rules' => 'required|numeric|greater_than_equal_to[0]',
    ],
    [
      'field' => 'fecha_limite_pago',
      'label' => 'Fecha límite pago',
      'rules' => 'required|fechaFormatoValido',
    ],
    [
      'field' => 'importe',
      'label' => 'Importe',
      'rules' => 'required|numeric|greater_than_equal_to[0]',
    ],
  ],

  // Cuota mantenimiento - Registrar Pago
  'cuotaMantenimientoRegistrarPago' => [
    [
      'field' => 'importe',
      'label' => 'Importe',
      'rules' => 'required|numeric|greater_than[0]',
    ],
    [
      'field' => 'fecha_pago',
      'label' => 'Fecha pago',
      'rules' => 'required|fechaFormatoValido',
    ],
    [
      'field' => 'id_forma_pago',
      'label' => 'Forma de pago',
      'rules' => 'required|is_natural_no_zero',
    ],
    [
      'field' => 'numero_referencia',
      'label' => 'Número referencia',
      'rules' => 'trim',
    ],
  ],

  // Cuota mantenimiento - Generar masivamente
  'generarCuotasMantenimiento' => [
    [
      'field' => 'anio',
      'label' => 'Año',
      'rules' => 'required|is_natural_no_zero',
    ],
    [
      'field' => 'mes',
      'label' => 'Mes',
      'rules' => 'required|numeric|greater_than_equal_to[1]|less_than_equal_to[12]',
    ],
  ],

  // Fondo monetario
  // Fondo monetario - Insertar
  'fondoMonetarioInsertar' => [
    [
      'field' => 'id_tipo_fondo_monetario',
      'label' => 'Tipo fondo monetario',
      'rules' => 'required|is_natural_no_zero',
    ],
    [
      'field' => 'fondo_monetario',
      'label' => 'Nombre',
      'rules' => 'required|trim|min_length[3]|max_length[50]',
    ],
    [
      'field' => 'requiere_datos_bancarios',
      'label' => 'Requiere datos bancarios',
      'rules' => 'required|regex_match[/^[01]$/]',
    ],
    [
      'field' => 'saldo',
      'label' => 'Saldo',
      'rules' => 'required|numeric|greater_than_equal_to[0]',
    ],
  ],
  // Fondo monetario - Insertar datos bancarios
  'fondoMonetarioInsertarDatosBancarios' => [
    [
      'field' => 'banco',
      'label' => 'Institución bancaria',
      'rules' => 'required|trim|min_length[3]|max_length[100]',
    ],
    [
      'field' => 'numero_cuenta',
      'label' => 'Número de cuenta',
      'rules' => 'required|trim|min_length[3]|max_length[50]',
    ],
    [
      'field' => 'clabe',
      'label' => 'CLABE',
      'rules' => 'trim|min_length[3]|max_length[50]',
    ],
  ],

  // Fondo Monetario - Traspaso
  'fondoMonetarioTraspaso' => [
    [
      'field' => 'id_fondo_monetario_destino',
      'label' => 'Fondo monetario destino',
      'rules' => 'required|is_natural_no_zero',
    ],
    [
      'field' => 'fecha',
      'label' => 'Fecha',
      'rules' => 'required|fechaFormatoValido',
    ],
    [
      'field' => 'importe',
      'label' => 'Importe',
      'rules' => 'required|numeric|greater_than[0]',
    ],
  ],
  // Fondo Monetario - Registrar movimiento
  'fondoMonetarioRegistrarMovto' => [
    [
      'field' => 'id_tipo_movimiento',
      'label' => 'Tipo movimiento',
      'rules' => 'required|is_natural_no_zero',
    ],
    [
      'field' => 'fecha',
      'label' => 'Fecha',
      'rules' => 'required|fechaFormatoValido',
    ],
    [
      'field' => 'concepto',
      'label' => 'Concepto',
      'rules' => 'required|trim|min_length[3]|max_length[150]',
    ],
    [
      'field' => 'importe',
      'label' => 'Importe',
      'rules' => 'required|numeric|greater_than[0]',
    ],
  ],
  // Fondo Monetario - Registrar movimiento externo
  'fondoMonetarioRegistrarMovtoExterno' => [
    [
      'field' => 'fk_id_fondo_monetario',
      'label' => 'Fondo monetario',
      'rules' => 'required|is_natural_no_zero',
    ],
    [
      'field' => 'fecha',
      'label' => 'Fecha',
      'rules' => 'required|fechaFormatoValido',
    ],
    [
      'field' => 'importe',
      'label' => 'Importe',
      'rules' => 'required|numeric|greater_than[0]',
    ],
  ],

  // Miembro Comité Administración - Insertar
  'miembroComiteAdmonInsertar' => [
    [
      'field' => 'nombre',
      'label' => 'Nombre',
      'rules' => 'required|trim|min_length[3]|max_length[255]',
    ],
    [
      'field' => 'email',
      'label' => 'Correo electrónico',
      'rules' => 'required|trim|emailValido',
    ],
    [
      'field' => 'telefono',
      'label' => 'Teléfono',
      'rules' => 'required|trim|numeric|min_length[10]|max_length[14]',
    ],
    [
      'field' => 'domicilio',
      'label' => 'Domicilio',
      'rules' => 'trim|min_length[3]|max_length[255]',
    ],
    [
      'field' => 'identificacion_folio',
      'label' => 'Folio Identificación',
      'rules' => 'trim|min_length[3]|max_length[50]',
    ],
    [
      'field' => 'identificacion_domicilio',
      'label' => 'Domicilio Identificación',
      'rules' => 'trim|min_length[3]|max_length[255]',
    ],
    [
      'field' => 'id_tipo_miembro',
      'label' => 'Tipo miembro',
      'rules' => 'required|is_natural_no_zero',
    ],
    [
      'field' => 'fecha_inicio',
      'label' => 'Fecha de inicio',
      'rules' => 'required|fechaFormatoValido',
    ],
  ],

  // Asamblea - Insertar
  'asambleaInsertar' => [
    [
      'field' => 'id_tipo_asamblea',
      'label' => 'Tipo asamblea',
      'rules' => 'required|is_natural_no_zero',
    ],
    [
      'field' => 'fecha_hora',
      'label' => 'Fecha y hora',
      'rules' => 'required|fechaHoraValidaNoMenorActual',
    ],
    [
      'field' => 'lugar',
      'label' => 'Lugar',
      'rules' => 'required|trim|min_length[3]|max_length[150]',
    ],
    [
      'field' => 'fundamento_legal',
      'label' => 'Fundamento legal',
      'rules' => 'required',
    ],
    [
      'field' => 'convocatoria_cierre',
      'label' => 'Cierre convocatoria',
      'rules' => 'required',
    ],
    [
      'field' => 'convocatoria_fecha',
      'label' => 'Fecha convocatoria',
      'rules' => 'required|fechaValidaNoMenorActual',
    ],
    [
      'field' => 'convocatoria_ciudad',
      'label' => 'Ciudad convocatoria',
      'rules' => 'required|trim|min_length[3]|max_length[150]',
    ],
    [
      'field' => 'convocatoria_quien_emite',
      'label' => 'Quién emite convocatoria',
      'rules' => 'required|trim|min_length[3]|max_length[150]',
    ],
  ],

  // Asamblea Orden día - Insertar
  'asambleaOrdenDiaInsertar' => [
    [
      'field' => 'orden_dia',
      'label' => 'Punto',
      'rules' => 'required|trim|min_length[3]|max_length[150]',
    ],
    [
      'field' => 'requiere_votacion',
      'label' => 'Requiere votación',
      'rules' => 'regex_match[/^[01]$/]',
    ],
  ],

  // Asamblea - Guardar
  'asambleaActaGuardar' => [
    [
      'field' => 'fecha_hora',
      'label' => 'Fecha y hora',
      'rules' => 'required|fechaHoraValidaNoMenorActual',
    ],

    [
      'field' => 'lugar',
      'label' => 'Lugar',
      'rules' => 'required|trim|min_length[3]|max_length[150]',
    ],
    [
      'field' => 'apertura',
      'label' => 'Apertura',
      'rules' => 'required',
    ],
    [
      'field' => 'cierre',
      'label' => 'Cierre',
      'rules' => 'required',
    ],
    [
      'field' => 'quien_emite',
      'label' => 'Quién emite',
      'rules' => 'required|trim|min_length[3]|max_length[150]',
    ],
    [
      'field' => 'finalizada',
      'label' => 'Finalizada',
      'rules' => 'regex_match[/^[01]$/]',
    ],
  ],

  // Asamblea Acta Pase lista - Guardar
  'asambleaActaPListaGuardar' => [
    [
      'field' => 'id_usuario',
      'label' => 'Propietario o Condómino',
      'rules' => 'required|is_natural_no_zero',
    ],
    [
      'field' => 'asistencia',
      'label' => 'Asistencia',
      'rules' => 'required|regex_match[/^[01]$/]',
    ],
  ],

  // Asamblea Acta Pase lista - Guardar
  'asambleaActaOrdenDiaGuardar' => [
    [
      'field' => 'id_asamblea_orden_dia',
      'label' => 'Punto del Orden del día',
      'rules' => 'required|is_natural_no_zero',
    ],
    [
      'field' => 'apertura',
      'label' => 'Apertura',
      'rules' => 'required',
    ],
  ],

  // Asamblea Acta Votación Punto Orden día - Guardar
  'asambleaActaVotacionODGuardar' => [
    [
      'field' => 'id_asamblea_orden_dia',
      'label' => 'Punto del Orden del día',
      'rules' => 'required|is_natural_no_zero',
    ],
    [
      'field' => 'id_usuario',
      'label' => 'Propietario o Condómino',
      'rules' => 'required|is_natural_no_zero',
    ],
    [
      'field' => 'votacion',
      'label' => 'Votación',
      'rules' => 'required|regex_match[/^[0123]$/]',
    ],
  ],

  // Visita - Insertar
  'visitaInsertar' => [
    [
      'field' => 'visitante',
      'label' => 'Visitante',
      'rules' => 'required|trim|min_length[3]|max_length[255]',
    ],
    [
      'field' => 'telefono',
      'label' => 'Teléfono',
      'rules' => 'trim|numeric|min_length[10]|max_length[14]',
    ],
    [
      'field' => 'domicilio',
      'label' => 'Domicilio',
      'rules' => 'trim|min_length[3]|max_length[255]',
    ],
    [
      'field' => 'identificacion_folio',
      'label' => 'Folio Identificación',
      'rules' => 'trim|min_length[3]|max_length[50]',
    ],
    [
      'field' => 'id_unidad',
      'label' => 'Unidad',
      'rules' => 'required|is_natural_no_zero',
    ],
    [
      'field' => 'fecha_hora_entrada',
      'label' => 'Fecha y hora de entrada',
      'rules' => 'required|fechaFormatoValido',
    ],
  ],

  // Visita - RegistrarSalida
  'visitaRegistrarSalida' => [
    [
      'field' => 'fecha_hora_salida',
      'label' => 'Fecha y hora de salida',
      'rules' => 'required|fechaFormatoValido',
    ],
  ],

  // Proyecto - Insertar
  'proyectoInsertar' => [
    [
      'field' => 'titulo',
      'label' => 'Título',
      'rules' => 'required|trim|min_length[3]|max_length[150]',
    ],
    [
      'field' => 'descripcion',
      'label' => 'Descripción',
      'rules' => 'trim|min_length[3]|max_length[1000]',
    ],
    [
      'field' => 'presupuesto',
      'label' => 'Presupuesto',
      'rules' => 'required|numeric|greater_than_equal_to[0]',
    ],
    [
      'field' => 'fecha_inicio',
      'label' => 'Fecha inicio',
      'rules' => 'required|fechaFormatoValido',
    ],
    [
      'field' => 'fecha_fin',
      'label' => 'Fecha fin',
      'rules' => 'required|fechaFormatoValido',
    ],
    [
      'field' => 'porcentaje_avance',
      'label' => 'Porcentaje de avance',
      'rules' => 'integer|greater_than_equal_to[0]|less_than_equal_to[100]',
    ],
  ],

  // Aviso - Insertar
  'avisoInsertar' => [
    [
      'field' => 'titulo',
      'label' => 'Título',
      'rules' => 'required|trim|min_length[3]|max_length[150]',
    ],
    [
      'field' => 'descripcion',
      'label' => 'Descripción',
      'rules' => 'required',
    ],
    [
      'field' => 'id_perfil_usuario_destino',
      'label' => 'Destinatario',
      'rules' => 'required|is_natural_no_zero',
    ],
    [
      'field' => 'publicado',
      'label' => 'Publicado',
      'rules' => 'required|regex_match[/^[01]$/]',
    ],
  ],

  // Notificación - Insertar
  'notificacionInsertar' => [
    [
      'field' => 'asunto',
      'label' => 'Asunto',
      'rules' => 'required|trim|min_length[3]|max_length[150]',
    ],
    [
      'field' => 'mensaje',
      'label' => 'Mensaje',
      'rules' => 'required',
    ],
    [
      'field' => 'destinatarios[]',
      'label' => 'Destinatarios',
      'rules' => 'required|tamanioMinimoArreglo[1]',
    ],
  ],

  // Queja - Insertar
  'quejaInsertar' => [
    [
      'field' => 'titulo',
      'label' => 'Título',
      'rules' => 'required|trim|min_length[3]|max_length[150]',
    ],
    [
      'field' => 'descripcion',
      'label' => 'Descripción',
      'rules' => 'required|trim|min_length[3]|max_length[1000]',
    ],
  ],

  // Queja - Seguimiento - Insertar
  'quejaSeguimientoInsertar' => [
    [
      'field' => 'fecha',
      'label' => 'Fecha',
      'rules' => 'required|fechaHoraValidaNoMayorActual',
    ],
    [
      'field' => 'seguimiento',
      'label' => 'Seguimiento',
      'rules' => 'required|trim|min_length[3]|max_length[1000]',
    ],
  ],

  // Queja - Asignar colaborador
  'quejaAsignarColaborador' => [
    [
      'field' => 'id_usuario_asignado',
      'label' => 'Colaborador asignado',
      'rules' => 'required|is_natural_no_zero',
    ],
  ],
  // Queja - Actualizar estatus
  'quejaActualizarEstatus' => [
    [
      'field' => 'id_estatus_queja',
      'label' => 'Estatus',
      'rules' => 'required|is_natural_no_zero',
    ],
  ],

  // Cloud - Carpeta Insertar
  'cloudCarpetaInsertar' => [
    [
      'field' => 'nombre',
      'label' => 'Nombre',
      'rules' => 'required|trim|min_length[1]|max_length[255]',
    ],
  ],

  // Cloud - Archivo Renombrar
  'cloudArchivoRenombrar' => [
    [
      'field' => 'nombre',
      'label' => 'Nombre',
      'rules' => 'required|trim|min_length[1]|max_length[255]',
    ],
  ],
];

?>