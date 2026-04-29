const urlBackend = 'http://localhost:5001/';
// const APIKey = '2d4c640a-13ed-44fc-9fe3-964fc0b2757f';
const APIKey = 'b7a142358f5590b2a887caad198d1c2';
// APIKey app.hoose.mx
// const APIKey = 'b7a142358f5590b2a887caad198d1c21';
// APIKey arboleda.hoose.mx
// const APIKey = '58be209d5462df9d9e9f99cf634c89ce';

export const environment = {
  production: false,
  appLaunchYear: 2022,
  appKey: APIKey,
  appName: 'Hoose',
  appVersion: require('../../package.json').version + '-dev',
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

/*
 * For easier debugging in development mode, you can import the following file
 * to ignore zone related error stack frames such as `zone.run`, `zoneDelegate.invokeTask`.
 *
 * This import should be commented out in production mode because it will have a negative impact
 * on performance if an error is thrown.
 */
// import 'zone.js/dist/zone-error';  // Included with Angular CLI.
