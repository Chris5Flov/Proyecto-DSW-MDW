<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Respuesta.php';
require_once __DIR__ . '/../core/Sesion.php';
require_once __DIR__ . '/../core/Validacion.php';
require_once __DIR__ . '/../repositories/OrdenRepository.php';
require_once __DIR__ . '/../repositories/UsuarioRepository.php';

/**
 * RF4 y RF5 (Cajero): órdenes listas, cálculo de totales y cobro.
 */
final class CajaController
{
    private OrdenRepository $ordenes;
    private UsuarioRepository $usuarios;

    public function __construct(OrdenRepository $ordenes, UsuarioRepository $usuarios)
    {
        $this->ordenes = $ordenes;
        $this->usuarios = $usuarios;
    }

    public function listarOrdenesListas(): never
    {
        Sesion::exigirRol($this->usuarios, ['CAJERO']);
        Respuesta::ok($this->ordenes->listarListasParaCaja());
    }

    public function consultarOrden(string $ordenId): never
    {
        Sesion::exigirRol($this->usuarios, ['CAJERO']);
        Respuesta::ok($this->ordenes->consultar(urldecode($ordenId)));
    }

    public function calcularTotales(string $ordenId): never
    {
        Sesion::exigirRol($this->usuarios, ['CAJERO']);
        $descuento = Validacion::decimal($_GET['descuento'] ?? null, 'descuento', false);
        $propina = Validacion::decimal($_GET['propina'] ?? null, 'propina', false);
        Respuesta::ok($this->ordenes->calcularTotales(urldecode($ordenId), $descuento, $propina));
    }

    public function cobrar(string $ordenId): never
    {
        $cajero = Sesion::exigirRol($this->usuarios, ['CAJERO']);
        $datos = Validacion::leerJson();
        $metodo = Validacion::enumeracion(
            $datos['metodo'] ?? null,
            ['EFECTIVO', 'TARJETA', 'PASARELA_DIGITAL'],
            'metodo'
        );
        $descuento = Validacion::decimal($datos['descuento'] ?? null, 'descuento', false);
        $propina = Validacion::decimal($datos['propina'] ?? null, 'propina', false);
        Respuesta::ok($this->ordenes->cobrar(urldecode($ordenId), $metodo, $descuento, $propina, $cajero['id']));
    }
}
