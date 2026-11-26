<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/CalculadoraTotales.php';
require_once __DIR__ . '/../core/ReglaNegocioException.php';
require_once __DIR__ . '/../core/Validacion.php';

/**
 * Repositorio transaccional PDO de órdenes, líneas, modificadores aplicados y pagos.
 */
final class OrdenRepository
{
    private const LIMITE_SIMULADO = '20000.00';

    private const PREFIJOS_PAGO = [
        'EFECTIVO'         => 'EFE-',
        'TARJETA'          => 'TAR-',
        'PASARELA_DIGITAL' => 'PAS-',
    ];

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** @return list<array<string, mixed>> */
    public function listar(?string $estado = null): array
    {
        if ($estado !== null) {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM ordenes WHERE estado = :estado ORDER BY fecha_creacion ASC, id ASC'
            );
            $stmt->execute([':estado' => $estado]);
        } else {
            $stmt = $this->pdo->query('SELECT * FROM ordenes ORDER BY fecha_creacion ASC, id ASC');
        }
        return $this->hidratarOrdenes($stmt->fetchAll());
    }

    /** Órdenes abiertas listas para entrega (al menos 1 línea activa y todas en LISTO). */
    public function listarListasParaCaja(): array
    {
        $abiertas = $this->listar('ABIERTA');
        return array_values(array_filter($abiertas, static function (array $orden): bool {
            $activas = array_filter(
                $orden['lineas'],
                static fn(array $l): bool => $l['estadoCocina'] !== 'CANCELADA'
            );
            if ($activas === []) {
                return false;
            }
            foreach ($activas as $l) {
                if ($l['estadoCocina'] !== 'LISTO') {
                    return false;
                }
            }
            return true;
        }));
    }

    /** @return array<string, mixed> */
    public function consultar(string $ordenId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM ordenes WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $ordenId]);
        $fila = $stmt->fetch();
        if (!$fila) {
            throw new ReglaNegocioException("No existe la orden $ordenId");
        }
        return $this->hidratarOrdenes([$fila])[0];
    }

    /** @return array<string, mixed> */
    public function abrir(string $tipo, string $referencia, int $usuarioId): array
    {
        $id = Validacion::uuid();
        $stmt = $this->pdo->prepare(
            'INSERT INTO ordenes (id, tipo, referencia, estado, usuario_creador_id)
             VALUES (:id, :tipo, :referencia, :estado, :usuario_id)'
        );
        $stmt->execute([
            ':id'         => $id,
            ':tipo'       => $tipo,
            ':referencia' => $referencia,
            ':estado'     => 'ABIERTA',
            ':usuario_id' => $usuarioId,
        ]);
        return $this->consultar($id);
    }

    /** @param list<string> $modificadorIds */
    public function agregarLinea(string $ordenId, string $platoId, int $cantidad, array $modificadorIds): array
    {
        if ($cantidad <= 0) {
            throw new ReglaNegocioException('La cantidad debe ser mayor a cero');
        }

        return $this->enTransaccion(function () use ($ordenId, $platoId, $cantidad, $modificadorIds): array {
            $this->bloquearOrdenAbierta($ordenId);
            $plato = $this->buscarPlatoActivo($platoId);
            $modificadores = $this->buscarModificadoresActivos($modificadorIds);

            $lineaId = Validacion::uuid();
            $stmt = $this->pdo->prepare(
                'INSERT INTO lineas_pedido
                    (id, orden_id, plato_id, plato_nombre, cantidad, precio_unitario, tasa_impuesto_aplicada, estado_cocina)
                 VALUES
                    (:id, :orden_id, :plato_id, :plato_nombre, :cantidad, :precio, :tasa, :estado)'
            );
            $stmt->execute([
                ':id'           => $lineaId,
                ':orden_id'     => $ordenId,
                ':plato_id'     => $plato['id'],
                ':plato_nombre' => $plato['nombre'],
                ':cantidad'     => $cantidad,
                ':precio'       => $plato['precio_base'],
                ':tasa'         => $plato['tasa_impuesto'],
                ':estado'       => 'PENDIENTE',
            ]);

            $this->insertarModificadoresLinea($lineaId, $modificadores);
            return $this->consultar($ordenId);
        });
    }

    /** @param list<string> $modificadorIds */
    public function modificarLinea(string $ordenId, string $lineaId, int $cantidad, array $modificadorIds): array
    {
        if ($cantidad <= 0) {
            throw new ReglaNegocioException('La cantidad debe ser mayor a cero');
        }

        return $this->enTransaccion(function () use ($ordenId, $lineaId, $cantidad, $modificadorIds): array {
            $this->bloquearOrdenAbierta($ordenId);
            $linea = $this->bloquearLinea($ordenId, $lineaId);
            if ($linea['estado_cocina'] !== 'PENDIENTE') {
                throw new ReglaNegocioException(
                    'No se puede modificar la línea: solo se permite mientras está PENDIENTE (estado actual: '
                    . $linea['estado_cocina'] . ')'
                );
            }

            $modificadores = $this->buscarModificadoresActivos($modificadorIds);

            $stmt = $this->pdo->prepare('UPDATE lineas_pedido SET cantidad = :cantidad WHERE id = :id');
            $stmt->execute([':cantidad' => $cantidad, ':id' => $lineaId]);

            $this->pdo->prepare('DELETE FROM linea_modificadores WHERE linea_id = :linea_id')
                ->execute([':linea_id' => $lineaId]);
            $this->insertarModificadoresLinea($lineaId, $modificadores);

            return $this->consultar($ordenId);
        });
    }

    public function cancelarLinea(string $ordenId, string $lineaId): array
    {
        return $this->enTransaccion(function () use ($ordenId, $lineaId): array {
            $this->bloquearOrdenAbierta($ordenId);
            $linea = $this->bloquearLinea($ordenId, $lineaId);
            if ($linea['estado_cocina'] !== 'PENDIENTE') {
                throw new ReglaNegocioException(
                    'No se puede cancelar la línea: solo se permite mientras está PENDIENTE (estado actual: '
                    . $linea['estado_cocina'] . ')'
                );
            }

            $this->pdo->prepare("UPDATE lineas_pedido SET estado_cocina = 'CANCELADA' WHERE id = :id")
                ->execute([':id' => $lineaId]);

            return $this->consultar($ordenId);
        });
    }

    public function cancelarOrden(string $ordenId): array
    {
        return $this->enTransaccion(function () use ($ordenId): array {
            $this->bloquearOrdenAbierta($ordenId);

            if ($this->tienePagoAprobado($ordenId)) {
                throw new ReglaNegocioException('No se puede cancelar una orden con un pago aprobado');
            }

            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM lineas_pedido
                 WHERE orden_id = :orden_id AND estado_cocina IN ('EN_COCCION', 'LISTO')"
            );
            $stmt->execute([':orden_id' => $ordenId]);
            if ((int) $stmt->fetchColumn() > 0) {
                throw new ReglaNegocioException('No se puede cancelar la orden: hay líneas en cocina o listas');
            }

            $this->pdo->prepare(
                "UPDATE lineas_pedido SET estado_cocina = 'CANCELADA'
                 WHERE orden_id = :orden_id AND estado_cocina = 'PENDIENTE'"
            )->execute([':orden_id' => $ordenId]);

            $this->pdo->prepare("UPDATE ordenes SET estado = 'CANCELADA' WHERE id = :id")
                ->execute([':id' => $ordenId]);

            return $this->consultar($ordenId);
        });
    }

    public function iniciarCoccion(string $ordenId, string $lineaId): void
    {
        $this->enTransaccion(function () use ($ordenId, $lineaId): void {
            $this->bloquearOrdenAbierta($ordenId);
            $linea = $this->bloquearLinea($ordenId, $lineaId);
            if ($linea['estado_cocina'] !== 'PENDIENTE') {
                throw new ReglaNegocioException(
                    'Solo se puede iniciar la cocción de una línea PENDIENTE (estado actual: '
                    . $linea['estado_cocina'] . ')'
                );
            }
            $this->pdo->prepare("UPDATE lineas_pedido SET estado_cocina = 'EN_COCCION' WHERE id = :id")
                ->execute([':id' => $lineaId]);
        });
    }

    public function marcarLista(string $ordenId, string $lineaId): void
    {
        $this->enTransaccion(function () use ($ordenId, $lineaId): void {
            $this->bloquearOrdenAbierta($ordenId);
            $linea = $this->bloquearLinea($ordenId, $lineaId);
            if ($linea['estado_cocina'] !== 'EN_COCCION') {
                throw new ReglaNegocioException(
                    'Solo se puede marcar como lista una línea EN_COCCION (estado actual: '
                    . $linea['estado_cocina'] . ')'
                );
            }
            $this->pdo->prepare("UPDATE lineas_pedido SET estado_cocina = 'LISTO' WHERE id = :id")
                ->execute([':id' => $lineaId]);
        });
    }

    /** @return array{subtotal:string, descuento:string, impuestos:string, propina:string, total:string} */
    public function calcularTotales(string $ordenId, ?string $descuento, ?string $propina): array
    {
        $this->consultar($ordenId);
        $activas = $this->cargarLineasActivasCrudas($ordenId);
        return CalculadoraTotales::calcular($activas, $descuento, $propina);
    }

    /** @return array<string, string> */
    public function cobrar(string $ordenId, string $metodo, ?string $descuento, ?string $propina, int $cajeroId): array
    {
        return $this->enTransaccion(function () use ($ordenId, $metodo, $descuento, $propina, $cajeroId): array {
            $this->bloquearOrdenAbierta($ordenId);
            $activas = $this->cargarLineasActivasCrudas($ordenId);
            if ($activas === []) {
                throw new ReglaNegocioException('No se puede cobrar una orden sin líneas');
            }
            if ($this->tienePagoAprobado($ordenId)) {
                throw new ReglaNegocioException('La orden ya tiene un pago aprobado');
            }

            $totales = CalculadoraTotales::calcular($activas, $descuento, $propina);
            $rechazado = $metodo !== 'EFECTIVO' && bccomp($totales['total'], self::LIMITE_SIMULADO, 2) > 0;
            $estadoPago = $rechazado ? 'RECHAZADO' : 'APROBADO';

            $pagoId = Validacion::uuid();
            $referencia = self::PREFIJOS_PAGO[$metodo] . substr(str_replace('-', '', Validacion::uuid()), 0, 8);
            $fecha = date('Y-m-d H:i:s');

            $stmtPago = $this->pdo->prepare(
                'INSERT INTO pagos (id, orden_id, fecha, estado, metodo, monto, referencia, usuario_cajero_id)
                 VALUES (:id, :orden_id, :fecha, :estado, :metodo, :monto, :referencia, :cajero_id)'
            );
            $stmtPago->execute([
                ':id'         => $pagoId,
                ':orden_id'   => $ordenId,
                ':fecha'      => $fecha,
                ':estado'     => $estadoPago,
                ':metodo'     => $metodo,
                ':monto'      => $totales['total'],
                ':referencia' => $referencia,
                ':cajero_id'  => $cajeroId,
            ]);

            if (!$rechazado) {
                $stmtOrden = $this->pdo->prepare(
                    "UPDATE ordenes
                     SET estado = 'CERRADA',
                         subtotal_cerrado = :subtotal,
                         descuento_cerrado = :descuento,
                         impuestos_cerrados = :impuestos,
                         propina_cerrada = :propina,
                         total_cerrado = :total
                     WHERE id = :id"
                );
                $stmtOrden->execute([
                    ':subtotal'  => $totales['subtotal'],
                    ':descuento' => $totales['descuento'],
                    ':impuestos' => $totales['impuestos'],
                    ':propina'   => $totales['propina'],
                    ':total'     => $totales['total'],
                    ':id'        => $ordenId,
                ]);
            }

            return [
                'id'         => $pagoId,
                'ordenId'    => $ordenId,
                'fecha'      => Validacion::fechaIso($fecha),
                'estado'     => $estadoPago,
                'metodo'     => $metodo,
                'monto'      => $totales['total'],
                'referencia' => $referencia,
            ];
        });
    }

    // ---------------------------------------------------------------- ayudas privadas

    /** @template T @param callable():T $fn @return T */
    private function enTransaccion(callable $fn): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $resultado = $fn();
            $this->pdo->commit();
            return $resultado;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    private function bloquearOrdenAbierta(string $ordenId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM ordenes WHERE id = :id FOR UPDATE');
        $stmt->execute([':id' => $ordenId]);
        $orden = $stmt->fetch();
        if (!$orden) {
            throw new ReglaNegocioException("No existe la orden $ordenId");
        }
        if ($orden['estado'] !== 'ABIERTA') {
            throw new ReglaNegocioException('La orden no está abierta (estado: ' . $orden['estado'] . ')');
        }
        return $orden;
    }

    private function bloquearLinea(string $ordenId, string $lineaId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM lineas_pedido WHERE id = :id AND orden_id = :orden_id FOR UPDATE'
        );
        $stmt->execute([':id' => $lineaId, ':orden_id' => $ordenId]);
        $linea = $stmt->fetch();
        if (!$linea) {
            throw new ReglaNegocioException("No existe la línea $lineaId");
        }
        return $linea;
    }

    private function tienePagoAprobado(string $ordenId): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT 1 FROM pagos WHERE orden_id = :orden_id AND estado = 'APROBADO' LIMIT 1"
        );
        $stmt->execute([':orden_id' => $ordenId]);
        return (bool) $stmt->fetchColumn();
    }

    private function buscarPlatoActivo(string $platoId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, nombre, precio_base, tasa_impuesto FROM platos WHERE id = :id AND activo = 1 LIMIT 1');
        $stmt->execute([':id' => $platoId]);
        $plato = $stmt->fetch();
        if (!$plato) {
            throw new ReglaNegocioException("No existe el plato $platoId");
        }
        return $plato;
    }

    /** @param list<string> $ids @return list<array<string, mixed>> */
    private function buscarModificadoresActivos(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $resultado = [];
        $stmt = $this->pdo->prepare('SELECT id, nombre, tipo, costo_extra FROM modificadores WHERE id = :id AND activo = 1 LIMIT 1');
        foreach ($ids as $id) {
            $stmt->execute([':id' => $id]);
            $mod = $stmt->fetch();
            if (!$mod) {
                throw new ReglaNegocioException("No existe el modificador $id");
            }
            $resultado[] = $mod;
        }
        return $resultado;
    }

    /** @param list<array<string, mixed>> $modificadores */
    private function insertarModificadoresLinea(string $lineaId, array $modificadores): void
    {
        if ($modificadores === []) {
            return;
        }
        $stmt = $this->pdo->prepare(
            'INSERT INTO linea_modificadores (linea_id, modificador_id, nombre_aplicado, tipo_aplicado, costo_extra_aplicado)
             VALUES (:linea_id, :mod_id, :nombre, :tipo, :costo)'
        );
        foreach ($modificadores as $mod) {
            $stmt->execute([
                ':linea_id' => $lineaId,
                ':mod_id'   => $mod['id'],
                ':nombre'   => $mod['nombre'],
                ':tipo'     => $mod['tipo'],
                ':costo'    => $mod['costo_extra'],
            ]);
        }
    }

    /** @return list<array<string, mixed>> */
    private function cargarLineasActivasCrudas(string $ordenId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM lineas_pedido WHERE orden_id = :orden_id AND estado_cocina <> 'CANCELADA' ORDER BY numero_linea ASC"
        );
        $stmt->execute([':orden_id' => $ordenId]);
        $lineas = $stmt->fetchAll();
        if ($lineas === []) {
            return [];
        }
        $modsPorLinea = $this->cargarModificadoresPorLineas(array_column($lineas, 'id'));
        foreach ($lineas as &$l) {
            $l['modificadores'] = $modsPorLinea[$l['id']] ?? [];
        }
        return $lineas;
    }

    /** @param list<array<string, mixed>> $ordenes @return list<array<string, mixed>> */
    private function hidratarOrdenes(array $ordenes): array
    {
        if ($ordenes === []) {
            return [];
        }
        $ordenIds = array_column($ordenes, 'id');
        $placeholders = implode(',', array_fill(0, count($ordenIds), '?'));

        $stmtLineas = $this->pdo->prepare(
            "SELECT * FROM lineas_pedido WHERE orden_id IN ($placeholders) ORDER BY numero_linea ASC"
        );
        $stmtLineas->execute($ordenIds);
        $todasLineas = $stmtLineas->fetchAll();

        $modsPorLinea = $this->cargarModificadoresPorLineas(array_column($todasLineas, 'id'));

        $lineasPorOrden = [];
        foreach ($todasLineas as $l) {
            $l['modificadores'] = $modsPorLinea[$l['id']] ?? [];
            $lineasPorOrden[$l['orden_id']][] = $l;
        }

        $stmtPagos = $this->pdo->prepare(
            "SELECT * FROM pagos WHERE orden_id IN ($placeholders) ORDER BY fecha ASC, id ASC"
        );
        $stmtPagos->execute($ordenIds);
        $pagosPorOrden = [];
        foreach ($stmtPagos->fetchAll() as $p) {
            $pagosPorOrden[$p['orden_id']][] = [
                'id'         => (string) $p['id'],
                'ordenId'    => (string) $p['orden_id'],
                'fecha'      => Validacion::fechaIso((string) $p['fecha']),
                'estado'     => (string) $p['estado'],
                'metodo'     => (string) $p['metodo'],
                'monto'      => Validacion::dinero($p['monto']),
                'referencia' => $p['referencia'] !== null ? (string) $p['referencia'] : null,
            ];
        }

        $resultado = [];
        foreach ($ordenes as $o) {
            $lineasCrudas = $lineasPorOrden[$o['id']] ?? [];
            $abierta = $o['estado'] === 'ABIERTA';

            $lineasDto = array_map(static function (array $l) use ($abierta): array {
                return [
                    'id'             => (string) $l['id'],
                    'platoId'        => (string) $l['plato_id'],
                    'platoNombre'    => (string) $l['plato_nombre'],
                    'cantidad'       => (int) $l['cantidad'],
                    'precioUnitario' => Validacion::dinero($l['precio_unitario']),
                    'tasaImpuesto'   => Validacion::tasa($l['tasa_impuesto_aplicada']),
                    'modificadores'  => array_map(static fn(array $m): array => [
                        'id'         => (string) $m['modificador_id'],
                        'nombre'     => (string) $m['nombre_aplicado'],
                        'tipo'       => (string) $m['tipo_aplicado'],
                        'costoExtra' => Validacion::dinero($m['costo_extra_aplicado']),
                    ], $l['modificadores']),
                    'estadoCocina'   => (string) $l['estado_cocina'],
                    'editable'       => $abierta && $l['estado_cocina'] === 'PENDIENTE',
                ];
            }, $lineasCrudas);

            if ($o['estado'] === 'CERRADA' && $o['total_cerrado'] !== null) {
                $totalesDto = [
                    'subtotal'  => Validacion::dinero($o['subtotal_cerrado']),
                    'descuento' => Validacion::dinero($o['descuento_cerrado']),
                    'impuestos' => Validacion::dinero($o['impuestos_cerrados']),
                    'propina'   => Validacion::dinero($o['propina_cerrada']),
                    'total'     => Validacion::dinero($o['total_cerrado']),
                ];
            } else {
                $activas = array_values(array_filter(
                    $lineasCrudas,
                    static fn(array $l): bool => $l['estado_cocina'] !== 'CANCELADA'
                ));
                $totalesDto = CalculadoraTotales::calcular($activas, '0', '0');
            }

            $resultado[] = [
                'id'            => (string) $o['id'],
                'tipo'          => (string) $o['tipo'],
                'referencia'    => (string) $o['referencia'],
                'estado'        => (string) $o['estado'],
                'fechaCreacion' => Validacion::fechaIso((string) $o['fecha_creacion']),
                'lineas'        => $lineasDto,
                'totales'       => $totalesDto,
                'pagos'         => $pagosPorOrden[$o['id']] ?? [],
            ];
        }
        return $resultado;
    }

    /** @param list<string> $lineaIds @return array<string, list<array<string, mixed>>> */
    private function cargarModificadoresPorLineas(array $lineaIds): array
    {
        if ($lineaIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($lineaIds), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT * FROM linea_modificadores WHERE linea_id IN ($placeholders) ORDER BY id ASC"
        );
        $stmt->execute($lineaIds);
        $agrupados = [];
        foreach ($stmt->fetchAll() as $m) {
            $agrupados[$m['linea_id']][] = $m;
        }
        return $agrupados;
    }
}
