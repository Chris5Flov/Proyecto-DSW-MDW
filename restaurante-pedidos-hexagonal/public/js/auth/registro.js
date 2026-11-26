// Controlador de registro.html: validación en cliente y creación de cuenta.

import { registrarUsuario } from "../api/api.js";
import { redirigirSiAutenticado } from "./sesion.js";
import { mostrarExito } from "../componentes/notificaciones.js";
import { mientrasEspera } from "../util/dom.js";

const PATRON_CORREO = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const PATRON_USUARIO = /^[A-Za-z0-9_.-]{3,30}$/;

const formulario = document.getElementById("form-registro");
const inputNombre = document.getElementById("reg-nombre");
const inputCorreo = document.getElementById("reg-correo");
const inputUsuario = document.getElementById("reg-usuario");
const inputPassword = document.getElementById("reg-password");
const inputConfirmar = document.getElementById("reg-confirmar");
const textoError = document.getElementById("registro-error");
const boton = document.getElementById("registro-boton");

redirigirSiAutenticado();

formulario.addEventListener("submit", (evento) => {
    evento.preventDefault();
    ocultarError();

    const nombre = inputNombre.value.trim();
    const correo = inputCorreo.value.trim();
    const usuario = inputUsuario.value.trim();
    const password = inputPassword.value;
    const confirmarPassword = inputConfirmar.value;

    if (nombre.length < 3) {
        mostrarErrorFormulario("El nombre completo debe tener al menos 3 caracteres.");
        inputNombre.focus();
        return;
    }
    if (!PATRON_CORREO.test(correo)) {
        mostrarErrorFormulario("Ingresa un correo electrónico válido.");
        inputCorreo.focus();
        return;
    }
    if (!PATRON_USUARIO.test(usuario)) {
        mostrarErrorFormulario("El usuario debe tener entre 3 y 30 caracteres (letras, números, ., - o _).");
        inputUsuario.focus();
        return;
    }
    if (password.length < 8) {
        mostrarErrorFormulario("La contraseña debe tener al menos 8 caracteres.");
        inputPassword.focus();
        return;
    }
    if (password !== confirmarPassword) {
        mostrarErrorFormulario("Las contraseñas no coinciden.");
        inputConfirmar.focus();
        return;
    }

    mientrasEspera(boton, async () => {
        try {
            await registrarUsuario({ nombre, correo, usuario, password, confirmarPassword });
            mostrarExito("Cuenta creada correctamente. Redirigiendo a iniciar sesión…");
            setTimeout(() => window.location.replace("login.html"), 700);
        } catch (error) {
            mostrarErrorFormulario(error.message || "No se pudo completar el registro.");
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
