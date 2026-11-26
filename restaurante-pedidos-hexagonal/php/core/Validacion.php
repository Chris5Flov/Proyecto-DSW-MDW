<?php
declare(strict_types=1);

require_once __DIR__ . '/ReglaNegocioException.php';

/**
 * Validación, sanitización y formateo determinista de entradas/salidas.
 */
final class Validacion
{
    private function __construct()
    {
    }

    /** @return array<string, mixed> */
    public static function leerJson(): array
    {
        $crudo = file_get_contents('php://input');
        if ($crudo === false || trim($crudo) === '') {
            throw new ReglaNegocioException('La petición necesita un cuerpo JSON');
        }
        $datos = json_decode($crudo, true);
        if (!is_array($datos)) {
            throw new ReglaNegocioException('El cuerpo de la petición no es un JSON válido');
        }
        return $datos;
    }

    public static function textoObligatorio(mixed $valor, string $mensaje): string
    {
        if (!is_string($valor) && !is_numeric($valor)) {
            throw new ReglaNegocioException($mensaje);
        }
        $limpio = trim((string) $valor);
        if ($limpio === '') {
            throw new ReglaNegocioException($mensaje);
        }
        return $limpio;
    }

    public static function decimal(mixed $valor, string $campo, bool $obligatorio = false): ?string
    {
        if ($valor === null || (is_string($valor) && trim($valor) === '')) {
            if ($obligatorio) {
                throw new ReglaNegocioException("El campo $campo es obligatorio");
            }
            return null;
        }
        $texto = str_replace(',', '.', trim((string) $valor));
        if (!preg_match('/^-?\d+(?:\.\d+)?$/', $texto)) {
            throw new ReglaNegocioException("El campo $campo debe ser un número (por ejemplo \"85.50\")");
        }
        return $texto;
    }

    public static function maximoDosDecimales(string $numero, string $mensaje): void
    {
        $partes = explode('.', $numero, 2);
        $decimales = isset($partes[1]) ? rtrim($partes[1], '0') : '';
        if (strlen($decimales) > 2) {
            throw new ReglaNegocioException($mensaje);
        }
    }

    public static function entero(mixed $valor): int
    {
        if (is_int($valor)) {
            return $valor;
        }
        if (is_string($valor) && preg_match('/^-?\d+$/', trim($valor))) {
            return (int) trim($valor);
        }
        if (is_float($valor) && floor($valor) === $valor) {
            return (int) $valor;
        }
        return 0;
    }

    /** @param list<string> $permitidos */
    public static function enumeracion(mixed $valor, array $permitidos, string $campo, bool $obligatorio = true): ?string
    {
        if ($valor === null || (is_string($valor) && trim($valor) === '')) {
            if ($obligatorio) {
                throw new ReglaNegocioException("El campo $campo es obligatorio");
            }
            return null;
        }
        $texto = trim((string) $valor);
        if (!in_array($texto, $permitidos, true)) {
            throw new ReglaNegocioException(
                "Valor no válido para $campo: \"$texto\". Valores permitidos: " . implode(', ', $permitidos)
            );
        }
        return $texto;
    }

    /** Redondeo HALF_UP a $decimales usando BCMath. */
    public static function redondearHalfUp(string $numero, int $decimales = 2): string
    {
        $negativo = str_starts_with($numero, '-');
        $abs = $negativo ? substr($numero, 1) : $numero;
        $ajuste = '0.' . str_repeat('0', $decimales) . '5';
        $truncado = bcadd($abs, $ajuste, $decimales);
        return ($negativo && bccomp($truncado, '0', $decimales) !== 0 ? '-' : '') . $truncado;
    }

    public static function dinero(string|int|float $valor): string
    {
        return self::redondearHalfUp((string) $valor, 2);
    }

    public static function tasa(string|int|float $valor): string
    {
        $texto = (string) $valor;
        if (bccomp($texto, '0', 6) === 0) {
            return '0';
        }
        $limpio = rtrim(rtrim($texto, '0'), '.');
        return $limpio === '' ? '0' : $limpio;
    }

    public static function fechaIso(string $mysqlDatetime): string
    {
        return str_replace(' ', 'T', substr($mysqlDatetime, 0, 19));
    }

    public static function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
