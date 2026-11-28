// Encabezado dinámico según el rol autenticado y botón de cierre de sesión.

import { cerrarSesion } from "../api/api.js";
import { crear, limpiar, mientrasEspera } from "../util/dom.js";

const SECCIONES = [
    { href: "ordenes.html", nombre: "Órdenes", roles: ["MESERO"] },
    { href: "cocina.html", nombre: "Cocina", roles: ["COCINERO"] },
    { href: "caja.html", nombre: "Caja", roles: ["CAJERO"] },
    { href: "menu.html", nombre: "Menú", roles: ["ADMINISTRADOR"] }
];

/**
 * Pinta el logo (enlace a la sección del usuario), las pestañas solo si el rol tiene
 * más de una sección, y el panel de usuario con el cierre de sesión.
 */
export function pintarEncabezado(usuario, paginaActual) {
    const encabezado = document.getElementById("encabezado-principal");
    if (encabezado) {
        encabezado.classList.add("encabezado--logo");
    }

    const propias = SECCIONES.filter((sec) => sec.roles.includes(usuario.rol));

    const nav = document.querySelector("#encabezado-principal .navegacion");
    if (nav) {
        limpiar(nav);
        nav.appendChild(crear("a", {
            class: "encabezado__logo",
            href: propias[0]?.href ?? "index.html",
            title: "Ir al inicio",
            "aria-label": "OMAKASE: ir al inicio"
        }, crear("img", { src: "img/logo.png", alt: "", width: "488", height: "443" })));

        if (propias.length > 1) {
            for (const sec of propias) {
                const activa = paginaActual === sec.href;
                nav.appendChild(crear("a", {
                    class: `pestana${activa ? " pestana--activa" : ""}`,
                    href: sec.href,
                    "aria-current": activa ? "page" : null
                }, crear("span", { class: "pestana__nombre" }, sec.nombre)));
            }
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
            crear("span", { class: "barra-usuario__nombre" }, usuario.nombre)),
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