<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../jwt.php';

class AuthController
{
    // POST /api/login  { "correo": "...", "password": "..." }
    public static function login(): void
    {
        $datos = leerJson();
        $correo = trim((string) ($datos['correo'] ?? ''));
        $password = (string) ($datos['password'] ?? '');

        if ($correo === '' || $password === '') {
            responder(['error' => 'Correo y contraseña son obligatorios'], 422);
        }

        $st = db()->prepare(
            'SELECT u.id, u.nombre, u.correo, u.password_hash, u.activo, r.nombre AS rol
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE LOWER(u.correo) = LOWER(:correo)'
        );
        $st->execute([':correo' => $correo]);
        $usuario = $st->fetch();

        // Mismo mensaje para correo o clave incorrectos (no revela cuál falló).
        if (!$usuario || !$usuario['activo'] || !password_verify($password, $usuario['password_hash'])) {
            responder(['error' => 'Credenciales incorrectas'], 401);
        }

        $token = crearToken([
            'id'     => (int) $usuario['id'],
            'nombre' => $usuario['nombre'],
            'rol'    => $usuario['rol'],
        ]);

        responder([
            'token'   => $token,
            'usuario' => [
                'id'     => (int) $usuario['id'],
                'nombre' => $usuario['nombre'],
                'correo' => $usuario['correo'],
                'rol'    => $usuario['rol'],
            ],
        ]);
    }

    // GET /api/me  (requiere token)
    public static function me(): void
    {
        responder(usuarioActual());
    }
}