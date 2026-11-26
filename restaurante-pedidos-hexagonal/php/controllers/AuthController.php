<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/ReglaNegocioException.php';
require_once __DIR__ . '/../core/Respuesta.php';
require_once __DIR__ . '/../core/Sesion.php';
require_once __DIR__ . '/../core/Validacion.php';
require_once __DIR__ . '/../repositories/UsuarioRepository.php';

/**
 * Autenticación y manejo de sesión (login, registro, logout, sesión actual).
 */
final class AuthController
{
    private UsuarioRepository $usuarios;

    public function __construct(UsuarioRepository $usuarios)
    {
        $this->usuarios = $usuarios;
    }

    public function register(): never
    {
        $datos = Validacion::leerJson();
        $nombre = Validacion::textoObligatorio($datos['nombre'] ?? null, 'El nombre completo es obligatorio');
        if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 100) {
            throw new ReglaNegocioException('El nombre completo debe tener entre 3 y 100 caracteres');
        }

        $correo = strtolower(Validacion::textoObligatorio($datos['correo'] ?? null, 'El correo electrónico es obligatorio'));
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || mb_strlen($correo) > 120) {
            throw new ReglaNegocioException('Ingresa un correo electrónico válido');
        }

        $usuario = Validacion::textoObligatorio($datos['usuario'] ?? null, 'El nombre de usuario es obligatorio');
        if (!preg_match('/^[A-Za-z0-9_.\-]{3,30}$/', $usuario)) {
            throw new ReglaNegocioException('El usuario debe tener entre 3 y 30 caracteres (letras, números, ., - o _)');
        }

        $password = (string) ($datos['password'] ?? '');
        $confirmar = (string) ($datos['confirmarPassword'] ?? '');
        if (strlen($password) < 8 || strlen($password) > 128) {
            throw new ReglaNegocioException('La contraseña debe tener entre 8 y 128 caracteres');
        }
        if ($password !== $confirmar) {
            throw new ReglaNegocioException('Las contraseñas no coinciden');
        }

        if ($this->usuarios->existeCorreoOUsuario($correo, $usuario)) {
            throw new ReglaNegocioException('El correo o nombre de usuario ya está registrado', 409);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $creado = $this->usuarios->crear($nombre, $correo, $usuario, $hash, 'MESERO');
        Respuesta::creado($creado);
    }

    public function login(): never
    {
        $datos = Validacion::leerJson();
        $identificador = Validacion::textoObligatorio(
            $datos['identificador'] ?? null,
            'El usuario o correo electrónico es obligatorio'
        );
        $password = (string) ($datos['password'] ?? '');
        if ($password === '') {
            throw new ReglaNegocioException('La contraseña es obligatoria');
        }

        $usuario = $this->usuarios->buscarPorIdentificador($identificador);
        if ($usuario === null || !$usuario['activo'] || !password_verify($password, $usuario['password_hash'])) {
            throw new ReglaNegocioException('Credenciales inválidas o usuario inactivo', 401);
        }

        Sesion::establecerUsuario($usuario['id']);
        unset($usuario['password_hash']);
        Respuesta::ok($usuario);
    }

    public function logout(): never
    {
        Sesion::destruir();
        Respuesta::sinContenido();
    }

    public function me(): never
    {
        $usuario = Sesion::usuarioActual($this->usuarios);
        if ($usuario === null) {
            Respuesta::error(401, 'No hay una sesión activa');
        }
        Respuesta::ok($usuario);
    }
}
