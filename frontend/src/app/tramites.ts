import { Component, OnInit, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { ApiService, Categoria, Opcion, TramiteFila } from './api.service';
import { leerError } from './errores';

@Component({
  selector: 'app-tramites',
  imports: [ReactiveFormsModule],
  template: `
    <section class="card">
      <div class="encabezado">
        <h2>Trámites de tratamiento de datos</h2>
        <button type="button" (click)="nuevo()">Nuevo trámite</button>
      </div>

      @if (mensaje()) { <p class="ok">{{ mensaje() }}</p> }

      <table>
        <thead>
          <tr>
            <th>Nombre</th><th>Categoría</th><th>Área</th><th>Responsable</th><th>Estado</th><th></th>
          </tr>
        </thead>
        <tbody>
          @for (t of tramites(); track t.id) {
            <tr>
              <td>{{ t.nombre }}</td>
              <td>{{ t.categoria_datos }}</td>
              <td>{{ t.area }}</td>
              <td>{{ t.responsable }}</td>
              <td>
                @if (t.activa) { <span class="etiqueta">Activo</span> }
                @else { <span class="etiqueta off">Inactivo</span> }
              </td>
              <td class="acciones">
                <button type="button" class="secundario" (click)="editar(t)">Editar</button>
                <button type="button" class="secundario" (click)="alternar(t)">
                  {{ t.activa ? 'Desactivar' : 'Activar' }}
                </button>
              </td>
            </tr>
          }
        </tbody>
      </table>
    </section>

    @if (mostrarForm()) {
      <section class="card">
        <h3>{{ editandoId() ? 'Editar trámite' : 'Nuevo trámite' }}</h3>
        <form [formGroup]="form" (ngSubmit)="guardar()">
          <div class="grid">
            <label>
              Nombre
              <input formControlName="nombre" />
              @if (errores()['nombre']) { <span class="campo-error">{{ errores()['nombre'] }}</span> }
            </label>

            <label>
              Categoría de datos
              <select formControlName="categoria_datos">
                <option [ngValue]="null">Selecciona una categoría</option>
                @for (c of categorias(); track c.clave) {
                  <option [ngValue]="c.clave">{{ c.clave }} (base {{ c.base }})</option>
                }
              </select>
              @if (errores()['categoria_datos']) {
                <span class="campo-error">{{ errores()['categoria_datos'] }}</span>
              }
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

            <label>
              Responsable (depende del área)
              <select formControlName="responsable_id">
                <option [ngValue]="null">
                  {{ form.controls.area_id.value ? 'Selecciona un responsable' : 'Primero elige un área' }}
                </option>
                @for (r of responsables(); track r.id) {
                  <option [ngValue]="r.id">{{ r.nombre }}</option>
                }
              </select>
              @if (errores()['responsable_id']) {
                <span class="campo-error">{{ errores()['responsable_id'] }}</span>
              }
            </label>
          </div>

          <label>
            Descripción
            <textarea rows="3" formControlName="descripcion"></textarea>
            @if (errores()['descripcion']) { <span class="campo-error">{{ errores()['descripcion'] }}</span> }
          </label>

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
export class Tramites implements OnInit {
  private api = inject(ApiService);
  private fb = inject(FormBuilder);

  tramites = signal<TramiteFila[]>([]);
  categorias = signal<Categoria[]>([]);
  areas = signal<Opcion[]>([]);
  responsables = signal<Opcion[]>([]);
  mostrarForm = signal(false);
  editandoId = signal<number | null>(null);
  errores = signal<Record<string, string>>({});
  error = signal('');
  mensaje = signal('');

  form = this.fb.group({
    nombre: this.fb.nonNullable.control(''),
    descripcion: this.fb.nonNullable.control(''),
    categoria_datos: this.fb.control<string | null>(null),
    area_id: this.fb.control<number | null>(null),
    responsable_id: this.fb.control<number | null>(null),
  });

  constructor() {
    // Cascada: al cambiar el área se vacía el responsable y se carga la lista de esa área.
    this.form.controls.area_id.valueChanges.pipe(takeUntilDestroyed()).subscribe((areaId) => {
      this.form.controls.responsable_id.setValue(null);
      this.cargarResponsables(areaId);
    });
  }

  ngOnInit(): void {
    this.api.categorias().subscribe((c) => this.categorias.set(c));
    this.api.areas().subscribe((a) => this.areas.set(a));
    this.cargar();
  }

  cargar(): void {
    this.api.tramites().subscribe((t) => this.tramites.set(t));
  }

  private cargarResponsables(areaId: number | null): void {
    if (!areaId) {
      this.responsables.set([]);
      return;
    }
    this.api.responsables(areaId).subscribe((r) => this.responsables.set(r));
  }

  private limpiarMensajes(): void {
    this.error.set('');
    this.errores.set({});
    this.mensaje.set('');
  }

  nuevo(): void {
    this.limpiarMensajes();
    this.form.reset(
      { nombre: '', descripcion: '', categoria_datos: null, area_id: null, responsable_id: null },
      { emitEvent: false },
    );
    this.responsables.set([]);
    this.editandoId.set(null);
    this.mostrarForm.set(true);
  }

  editar(t: TramiteFila): void {
    this.limpiarMensajes();
    this.api.tramite(t.id).subscribe((d) => {
      // emitEvent:false evita que la cascada borre el responsable al cargar el formulario.
      this.form.reset(
        {
          nombre: d.nombre,
          descripcion: d.descripcion ?? '',
          categoria_datos: d.categoria_datos,
          area_id: d.area_id,
          responsable_id: d.responsable_id,
        },
        { emitEvent: false },
      );
      this.cargarResponsables(d.area_id);
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
    const peticion = id ? this.api.actualizarTramite(id, datos) : this.api.crearTramite(datos);

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

  alternar(t: TramiteFila): void {
    this.limpiarMensajes();
    this.api.estadoTramite(t.id).subscribe(() => this.cargar());
  }
}