const urlBackend = 'api/';
const APIKey = '2d4c640a-13ed-44fc-9fe3-964fc0b2757f';

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
