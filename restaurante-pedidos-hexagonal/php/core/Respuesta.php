<?php
declare(strict_types=1);

/**
 * Respuestas JSON UTF-8 uniformes según el contrato de la API.
 */
final class Respuesta
{
    private function __construct()
    {
    }

    public static function ok(mixed $datos): never
    {
        self::enviar(200, $datos);
    }

    public static function creado(mixed $datos): never
    {
        self::enviar(201, $datos);
    }

    public static function sinContenido(): never
    {
        header('Cache-Control: no-store');
        http_response_code(204);
        exit;
    }

    public static function error(int $codigo, string $mensaje): never
    {
        self::enviar($codigo, ['error' => $mensaje]);
    }

    private static function enviar(int $codigo, mixed $cuerpo): never
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        http_response_code($codigo);
        echo json_encode($cuerpo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
