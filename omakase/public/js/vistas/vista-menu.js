// Vista Menú y Administración (Administrador, RF1 + gestión de roles de usuarios).

import {
    listarPlatos, registrarPlato, actualizarPlato, subirImagenPlato, eliminarPlato,
    listarModificadores, registrarModificador, actualizarModificador, eliminarModificador,
    listarUsuarios, actualizarUsuario
} from "../api/api.js";
import { mostrarExito, mostrarError } from "../componentes/notificaciones.js";
import { abrirModal, cerrarModal, confirmar } from "../componentes/modal.js";
import { insigniaEstado } from "../componentes/insignia.js";
import { crear, limpiar, campo, mientrasEspera } from "../util/dom.js";
import { botonIcono } from "../util/iconos.js";
import { formatoDinero, formatoPorcentaje, normalizarMonto, etiqueta } from "../util/formato.js";

const ROLES = ["ADMINISTRADOR", "MESERO", "COCINERO", "CAJERO"];
const CATEGORIAS = ["Del mar", "Platos fuertes", "Antojitos", "Postres", "Bebidas"];
const TASA_IVA = "0.16"; // IVA fijo del 16 %: ya no se captura en los formularios

export function montar(contenedor, usuarioActual = null) {
    let activa = true;

    // ------------------------------------------------------------ platos
    const listaPlatos = crear("ul", { class: "lista-platos" });
    const idPlato = crear("input", { type: "text", id: "plato-id", required: true, maxlength: "32", autocomplete: "off", placeholder: "P-001" });
    const nombrePlato = crear("input", { type: "text", id: "plato-nombre", required: true, maxlength: "120", autocomplete: "off", placeholder: "Tacos al pastor" });
    const categoriaPlato = crear("input", {
        type: "text",
        id: "plato-categoria",
        required: true,
        maxlength: "60",
        list: "categorias-menu",
        autocomplete: "off",
        placeholder: "Platos fuertes"
    });
    const listaCategorias = crear("datalist", { id: "categorias-menu" },
        CATEGORIAS.map((cat) => crear("option", { value: cat })));
    const precioPlato = crear("input", { type: "number", id: "plato-precio", required: true, min: "0.01", step: "0.01", inputmode: "decimal", autocomplete: "off", placeholder: "85.50" });
    const imagenPlato = crear("input", { type: "file", id: "plato-imagen", accept: "image/jpeg,image/png,image/webp" });
    const botonPlato = crear("button", { type: "submit", class: "boton boton--primario" }, "Guardar plato");

    const formularioPlato = crear("form", { class: "formulario formulario--compacto", onsubmit: guardarPlato },
        crear("h3", { class: "tarjeta__subtitulo" }, "Registrar plato"),
        crear("div", { class: "formulario__campos" },
            campo("ID", idPlato),
            campo("Nombre", nombrePlato),
            campo("Categoría", categoriaPlato),
            campo("Precio base", precioPlato),
            campo("Imagen (opcional)", imagenPlato),
            crear("div", { class: "formulario__accion" }, botonPlato)),
        crear("p", { class: "texto-secundario" }, "Imagen: JPG, PNG o WEBP (máx. 5 MB). Al precio base se le suma el IVA (16 %) automáticamente."),
        listaCategorias);

    // ------------------------------------------------------------ modificadores
    const cuerpoModificadores = crear("tbody");
    const idModificador = crear("input", { type: "text", id: "modificador-id", required: true, maxlength: "32", autocomplete: "off", placeholder: "M-001" });
    const nombreModificador = crear("input", { type: "text", id: "modificador-nombre", required: true, maxlength: "120", autocomplete: "off", placeholder: "Queso extra" });
    const tipoModificador = crear("select", { id: "modificador-tipo", required: true, onchange: actualizarCosto },
        crear("option", { value: "EXTRA" }, "Extra"),
        crear("option", { value: "EXCLUSION" }, "Exclusión"));
    const costoModificador = crear("input", { type: "number", id: "modificador-costo", required: true, min: "0", step: "0.01", inputmode: "decimal", autocomplete: "off", placeholder: "10.00" });
    const botonModificador = crear("button", { type: "submit", class: "boton boton--primario" }, "Guardar modificador");

    const formularioModificador = crear("form", { class: "formulario formulario--compacto", onsubmit: guardarModificador },
        crear("h3", { class: "tarjeta__subtitulo" }, "Registrar modificador"),
        crear("div", { class: "formulario__campos" },
            campo("ID", idModificador),
            campo("Nombre", nombreModificador),
            campo("Tipo", tipoModificador),
            campo("Costo extra", costoModificador),
            crear("div", { class: "formulario__accion" }, botonModificador)),
        crear("p", { class: "texto-secundario" }, "Una exclusión no tiene costo."));

    // ------------------------------------------------------------ usuarios
    const cuerpoUsuarios = crear("tbody");

    contenedor.appendChild(crear("section", { class: "vista" },
        crear("div", { class: "vista__encabezado" }, crear("h2", { class: "vista__titulo" }, "Menú y Administración")),
        crear("section", { class: "tarjeta" },
            crear("h3", { class: "tarjeta__titulo" }, "Platos"),
            listaPlatos,
            crear("hr", { class: "separador" }),
            formularioPlato),
        crear("section", { class: "tarjeta" },
            crear("h3", { class: "tarjeta__titulo" }, "Modificadores"),
            tabla(["ID", "Nombre", "Tipo", "Costo extra", "Acciones"], [3], cuerpoModificadores),
            crear("hr", { class: "separador" }),
            formularioModificador),
        crear("section", { class: "tarjeta" },
            crear("div", { class: "pila" },
                crear("h3", { class: "tarjeta__titulo" }, "Usuarios y roles"),
                crear("p", { class: "texto-secundario" }, "Administra los roles y el estado de acceso del personal.")),
            tabla(["Usuario", "Nombre", "Correo", "Rol", "Estado", "Acciones"], [], cuerpoUsuarios))));

    cargarPlatos();
    cargarModificadores();
    cargarUsuarios();

    return () => { activa = false; };

    // ------------------------------------------------------------ carga de datos

    async function cargarPlatos() {
        if (!listaPlatos.firstChild) {
            mensajeLista(listaPlatos, "Cargando platos…", "cargando");
        }
        try {
            const platos = await listarPlatos();
            if (!activa) return;
            limpiar(listaPlatos);
            if (platos.length === 0) {
                mensajeLista(listaPlatos, "Aún no hay platos registrados.");
                return;
            }
            for (const plato of platos) {
                listaPlatos.appendChild(crear("li", { class: "plato-fila" },
                    miniaturaPlato(plato),
                    crear("div", { class: "plato-fila__info" },
                        crear("span", { class: "plato-fila__nombre" }, plato.nombre),
                        crear("span", { class: "plato-fila__meta" }, `${plato.id} · ${plato.categoria || "Sin categoría"}`)),
                    crear("div", { class: "plato-fila__precio" },
                        crear("strong", {}, formatoDinero(plato.precioBase)),
                        crear("span", { class: "plato-fila__meta" }, `IVA ${formatoPorcentaje(plato.tasaImpuesto)}`)),
                    crear("div", { class: "plato-fila__acciones" },
                        botonIcono("editar", `Editar ${plato.nombre}`, () => editarPlato(plato)),
                        botonIcono("imagen", `Cambiar imagen de ${plato.nombre}`, () => subirFotoPlato(plato)),
                        botonIcono("eliminar", `Eliminar ${plato.nombre}`, () => pedirEliminarPlato(plato), "peligro"))));
            }
        } catch (error) {
            if (!activa) return;
            mensajeLista(listaPlatos, "No se pudieron cargar los platos.");
            mostrarError(error);
        }
    }

    async function cargarModificadores() {
        if (!cuerpoModificadores.firstChild) {
            filaMensaje(cuerpoModificadores, 5, "Cargando modificadores…", "cargando");
        }
        try {
            const modificadores = await listarModificadores();
            if (!activa) return;
            limpiar(cuerpoModificadores);
            if (modificadores.length === 0) {
                filaMensaje(cuerpoModificadores, 5, "Aún no hay modificadores registrados.");
                return;
            }
            for (const modificador of modificadores) {
                cuerpoModificadores.appendChild(crear("tr", {},
                    crear("td", {}, modificador.id),
                    crear("td", {}, modificador.nombre),
                    crear("td", {}, insigniaEstado(modificador.tipo)),
                    crear("td", { class: "numero" }, formatoDinero(modificador.costoExtra)),
                    crear("td", { class: "acciones" },
                        crear("div", { class: "fila fila--iconos" },
                            botonIcono("editar", `Editar ${modificador.nombre}`, () => editarModificador(modificador)),
                            botonIcono("eliminar", `Eliminar ${modificador.nombre}`, () => pedirEliminarModificador(modificador), "peligro")))));
            }
        } catch (error) {
            if (!activa) return;
            filaMensaje(cuerpoModificadores, 5, "No se pudieron cargar los modificadores.");
            mostrarError(error);
        }
    }

    async function cargarUsuarios() {
        if (!cuerpoUsuarios.firstChild) {
            filaMensaje(cuerpoUsuarios, 6, "Cargando usuarios…", "cargando");
        }
        try {
            const usuarios = await listarUsuarios();
            if (!activa) return;
            limpiar(cuerpoUsuarios);
            if (usuarios.length === 0) {
                filaMensaje(cuerpoUsuarios, 6, "No hay usuarios registrados.");
                return;
            }
            for (const usr of usuarios) {
                cuerpoUsuarios.appendChild(crear("tr", {},
                    crear("td", {}, usr.usuario),
                    crear("td", {}, usr.nombre),
                    crear("td", {}, usr.correo),
                    crear("td", {}, insigniaEstado(usr.rol)),
                    crear("td", {}, insigniaEstado(usr.activo ? "ACTIVO" : "INACTIVO")),
                    crear("td", { class: "acciones" },
                        crear("button", {
                            type: "button",
                            class: "boton boton--secundario boton--pequeno",
                            onclick: () => editarUsuario(usr)
                        }, "Gestionar"))));
            }
        } catch (error) {
            if (!activa) return;
            filaMensaje(cuerpoUsuarios, 6, "No se pudieron cargar los usuarios.");
            mostrarError(error);
        }
    }

    // ------------------------------------------------------------ editar, imagen y eliminar

    function editarPlato(plato) {
        const nombre = crear("input", { type: "text", id: "editar-plato-nombre", required: true, maxlength: "120", autocomplete: "off", value: plato.nombre });
        const categoria = crear("input", {
            type: "text",
            id: "editar-plato-categoria",
            required: true,
            maxlength: "60",
            list: "categorias-menu",
            autocomplete: "off",
            value: plato.categoria || ""
        });
        const precio = crear("input", { type: "number", id: "editar-plato-precio", required: true, min: "0.01", step: "0.01", inputmode: "decimal", autocomplete: "off", value: plato.precioBase });

        abrirModal({
            titulo: `Editar plato ${plato.id}`,
            contenido: crear("form", { class: "formulario" },
                campo("Nombre", nombre),
                campo("Categoría", categoria, "Ej. Del mar, Platos fuertes, Bebidas"),
                campo("Precio base", precio, "Se le suma el IVA (16 %) automáticamente."),
                crear("p", { class: "texto-secundario" }, "Las órdenes que ya tienen este plato conservan su precio.")),
            acciones: [
                { texto: "Volver", tipo: "secundario" },
                {
                    texto: "Guardar cambios",
                    tipo: "primario",
                    alHacerClic: async () => {
                        try {
                            const actualizado = await actualizarPlato(plato.id, {
                                nombre: nombre.value.trim(),
                                categoria: categoria.value.trim(),
                                precioBase: normalizarMonto(precio.value),
                                tasaImpuesto: TASA_IVA
                            });
                            cerrarModal();
                            mostrarExito(`Plato "${actualizado.nombre}" actualizado`);
                            await cargarPlatos();
                        } catch (error) {
                            mostrarError(error);
                        }
                    }
                }
            ]
        });
    }

    function subirFotoPlato(plato) {
        const archivoInput = crear("input", {
            type: "file",
            id: "modal-plato-imagen",
            accept: "image/jpeg,image/png,image/webp"
        });

        abrirModal({
            titulo: `Imagen de ${plato.nombre} (${plato.id})`,
            contenido: crear("form", { class: "formulario" },
                plato.imagen
                    ? crear("div", { class: "ficha-plato-preview" },
                        crear("img", { src: plato.imagen, alt: plato.nombre, class: "ficha-plato-preview__img" }))
                    : crear("p", { class: "texto-secundario" }, "Este plato aún no tiene una imagen asignada."),
                campo("Seleccionar archivo", archivoInput, "Formatos permitidos: JPG, PNG o WEBP (máx. 5 MB)")),
            acciones: [
                { texto: "Volver", tipo: "secundario" },
                {
                    texto: "Subir imagen",
                    tipo: "primario",
                    alHacerClic: async () => {
                        const archivo = archivoInput.files && archivoInput.files[0];
                        if (!archivo) {
                            mostrarError(new Error("Selecciona una imagen antes de subirla."));
                            return;
                        }
                        try {
                            await subirImagenPlato(plato.id, archivo);
                            cerrarModal();
                            mostrarExito(`Imagen actualizada para "${plato.nombre}"`);
                            await cargarPlatos();
                        } catch (error) {
                            mostrarError(error);
                        }
                    }
                }
            ]
        });
    }

    function pedirEliminarPlato(plato) {
        confirmar({
            titulo: "Eliminar plato",
            mensaje: `¿Eliminar "${plato.nombre}" del menú? Ya no se podrá agregar a órdenes nuevas; las órdenes que ya lo tienen no cambian.`,
            textoConfirmar: "Eliminar",
            alConfirmar: async () => {
                try {
                    await eliminarPlato(plato.id);
                    mostrarExito(`Plato "${plato.nombre}" eliminado`);
                    cargarPlatos();
                } catch (error) {
                    mostrarError(error);
                    return false;
                }
            }
        });
    }

    function editarModificador(modificador) {
        const nombre = crear("input", { type: "text", id: "editar-mod-nombre", required: true, maxlength: "120", autocomplete: "off", value: modificador.nombre });
        const costo = crear("input", { type: "number", id: "editar-mod-costo", required: true, min: "0", step: "0.01", inputmode: "decimal", autocomplete: "off", value: modificador.costoExtra });
        const estadoCosto = { valorPrevio: modificador.costoExtra };
        const tipo = crear("select", { id: "editar-mod-tipo", onchange: () => fijarCostoSegunTipo(tipo, costo, estadoCosto) },
            crear("option", { value: "EXTRA", selected: modificador.tipo === "EXTRA" }, "Extra"),
            crear("option", { value: "EXCLUSION", selected: modificador.tipo === "EXCLUSION" }, "Exclusión"));
        fijarCostoSegunTipo(tipo, costo, estadoCosto);

        abrirModal({
            titulo: `Editar modificador ${modificador.id}`,
            contenido: crear("form", { class: "formulario" },
                campo("Nombre", nombre),
                crear("div", { class: "formulario__campos" },
                    campo("Tipo", tipo),
                    campo("Costo extra", costo, "Una exclusión no tiene costo")),
                crear("p", { class: "texto-secundario" }, "Las órdenes que ya lo tienen conservan su costo.")),
            acciones: [
                { texto: "Volver", tipo: "secundario" },
                {
                    texto: "Guardar cambios",
                    tipo: "primario",
                    alHacerClic: async () => {
                        try {
                            const actualizado = await actualizarModificador(modificador.id, {
                                nombre: nombre.value.trim(),
                                tipo: tipo.value,
                                costoExtra: tipo.value === "EXCLUSION" ? "0" : normalizarMonto(costo.value)
                            });
                            cerrarModal();
                            mostrarExito(`Modificador "${actualizado.nombre}" actualizado`);
                            await cargarModificadores();
                        } catch (error) {
                            mostrarError(error);
                        }
                    }
                }
            ]
        });
    }

    function pedirEliminarModificador(modificador) {
        confirmar({
            titulo: "Eliminar modificador",
            mensaje: `¿Eliminar "${modificador.nombre}" del menú? Ya no se podrá elegir en órdenes nuevas; las órdenes que ya lo tienen no cambian.`,
            textoConfirmar: "Eliminar",
            alConfirmar: async () => {
                try {
                    await eliminarModificador(modificador.id);
                    mostrarExito(`Modificador "${modificador.nombre}" eliminado`);
                    cargarModificadores();
                } catch (error) {
                    mostrarError(error);
                    return false;
                }
            }
        });
    }

    function editarUsuario(usr) {
        const esPropio = usuarioActual && Number(usuarioActual.id) === Number(usr.id);
        const rol = crear("select", { id: "editar-usr-rol", disabled: esPropio },
            ROLES.map((r) => crear("option", { value: r, selected: usr.rol === r }, etiqueta(r))));
        const activo = crear("input", { type: "checkbox", id: "editar-usr-activo", checked: Boolean(usr.activo), disabled: esPropio });

        abrirModal({
            titulo: `Usuario: ${usr.usuario}`,
            contenido: crear("form", { class: "formulario" },
                crear("p", { class: "texto-secundario" }, `${usr.nombre} (${usr.correo})`),
                campo("Rol asignado", rol),
                crear("label", { class: "casilla", for: "editar-usr-activo" }, activo, "Cuenta activa"),
                esPropio ? crear("p", { class: "texto-secundario" }, "No puedes cambiar tu propio rol ni desactivar tu propia cuenta.") : null),
            acciones: [
                { texto: "Volver", tipo: "secundario" },
                esPropio ? null : {
                    texto: "Guardar cambios",
                    tipo: "primario",
                    alHacerClic: async () => {
                        try {
                            await actualizarUsuario(usr.id, { rol: rol.value, activo: activo.checked });
                            cerrarModal();
                            mostrarExito(`Usuario "${usr.usuario}" actualizado`);
                            await cargarUsuarios();
                        } catch (error) {
                            mostrarError(error);
                        }
                    }
                }
            ].filter(Boolean)
        });
    }

    function guardarPlato(evento) {
        evento.preventDefault();
        const platoId = idPlato.value.trim();
        const nombre = nombrePlato.value.trim();
        const categoria = categoriaPlato.value.trim();
        const archivoImagen = imagenPlato.files && imagenPlato.files[0] ? imagenPlato.files[0] : null;

        mientrasEspera(botonPlato, async () => {
            try {
                await registrarPlato({
                    id: platoId,
                    nombre,
                    categoria,
                    precioBase: normalizarMonto(precioPlato.value),
                    tasaImpuesto: TASA_IVA
                });
                if (archivoImagen) {
                    await subirImagenPlato(platoId, archivoImagen);
                }
                mostrarExito(`Plato "${nombre}" registrado`);
                formularioPlato.reset();
                idPlato.focus();
                await cargarPlatos();
            } catch (error) {
                mostrarError(error);
            }
        });
    }

    const estadoCostoNuevo = { valorPrevio: "" };
    function guardarModificador(evento) {
        evento.preventDefault();
        const nombre = nombreModificador.value.trim();
        const tipo = tipoModificador.value;
        mientrasEspera(botonModificador, async () => {
            try {
                await registrarModificador({
                    id: idModificador.value.trim(),
                    nombre,
                    tipo,
                    costoExtra: tipo === "EXCLUSION" ? "0" : normalizarMonto(costoModificador.value)
                });
                mostrarExito(`Modificador "${nombre}" registrado`);
                formularioModificador.reset();
                estadoCostoNuevo.valorPrevio = "";
                actualizarCosto();
                idModificador.focus();
                await cargarModificadores();
            } catch (error) {
                mostrarError(error);
            }
        });
    }

    function actualizarCosto() {
        fijarCostoSegunTipo(tipoModificador, costoModificador, estadoCostoNuevo);
    }
}

