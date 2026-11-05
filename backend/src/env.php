<?php
// Lee el archivo .env y deja las variables disponibles con env('NOMBRE').
function cargarEnv(string $ruta): void
{
    if (!is_file($ruta)) {
        return;
    }
    foreach (file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
        $linea = trim($linea);
        if ($linea === '' || $linea[0] === '#') {
            continue;
        }
        $pos = strpos($linea, '=');
        if ($pos === false) {
            continue;
        }
        $clave = trim(substr($linea, 0, $pos));
        $valor = trim(substr($linea, $pos + 1));
        // Quita comillas si las tiene (así el # dentro de la clave no molesta).
        if (strlen($valor) >= 2 && $valor[0] === $valor[strlen($valor) - 1] && ($valor[0] === '"' || $valor[0] === "'")) {
            $valor = substr($valor, 1, -1);
        }
        $_ENV[$clave] = $valor;
    }
}

function env(string $clave, ?string $porDefecto = null): ?string
{
    if (isset($_ENV[$clave])) {
        return $_ENV[$clave];
    }
    $v = getenv($clave);
    return $v === false ? $porDefecto : $v;
}

cargarEnv(__DIR__ . '/../.env');