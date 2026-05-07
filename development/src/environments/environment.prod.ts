const urlBackend = 'http://api.residenciales.hoose.mx/index.php/';
const APIKey = 'b7a142358f5590b2a887caad198d1c2';

export const environment = {
  production: true,
  appLaunchYear: 2022,
  appKey: APIKey,
  appName: 'Hoose',
  appVersion: require('../../package.json').version,
  appTitle: 'Software de Gestión de Condominios',
  urlBackend: urlBackend,
  urlBackendUsuariosFiles: urlBackend + 'uploads/usuarios/',
  urlBackendCondominiosFiles: urlBackend + 'uploads/condominios/',
  urlBackendUnidadesFiles: urlBackend + 'uploads/unidades/',
  urlBackendMiembrosComiteFiles: urlBackend + 'uploads/miembros_comites_administracion/',
  urlBackendGastosMantenimientoFiles: urlBackend + 'uploads/gastos_mantenimiento/',
  urlBackendCloudFiles: urlBackend + 'uploads/cloud/',
  urlBackendImagesFiles: urlBackend + 'uploads/images/',
  urlBackendFondosMonetariosFiles: urlBackend + 'uploads/fondos_monetarios/',
  urlBackendProyectosFiles: urlBackend + 'uploads/proyectos/',
  urlBackendQuejasFiles: urlBackend + 'uploads/quejas/',
  phoneContact: '522281505214',
};
