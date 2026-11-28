<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/ReglaNegocioException.php';
require_once __DIR__ . '/../core/Validacion.php';

/**
 * Repositorio PDO del catálogo de menú (platos y modificadores).
 * Usa borrado lógico (activo = 0) para preservar la integridad histórica de órdenes existentes.
 */
final class MenuRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** @return list<array<string, string>> */
    public function listarPlatos(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, nombre, categoria, imagen, precio_base, tasa_impuesto
             FROM platos
             WHERE activo = 1
             ORDER BY nombre ASC'
        );
        return array_map([$this, 'platoDto'], $stmt->fetchAll());
    }

    /** @return array<string, string> */
    public function registrarPlato(string $id, string $nombre, string $categoria, string $precioBase, string $tasaImpuesto): array
    {
        $existente = $this->buscarPlatoFila($id);
        if ($existente !== null) {
            if ((int) $existente['activo'] === 1) {
                throw new ReglaNegocioException("Ya existe un plato con id $id");
            }
            $stmt = $this->pdo->prepare(
                'UPDATE platos
                 SET nombre = :nombre, categoria =:categoria, precio_base = :precio, tasa_impuesto = :tasa, activo = 1
                 WHERE id = :id'
            );
            $stmt->execute([
                ':id'     => $id,
                ':nombre' => $nombre,
                ':categria' => $categoria,
                ':precio' => $precioBase,
                ':tasa'   => $tasaImpuesto,
            ]);
        } else {
            $stmt = $this->pdo->prepare(
                'INSERT INTO platos (id, nombre, categoria, precio_base, tasa_impuesto, activo)
                 VALUES (:id, :nombre, :categoria, :precio, :tasa, 1)'
            );
            $stmt->execute([
                ':id'     => $id,
                ':nombre' => $nombre,
                ':categoria' => $categoria,
                ':precio' => $precioBase,
                ':tasa'   => $tasaImpuesto,
            ]);
        }
        return $this->platoDto($this->buscarPlatoFila($id));
    }

    /** @return array<string, string> */
    public function actualizarPlato(string $id, string $nombre, string $categoria, string $precioBase, string $tasaImpuesto): array
    {
        $existente = $this->buscarPlatoFila($id);
        if ($existente === null || (int) $existente['activo'] !== 1) {
            throw new ReglaNegocioException("No existe el plato $id");
        }
        $stmt = $this->pdo->prepare(
            'UPDATE platos SET nombre = :nombre, categoria = :categoria, precio_base = :precio, tasa_impuesto = :tasa WHERE id = :id'
        );
        $stmt->execute([
            ':id'     => $id,
            ':nombre' => $nombre,
            ':categoria' => $categoria, 
            ':precio' => $precioBase,
            ':tasa'   => $tasaImpuesto,
        ]);
        return $this->platoDto($this->buscarPlatoFila($id));
    }

/** @return array<string, mixed> */
    public function actualizarImagenPlato(string $id, ?string $imagen): array
    {
        $existente = $this->buscarPlatoFila($id);
        if ($existente === null || (int) $existente['activo'] !== 1) {
            throw new ReglaNegocioException("No existe el plato $id");
        }
        $stmt = $this->pdo->prepare(
            'UPDATE platos SET imagen = :imagen WHERE id = :id'
        );
        $stmt->execute([
            ':id'     => $id,
            ':imagen' => $imagen,
        ]);
        return $this->platoDto($this->buscarPlatoFila($id));
    }

    public function eliminarPlato(string $id): void
    {
        $existente = $this->buscarPlatoFila($id);
        if ($existente === null || (int) $existente['activo'] !== 1) {
            throw new ReglaNegocioException("No existe el plato $id");
        }
        $stmt = $this->pdo->prepare('UPDATE platos SET activo = 0 WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    /** @return list<array<string, string>> */
    public function listarModificadores(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, nombre, tipo, costo_extra
             FROM modificadores
             WHERE activo = 1
             ORDER BY nombre ASC'
        );
        return array_map([$this, 'modificadorDto'], $stmt->fetchAll());
    }

    /** @return array<string, string> */
    public function registrarModificador(string $id, string $nombre, string $tipo, string $costoExtra): array
    {
        $existente = $this->buscarModificadorFila($id);
        if ($existente !== null) {
            if ((int) $existente['activo'] === 1) {
                throw new ReglaNegocioException("Ya existe un modificador con id $id");
            }
            $stmt = $this->pdo->prepare(
                'UPDATE modificadores
                 SET nombre = :nombre, tipo = :tipo, costo_extra = :costo, activo = 1
                 WHERE id = :id'
            );
            $stmt->execute([
                ':id'     => $id,
                ':nombre' => $nombre,
                ':tipo'   => $tipo,
                ':costo'  => $costoExtra,
            ]);
        } else {
            $stmt = $this->pdo->prepare(
                'INSERT INTO modificadores (id, nombre, tipo, costo_extra, activo)
                 VALUES (:id, :nombre, :tipo, :costo, 1)'
            );
            $stmt->execute([
                ':id'     => $id,
                ':nombre' => $nombre,
                ':tipo'   => $tipo,
                ':costo'  => $costoExtra,
            ]);
        }
        return $this->modificadorDto($this->buscarModificadorFila($id));
    }

    /** @return array<string, string> */
    public function actualizarModificador(string $id, string $nombre, string $tipo, string $costoExtra): array
    {
        $existente = $this->buscarModificadorFila($id);
        if ($existente === null || (int) $existente['activo'] !== 1) {
            throw new ReglaNegocioException("No existe el modificador $id");
        }
        $stmt = $this->pdo->prepare(
            'UPDATE modificadores SET nombre = :nombre, tipo = :tipo, costo_extra = :costo WHERE id = :id'
        );
        $stmt->execute([
            ':id'     => $id,
            ':nombre' => $nombre,
            ':tipo'   => $tipo,
            ':costo'  => $costoExtra,
        ]);
        return $this->modificadorDto($this->buscarModificadorFila($id));
    }

    public function eliminarModificador(string $id): void
    {
        $existente = $this->buscarModificadorFila($id);
        if ($existente === null || (int) $existente['activo'] !== 1) {
            throw new ReglaNegocioException("No existe el modificador $id");
        }
        $stmt = $this->pdo->prepare('UPDATE modificadores SET activo = 0 WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    private function buscarPlatoFila(string $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, nombre, categoria, imagen, precio_base, tasa_impuesto, activo FROM platos WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    private function buscarModificadorFila(string $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, nombre, tipo, costo_extra, activo FROM modificadores WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    private function platoDto(array $fila): array
    {
        return [
            'id'           => (string) $fila['id'],
            'nombre'       => (string) $fila['nombre'],
            'categoria'    => (string) $fila['categoria'],
            'imagen'       => $fila['imagen'] !==null ? (string) $fila['imagen']: null,
            'precioBase'   => Validacion::dinero($fila['precio_base']),
            'tasaImpuesto' => Validacion::tasa($fila['tasa_impuesto']),
        ];
    }

    private function modificadorDto(array $fila): array
    {
        return [
            'id'         => (string) $fila['id'],
            'nombre'     => (string) $fila['nombre'],
            'tipo'       => (string) $fila['tipo'],
            'costoExtra' => Validacion::dinero($fila['costo_extra']),
        ];
    }
}
