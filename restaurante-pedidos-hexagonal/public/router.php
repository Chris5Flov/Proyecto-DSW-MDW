<?php
declare(strict_types=1);

// Router para el servidor embebido de PHP: php -S 127.0.0.1:8080 -t public public/router.php

$ruta = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if (str_starts_with($ruta, '/api')) {
    require __DIR__ . '/api.php';
    return true;
}

$archivo = __DIR__ . $ruta;
if ($ruta !== '/' && is_file($archivo)) {
    return false;
}

if ($ruta === '/') {
    readfile(__DIR__ . '/index.html');
    return true;
}

http_response_code(404);
echo 'No encontrado';
return true;
