import { obtenerSesionActual } from "../api/api.js";
import { rutaInicio } from "../auth/sesion.js";

async function iniciar() {
    try {
        const usuario = await obtenerSesionActual();
        if (usuario && usuario.rol) {
            window.location.replace(rutaInicio(usuario.rol));
            return;
        }
    } catch {
        // Sin sesión: redirigir a login.
    }
    window.location.replace("login.html");
}

iniciar();
