// Iconos SVG (trazo, estilo línea) creados con el DOM, sin innerHTML ni librerías externas.

import { crear } from "./dom.js";

const NS = "http://www.w3.org/2000/svg";

const FORMAS = {
    editar: [
        ["path", { d: "M12 20h9" }],
        ["path", { d: "M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" }]
    ],
    imagen: [
        ["rect", { x: "3", y: "3", width: "18", height: "18", rx: "2", ry: "2" }],
        ["circle", { cx: "8.5", cy: "8.5", r: "1.5" }],
        ["polyline", { points: "21 15 16 10 5 21" }]
    ],
    eliminar: [
        ["polyline", { points: "3 6 5 6 21 6" }],
        ["path", { d: "M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" }],
        ["path", { d: "M10 11v6" }],
        ["path", { d: "M14 11v6" }],
        ["path", { d: "M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" }]
    ],
    gestionar: [
        ["circle", { cx: "12", cy: "12", r: "3" }],
        ["path", { d: "M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M4.9 19.1L7 17M17 7l2.1-2.1" }]
    ]
};

/** Devuelve un <svg> decorativo (aria-hidden) con el icono pedido. */
export function icono(nombre) {
    const svg = document.createElementNS(NS, "svg");
    svg.setAttribute("viewBox", "0 0 24 24");
    svg.setAttribute("class", "icono");
    svg.setAttribute("aria-hidden", "true");
    svg.setAttribute("focusable", "false");
    for (const [etiqueta, atributos] of FORMAS[nombre] ?? []) {
        const forma = document.createElementNS(NS, etiqueta);
        for (const [clave, valor] of Object.entries(atributos)) {
            forma.setAttribute(clave, valor);
        }
        svg.appendChild(forma);
    }
    return svg;
}

/**
 * Botón cuadrado con solo icono. "texto" va en title y aria-label (lo que lee un lector de pantalla).
 * variante: "secundario" | "peligro" | "primario".
 */
export function botonIcono(nombre, texto, alHacerClic, variante = "secundario") {
    return crear("button", {
        type: "button",
        class: `boton boton--${variante} boton--icono`,
        title: texto,
        "aria-label": texto,
        onclick: alHacerClic
    }, icono(nombre));
}