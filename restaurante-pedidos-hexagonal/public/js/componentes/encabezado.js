// Encabezado dinámico según el rol autenticado y botón de cierre de sesión.

import { cerrarSesion } from "../api/api.js";
import { insigniaEstado } from "./insignia.js";
import { crear, limpiar, mientrasEspera } from "../util/dom.js";

const SECCIONES = [
    { href: "ordenes.html", nombre: "Órdenes", actor: "Mesero", roles: ["MESERO"] },
    { href: "cocina.html", nombre: "Cocina", actor: "Cocinero", roles: ["COCINERO"] },
    { href: "caja.html", nombre: "Caja", actor: "Cajero", roles: ["CAJERO"] },
    { href: "menu.html", nombre: "Menú", actor: "Administrador", roles: ["ADMINISTRADOR"] }
];

/** Construye la barra de navegación visible para el rol y el panel de usuario con logout. */
export function pintarEncabezado(usuario, paginaActual) {
    const nav = document.querySelector("#encabezado-principal .navegacion");
    if (nav) {
        limpiar(nav);
        for (const sec of SECCIONES) {
            if (!sec.roles.includes(usuario.rol)) {
                continue;
            }
            const activa = paginaActual === sec.href;
            nav.appendChild(crear("a", {
                class: `pestana${activa ? " pestana--activa" : ""}`,
                href: sec.href,
                "aria-current": activa ? "page" : null
            },
                crear("span", { class: "pestana__nombre" }, sec.nombre),
                crear("span", { class: "pestana__actor" }, sec.actor)));
        }
    }

    const barra = document.getElementById("barra-usuario");
    if (!barra) {
        return;
    }
    limpiar(barra);

    const botonSalir = crear("button", {
        type: "button",
        class: "boton boton--secundario boton--pequeno",
        onclick: () => salir(botonSalir)
    }, "Cerrar sesión");

    barra.append(
        crear("div", { class: "barra-usuario__info" },
            crear("span", { class: "barra-usuario__nombre" }, usuario.nombre),
            insigniaEstado(usuario.rol)),
        botonSalir
    );
}

export async function salir(boton) {
    await mientrasEspera(boton, async () => {
        try {
            await cerrarSesion();
        } catch {
            // Aunque falle la red, forzamos salida en el cliente hacia login.
        } finally {
            window.location.replace("login.html");
        }
    });
}
