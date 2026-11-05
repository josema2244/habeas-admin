<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../http.php';
require_once __DIR__ . '/../jwt.php';

class TarifaController
{
    private static function versionReglas(): ?int
    {
        $v = db()->query('SELECT MAX(version) FROM rules_meta')->fetchColumn();
        return $v === null || $v === false ? null : (int) $v;
    }

    // GET /api/tarifas?tipo=BASE   (primer dropdown filtra el segundo)
    public static function listar(): void
    {
        exigirRol(['Administrador']);
        $sql = 'SELECT id, tipo_regla, clave, valor FROM risk_rates';
        $params = [];
        if (!empty($_GET['tipo'])) {
            $sql .= ' WHERE tipo_regla = :tipo';
            $params[':tipo'] = strtoupper((string) $_GET['tipo']);
        }
        $st = db()->prepare($sql . ' ORDER BY tipo_regla, clave');
        $st->execute($params);
        responder(['version' => self::versionReglas(), 'tarifas' => $st->fetchAll()]);
    }

    // PUT /api/tarifas/{id}   { "valor": 45 }
    public static function actualizar(int $id): void
    {
        exigirRol(['Administrador']);
        $valor = leerJson()['valor'] ?? null;
        if (is_string($valor) && preg_match('/^\d+$/', $valor)) {
            $valor = (int) $valor;
        }
        if (!is_int($valor) || $valor < 0 || $valor > 100) {
            responder([
                'error'  => 'Datos inválidos',
                'campos' => ['valor' => 'El valor debe ser un número entero entre 0 y 100'],
            ], 422);
        }

        $st = db()->prepare('SELECT tipo_regla, clave, valor FROM risk_rates WHERE id = :id');
        $st->execute([':id' => $id]);
        $tarifa = $st->fetch();
        if (!$tarifa) {
            responder(['error' => 'Tarifa no encontrada'], 404);
        }

        // Regla de negocio: el umbral Bajo debe ser menor que el umbral Medio.
        if ($tarifa['tipo_regla'] === 'UMBRAL') {
            $u = db()->query("SELECT clave, valor FROM risk_rates WHERE tipo_regla = 'UMBRAL'")
                     ->fetchAll(PDO::FETCH_KEY_PAIR);
            $u[$tarifa['clave']] = $valor;
            if ((int) ($u['bajo_max'] ?? 0) >= (int) ($u['medio_max'] ?? 100)) {
                responder([
                    'error'  => 'Datos inválidos',
                    'campos' => ['valor' => 'El umbral Bajo debe ser menor que el umbral Medio'],
                ], 422);
            }
        }

        if ((int) $tarifa['valor'] === $valor) {
            responder(['mensaje' => 'Sin cambios', 'version' => self::versionReglas()]);
        }

        // Cambia la tarifa y sube la versión de las reglas en una sola transacción.
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $up = $pdo->prepare('UPDATE risk_rates SET valor = :v WHERE id = :id');
            $up->execute([':v' => $valor, ':id' => $id]);
            if ($pdo->exec('UPDATE rules_meta SET version = version + 1') === 0) {
                $pdo->exec('INSERT INTO rules_meta (version) VALUES (1)');
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        responder(['mensaje' => 'Tarifa actualizada', 'version' => self::versionReglas()]);
    }
}