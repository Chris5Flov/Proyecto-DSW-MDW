// Vista Cocina (Cocinero, RF3): flujo de los platillos En fila -> En cocción -> Listo para entrega.

import { listarTableroCocina, iniciarCoccion, marcarLista } from "../api/api.js";
import { mostrarExito, mostrarError } from "../componentes/notificaciones.js";
import { crear, limpiar, mientrasEspera } from "../util/dom.js";
import { horaActual } from "../util/formato.js";

const INTERVALO_MS = 5000;

export function montar(contenedor) {
    const estado = {
        activa: true,
        cargando: false,
        exclusiones: new Set()
    };

    const botonActualizar = crear("button", { type: "button", class: "boton boton--secundario", onclick: () => refrescar(true) }, "Actualizar");
    const textoActualizado = crear("span", { class: "texto-secundario", "aria-live": "polite" }, "");
    const tablero = crear("div", { class: "tablero-contenedor" });

    contenedor.appendChild(crear("section", { class: "vista" },
        crear("div", { class: "vista__encabezado" },
            crear("h2", { class: "vista__titulo" }, "Cocina"),
            crear("div", { class: "barra-herramientas" }, textoActualizado, botonActualizar)),
        tablero));

    tablero.appendChild(crear("p", { class: "cargando" }, "Cargando pedidos…"));
    refrescar(false);
    const intervalo = setInterval(() => refrescar(false), INTERVALO_MS);

    return () => {
        estado.activa = false;
        clearInterval(intervalo);
    };

    async function refrescar(manual) {
        if (!estado.activa || estado.cargando) {
            return;
        }
        estado.cargando = true;
        botonActualizar.disabled = true;
        try {
            const datos = await listarTableroCocina();
            if (!estado.activa) return;
            estado.exclusiones = new Set(datos.exclusiones ?? []);
            pintar(datos.lineas ?? []);
            textoActualizado.textContent = `Actualizado ${horaActual()}`;
        } catch (error) {
            if (!estado.activa) return;
            textoActualizado.textContent = `No se pudo actualizar (${horaActual()}): ${error.message}`;
            if (manual) mostrarError(error);
        } finally {
            estado.cargando = false;
            botonActualizar.disabled = false;
        }
    }

    function pintar(lineas) {
        limpiar(tablero);
        if (lineas.length === 0) {
            tablero.appendChild(crear("p", { class: "texto-vacio tarjeta" }, "No hay pedidos pendientes"));
            return;
        }
        tablero.appendChild(crear("div", { class: "tablero" },
            columna("En fila", lineas.filter((l) => l.estado === "PENDIENTE"), "pendientes"),
            columna("En cocción", lineas.filter((l) => l.estado === "EN_COCCION"), "coccion"),
            columna("Listo para entrega", lineas.filter((l) => l.estado === "LISTO"), "listo")));
    }

    function columna(titulo, lineas, tipo) {
        return crear("section", { class: `columna-tablero columna-tablero--${tipo}`, "aria-label": titulo },
            crear("h3", { class: "columna-tablero__titulo" },
                titulo, crear("span", { class: "columna-tablero__contador" }, String(lineas.length))),
            lineas.length === 0
                ? crear("p", { class: "texto-secundario" }, "Nada por aquí.")
                : lineas.map((linea) => tarjeta(linea)));
    }

    function tarjeta(linea) {
        const clases = { EN_COCCION: " tarjeta-cocina--coccion", LISTO: " tarjeta-cocina--listo" };
        return crear("article", { class: `tarjeta-cocina${clases[linea.estado] ?? ""}` },
            crear("div", { class: "tarjeta-cocina__referencia" }, linea.referenciaOrden),
            crear("div", { class: "tarjeta-cocina__plato" }, `${linea.plato} × ${linea.cantidad}`),
            linea.modificadores?.length
                ? crear("ul", { class: "tarjeta-cocina__modificadores" }, linea.modificadores.map((nombre) =>
                    crear("li", { class: estado.exclusiones.has(nombre) ? "modificador--exclusion" : null }, nombre)))
                : null,
            accion(linea));
    }

    function accion(linea) {
        if (linea.estado === "LISTO") {
            return crear("p", { class: "texto-secundario" }, "Esperando cobro en caja");
        }
        const enCoccion = linea.estado === "EN_COCCION";
        const boton = crear("button", {
            type: "button",
            class: `boton ${enCoccion ? "boton--primario" : "boton--secundario"}`,
            onclick: () => avanzar(linea, boton)
        }, enCoccion ? "Listo para entrega" : "Iniciar cocción");
        return boton;
    }

    function avanzar(linea, boton) {
        const enCoccion = linea.estado === "EN_COCCION";
        mientrasEspera(boton, async () => {
            try {
                if (enCoccion) {
                    await marcarLista(linea.ordenId, linea.lineaId);
                    mostrarExito(`${linea.plato} (${linea.referenciaOrden}) listo para entrega`);
                } else {
                    await iniciarCoccion(linea.ordenId, linea.lineaId);
                    mostrarExito(`Cocción iniciada: ${linea.plato} (${linea.referenciaOrden})`);
                }
            } catch (error) {
                mostrarError(error);
            }
        }).then(() => refrescarTrasAccion());
    }

    async function refrescarTrasAccion() {
        while (estado.cargando && estado.activa) {
            await new Promise((resolver) => setTimeout(resolver, 50));
        }
        await refrescar(false);
    }
}