/** Si el tipo es EXCLUSION, el costo se deshabilita y se fija en "0"; al volver a EXTRA restaura el valor previo. */
function fijarCostoSegunTipo(tipo, costo, estadoCosto) {
    const esExclusion = tipo.value === "EXCLUSION";
    if (esExclusion) {
        if (!costo.disabled && costo.value !== "0") {
            estadoCosto.valorPrevio = costo.value;
        }
        costo.value = "0";
    } else if (costo.disabled) {
        costo.value = estadoCosto.valorPrevio ?? "";
    }
    costo.disabled = esExclusion;
}

function miniaturaPlato(plato) {
    const sinFoto = () => crear("span", { class: "ficha-plato__sin-imagen" }, "Sin foto");
    if (!plato.imagen) return sinFoto();
    const img = crear("img", { src: plato.imagen, alt: "", class: "ficha-plato__miniatura", loading: "lazy" });
    img.addEventListener("error", () => img.replaceWith(sinFoto()));
    return img;
}

function mensajeLista(lista, mensaje, clase = "texto-secundario") {
    limpiar(lista).appendChild(crear("li", { class: `plato-mensaje ${clase}` }, mensaje));
}

function tabla(encabezados, columnasNumericas, cuerpo) {
    return crear("div", { class: "tabla-contenedor" },
        crear("table", { class: "tabla" },
            crear("thead", {}, crear("tr", {},
                encabezados.map((texto, i) => crear("th", { scope: "col", class: columnasNumericas.includes(i) ? "numero" : null }, texto)))),
            cuerpo));
}

function filaMensaje(cuerpo, columnas, mensaje, clase = "texto-secundario") {
    limpiar(cuerpo).appendChild(crear("tr", {}, crear("td", { colspan: columnas, class: clase }, mensaje)));
}