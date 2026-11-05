// Lee el error que devuelve el backend: mensaje general y errores por campo.
// eslint-disable-next-line @typescript-eslint/no-explicit-any
export function leerError(e: any): { mensaje: string; campos: Record<string, string> } {
  return {
    mensaje: e?.error?.error ?? 'No se pudo conectar con el servidor',
    campos: e?.error?.campos ?? {},
  };
}