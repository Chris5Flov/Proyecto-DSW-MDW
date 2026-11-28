import { requerirSesion, rutaInicio } from "../auth/sesion.js";
import { salir } from "../componentes/encabezado.js";
import { etiqueta } from "../util/formato.js";

async function iniciar() {
    const usuario = await requerirSesion();
    if (!usuario) return;

    const mensaje = document.getElementById("mensaje-denegado");
    const enlace = document.getElementById("enlace-inicio-rol");
    const botonSalir = document.getElementById("boton-cerrar-sesion");

    if (mensaje) {
        mensaje.textContent = `Tu cuenta (${usuario.nombre}) tiene el rol ${etiqueta(usuario.rol)}, que no tiene permiso para acceder a la sección solicitada.`;
    }
    if (enlace) {
        enlace.href = rutaInicio(usuario.rol);
    }
    if (botonSalir) {
        botonSalir.addEventListener("click", () => salir(botonSalir));
    }
}

iniciar();
