<?php
declare(strict_types=1);

/**
 * Repositorio PDO especializado para el módulo de Cocina.
 */
final class CocinaRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Devuelve líneas PENDIENTE y EN_COCCION de órdenes ABIERTA (contrato /api/cocina/pendientes).
     * @return list<array<string, mixed>>
     */
    public function listarPendientes(): array
    {
        return $this->consultarLineasCocina(['PENDIENTE', 'EN_COCCION']);
    }

    /**
     * Devuelve todas las líneas del tablero (PENDIENTE, EN_COCCION, LISTO) y nombres de exclusiones.
     * @return array{lineas: list<array<string, mixed>>, exclusiones: list<string>}
     */
    public function listarTablero(): array
    {
        $lineas = $this->consultarLineasCocina(['PENDIENTE', 'EN_COCCION', 'LISTO']);
        $stmt = $this->pdo->query(
            "SELECT DISTINCT nombre FROM modificadores WHERE tipo = 'EXCLUSION'
             UNION
             SELECT DISTINCT nombre_aplicado FROM linea_modificadores WHERE tipo_aplicado = 'EXCLUSION'"
        );
        $exclusiones = array_map(static fn(array $f): string => (string) $f['nombre'], $stmt->fetchAll());

        return [
            'lineas'      => $lineas,
            'exclusiones' => $exclusiones,
        ];
    }

    /** @param list<string> $estados @return list<array<string, mixed>> */
    private function consultarLineasCocina(array $estados): array
    {
        $placeholders = implode(',', array_fill(0, count($estados), '?'));
        $sql = "SELECT lp.id AS linea_id, lp.orden_id, o.referencia AS referencia_orden,
                       lp.plato_nombre, lp.cantidad, lp.estado_cocina
                FROM lineas_pedido lp
                INNER JOIN ordenes o ON o.id = lp.orden_id
                WHERE o.estado = 'ABIERTA'
                  AND lp.estado_cocina IN ($placeholders)
                ORDER BY o.fecha_creacion ASC, lp.numero_linea ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($estados);
        $filas = $stmt->fetchAll();

        if ($filas === []) {
            return [];
        }

        $lineaIds = array_column($filas, 'linea_id');
        $phMods = implode(',', array_fill(0, count($lineaIds), '?'));
        $stmtMods = $this->pdo->prepare(
            "SELECT linea_id, nombre_aplicado FROM linea_modificadores WHERE linea_id IN ($phMods) ORDER BY id ASC"
        );
        $stmtMods->execute($lineaIds);
        $modsPorLinea = [];
        foreach ($stmtMods->fetchAll() as $m) {
            $modsPorLinea[$m['linea_id']][] = (string) $m['nombre_aplicado'];
        }

        return array_map(static fn(array $f): array => [
            'ordenId'         => (string) $f['orden_id'],
            'lineaId'         => (string) $f['linea_id'],
            'referenciaOrden' => (string) $f['referencia_orden'],
            'plato'           => (string) $f['plato_nombre'],
            'cantidad'        => (int) $f['cantidad'],
            'modificadores'   => $modsPorLinea[$f['linea_id']] ?? [],
            'estado'          => (string) $f['estado_cocina'],
        ], $filas);
    }
}
