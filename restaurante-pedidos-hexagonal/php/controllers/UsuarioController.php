<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/ReglaNegocioException.php';
require_once __DIR__ . '/../core/Respuesta.php';
require_once __DIR__ . '/../core/Sesion.php';
require_once __DIR__ . '/../core/Validacion.php';
require_once __DIR__ . '/../repositories/UsuarioRepository.php';

/**
 * Administración de usuarios y roles (solo ADMINISTRADOR).
 */
final class UsuarioController
{
    private const ROLES = ['ADMINISTRADOR', 'MESERO', 'COCINERO', 'CAJERO'];

    private UsuarioRepository $usuarios;

    public function __construct(UsuarioRepository $usuarios)
    {
        $this->usuarios = $usuarios;
    }

    public function listar(): never
    {
        Sesion::exigirRol($this->usuarios, ['ADMINISTRADOR']);
        Respuesta::ok($this->usuarios->listar());
    }

    public function actualizar(string $usuarioId): never
    {
        $admin = Sesion::exigirRol($this->usuarios, ['ADMINISTRADOR']);
        $objetivoId = Validacion::entero($usuarioId);
        if ($objetivoId <= 0) {
            throw new ReglaNegocioException('Identificador de usuario no válido');
        }
        if ($objetivoId === $admin['id']) {
            throw new ReglaNegocioException('No puedes modificar tu propio rol ni desactivar tu propia cuenta');
        }

        $datos = Validacion::leerJson();
        $rol = Validacion::enumeracion($datos['rol'] ?? null, self::ROLES, 'rol');
        if (!array_key_exists('activo', $datos) || !is_bool($datos['activo'])) {
            throw new ReglaNegocioException('El campo activo debe ser booleano');
        }

        Respuesta::ok($this->usuarios->actualizarRolYEstado($objetivoId, $rol, $datos['activo']));
    }
}
