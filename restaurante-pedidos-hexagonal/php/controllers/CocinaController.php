<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Respuesta.php';
require_once __DIR__ . '/../core/Sesion.php';
require_once __DIR__ . '/../repositories/CocinaRepository.php';
require_once __DIR__ . '/../repositories/OrdenRepository.php';
require_once __DIR__ . '/../repositories/UsuarioRepository.php';

/**
 * RF3 (Cocinero): tablero de cocina y transición de preparación.
 */
final class CocinaController
{
    private CocinaRepository $cocina;
    private OrdenRepository $ordenes;
    private UsuarioRepository $usuarios;

    public function __construct(CocinaRepository $cocina, OrdenRepository $ordenes, UsuarioRepository $usuarios)
    {
        $this->cocina = $cocina;
        $this->ordenes = $ordenes;
        $this->usuarios = $usuarios;
    }

    public function listarPendientes(): never
    {
        Sesion::exigirRol($this->usuarios, ['COCINERO']);
        Respuesta::ok($this->cocina->listarPendientes());
    }

    public function listarTablero(): never
    {
        Sesion::exigirRol($this->usuarios, ['COCINERO']);
        Respuesta::ok($this->cocina->listarTablero());
    }

    public function iniciarCoccion(string $ordenId, string $lineaId): never
    {
        Sesion::exigirRol($this->usuarios, ['COCINERO']);
        $this->ordenes->iniciarCoccion(urldecode($ordenId), urldecode($lineaId));
        Respuesta::sinContenido();
    }

    public function marcarLista(string $ordenId, string $lineaId): never
    {
        Sesion::exigirRol($this->usuarios, ['COCINERO']);
        $this->ordenes->marcarLista(urldecode($ordenId), urldecode($lineaId));
        Respuesta::sinContenido();
    }
}
