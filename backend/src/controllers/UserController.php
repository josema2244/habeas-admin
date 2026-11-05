<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../http.php';
require_once __DIR__ . '/../jwt.php';
require_once __DIR__ . '/../validators.php';

class UserController
{
    private const SELECT_BASE =
        'SELECT u.id, u.nombre, u.correo, u.cedula, u.activo, u.role_id, r.nombre AS rol,
                u.area_id, a.nombre AS area
         FROM users u
         JOIN roles r ON r.id = u.role_id
         LEFT JOIN areas a ON a.id = u.area_id';

    private static function enmascarar(string $cedula): string
    {
        return substr($cedula, 0, 2) . '******' . substr($cedula, -2);
    }

    // GET /api/usuarios?area_id=1&rol=Responsable de área
    public static function listar(): void
    {
        exigirRol(['Administrador']);
        $where = [];
        $params = [];
        if (!empty($_GET['area_id'])) {
            $where[] = 'u.area_id = :area_id';
            $params[':area_id'] = (int) $_GET['area_id'];
        }
        if (!empty($_GET['rol'])) {
            $where[] = 'r.nombre = :rol';
            $params[':rol'] = (string) $_GET['rol'];
        }
        $sql = self::SELECT_BASE . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY u.id';
        $st = db()->prepare($sql);
        $st->execute($params);
        $filas = $st->fetchAll();
        // En el listado la cédula va enmascarada (dato sensible).
        foreach ($filas as &$f) {
            $f['cedula'] = $f['cedula'] ? self::enmascarar($f['cedula']) : null;
        }
        responder($filas);
    }

    // GET /api/usuarios/{id}
    public static function ver(int $id): void
    {
        exigirRol(['Administrador']);
        $st = db()->prepare(self::SELECT_BASE . ' WHERE u.id = :id');
        $st->execute([':id' => $id]);
        $usuario = $st->fetch();
        if (!$usuario) {
            responder(['error' => 'Usuario no encontrado'], 404);
        }
        responder($usuario);
    }

    // POST /api/usuarios
    public static function crear(): void
    {
        exigirRol(['Administrador']);
        $u = limpiarUsuario(leerJson());
        $errores = validarUsuario($u, true);
        if ($errores) {
            responder(['error' => 'Datos inválidos', 'campos' => $errores], 422);
        }

        $st = db()->prepare(
            'INSERT INTO users (role_id, nombre, correo, password_hash, area, area_id, cedula, activo)
             VALUES (:role_id, :nombre, :correo, :hash, :area, :area_id, :cedula, TRUE)
             RETURNING id'
        );
        $st->execute([
            ':role_id' => $u['role_id'],
            ':nombre'  => $u['nombre'],
            ':correo'  => $u['correo'],
            ':hash'    => password_hash($u['password'], PASSWORD_BCRYPT),
            ':area'    => self::nombreArea($u['area_id']),
            ':area_id' => $u['area_id'],
            ':cedula'  => $u['cedula'],
        ]);
        responder(['id' => (int) $st->fetchColumn(), 'mensaje' => 'Usuario creado'], 201);
    }

    // PUT /api/usuarios/{id}  (la contraseña es opcional al editar)
    public static function actualizar(int $id): void
    {
        exigirRol(['Administrador']);
        $u = limpiarUsuario(leerJson());
        $errores = validarUsuario($u, false, $id);
        if ($errores) {
            responder(['error' => 'Datos inválidos', 'campos' => $errores], 422);
        }

        $sql = 'UPDATE users SET role_id = :role_id, nombre = :nombre, correo = :correo,
                       area = :area, area_id = :area_id, cedula = :cedula';
        $params = [
            ':role_id' => $u['role_id'],
            ':nombre'  => $u['nombre'],
            ':correo'  => $u['correo'],
            ':area'    => self::nombreArea($u['area_id']),
            ':area_id' => $u['area_id'],
            ':cedula'  => $u['cedula'],
            ':id'      => $id,
        ];
        if ($u['password'] !== '') {
            $sql .= ', password_hash = :hash';
            $params[':hash'] = password_hash($u['password'], PASSWORD_BCRYPT);
        }
        $st = db()->prepare($sql . ' WHERE id = :id');
        $st->execute($params);
        if ($st->rowCount() === 0) {
            responder(['error' => 'Usuario no encontrado'], 404);
        }
        responder(['mensaje' => 'Usuario actualizado']);
    }

    // PUT /api/usuarios/{id}/estado  (activa o desactiva)
    public static function cambiarEstado(int $id): void
    {
        $admin = exigirRol(['Administrador']);
        if ($admin['id'] === $id) {
            responder(['error' => 'No puedes desactivar tu propio usuario'], 422);
        }
        $st = db()->prepare('UPDATE users SET activo = NOT activo WHERE id = :id RETURNING activo');
        $st->execute([':id' => $id]);
        $fila = $st->fetch();
        if (!$fila) {
            responder(['error' => 'Usuario no encontrado'], 404);
        }
        responder(['activo' => (bool) $fila['activo']]);
    }

    private static function nombreArea(?int $areaId): string
    {
        if ($areaId === null) {
            return '';
        }
        $st = db()->prepare('SELECT nombre FROM areas WHERE id = :id');
        $st->execute([':id' => $areaId]);
        return (string) $st->fetchColumn();
    }
}