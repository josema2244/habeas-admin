import { Injectable, computed, inject, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { tap } from 'rxjs';
import { API_URL } from './config';

export interface Usuario {
  id: number;
  nombre: string;
  correo: string;
  rol: string;
}

interface RespuestaLogin {
  token: string;
  usuario: Usuario;
}

@Injectable({ providedIn: 'root' })
export class AuthService {
  private http = inject(HttpClient);
  private router = inject(Router);

  private _usuario = signal<Usuario | null>(this.leerUsuario());
  readonly usuario = this._usuario.asReadonly();
  readonly esAdmin = computed(() => this._usuario()?.rol === 'Administrador');

  get token(): string | null {
    try {
      return localStorage.getItem('token');
    } catch {
      return null;
    }
  }

  login(correo: string, password: string) {
    return this.http
      .post<RespuestaLogin>(`${API_URL}/api/login`, { correo, password })
      .pipe(
        tap((r) => {
          try {
            localStorage.setItem('token', r.token);
            localStorage.setItem('usuario', JSON.stringify(r.usuario));
          } catch {
            /* sin almacenamiento: la sesión dura hasta recargar */
          }
          this._usuario.set(r.usuario);
        }),
      );
  }

  logout(): void {
    try {
      localStorage.removeItem('token');
      localStorage.removeItem('usuario');
    } catch {
      /* nada que limpiar */
    }
    this._usuario.set(null);
    this.router.navigate(['/login']);
  }

  private leerUsuario(): Usuario | null {
    try {
      const texto = localStorage.getItem('usuario');
      return texto ? (JSON.parse(texto) as Usuario) : null;
    } catch {
      return null;
    }
  }
}