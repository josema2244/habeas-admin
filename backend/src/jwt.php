<?php
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/http.php';

function b64url(string $d): string
{
    return rtrim(strtr(base64_encode($d), '+/', '-_'), '=');
}

function b64urlDecode(string $d): string
{
    return base64_decode(strtr($d, '-_', '+/'));
}

// Crea un token firmado (JWT, algoritmo HS256) que dura 2 horas.
function crearToken(array $datos, int $segundos = 7200): string
{
    $cabecera = b64url(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $datos['exp'] = time() + $segundos;
    $cuerpo = b64url(json_encode($datos));
    $firma = b64url(hash_hmac('sha256', "$cabecera.$cuerpo", env('JWT_SECRET'), true));
    return "$cabecera.$cuerpo.$firma";
}

function verificarToken(string $token): ?array
{
    $partes = explode('.', $token);
    if (count($partes) !== 3) {
        return null;
    }
    [$cabecera, $cuerpo, $firma] = $partes;
    $esperada = b64url(hash_hmac('sha256', "$cabecera.$cuerpo", env('JWT_SECRET'), true));
    if (!hash_equals($esperada, $firma)) {
        return null;
    }
    $datos = json_decode(b64urlDecode($cuerpo), true);
    if (!is_array($datos) || ($datos['exp'] ?? 0) < time()) {
        return null;
    }
    return $datos;
}

// Middleware: exige un token válido. Devuelve los datos del usuario.
function usuarioActual(): array
{
    $cabecera = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/^Bearer\s+(.+)$/i', $cabecera, $m)) {
        responder(['error' => 'Falta el token de acceso'], 401);
    }
    $datos = verificarToken($m[1]);
    if ($datos === null) {
        responder(['error' => 'Token inválido o vencido'], 401);
    }
    return $datos;
}

// Middleware por rol: solo deja pasar a los roles indicados.
function exigirRol(array $rolesPermitidos): array
{
    $usuario = usuarioActual();
    if (!in_array($usuario['rol'], $rolesPermitidos, true)) {
        responder(['error' => 'No tienes permiso para esta acción'], 403);
    }
    return $usuario;
}