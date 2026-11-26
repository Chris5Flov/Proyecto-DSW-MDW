<?php
declare(strict_types=1);

require_once __DIR__ . '/ReglaNegocioException.php';
require_once __DIR__ . '/../repositories/UsuarioRepository.php';

/**
 * Manejo seguro de sesiones PHP y verificación de roles (RBAC) en servidor.
 */
final class Sesion
{
    private function __construct()
    {
    }

    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        ini_set('session.use_strict_mode', '1');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public static function establecerUsuario(int $usuarioId): void
    {
        self::iniciar();
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = $usuarioId;
    }

    public static function destruir(): void
    {
        self::iniciar();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }

    /** @return array{id:int, usuario:string, nombre:string, correo:string, rol:string, activo:bool}|null */
    public static function usuarioActual(UsuarioRepository $usuarios): ?array
    {
        self::iniciar();
        $id = $_SESSION['usuario_id'] ?? null;
        if (!is_int($id) && !is_string($id)) {
            return null;
        }
        $usuario = $usuarios->buscarPorId((int) $id);
        if ($usuario === null || !$usuario['activo']) {
            self::destruir();
            return null;
        }
        return $usuario;
    }

    /** @param list<string> $rolesPermitidos */
    public static function exigirRol(UsuarioRepository $usuarios, array $rolesPermitidos): array
    {
        $usuario = self::usuarioActual($usuarios);
        if ($usuario === null) {
            throw new ReglaNegocioException('Debes iniciar sesión para continuar', 401);
        }
        if ($rolesPermitidos !== [] && !in_array($usuario['rol'], $rolesPermitidos, true)) {
            throw new ReglaNegocioException('No tienes permisos para realizar esta acción', 403);
        }
        return $usuario;
    }
}
