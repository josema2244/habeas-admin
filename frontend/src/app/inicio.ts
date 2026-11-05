import { Component, inject } from '@angular/core';
import { RouterLink } from '@angular/router';
import { AuthService } from './auth.service';

@Component({
  selector: 'app-inicio',
  template: `
    <section class="card">
      <h2>Hola, {{ auth.usuario()?.nombre }}</h2>
      <p>
        Desde aquí administras los datos que alimentan el core de riesgo: usuarios,
        trámites de tratamiento de datos y tarifas de riesgo.
      </p>
    </section>

    <div class="accesos">
      <a class="card acceso" routerLink="/admin/usuarios">
        <h3>Usuarios</h3>
        <p>Gestiona las cuentas y roles de acceso.</p>
      </a>
      <a class="card acceso" routerLink="/admin/tramites">
        <h3>Trámites</h3>
        <p>Administra los trámites de tratamiento de datos.</p>
      </a>
      <a class="card acceso" routerLink="/admin/tarifas">
        <h3>Tarifas</h3>
        <p>Configura las tarifas de riesgo.</p>
      </a>
    </div>
  `,
  imports: [RouterLink],
})
export class Inicio {
  auth = inject(AuthService);
}