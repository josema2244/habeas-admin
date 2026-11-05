import { Routes } from '@angular/router';
import { Login } from './login';
import { Shell } from './shell';
import { Inicio } from './inicio';
import { Usuarios } from './usuarios';
import { Tramites } from './tramites';
import { Tarifas } from './tarifas';
import { adminGuard } from './auth.guard';

export const routes: Routes = [
  { path: 'login', component: Login },
  {
    path: 'admin',
    component: Shell,
    canActivate: [adminGuard],
    children: [
      { path: '', component: Inicio },
      { path: 'usuarios', component: Usuarios },
      { path: 'tramites', component: Tramites },
      { path: 'tarifas', component: Tarifas },
    ],
  },
  { path: '**', redirectTo: 'admin' },
];