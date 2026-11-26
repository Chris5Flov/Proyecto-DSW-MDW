// Componentes compartidos para mostrar desglose de totales e historial de pagos.

import { crear } from "../util/dom.js";
import { formatoDinero, formatoFecha, etiqueta } from "../util/formato.js";
import { insigniaEstado } from "./insignia.js";

export function resumenTotales(totales) {
    if (!totales) {
        return crear("p", { class: "texto-secundario" }, "Sin totales.");
    }
    return crear("dl", { class: "resumen-totales" },
        crear("dt", {}, "Subtotal"), crear("dd", {}, formatoDinero(totales.subtotal)),
        crear("dt", {}, "Descuento"),
        crear("dd", {}, Number(totales.descuento) > 0 ? `−${formatoDinero(totales.descuento)}` : formatoDinero(totales.descuento)),
        crear("dt", {}, "Impuestos"), crear("dd", {}, formatoDinero(totales.impuestos)),
        crear("dt", {}, "Propina"), crear("dd", {}, formatoDinero(totales.propina)),
        crear("dt", { class: "resumen-totales__total" }, "Total"),
        crear("dd", { class: "resumen-totales__total" }, formatoDinero(totales.total)));
}

export function listaPagos(pagos) {
    return crear("ul", { class: "lista-pagos" }, pagos.map((pago) => crear("li", {},
        insigniaEstado(pago.estado, "pago"),
        crear("span", {}, etiqueta(pago.metodo)),
        crear("span", { class: "texto-secundario" }, `${formatoFecha(pago.fecha)} · ${pago.referencia ?? ""}`),
        crear("span", { class: "lista-pagos__monto" }, formatoDinero(pago.monto)))));
}
