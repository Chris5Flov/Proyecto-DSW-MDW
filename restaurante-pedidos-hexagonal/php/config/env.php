<?php
declare(strict_types=1);

/**
 * Carga variables de entorno desde .env en la raíz del proyecto si existe,
 * sin sobreescribir variables ya definidas en el entorno del sistema.
 */
function cargarEntorno(string $rutaRaiz): void
{
    static $cargado = false;
    if ($cargado) {
        return;
    }
    $cargado = true;

    $archivo = $rutaRaiz . DIRECTORY_SEPARATOR . '.env';
    if (!is_file($archivo) || !is_readable($archivo)) {
        return;
    }

    $lineas = file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lineas === false) {
        return;
    }

    foreach ($lineas as $linea) {
        $linea = trim($linea);
        if ($linea === '' || str_starts_with($linea, '#')) {
            continue;
        }
        $pos = strpos($linea, '=');
        if ($pos === false) {
            continue;
        }
        $clave = trim(substr($linea, 0, $pos));
        $valor = trim(substr($linea, $pos + 1));
        $valor = trim($valor, "\"'");
        if ($clave !== '' && getenv($clave) === false) {
            putenv("$clave=$valor");
            $_ENV[$clave] = $valor;
        }
    }
}

function env(string $clave, string $porDefecto = ''): string
{
    $valor = getenv($clave);
    return $valor === false ? $porDefecto : $valor;
}
