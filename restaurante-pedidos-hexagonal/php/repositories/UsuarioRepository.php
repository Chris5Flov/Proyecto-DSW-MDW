<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/ReglaNegocioException.php';

/**
 * Repositorio PDO de usuarios y roles.
 */
final class UsuarioRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** @return array{id:int, usuario:string, nombre:string, correo:string, password_hash:string, rol:string, activo:bool}|null */
    public function buscarPorIdentificador(string $identificador): ?array
    {
        $sql = 'SELECT u.id, u.usuario, u.nombre, u.correo, u.password_hash, r.nombre AS rol, u.activo
                FROM usuarios u
                INNER JOIN roles r ON r.id = u.rol_id
                WHERE u.usuario = :u OR u.correo = :c
                LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':u' => $identificador, ':c' => $identificador]);
        $fila = $stmt->fetch();
        return $fila ? $this->mapear($fila, true) : null;
    }

    /** @return array{id:int, usuario:string, nombre:string, correo:string, rol:string, activo:bool}|null */
    public function buscarPorId(int $id): ?array
    {
        $sql = 'SELECT u.id, u.usuario, u.nombre, u.correo, r.nombre AS rol, u.activo
                FROM usuarios u
                INNER JOIN roles r ON r.id = u.rol_id
                WHERE u.id = :id
                LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila ? $this->mapear($fila, false) : null;
    }

    public function existeCorreoOUsuario(string $correo, string $usuario): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM usuarios WHERE correo = :correo OR usuario = :usuario LIMIT 1');
        $stmt->execute([':correo' => $correo, ':usuario' => $usuario]);
        return (bool) $stmt->fetchColumn();
    }

    /** @return array{id:int, usuario:string, nombre:string, correo:string, rol:string, activo:bool} */
    public function crear(string $nombre, string $correo, string $usuario, string $passwordHash, string $rolNombre): array
    {
        $rolId = $this->obtenerRolId($rolNombre);
        $sql = 'INSERT INTO usuarios (usuario, nombre, correo, password_hash, rol_id, activo)
                VALUES (:usuario, :nombre, :correo, :hash, :rol_id, 1)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':usuario' => $usuario,
            ':nombre'  => $nombre,
            ':correo'  => $correo,
            ':hash'    => $passwordHash,
            ':rol_id'  => $rolId,
        ]);
        $nuevoId = (int) $this->pdo->lastInsertId();
        return $this->buscarPorId($nuevoId) ?? throw new RuntimeException('No se pudo recuperar el usuario creado');
    }

    /** @return list<array{id:int, usuario:string, nombre:string, correo:string, rol:string, activo:bool}> */
    public function listar(): array
    {
        $sql = 'SELECT u.id, u.usuario, u.nombre, u.correo, r.nombre AS rol, u.activo
                FROM usuarios u
                INNER JOIN roles r ON r.id = u.rol_id
                ORDER BY u.id ASC';
        $filas = $this->pdo->query($sql)->fetchAll();
        return array_map(fn(array $f) => $this->mapear($f, false), $filas);
    }

    /** @return array{id:int, usuario:string, nombre:string, correo:string, rol:string, activo:bool} */
    public function actualizarRolYEstado(int $id, string $rolNombre, bool $activo): array
    {
        if ($this->buscarPorId($id) === null) {
            throw new ReglaNegocioException("No existe el usuario $id", 404);
        }
        $rolId = $this->obtenerRolId($rolNombre);
        $stmt = $this->pdo->prepare('UPDATE usuarios SET rol_id = :rol_id, activo = :activo WHERE id = :id');
        $stmt->execute([
            ':rol_id' => $rolId,
            ':activo' => $activo ? 1 : 0,
            ':id'     => $id,
        ]);
        return $this->buscarPorId($id);
    }

    private function obtenerRolId(string $rolNombre): int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM roles WHERE nombre = :nombre LIMIT 1');
        $stmt->execute([':nombre' => $rolNombre]);
        $id = $stmt->fetchColumn();
        if ($id === false) {
            throw new ReglaNegocioException("Rol no válido: $rolNombre");
        }
        return (int) $id;
    }

    private function mapear(array $fila, bool $incluirHash): array
    {
        $datos = [
            'id'      => (int) $fila['id'],
            'usuario' => (string) $fila['usuario'],
            'nombre'  => (string) $fila['nombre'],
            'correo'  => (string) $fila['correo'],
            'rol'     => (string) $fila['rol'],
            'activo'  => (bool) $fila['activo'],
        ];
        if ($incluirHash) {
            $datos['password_hash'] = (string) $fila['password_hash'];
        }
        return $datos;
    }
}
