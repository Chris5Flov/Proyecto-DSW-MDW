// ÚNICO punto de acceso a datos. Las vistas y módulos de autenticación importan solo este archivo.

import { peticion } from "./http.js";

const id = (valor) => encodeURIComponent(valor);

// ---------------------------------------------------------------- Autenticación y usuarios

export function obtenerSesionActual() {
    return peticion("GET", "/auth/me", undefined, { redirigirSiNoAutenticado: false });
}

export function iniciarSesion({ identificador, password }) {
    return peticion("POST", "/auth/login", { identificador, password }, { redirigirSiNoAutenticado: false });
}

export function registrarUsuario({ nombre, correo, usuario, password, confirmarPassword }) {
    return peticion("POST", "/auth/register", { nombre, correo, usuario, password, confirmarPassword }, {
        redirigirSiNoAutenticado: false
    });
}

export function cerrarSesion() {
    return peticion("POST", "/auth/logout", undefined, { redirigirSiNoAutenticado: false });
}

export function listarUsuarios() {
    return peticion("GET", "/usuarios");
}

export function actualizarUsuario(usuarioId, { rol, activo }) {
    return peticion("PUT", `/usuarios/${id(usuarioId)}`, { rol, activo });
}

// ---------------------------------------------------------------- Menú

export function listarPlatos() {
    return peticion("GET", "/platos");
}

export function registrarPlato({ id: platoId, nombre, categoria, precioBase, tasaImpuesto }) {
    return peticion("POST", "/platos", { id: platoId, nombre, categoria, precioBase, tasaImpuesto });
}

export function actualizarPlato(platoId, { nombre, categoria, precioBase, tasaImpuesto }) {
    return peticion("PUT", `/platos/${id(platoId)}`, { nombre, categoria, precioBase, tasaImpuesto });
}

export function subirImagenPlato(platoId, archivo) {
    const datos = new FormData();
    datos.append("imagen", archivo);
    return peticion("POST", `/platos/${id(platoId)}/imagen`, datos);
}

export function eliminarPlato(platoId) {
    return peticion("DELETE", `/platos/${id(platoId)}`);
}

export function listarModificadores() {
    return peticion("GET", "/modificadores");
}

export function registrarModificador({ id: modificadorId, nombre, tipo, costoExtra }) {
    return peticion("POST", "/modificadores", { id: modificadorId, nombre, tipo, costoExtra });
}

export function actualizarModificador(modificadorId, { nombre, tipo, costoExtra }) {
    return peticion("PUT", `/modificadores/${id(modificadorId)}`, { nombre, tipo, costoExtra });
}

export function eliminarModificador(modificadorId) {
    return peticion("DELETE", `/modificadores/${id(modificadorId)}`);
}

// ---------------------------------------------------------------- Órdenes

export function listarOrdenes(estado) {
    return peticion("GET", estado ? `/ordenes?estado=${id(estado)}` : "/ordenes");
}

export function obtenerOrden(ordenId) {
    return peticion("GET", `/ordenes/${id(ordenId)}`);
}

export function abrirOrden({ tipo, referencia }) {
    return peticion("POST", "/ordenes", { tipo, referencia });
}

export function agregarLinea(ordenId, { platoId, cantidad, modificadorIds }) {
    return peticion("POST", `/ordenes/${id(ordenId)}/lineas`, {
        platoId,
        cantidad,
        modificadorIds: modificadorIds ?? []
    });
}

export function modificarLinea(ordenId, lineaId, { cantidad, modificadorIds }) {
    return peticion("PUT", `/ordenes/${id(ordenId)}/lineas/${id(lineaId)}`, {
        cantidad,
        modificadorIds: modificadorIds ?? []
    });
}

export function cancelarLinea(ordenId, lineaId) {
    return peticion("DELETE", `/ordenes/${id(ordenId)}/lineas/${id(lineaId)}`);
}

export function cancelarOrden(ordenId) {
    return peticion("POST", `/ordenes/${id(ordenId)}/cancelar`);
}

// ---------------------------------------------------------------- Cocina

export function listarTableroCocina() {
    return peticion("GET", "/cocina/tablero");
}

export function iniciarCoccion(ordenId, lineaId) {
    return peticion("POST", `/cocina/ordenes/${id(ordenId)}/lineas/${id(lineaId)}/iniciar`);
}

export function marcarLista(ordenId, lineaId) {
    return peticion("POST", `/cocina/ordenes/${id(ordenId)}/lineas/${id(lineaId)}/lista`);
}

// ---------------------------------------------------------------- Caja

export function listarOrdenesListasCaja() {
    return peticion("GET", "/caja/ordenes");
}

export function obtenerOrdenCaja(ordenId) {
    return peticion("GET", `/caja/ordenes/${id(ordenId)}`);
}

export function calcularTotales(ordenId, { descuento, propina }) {
    const parametros = new URLSearchParams({ descuento, propina });
    return peticion("GET", `/caja/ordenes/${id(ordenId)}/totales?${parametros}`);
}

export function cobrar(ordenId, { metodo, descuento, propina }) {
    return peticion("POST", `/caja/ordenes/${id(ordenId)}/cobrar`, { metodo, descuento, propina });
}