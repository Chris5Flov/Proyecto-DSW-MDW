// Controlador de login.html: validación en cliente e inicio de sesión.

import { iniciarSesion } from "../api/api.js";
import { redirigirSiAutenticado, rutaInicio } from "./sesion.js";
import { mostrarExito } from "../componentes/notificaciones.js";
import { mientrasEspera } from "../util/dom.js";

const formulario = document.getElementById("form-login");
const inputIdentificador = document.getElementById("login-identificador");
const inputPassword = document.getElementById("login-password");
const textoError = document.getElementById("login-error");
const boton = document.getElementById("login-boton");

redirigirSiAutenticado();

formulario.addEventListener("submit", (evento) => {
    evento.preventDefault();
    ocultarError();

    const identificador = inputIdentificador.value.trim();
    const password = inputPassword.value;

    if (identificador.length < 3) {
        mostrarErrorFormulario("Ingresa tu usuario o correo electrónico (mínimo 3 caracteres).");
        inputIdentificador.focus();
        return;
    }
    if (password.length < 6) {
        mostrarErrorFormulario("La contraseña debe tener al menos 6 caracteres.");
        inputPassword.focus();
        return;
    }

    mientrasEspera(boton, async () => {
        try {
            const usuario = await iniciarSesion({ identificador, password });
            mostrarExito(`Bienvenido, ${usuario.nombre}`);
            window.location.replace(rutaInicio(usuario.rol));
        } catch (error) {
            mostrarErrorFormulario(error.message || "No se pudo iniciar sesión.");
        }
    });
});

function mostrarErrorFormulario(mensaje) {
    textoError.textContent = mensaje;
    textoError.hidden = false;
}

function ocultarError() {
    textoError.textContent = "";
    textoError.hidden = true;
}
