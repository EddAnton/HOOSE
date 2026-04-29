import { Component, OnInit, isDevMode } from '@angular/core';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';

import { environment } from '../../../environments/environment';
import * as hlpApp from '../../helpers/app-helper';
import * as hlpSwal from '../../helpers/sweetalert2-helper';
import * as hlpPrimeNGTable from '../../helpers/primeng-table-helper';

import { SesionUsuarioService } from '../../services/sesion-usuario.service';
import { TipoComiteModel } from 'src/app/models/comites.model';

@Component({
  selector: 'app-comites',
  templateUrl: './comites.component.html',
  styleUrls: ['./comites.component.css']
})
export class ComitesComponent implements OnInit {
  appData = environment;
  hlpApp = hlpApp;
  hlpPrimeNGTable = hlpPrimeNGTable;
  isDevelopment = isDevMode;
  expandedRows = {};

  // Tabla: Comités
  ComitesCols: any[] = [
    { textAlign: 'center', width: '40px' },
    { header: 'Tipo' },
  ];

  // Tabla Cuotas Mantenimiento
  // Columnas de la tabla
  MiembrosCols: any[] = [
    { header: 'Tipo' },
    { header: 'Nombre' },
    { header: 'Email' },
    { header: 'Teléfono' },
    { header: 'Fecha Inicio', width: '120px' },
    // Botones de acción
    { textAlign: 'center', width: '50px' },
  ];
  MiembrosFilter: any[] = ['tipo_miembro', 'usuario'];

  TiposComites: TipoComiteModel[] = [];

  constructor() { }

  ngOnInit(): void {
  }

}
