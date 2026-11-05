<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../http.php';
require_once __DIR__ . '/../jwt.php';

class TramiteController
{
    private const SELECT_BASE =
        'SELECT t.id, t.nombre, t.descripcion, t.categoria_datos, t.activa,
                t.area_id, a.nombre AS area, t.responsable_id, u.nombre AS responsable
         FROM processing_activities t
         LEFT JOIN areas a ON a.id = t.area_id
         LEFT JOIN users u ON u.id = t.responsable_id';

    private static function limpiar(array $d): array
    {
        return [
            'nombre'          => trim((string) ($d['nombre'] ?? '')),
            'descripcion'     => trim((string) ($d['descripcion'] ?? '')),
            'categoria_datos' => trim((string) ($d['categoria_datos'] ?? '')),
            'area_id'         => (int) ($d['area_id'] ?? 0),
            'responsable_id'  => (int) ($d['responsable_id'] ?? 0),
        ];
    }

    private static function validar(array $t, ?int $idActual): array
    {
        $e = [];
        $pdo = db();

        if (mb_strlen($t['nombre']) < 3 || mb_strlen($t['nombre']) > 100) {
            $e['nombre'] = 'El nombre debe tener entre 3 y 100 caracteres';
        } else {
            $sql = 'SELECT 1 FROM processing_activities WHERE LOWER(nombre) = LOWER(:n)'
                 . ($idActual ? ' AND id <> :id' : '');
            $params = [':n' => $t['nombre']];
            if ($idActual) {
                $params[':id'] = $idActual;
            }
            $st = $pdo->prepare($sql);
            $st->execute($params);
            if ($st->fetchColumn()) {
                $e['nombre'] = 'Ya existe un trámite con ese nombre';
            }
        }

        if (mb_strlen($t['descripcion']) > 250) {
            $e['descripcion'] = 'La descripción no puede superar 250 caracteres';
        }

        // La categoría debe existir como tarifa BASE (no se acepta texto libre).
        $st = $pdo->prepare("SELECT 1 FROM risk_rates WHERE tipo_regla = 'BASE' AND clave = :c");
        $st->execute([':c' => $t['categoria_datos']]);
        if (!$st->fetchColumn()) {
            $e['categoria_datos'] = 'Selecciona una categoría de datos válida';
        }

        $st = $pdo->prepare('SELECT 1 FROM areas WHERE id = :id');
        $st->execute([':id' => $t['area_id']]);
        if (!$st->fetchColumn()) {
            $e['area_id'] = 'Selecciona un área válida';
        }

        // El responsable debe ser un Responsable de área activo y de ESA área.
        $st = $pdo->prepare(
            "SELECT u.area_id
             FROM users u JOIN roles r ON r.id = u.role_id
             WHERE u.id = :id AND u.activo = TRUE AND r.nombre = 'Responsable de área'"
        );
        $st->execute([':id' => $t['responsable_id']]);
        $areaResp = $st->fetch();
        if (!$areaResp) {
            $e['responsable_id'] = 'Selecciona un responsable válido';
        } elseif ((int) $areaResp['area_id'] !== $t['area_id']) {
            $e['responsable_id'] = 'El responsable no pertenece al área seleccionada';
        }

        return $e;
    }

    // GET /api/tramites
    public static function listar(): void
    {
        exigirRol(['Administrador']);
        responder(db()->query(self::SELECT_BASE . ' ORDER BY t.id')->fetchAll());
    }

    // GET /api/tramites/{id}
    public static function ver(int $id): void
    {
        exigirRol(['Administrador']);
        $st = db()->prepare(self::SELECT_BASE . ' WHERE t.id = :id');
        $st->execute([':id' => $id]);
        $fila = $st->fetch();
        if (!$fila) {
            responder(['error' => 'Trámite no encontrado'], 404);
        }
        responder($fila);
    }

    // POST /api/tramites
    public static function crear(): void
    {
        exigirRol(['Administrador']);
        $t = self::limpiar(leerJson());
        $errores = self::validar($t, null);
        if ($errores) {
            responder(['error' => 'Datos inválidos', 'campos' => $errores], 422);
        }
        $st = db()->prepare(
            'INSERT INTO processing_activities (nombre, descripcion, categoria_datos, activa, area_id, responsable_id)
             VALUES (:nombre, :descripcion, :categoria, TRUE, :area, :resp)
             RETURNING id'
        );
        $st->execute([
            ':nombre'      => $t['nombre'],
            ':descripcion' => $t['descripcion'] !== '' ? $t['descripcion'] : null,
            ':categoria'   => $t['categoria_datos'],
            ':area'        => $t['area_id'],
            ':resp'        => $t['responsable_id'],
        ]);
        responder(['id' => (int) $st->fetchColumn(), 'mensaje' => 'Trámite creado'], 201);
    }

    // PUT /api/tramites/{id}
    public static function actualizar(int $id): void
    {
        exigirRol(['Administrador']);
        $t = self::limpiar(leerJson());
        $errores = self::validar($t, $id);
        if ($errores) {
            responder(['error' => 'Datos inválidos', 'campos' => $errores], 422);
        }
        $st = db()->prepare(
            'UPDATE processing_activities
             SET nombre = :nombre, descripcion = :descripcion, categoria_datos = :categoria,
                 area_id = :area, responsable_id = :resp
             WHERE id = :id'
        );
        $st->execute([
            ':nombre'      => $t['nombre'],
            ':descripcion' => $t['descripcion'] !== '' ? $t['descripcion'] : null,
            ':categoria'   => $t['categoria_datos'],
            ':area'        => $t['area_id'],
            ':resp'        => $t['responsable_id'],
            ':id'          => $id,
        ]);
        if ($st->rowCount() === 0) {
            responder(['error' => 'Trámite no encontrado'], 404);
        }
        responder(['mensaje' => 'Trámite actualizado']);
    }

    // PUT /api/tramites/{id}/estado  (activa o desactiva; no se borra para no perder el historial)
    public static function cambiarEstado(int $id): void
    {
        exigirRol(['Administrador']);
        $st = db()->prepare('UPDATE processing_activities SET activa = NOT activa WHERE id = :id RETURNING activa');
        $st->execute([':id' => $id]);
        $fila = $st->fetch();
        if (!$fila) {
            responder(['error' => 'Trámite no encontrado'], 404);
        }
        responder(['activa' => (bool) $fila['activa']]);
    }
}