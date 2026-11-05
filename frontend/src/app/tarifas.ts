import { Component, OnInit, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { ApiService, Tarifa } from './api.service';
import { leerError } from './errores';

@Component({
  selector: 'app-tarifas',
  imports: [ReactiveFormsModule],
  template: `
    <section class="card">
      <div class="encabezado">
        <h2>Tarifas de riesgo</h2>
        <span class="etiqueta">Versión de reglas: {{ version() ?? '-' }}</span>
      </div>

      <form [formGroup]="form" (ngSubmit)="guardar()">
        <div class="grid">
          <label>
            Tipo de regla
            <select formControlName="tipo">
              <option [ngValue]="null">Selecciona un tipo</option>
              @for (t of tipos(); track t) {
                <option [ngValue]="t">{{ t }}</option>
              }
            </select>
          </label>

          <label>
            Regla (depende del tipo)
            <select formControlName="tarifa_id">
              <option [ngValue]="null">
                {{ form.controls.tipo.value ? 'Selecciona una regla' : 'Primero elige un tipo' }}
              </option>
              @for (c of claves(); track c.id) {
                <option [ngValue]="c.id">{{ c.clave }} (actual {{ c.valor }})</option>
              }
            </select>
          </label>

          <label>
            Nuevo valor (0 a 100)
            <input type="number" formControlName="valor" min="0" max="100" step="1" />
            @if (errores()['valor']) { <span class="campo-error">{{ errores()['valor'] }}</span> }
          </label>
        </div>

        @if (error()) { <p class="error">{{ error() }}</p> }
        @if (mensaje()) { <p class="ok">{{ mensaje() }}</p> }

        <button type="submit">Guardar tarifa</button>
      </form>
    </section>

    <section class="card">
      <h3>Reglas vigentes</h3>
      <table>
        <thead><tr><th>Tipo</th><th>Regla</th><th>Valor</th></tr></thead>
        <tbody>
          @for (t of todas(); track t.id) {
            <tr><td>{{ t.tipo_regla }}</td><td>{{ t.clave }}</td><td>{{ t.valor }}</td></tr>
          }
        </tbody>
      </table>
    </section>
  `,
})
export class Tarifas implements OnInit {
  private api = inject(ApiService);
  private fb = inject(FormBuilder);

  tipos = signal<string[]>([]);
  claves = signal<Tarifa[]>([]);
  todas = signal<Tarifa[]>([]);
  version = signal<number | null>(null);
  errores = signal<Record<string, string>>({});
  error = signal('');
  mensaje = signal('');

  form = this.fb.group({
    tipo: this.fb.control<string | null>(null),
    tarifa_id: this.fb.control<number | null>(null),
    valor: this.fb.control<number | null>(null),
  });

  constructor() {
    // Cascada: el tipo elegido carga, desde el servidor, solo las reglas de ese tipo.
    this.form.controls.tipo.valueChanges.pipe(takeUntilDestroyed()).subscribe((tipo) => {
      this.form.patchValue({ tarifa_id: null, valor: null }, { emitEvent: false });
      this.claves.set([]);
      if (tipo) {
        this.api.tarifas(tipo).subscribe((r) => this.claves.set(r.tarifas));
      }
    });

    this.form.controls.tarifa_id.valueChanges.pipe(takeUntilDestroyed()).subscribe((id) => {
      const tarifa = this.claves().find((c) => c.id === id);
      this.form.controls.valor.setValue(tarifa ? tarifa.valor : null);
    });
  }

  ngOnInit(): void {
    this.api.tiposRegla().subscribe((t) => this.tipos.set(t));
    this.recargar();
  }

  private recargar(): void {
    this.api.tarifas().subscribe((r) => {
      this.todas.set(r.tarifas);
      this.version.set(r.version);
    });
  }

  guardar(): void {
    this.error.set('');
    this.errores.set({});
    this.mensaje.set('');
    const { tipo, tarifa_id, valor } = this.form.getRawValue();

    if (!tarifa_id) {
      this.error.set('Selecciona el tipo y la regla que quieres cambiar');
      return;
    }

    this.api.actualizarTarifa(tarifa_id, valor).subscribe({
      next: (r) => {
        this.mensaje.set(`${r.mensaje}. Versión de reglas: ${r.version}`);
        this.recargar();
        if (tipo) {
          this.api.tarifas(tipo).subscribe((x) => this.claves.set(x.tarifas));
        }
      },
      error: (e) => {
        const r = leerError(e);
        this.error.set(r.mensaje);
        this.errores.set(r.campos);
      },
    });
  }
}