import { Component, OnInit, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { ApiService, Opcion, UsuarioFila } from './api.service';
import { leerError } from './errores';

@Component({
  selector: 'app-usuarios',
  imports: [ReactiveFormsModule],
  template: `
    <section class="card">
      <div class="encabezado">
        <h2>Usuarios</h2>
        <button type="button" (click)="nuevo()">Nuevo usuario</button>
      </div>

      @if (mensaje()) { <p class="ok">{{ mensaje() }}</p> }

      <table>
        <thead>
          <tr>
            <th>Nombre</th><th>Correo</th><th>Cédula</th><th>Rol</th><th>Área</th><th>Estado</th><th></th>
          </tr>
        </thead>
        <tbody>
          @for (u of usuarios(); track u.id) {
            <tr>
              <td>{{ u.nombre }}</td>
              <td>{{ u.correo }}</td>
              <td>{{ u.cedula }}</td>
              <td>{{ u.rol }}</td>
              <td>{{ u.area }}</td>
              <td>
                @if (u.activo) { <span class="etiqueta">Activo</span> }
                @else { <span class="etiqueta off">Inactivo</span> }
              </td>
              <td class="acciones">
                <button type="button" class="secundario" (click)="editar(u)">Editar</button>
                <button type="button" class="secundario" (click)="alternar(u)">
                  {{ u.activo ? 'Desactivar' : 'Activar' }}
                </button>
              </td>
            </tr>
          }
        </tbody>
      </table>
    </section>

    @if (mostrarForm()) {
      <section class="card">
        <h3>{{ editandoId() ? 'Editar usuario' : 'Nuevo usuario' }}</h3>
        <form [formGroup]="form" (ngSubmit)="guardar()">
          <div class="grid">
            <label>
              Nombre
              <input formControlName="nombre" />
              @if (errores()['nombre']) { <span class="campo-error">{{ errores()['nombre'] }}</span> }
            </label>

            <label>
              Correo
              <input type="email" formControlName="correo" />
              @if (errores()['correo']) { <span class="campo-error">{{ errores()['correo'] }}</span> }
            </label>

            <label>
              Cédula
              <input formControlName="cedula" maxlength="10" inputmode="numeric" />
              @if (errores()['cedula']) { <span class="campo-error">{{ errores()['cedula'] }}</span> }
            </label>

            <label>
              Contraseña {{ editandoId() ? '(déjala vacía para no cambiarla)' : '' }}
              <input type="password" formControlName="password" autocomplete="new-password" />
              @if (errores()['password']) { <span class="campo-error">{{ errores()['password'] }}</span> }
            </label>

            <label>
              Rol
              <select formControlName="role_id">
                <option [ngValue]="null">Selecciona un rol</option>
                @for (r of roles(); track r.id) {
                  <option [ngValue]="r.id">{{ r.nombre }}</option>
                }
              </select>
              @if (errores()['role_id']) { <span class="campo-error">{{ errores()['role_id'] }}</span> }
            </label>

            <label>
              Área
              <select formControlName="area_id">
                <option [ngValue]="null">Selecciona un área</option>
                @for (a of areas(); track a.id) {
                  <option [ngValue]="a.id">{{ a.nombre }}</option>
                }
              </select>
              @if (errores()['area_id']) { <span class="campo-error">{{ errores()['area_id'] }}</span> }
            </label>
          </div>

          @if (error()) { <p class="error">{{ error() }}</p> }

          <div class="acciones">
            <button type="submit">Guardar</button>
            <button type="button" class="secundario" (click)="cancelar()">Cancelar</button>
          </div>
        </form>
      </section>
    }
  `,
})
export class Usuarios implements OnInit {
  private api = inject(ApiService);
  private fb = inject(FormBuilder);

  usuarios = signal<UsuarioFila[]>([]);
  roles = signal<Opcion[]>([]);
  areas = signal<Opcion[]>([]);
  mostrarForm = signal(false);
  editandoId = signal<number | null>(null);
  errores = signal<Record<string, string>>({});
  error = signal('');
  mensaje = signal('');

  // La cédula NO se valida aquí: la valida el servidor antes de guardar.
  form = this.fb.group({
    nombre: this.fb.nonNullable.control(''),
    correo: this.fb.nonNullable.control(''),
    cedula: this.fb.nonNullable.control(''),
    password: this.fb.nonNullable.control(''),
    role_id: this.fb.control<number | null>(null),
    area_id: this.fb.control<number | null>(null),
  });

  ngOnInit(): void {
    this.api.roles().subscribe((r) => this.roles.set(r));
    this.api.areas().subscribe((a) => this.areas.set(a));
    this.cargar();
  }

  cargar(): void {
    this.api.usuarios().subscribe((u) => this.usuarios.set(u));
  }

  private limpiarMensajes(): void {
    this.error.set('');
    this.errores.set({});
    this.mensaje.set('');
  }

  nuevo(): void {
    this.limpiarMensajes();
    this.form.reset({ nombre: '', correo: '', cedula: '', password: '', role_id: null, area_id: null });
    this.editandoId.set(null);
    this.mostrarForm.set(true);
  }

  editar(u: UsuarioFila): void {
    this.limpiarMensajes();
    // El detalle trae la cédula completa (el listado la muestra enmascarada).
    this.api.usuario(u.id).subscribe((d) => {
      this.form.reset({
        nombre: d.nombre,
        correo: d.correo,
        cedula: d.cedula ?? '',
        password: '',
        role_id: d.role_id,
        area_id: d.area_id,
      });
      this.editandoId.set(d.id);
      this.mostrarForm.set(true);
    });
  }

  cancelar(): void {
    this.mostrarForm.set(false);
    this.limpiarMensajes();
  }

  guardar(): void {
    this.error.set('');
    this.errores.set({});
    const datos = this.form.getRawValue();
    const id = this.editandoId();
    const peticion = id ? this.api.actualizarUsuario(id, datos) : this.api.crearUsuario(datos);

    peticion.subscribe({
      next: (r) => {
        this.mensaje.set(r.mensaje);
        this.mostrarForm.set(false);
        this.cargar();
      },
      error: (e) => {
        const r = leerError(e);
        this.error.set(r.mensaje);
        this.errores.set(r.campos);
      },
    });
  }

  alternar(u: UsuarioFila): void {
    this.limpiarMensajes();
    this.api.estadoUsuario(u.id).subscribe({
      next: () => this.cargar(),
      error: (e) => this.mensaje.set(''),
    });
  }
}