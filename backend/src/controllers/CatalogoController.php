<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../http.php';
require_once __DIR__ . '/../jwt.php';

// Listas para llenar los dropdowns de los formularios.
class CatalogoController
{
    // GET /api/catalogos/roles
    public static function roles(): void
    {
        exigirRol(['Administrador']);
        responder(db()->query('SELECT id, nombre FROM roles ORDER BY id')->fetchAll());
    }

    // GET /api/catalogos/areas
    public static function areas(): void
    {
        exigirRol(['Administrador']);
        responder(db()->query('SELECT id, nombre FROM areas ORDER BY nombre')->fetchAll());
    }

    // GET /api/catalogos/categorias  (categorías de datos = tarifas BASE)
    public static function categorias(): void
    {
        exigirRol(['Administrador']);
        responder(db()->query(
            "SELECT clave, valor AS base FROM risk_rates WHERE tipo_regla = 'BASE' ORDER BY clave"
        )->fetchAll());
    }

    // GET /api/catalogos/tipos-regla
    public static function tiposRegla(): void
    {
        exigirRol(['Administrador']);
        responder(db()->query(
            'SELECT DISTINCT tipo_regla FROM risk_rates ORDER BY tipo_regla'
        )->fetchAll(PDO::FETCH_COLUMN));
    }

    // GET /api/catalogos/responsables?area_id=3   (segundo dropdown de la cascada)
    public static function responsables(): void
    {
        exigirRol(['Administrador']);
        $areaId = (int) ($_GET['area_id'] ?? 0);
        if ($areaId <= 0) {
            responder(['error' => 'Falta el área'], 422);
        }
        $st = db()->prepare(
            "SELECT u.id, u.nombre
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.area_id = :area AND u.activo = TRUE AND r.nombre = 'Responsable de área'
             ORDER BY u.nombre"
        );
        $st->execute([':area' => $areaId]);
        responder($st->fetchAll());
    }
}