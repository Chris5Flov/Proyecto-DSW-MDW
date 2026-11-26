// Acceso a la API real PHP (mismo origen, prefijo relativo ./api).

const PREFIJO = new URL("api", window.location.href).pathname.replace(/\/$/, "");

export class ErrorApi extends Error {
    estadoHttp;

    constructor(estadoHttp, mensaje) {
        super(mensaje);
        this.name = "ErrorApi";
        this.estadoHttp = estadoHttp;
    }
}

/**
 * Hace una petición a la API.
 * - ruta relativa a "/api", p. ej. peticion("GET", "/platos")
 * - 204 → null; 2xx → el JSON de la respuesta
 * - 401 → si redirigirSiNoAutenticado es true, redirige a login.html
 */
export async function peticion(metodo, ruta, cuerpo = undefined, { redirigirSiNoAutenticado = true } = {}) {
    const opciones = {
        method: metodo,
        credentials: "same-origin",
        headers: { Accept: "application/json" }
    };
    if (cuerpo !== undefined) {
        opciones.headers["Content-Type"] = "application/json";
        opciones.body = JSON.stringify(cuerpo);
    }

    let respuesta;
    try {
        respuesta = await fetch(PREFIJO + ruta, opciones);
    } catch {
        throw new ErrorApi(0, "No se pudo conectar con el servidor");
    }

    if (respuesta.status === 204) {
        return null;
    }

    const datos = await leerJson(respuesta);
    if (!respuesta.ok) {
        const mensaje = datos && typeof datos.error === "string" && datos.error ? datos.error : "Error de conexión";
        if (respuesta.status === 401 && redirigirSiNoAutenticado) {
            const pagina = window.location.pathname.split("/").pop();
            if (pagina !== "login.html" && pagina !== "registro.html") {
                window.location.replace("login.html");
            }
        }
        throw new ErrorApi(respuesta.status, mensaje);
    }
    return datos;
}

async function leerJson(respuesta) {
    const texto = await respuesta.text();
    if (!texto) {
        return null;
    }
    try {
        return JSON.parse(texto);
    } catch {
        return null;
    }
}
