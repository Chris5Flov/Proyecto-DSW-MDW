import { requerirSesion } from "../auth/sesion.js";
import { limpiar } from "../util/dom.js";
import { montar } from "../vistas/vista-menu.js";

async function iniciar() {
    const usuario = await requerirSesion(["ADMINISTRADOR"]);
    if (!usuario) return;
    const contenedor = limpiar(document.getElementById("contenido"));
    montar(contenedor, usuario);
}

iniciar();
