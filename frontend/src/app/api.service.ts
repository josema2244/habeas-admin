import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { API_URL } from './config';

export interface Opcion { id: number; nombre: string; }
export interface Categoria { clave: string; base: number; }

export interface UsuarioFila {
  id: number;
  nombre: string;
  correo: string;
  cedula: string | null;
  activo: boolean;
  role_id: number;
  rol: string;
  area_id: number | null;
  area: string | null;
}

export interface Tarifa { id: number; tipo_regla: string; clave: string; valor: number; }
export interface RespuestaTarifas { version: number | null; tarifas: Tarifa[]; }

export interface TramiteFila {
  id: number;
  nombre: string;
  descripcion: string | null;
  categoria_datos: string;
  activa: boolean;
  area_id: number | null;
  area: string | null;
  responsable_id: number | null;
  responsable: string | null;
}

export interface Respuesta { mensaje: string; id?: number; version?: number | null; }

@Injectable({ providedIn: 'root' })
export class ApiService {
  private http = inject(HttpClient);
  private u(ruta: string): string {
    return `${API_URL}${ruta}`;
  }

  // Catálogos (dropdowns)
  roles() { return this.http.get<Opcion[]>(this.u('/api/catalogos/roles')); }
  areas() { return this.http.get<Opcion[]>(this.u('/api/catalogos/areas')); }
  categorias() { return this.http.get<Categoria[]>(this.u('/api/catalogos/categorias')); }
  tiposRegla() { return this.http.get<string[]>(this.u('/api/catalogos/tipos-regla')); }
  responsables(areaId: number) {
    const params = new HttpParams().set('area_id', areaId);
    return this.http.get<Opcion[]>(this.u('/api/catalogos/responsables'), { params });
  }

  // Usuarios
  usuarios() { return this.http.get<UsuarioFila[]>(this.u('/api/usuarios')); }
  usuario(id: number) { return this.http.get<UsuarioFila>(this.u(`/api/usuarios/${id}`)); }
  crearUsuario(datos: object) { return this.http.post<Respuesta>(this.u('/api/usuarios'), datos); }
  actualizarUsuario(id: number, datos: object) {
    return this.http.put<Respuesta>(this.u(`/api/usuarios/${id}`), datos);
  }
  estadoUsuario(id: number) {
    return this.http.put<{ activo: boolean }>(this.u(`/api/usuarios/${id}/estado`), {});
  }

  // Tarifas
  tarifas(tipo?: string) {
    let params = new HttpParams();
    if (tipo) {
      params = params.set('tipo', tipo);
    }
    return this.http.get<RespuestaTarifas>(this.u('/api/tarifas'), { params });
  }
  actualizarTarifa(id: number, valor: number | null) {
    return this.http.put<Respuesta>(this.u(`/api/tarifas/${id}`), { valor });
  }

  // Trámites
  tramites() { return this.http.get<TramiteFila[]>(this.u('/api/tramites')); }
  tramite(id: number) { return this.http.get<TramiteFila>(this.u(`/api/tramites/${id}`)); }
  crearTramite(datos: object) { return this.http.post<Respuesta>(this.u('/api/tramites'), datos); }
  actualizarTramite(id: number, datos: object) {
    return this.http.put<Respuesta>(this.u(`/api/tramites/${id}`), datos);
  }
  estadoTramite(id: number) {
    return this.http.put<{ activa: boolean }>(this.u(`/api/tramites/${id}/estado`), {});
  }
}