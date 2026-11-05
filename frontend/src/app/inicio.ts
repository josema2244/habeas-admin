import { Component, inject } from '@angular/core';
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
  `,
})
export class Inicio {
  auth = inject(AuthService);
}