import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from './auth.service';

@Component({
  selector: 'app-login',
  imports: [ReactiveFormsModule],
  template: `
    <main class="login">
      <form class="card" [formGroup]="form" (ngSubmit)="entrar()">
        <h1>Habeas</h1>
        <p class="sub">Administración</p>

        <label>
          Correo
          <input type="email" formControlName="correo" autocomplete="username" />
        </label>

        <label>
          Contraseña
          <input type="password" formControlName="password" autocomplete="current-password" />
        </label>

        @if (error()) {
          <p class="error">{{ error() }}</p>
        }

        <button type="submit" [disabled]="form.invalid || cargando()">
          {{ cargando() ? 'Entrando...' : 'Entrar' }}
        </button>
      </form>
    </main>
  `,
})
export class Login {
  private fb = inject(FormBuilder);
  private auth = inject(AuthService);
  private router = inject(Router);

  form = this.fb.nonNullable.group({
    correo: ['', [Validators.required, Validators.email]],
    password: ['', Validators.required],
  });
  error = signal('');
  cargando = signal(false);

  entrar(): void {
    if (this.form.invalid) {
      return;
    }
    this.error.set('');
    this.cargando.set(true);
    const { correo, password } = this.form.getRawValue();

    this.auth.login(correo, password).subscribe({
      next: (r) => {
        this.cargando.set(false);
        if (r.usuario.rol !== 'Administrador') {
          this.auth.logout();
          this.error.set('Este módulo es solo para administradores');
          return;
        }
        this.router.navigate(['/admin']);
      },
      error: (e) => {
        this.cargando.set(false);
        this.error.set(e.error?.error ?? 'No se pudo conectar con el servidor');
      },
    });
  }
}