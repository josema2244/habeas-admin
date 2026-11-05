<?php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/http.php';
require_once __DIR__ . '/../src/jwt.php';
require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/../src/controllers/UserController.php';
require_once __DIR__ . '/../src/controllers/CatalogoController.php';
require_once __DIR__ . '/../src/controllers/TarifaController.php';
require_once __DIR__ . '/../src/controllers/TramiteController.php';

// CORS: permite que Angular (localhost:4200) llame a este backend.
header('Access-Control-Allow-Origin: ' . env('CORS_ORIGIN', 'http://localhost:4200'));
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$metodo = $_SERVER['REQUEST_METHOD'];
$ruta = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') ?: '/';

try {
    if ($ruta === '/health' && $metodo === 'GET') {
        $pdo = db();
        $tablas = $pdo->query(
            "SELECT COUNT(*) AS total FROM information_schema.tables WHERE table_schema = 'public'"
        )->fetch();
        $usuarios = $pdo->query('SELECT COUNT(*) AS total FROM users')->fetch();
        responder([
            'estado'   => 'OK',
            'tablas'   => (int) $tablas['total'],
            'usuarios' => (int) $usuarios['total'],
        ]);
    }

    // Autenticación
    if ($ruta === '/api/login' && $metodo === 'POST') {
        AuthController::login();
    }
    if ($ruta === '/api/me' && $metodo === 'GET') {
        AuthController::me();
    }

    // Catálogos para dropdowns
    if ($metodo === 'GET') {
        switch ($ruta) {
            case '/api/catalogos/roles':
                CatalogoController::roles();
            case '/api/catalogos/areas':
                CatalogoController::areas();
            case '/api/catalogos/categorias':
                CatalogoController::categorias();
            case '/api/catalogos/tipos-regla':
                CatalogoController::tiposRegla();
            case '/api/catalogos/responsables':
                CatalogoController::responsables();
        }
    }

    // Usuarios
    if ($ruta === '/api/usuarios') {
        if ($metodo === 'GET') {
            UserController::listar();
        }
        if ($metodo === 'POST') {
            UserController::crear();
        }
    }
    if (preg_match('#^/api/usuarios/(\d+)$#', $ruta, $m)) {
        if ($metodo === 'GET') {
            UserController::ver((int) $m[1]);
        }
        if ($metodo === 'PUT') {
            UserController::actualizar((int) $m[1]);
        }
    }
    if (preg_match('#^/api/usuarios/(\d+)/estado$#', $ruta, $m) && $metodo === 'PUT') {
        UserController::cambiarEstado((int) $m[1]);
    }

    // Tarifas de riesgo
    if ($ruta === '/api/tarifas' && $metodo === 'GET') {
        TarifaController::listar();
    }
    if (preg_match('#^/api/tarifas/(\d+)$#', $ruta, $m) && $metodo === 'PUT') {
        TarifaController::actualizar((int) $m[1]);
    }

    // Trámites
    if ($ruta === '/api/tramites') {
        if ($metodo === 'GET') {
            TramiteController::listar();
        }
        if ($metodo === 'POST') {
            TramiteController::crear();
        }
    }
    if (preg_match('#^/api/tramites/(\d+)$#', $ruta, $m)) {
        if ($metodo === 'GET') {
            TramiteController::ver((int) $m[1]);
        }
        if ($metodo === 'PUT') {
            TramiteController::actualizar((int) $m[1]);
        }
    }
    if (preg_match('#^/api/tramites/(\d+)/estado$#', $ruta, $m) && $metodo === 'PUT') {
        TramiteController::cambiarEstado((int) $m[1]);
    }

    responder(['error' => 'Ruta no encontrada'], 404);
} catch (Throwable $e) {
    responder(['estado' => 'ERROR', 'detalle' => $e->getMessage()], 500);
}