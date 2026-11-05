import { Component, inject } from '@angular/core';
import { RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { AuthService } from './auth.service';

@Component({
  selector: 'app-shell',
  imports: [RouterOutlet, RouterLink, RouterLinkActive],
  template: `
    <header class="barra">
      <strong>Habeas · Admin</strong>
      <nav>
        <a routerLink="/admin" routerLinkActive="activo" [routerLinkActiveOptions]="{ exact: true }">Inicio</a>
        <a routerLink="/admin/usuarios" routerLinkActive="activo">Usuarios</a>
        <a routerLink="/admin/tramites" routerLinkActive="activo">Trámites</a>
        <a routerLink="/admin/tarifas" routerLinkActive="activo">Tarifas</a>
      </nav>
      <span class="usuario">
        {{ auth.usuario()?.nombre }}
        <button type="button" class="secundario" (click)="auth.logout()">Salir</button>
      </span>
    </header>
    <main class="contenido">
      <router-outlet />
    </main>
  `,
})
export class Shell {
  auth = inject(AuthService);
}