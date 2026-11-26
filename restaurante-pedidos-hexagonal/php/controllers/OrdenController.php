<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/ReglaNegocioException.php';
require_once __DIR__ . '/../core/Respuesta.php';
require_once __DIR__ . '/../core/Sesion.php';
require_once __DIR__ . '/../core/Validacion.php';
require_once __DIR__ . '/../repositories/OrdenRepository.php';
require_once __DIR__ . '/../repositories/UsuarioRepository.php';

/**
 * RF2 y RF3 (Mesero): órdenes y líneas de pedido.
 */
final class OrdenController
{
    private OrdenRepository $ordenes;
    private UsuarioRepository $usuarios;

    public function __construct(OrdenRepository $ordenes, UsuarioRepository $usuarios)
    {
        $this->ordenes = $ordenes;
        $this->usuarios = $usuarios;
    }

    public function listar(): never
    {
        Sesion::exigirRol($this->usuarios, ['MESERO']);
        $estado = Validacion::enumeracion($_GET['estado'] ?? null, ['ABIERTA', 'CERRADA', 'CANCELADA'], 'estado', false);
        Respuesta::ok($this->ordenes->listar($estado));
    }

    public function consultar(string $ordenId): never
    {
        Sesion::exigirRol($this->usuarios, ['MESERO']);
        Respuesta::ok($this->ordenes->consultar(urldecode($ordenId)));
    }

    public function abrir(): never
    {
        $usuario = Sesion::exigirRol($this->usuarios, ['MESERO']);
        $datos = Validacion::leerJson();
        $tipo = Validacion::enumeracion($datos['tipo'] ?? null, ['SALA', 'PARA_LLEVAR', 'DELIVERY'], 'tipo');
        $referencia = Validacion::textoObligatorio(
            $datos['referencia'] ?? null,
            'La orden necesita una mesa o un identificador de pedido'
        );
        Respuesta::creado($this->ordenes->abrir($tipo, $referencia, $usuario['id']));
    }

    public function agregarLinea(string $ordenId): never
    {
        Sesion::exigirRol($this->usuarios, ['MESERO']);
        $datos = Validacion::leerJson();
        $platoId = Validacion::textoObligatorio($datos['platoId'] ?? null, 'El plato es obligatorio');
        $cantidad = Validacion::entero($datos['cantidad'] ?? null);
        $modIds = $this->extraerModificadorIds($datos);
        Respuesta::ok($this->ordenes->agregarLinea(urldecode($ordenId), $platoId, $cantidad, $modIds));
    }

    public function modificarLinea(string $ordenId, string $lineaId): never
    {
        Sesion::exigirRol($this->usuarios, ['MESERO']);
        $datos = Validacion::leerJson();
        $cantidad = Validacion::entero($datos['cantidad'] ?? null);
        $modIds = $this->extraerModificadorIds($datos);
        Respuesta::ok($this->ordenes->modificarLinea(urldecode($ordenId), urldecode($lineaId), $cantidad, $modIds));
    }

    public function cancelarLinea(string $ordenId, string $lineaId): never
    {
        Sesion::exigirRol($this->usuarios, ['MESERO']);
        Respuesta::ok($this->ordenes->cancelarLinea(urldecode($ordenId), urldecode($lineaId)));
    }

    public function cancelarOrden(string $ordenId): never
    {
        Sesion::exigirRol($this->usuarios, ['MESERO']);
        Respuesta::ok($this->ordenes->cancelarOrden(urldecode($ordenId)));
    }

    /** @return list<string> */
    private function extraerModificadorIds(array $datos): array
    {
        $crudo = $datos['modificadorIds'] ?? [];
        if (!is_array($crudo)) {
            throw new ReglaNegocioException('modificadorIds debe ser un arreglo');
        }
        return array_values(array_map(static fn(mixed $v): string => (string) $v, $crudo));
    }
}
