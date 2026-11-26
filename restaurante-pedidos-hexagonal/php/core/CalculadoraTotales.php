<?php
declare(strict_types=1);

require_once __DIR__ . '/ReglaNegocioException.php';
require_once __DIR__ . '/Validacion.php';

/**
 * RF4: Cálculo determinista de subtotal, descuento, impuestos proporcionales por línea,
 * propina y total, usando BCMath con redondeo HALF_UP a 2 decimales.
 */
final class CalculadoraTotales
{
    private function __construct()
    {
    }

    /**
     * @param list<array<string, mixed>> $lineasActivas
     * @return array{subtotal: string, descuento: string, impuestos: string, propina: string, total: string}
     */
    public static function calcular(array $lineasActivas, ?string $descuento, ?string $propina): array
    {
        $descuentoFinal = Validacion::redondearHalfUp($descuento ?? '0', 2);
        $propinaFinal = Validacion::redondearHalfUp($propina ?? '0', 2);

        if (bccomp($descuentoFinal, '0', 2) < 0) {
            throw new ReglaNegocioException('El descuento no puede ser negativo');
        }
        if (bccomp($propinaFinal, '0', 2) < 0) {
            throw new ReglaNegocioException('La propina no puede ser negativa');
        }

        $importes = [];
        $subtotal = '0.00';

        foreach ($lineasActivas as $linea) {
            $unitario = (string) $linea['precio_unitario'];
            foreach ($linea['modificadores'] as $mod) {
                $unitario = bcadd($unitario, (string) $mod['costo_extra_aplicado'], 4);
            }
            $importeBruto = bcmul($unitario, (string) $linea['cantidad'], 4);
            $importeRedondeado = Validacion::redondearHalfUp($importeBruto, 2);
            $importes[] = [
                'importe' => $importeRedondeado,
                'tasa' => (string) $linea['tasa_impuesto_aplicada'],
            ];
            $subtotal = bcadd($subtotal, $importeRedondeado, 2);
        }

        $subtotal = Validacion::redondearHalfUp($subtotal, 2);
        if (bccomp($descuentoFinal, $subtotal, 2) > 0) {
            throw new ReglaNegocioException('El descuento no puede ser mayor al subtotal');
        }

        $baseGravable = bcsub($subtotal, $descuentoFinal, 2);
        $impuestos = '0.00';

        if (bccomp($subtotal, '0', 2) > 0) {
            foreach ($importes as $item) {
                $numerador = bcmul(bcmul($item['importe'], $item['tasa'], 8), $baseGravable, 10);
                $impuestoLinea = Validacion::redondearHalfUp(bcdiv($numerador, $subtotal, 10), 2);
                $impuestos = bcadd($impuestos, $impuestoLinea, 2);
            }
        }
        $impuestos = Validacion::redondearHalfUp($impuestos, 2);
        $total = Validacion::redondearHalfUp(bcadd(bcadd($baseGravable, $impuestos, 2), $propinaFinal, 2), 2);

        return [
            'subtotal'  => $subtotal,
            'descuento' => $descuentoFinal,
            'impuestos' => $impuestos,
            'propina'   => $propinaFinal,
            'total'     => $total,
        ];
    }
}
