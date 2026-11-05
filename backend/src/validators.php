<?php
require_once __DIR__ . '/db.php';

// Valida una cédula ecuatoriana de persona natural (módulo 10).
function cedulaValida(string $c): bool
{
    if (!preg_match('/^\d{10}$/', $c)) {
        return false;
    }
    $provincia = (int) substr($c, 0, 2);
    if ($provincia < 1 || ($provincia > 24 && $provincia !== 30)) {
        return false;
    }
    if ((int) $c[2] > 5) {
        return false;
    }
    $suma = 0;
    for ($i = 0; $i < 9; $i++) {
        $d = (int) $c[$i] * ($i % 2 === 0 ? 2 : 1);
        if ($d > 9) {
            $d -= 9;
        }
        $suma += $d;
    }
    $verificador = (10 - ($suma % 10)) % 10;
    return $verificador === (int) $c[9];
}

// Mínimo 8 caracteres, con al menos una letra y un número.
function passwordValida(string $p): bool
{
    return strlen($p) >= 8 && preg_match('/[A-Za-z]/', $p) && preg_match('/\d/', $p);
}

function valorDuplicado(string $columna, string $valor, ?int $idExcluir): bool
{
    $expresion = match ($columna) {
        'correo' => 'LOWER(correo)',
        'cedula' => 'cedula',
        default  => throw new InvalidArgumentException('Columna no permitida'),
    };
    $sql = "SELECT 1 FROM users WHERE $expresion = :v" . ($idExcluir ? ' AND id <> :id' : '');
    $params = [':v' => $valor];
    if ($idExcluir) {
        $params[':id'] = $idExcluir;
    }
    $st = db()->prepare($sql);
    $st->execute($params);
    return (bool) $st->fetchColumn();
}

function limpiarUsuario(array $d): array
{
    return [
        'nombre'   => trim((string) ($d['nombre'] ?? '')),
        'correo'   => strtolower(trim((string) ($d['correo'] ?? ''))),
        'cedula'   => preg_replace('/\s+/', '', (string) ($d['cedula'] ?? '')),
        'password' => (string) ($d['password'] ?? ''),
        'role_id'  => (int) ($d['role_id'] ?? 0),
        'area_id'  => (isset($d['area_id']) && $d['area_id'] !== '') ? (int) $d['area_id'] : null,
    ];
}

// Devuelve un arreglo campo => mensaje. Vacío significa que todo es válido.
function validarUsuario(array $u, bool $esNuevo, ?int $idActual = null): array
{
    $e = [];

    if (mb_strlen($u['nombre']) < 3 || mb_strlen($u['nombre']) > 100) {
        $e['nombre'] = 'El nombre debe tener entre 3 y 100 caracteres';
    }

    if (!filter_var($u['correo'], FILTER_VALIDATE_EMAIL)) {
        $e['correo'] = 'El correo no tiene un formato válido';
    } elseif (valorDuplicado('correo', $u['correo'], $idActual)) {
        $e['correo'] = 'Ya existe un usuario con ese correo';
    }

    if (!cedulaValida($u['cedula'])) {
        $e['cedula'] = 'La cédula no es válida';
    } elseif (valorDuplicado('cedula', $u['cedula'], $idActual)) {
        $e['cedula'] = 'Ya existe un usuario con esa cédula';
    }

    if ($esNuevo || $u['password'] !== '') {
        if (!passwordValida($u['password'])) {
            $e['password'] = 'La contraseña debe tener mínimo 8 caracteres, con letras y números';
        }
    }

    // La clave foránea debe existir: no se acepta cualquier número.
    $rolNombre = null;
    $st = db()->prepare('SELECT nombre FROM roles WHERE id = :id');
    $st->execute([':id' => $u['role_id']]);
    $rolNombre = $st->fetchColumn();
    if ($rolNombre === false) {
        $e['role_id'] = 'Selecciona un rol válido';
    }

    if ($u['area_id'] !== null) {
        $st = db()->prepare('SELECT 1 FROM areas WHERE id = :id');
        $st->execute([':id' => $u['area_id']]);
        if (!$st->fetchColumn()) {
            $e['area_id'] = 'Selecciona un área válida';
        }
    } elseif ($rolNombre === 'Responsable de área') {
        $e['area_id'] = 'El responsable de área debe tener un área';
    }

    return $e;
}