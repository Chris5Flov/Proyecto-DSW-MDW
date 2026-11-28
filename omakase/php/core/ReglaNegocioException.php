<?php
declare(strict_types=1);

final class ReglaNegocioException extends RuntimeException
{
    private int $codigoHttp;

    public function __construct(string $mensaje, int $codigoHttp = 400)
    {
        parent::__construct($mensaje);
        $this->codigoHttp = $codigoHttp;
    }

    public function codigoHttp(): int
    {
        return $this->codigoHttp;
    }
}
