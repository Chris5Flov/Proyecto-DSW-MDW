<?php
declare(strict_types=1);

require_once __DIR__ . '/../php/config/database.php';
require_once __DIR__ . '/../php/core/ReglaNegocioException.php';
require_once __DIR__ . '/../php/core/Respuesta.php';
require_once __DIR__ . '/../php/repositories/UsuarioRepository.php';
require_once __DIR__ . '/../php/repositories/MenuRepository.php';
require_once __DIR__ . '/../php/repositories/OrdenRepository.php';
require_once __DIR__ . '/../php/repositories/CocinaRepository.php';
require_once __DIR__ . '/../php/controllers/AuthController.php';
require_once __DIR__ . '/../php/controllers/UsuarioController.php';
require_once __DIR__ . '/../php/controllers/MenuController.php';
require_once __DIR__ . '/../php/controllers/OrdenController.php';
require_once __DIR__ . '/../php/controllers/CocinaController.php';
require_once __DIR__ . '/../php/controllers/CajaController.php';

try {
    $pdo = Database::conexion();
    $usuarioRepo = new UsuarioRepository($pdo);
    $menuRepo    = new MenuRepository($pdo);
    $ordenRepo   = new OrdenRepository($pdo);
    $cocinaRepo  = new CocinaRepository($pdo);

    $auth    = new AuthController($usuarioRepo);
    $usuario = new UsuarioController($usuarioRepo);
    $menu    = new MenuController($menuRepo, $usuarioRepo);
    $orden   = new OrdenController($ordenRepo, $usuarioRepo);
    $cocina  = new CocinaController($cocinaRepo, $ordenRepo, $usuarioRepo);
    $caja    = new CajaController($ordenRepo, $usuarioRepo);

    $metodo = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $posApi = strpos($uriPath, '/api');
    $ruta = $posApi !== false ? substr($uriPath, $posApi) : $uriPath;
    $ruta = '/' . trim($ruta, '/');

    $rutas = [
        // Autenticación y usuarios
        ['POST', '#^/api/auth/register$#', fn() => $auth->register()],
        ['POST', '#^/api/auth/login$#',    fn() => $auth->login()],
        ['POST', '#^/api/auth/logout$#',   fn() => $auth->logout()],
        ['GET',  '#^/api/auth/me$#',       fn() => $auth->me()],
        ['GET',  '#^/api/usuarios$#',      fn() => $usuario->listar()],
        ['PUT',  '#^/api/usuarios/([^/]+)$#', fn(string $id) => $usuario->actualizar($id)],

        // Menú (RF1)
        ['GET',    '#^/api/platos$#',             fn() => $menu->listarPlatos()],
        ['POST',   '#^/api/platos$#',             fn() => $menu->registrarPlato()],
        ['PUT',    '#^/api/platos/([^/]+)$#',     fn(string $id) => $menu->actualizarPlato($id)],
        ['POST',   '#^/api/platos/([^/]+)/imagen$#', fn(string $id) => $menu->subirImagenPlato($id)],
        ['DELETE', '#^/api/platos/([^/]+)$#',     fn(string $id) => $menu->eliminarPlato($id)],
        ['GET',    '#^/api/modificadores$#',      fn() => $menu->listarModificadores()],
        ['POST',   '#^/api/modificadores$#',      fn() => $menu->registrarModificador()],
        ['PUT',    '#^/api/modificadores/([^/]+)$#', fn(string $id) => $menu->actualizarModificador($id)],
        ['DELETE', '#^/api/modificadores/([^/]+)$#', fn(string $id) => $menu->eliminarModificador($id)],

        // Órdenes (RF2, RF3)
        ['GET',    '#^/api/ordenes$#',                             fn() => $orden->listar()],
        ['POST',   '#^/api/ordenes$#',                             fn() => $orden->abrir()],
        ['GET',    '#^/api/ordenes/([^/]+)$#',                     fn(string $id) => $orden->consultar($id)],
        ['POST',   '#^/api/ordenes/([^/]+)/lineas$#',              fn(string $id) => $orden->agregarLinea($id)],
        ['PUT',    '#^/api/ordenes/([^/]+)/lineas/([^/]+)$#',      fn(string $o, string $l) => $orden->modificarLinea($o, $l)],
        ['DELETE', '#^/api/ordenes/([^/]+)/lineas/([^/]+)$#',      fn(string $o, string $l) => $orden->cancelarLinea($o, $l)],
        ['POST',   '#^/api/ordenes/([^/]+)/cancelar$#',            fn(string $id) => $orden->cancelarOrden($id)],

        // Cocina (RF3)
        ['GET',  '#^/api/cocina/pendientes$#',                     fn() => $cocina->listarPendientes()],
        ['GET',  '#^/api/cocina/tablero$#',                        fn() => $cocina->listarTablero()],
        ['POST', '#^/api/cocina/ordenes/([^/]+)/lineas/([^/]+)/iniciar$#', fn(string $o, string $l) => $cocina->iniciarCoccion($o, $l)],
        ['POST', '#^/api/cocina/ordenes/([^/]+)/lineas/([^/]+)/lista$#',   fn(string $o, string $l) => $cocina->marcarLista($o, $l)],

        // Caja (RF4, RF5)
        ['GET',  '#^/api/caja/ordenes$#',                 fn() => $caja->listarOrdenesListas()],
        ['GET',  '#^/api/caja/ordenes/([^/]+)$#',         fn(string $id) => $caja->consultarOrden($id)],
        ['GET',  '#^/api/caja/ordenes/([^/]+)/totales$#', fn(string $id) => $caja->calcularTotales($id)],
        ['POST', '#^/api/caja/ordenes/([^/]+)/cobrar$#',  fn(string $id) => $caja->cobrar($id)],
    ];

    foreach ($rutas as [$metodoRuta, $patron, $accion]) {
        if ($metodo === $metodoRuta && preg_match($patron, $ruta, $coincidencias) === 1) {
            array_shift($coincidencias);
            $accion(...$coincidencias);
            exit;
        }
    }

    Respuesta::error(404, "Ruta no encontrada: $metodo $ruta");
} catch (ReglaNegocioException $e) {
    Respuesta::error($e->codigoHttp(), $e->getMessage());
} catch (Throwable $e) {
    error_log('[API Error] ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    Respuesta::error(500, 'Error interno del servidor');
}
