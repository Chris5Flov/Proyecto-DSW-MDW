// Manejo de sesión en el cliente, redirecciones por rol y guardias de navegación.
// NOTA: Toda restricción en JS es solo de UX; la seguridad real se valida en PHP.

import { obtenerSesionActual } from "../api/api.js";
import { pintarEncabezado } from "../componentes/encabezado.js";

const RUTA_POR_ROL = {
    MESERO: "ordenes.html",
    COCINERO: "cocina.html",
    CAJERO: "caja.html",
    ADMINISTRADOR: "menu.html"
};

export function rutaInicio(rol) {
    return RUTA_POR_ROL[rol] ?? "login.html";
}

/**
 * Exige una sesión válida y, opcionalmente, que el rol del usuario esté en rolesPermitidos.
 * - Sin sesión → redirige a login.html
 * - Rol no permitido → redirige a acceso-denegado.html
 */
export async function requerirSesion(rolesPermitidos = null) {
    let usuario = null;
    try {
        usuario = await obtenerSesionActual();
    } catch {
        window.location.replace("login.html");
        return null;
    }

    if (!usuario || !usuario.rol) {
        window.location.replace("login.html");
        return null;
    }

    if (Array.isArray(rolesPermitidos) && !rolesPermitidos.includes(usuario.rol)) {
        window.location.replace("acceso-denegado.html");
        return null;
    }

    const paginaActual = window.location.pathname.split("/").pop() || "index.html";
    pintarEncabezado(usuario, paginaActual);
    return usuario;
}

/** Para páginas públicas (login/registro): si ya hay sesión, redirige a la vista del rol. */
export async function redirigirSiAutenticado() {
    try {
        const usuario = await obtenerSesionActual();
        if (usuario && usuario.rol) {
            window.location.replace(rutaInicio(usuario.rol));
            return true;
        }
    } catch {
        // Sin sesión: se mantiene en la página pública.
    }
    return false;
}
