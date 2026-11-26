# Requisitos del Sistema y Reglas de Negocio

> **Nota sobre supuestos:** Los puntos marcados como **[Supuesto S-XX]** corresponden a definiciones nuevas del stack objetivo (autenticación, sesiones, roles y migración desde Java) que no existían en el código original y requieren validación antes o durante la Fase 3.

---

## 1. Requisitos Funcionales (RF)

### Módulo 1: Autenticación, Sesión y Control de Acceso
| ID | Descripción | Actor(es) | Origen / Referencia |
|---|---|---|---|
| **RF-01** | **Registro de usuarios:** El sistema debe permitir registrar un nuevo usuario capturando nombre completo, correo electrónico (o nombre de usuario único), contraseña y confirmación de contraseña, validando formato y unicidad antes de almacenar la contraseña con hash seguro. **[Supuesto S-01: El registro público asigna el rol base `MESERO` por defecto; los demás roles se asignan por el Administrador o existen en `seed.sql` para pruebas. Validar si se desea permitir elegir rol operativo durante el registro.]** | Visitante / Administrador | Nuevo (Stack objetivo) |
| **RF-02** | **Inicio de sesión:** El sistema debe autenticar al usuario mediante identificador (correo/usuario) y contraseña verificada en el servidor, creando una sesión segura y redirigiendo a su pantalla inicial según su rol. | Todos los actores | Nuevo (Stack objetivo) |
| **RF-03** | **Cierre de sesión:** El sistema debe permitir al usuario autenticado cerrar su sesión activa, destruyendo la sesión en servidor y limpiando el estado de cliente para redirigir a la pantalla de login. | Todos los actores | Nuevo (Stack objetivo) |
| **RF-04** | **Control de acceso por rol:** El sistema debe restringir la navegación en interfaz y la ejecución de endpoints en el backend según el rol del usuario autenticado (`ADMINISTRADOR`, `MESERO`, `COCINERO`, `CAJERO`), devolviendo `401` si no hay sesión y `403` si no tiene permiso. **[Supuesto S-02: Se mantienen exactamente los 4 roles originales del proyecto; el `ADMINISTRADOR` también puede consultar órdenes o acceder únicamente a Menú y gestión de usuarios según se confirme.]** | Todos los actores | Nuevo + `docs/arquitectura-base.md` |
| **RF-05** | **Gestión básica de usuarios (Administrador):** El Administrador debe poder listar usuarios y cambiar su rol o estado activo/inactivo desde la consola de administración. **[Supuesto S-01]** | Administrador | Nuevo (Stack objetivo) |

### Módulo 2: Administración del Menú (RF1 original)
| ID | Descripción | Actor(es) | Origen / Referencia |
|---|---|---|---|
| **RF-06** | **Consultar menú (platos y modificadores):** Listar platos ordenados alfabéticamente por nombre (`id`, `nombre`, `precioBase`, `tasaImpuesto`) y modificadores ordenados por nombre (`id`, `nombre`, `tipo`, `costoExtra`). | Administrador, Mesero (lectura para armar órdenes) | `AdministrarMenuService`, `MenuRestController` |
| **RF-07** | **Registrar plato:** Permitir crear un plato con identificador único, nombre, precio base y tasa de impuesto expresada en fracción (`0` a `1`). | Administrador | `AdministrarMenuService.registrarPlato` |
| **RF-08** | **Editar plato:** Permitir modificar nombre, precio base y tasa de impuesto de un plato existente sin alterar los importes de órdenes ya creadas. | Administrador | `AdministrarMenuService.actualizarPlato` |
| **RF-09** | **Eliminar plato:** Permitir retirar un plato del catálogo activo para nuevas órdenes sin afectar el historial ni la integridad referencial de órdenes existentes. | Administrador | `AdministrarMenuService.eliminarPlato` |
| **RF-10** | **Registrar, editar y eliminar modificador:** Permitir administrar modificadores de tipo `EXTRA` (costo ≥ 0) o `EXCLUSION` (costo fijo 0), conservando los costos históricos en líneas ya registradas. | Administrador | `AdministrarMenuService` |

### Módulo 3: Gestión de Órdenes y Líneas (RF2 y RF3 originales)
| ID | Descripción | Actor(es) | Origen / Referencia |
|---|---|---|---|
| **RF-11** | **Abrir orden:** Permitir crear una nueva orden especificando el tipo (`SALA`, `PARA_LLEVAR` o `DELIVERY`) y una referencia (número de mesa o identificador de pedido externo), iniciando en estado `ABIERTA`. | Mesero | `Orden.abrir`, `OrdenRestController` |
| **RF-12** | **Listar y consultar órdenes:** Permitir listar órdenes (con filtro opcional por estado `ABIERTA`, `CERRADA`, `CANCELADA`, ordenadas de la más antigua a la más reciente) y ver su detalle completo (líneas, modificadores, totales e historial de pagos). | Mesero, Cajero (órdenes listas) | `GestionarOrdenService` |
| **RF-13** | **Agregar línea a orden:** Permitir agregar a una orden `ABIERTA` un plato activo, una cantidad entera positiva y cero o más modificadores, copiando precio, tasa e importes vigentes en ese momento. | Mesero | `Orden.agregarLinea` |
| **RF-14** | **Modificar línea de orden:** Permitir cambiar cantidad y modificadores de una línea únicamente mientras la orden esté `ABIERTA` y la línea esté en estado `PENDIENTE`. | Mesero | `Orden.modificarLinea` |
| **RF-15** | **Cancelar línea de orden:** Permitir cancelar una línea en estado `PENDIENTE` dentro de una orden `ABIERTA`; la línea pasa a `CANCELADA` y deja de contar en cocina y totales. | Mesero | `Orden.cancelarLinea` |
| **RF-16** | **Cancelar orden completa:** Permitir cancelar una orden `ABIERTA` solo si no tiene pago `APROBADO` y ninguna línea activa ha entrado a cocina (`EN_COCCION` o `LISTO`). | Mesero | `Orden.cancelar` |

### Módulo 4: Cocina (RF3 original)
| ID | Descripción | Actor(es) | Origen / Referencia |
|---|---|---|---|
| **RF-17** | **Tablero de cocina:** Mostrar las líneas activas de órdenes `ABIERTA` agrupadas en tres columnas: *En fila* (`PENDIENTE`), *En cocción* (`EN_COCCION`) y *Listo para entrega* (`LISTO`), destacando visualmente los modificadores de tipo `EXCLUSION` y actualizando periódicamente (cada 5 s o manual). | Cocinero | `GestionarCocinaService`, `vista-cocina.js` |
| **RF-18** | **Avanzar estado de preparación:** Permitir pasar una línea de `PENDIENTE` a `EN_COCCION` (*Iniciar cocción*) y de `EN_COCCION` a `LISTO` (*Listo para entrega*). | Cocinero | `Orden.iniciarCoccionLinea`, `Orden.marcarLineaLista` |

### Módulo 5: Caja, Totales y Cobro (RF4 y RF5 originales)
| ID | Descripción | Actor(es) | Origen / Referencia |
|---|---|---|---|
| **RF-19** | **Listar órdenes listas para cobro:** Mostrar en Caja las órdenes `ABIERTA` que tengan al menos una línea activa y todas sus líneas activas en estado `LISTO`. | Cajero | `vista-caja.js` (`estaListaParaEntrega`) |
| **RF-20** | **Calcular totales en servidor:** Calcular subtotal, descuento, impuestos proporcionales por línea, propina y total (con sugerencias rápidas de propina del 10 %, 15 % y 20 % en UI). | Cajero | `Orden.calcularTotales`, `CajaRestController` |
| **RF-21** | **Registrar cobro y cerrar orden:** Procesar el cobro mediante `EFECTIVO`, `TARJETA` o `PASARELA_DIGITAL`. Si el pago es `APROBADO` y cubre el total, cerrar la orden (`CERRADA`) y fijar los totales cobrados; si es `RECHAZADO`, registrar el intento y mantener la orden `ABIERTA` para reintentar. | Cajero | `CobrarOrdenService.cobrar` |

---

## 2. Requisitos No Funcionales (RNF)

| ID | Categoría | Descripción |
|---|---|---|
| **RNF-01** | Stack Tecnológico | Usar exclusivamente HTML5 semántico, CSS3 puro, JavaScript ES Modules puro, PHP 8.x (sin frameworks externos) y MySQL/MariaDB mediante PDO. |
| **RNF-02** | Diseño Responsivo | Diseño **Mobile First**: estilos base pensados para móvil (≥ 320 px) escalando con `@media (min-width: ...)` para tablet y escritorio, usando Flexbox/Grid y unidades relativas, sin desbordamiento horizontal ni estilos en línea. |
| **RNF-03** | Accesibilidad y Semántica | Uso de etiquetas semánticas (`header`, `nav`, `main`, `section`, `article`, `footer`), atributos ARIA (`aria-live`, `aria-current`, `role="dialog"`), contraste suficiente, navegación por teclado y `<label>` asociado en todo campo de formulario. |
| **RNF-04** | Seguridad de Contraseñas | Las contraseñas deben almacenarse únicamente como hash criptográfico mediante `password_hash()` (`PASSWORD_DEFAULT` / bcrypt o argon2) y verificarse con `password_verify()`. |
| **RNF-05** | Seguridad de Consultas | Todas las consultas SQL deben ejecutarse mediante sentencias preparadas de PDO (`prepare`/`execute`) con parámetros enlazados para prevenir inyección SQL. |
| **RNF-06** | Seguridad de Sesión y Autorización | Sesiones PHP con cookies `HttpOnly`, `SameSite=Lax`, regeneración de ID al iniciar sesión (`session_regenerate_id(true)`), validación de entrada/sanitización y verificación de sesión + rol en cada endpoint protegido (la lógica JS es solo UX). |
| **RNF-07** | Integridad Transaccional | Las operaciones críticas de cambio de estado (`cobrar`, `cancelarOrden`, `iniciarCoccion`, `marcarLista`, `modificarLinea`, `cancelarLinea`) deben ejecutarse dentro de transacciones SQL (`BEGIN`/`COMMIT`/`ROLLBACK`) con bloqueo de fila cuando aplique. |
| **RNF-08** | Persistencia y Normalización | Base de datos relacional normalizada al menos en **3FN**, con llaves primarias, llaves foráneas, restricciones `CHECK`/`UNIQUE` e índices en columnas de búsqueda frecuente. |
| **RNF-09** | Precisión Monetaria | Los cálculos monetarios y de impuestos deben evitar errores de punto flotante (usando `BCMath` o aritmética entera en centavos en PHP y columnas `DECIMAL(10,2)` / `DECIMAL(5,4)` en MySQL), con redondeo `HALF_UP` a 2 decimales. |
| **RNF-10** | Prevención de XSS | No usar `innerHTML` con datos de usuario o de BD en el frontend (mantener construcción segura mediante `document.createTextNode` / `util/dom.js`) y escapar/serializar correctamente respuestas JSON UTF-8. |
| **RNF-11** | Consistencia de API | Todas las respuestas de error HTTP (`400`, `401`, `403`, `404`, `409`, `500`) deben devolver el formato JSON `{ "error": "mensaje claro" }` en UTF-8. |
| **RNF-12** | Migración de Datos | Los datos demo actuales (`P-001`..`P-004`, `M-001`..`M-003` y orden de ejemplo de `DatosDemo.java`) deben migrarse al `seed.sql` sin pérdida de información, junto con usuarios de prueba por rol. |

---

## 3. Reglas de Negocio (RN)

| ID | Regla de Negocio |
|---|---|
| **RN-01** | **Unicidad de catálogo:** Cada plato y cada modificador tienen un `id` único; no se permiten IDs duplicados ni vacíos. |
| **RN-02** | **Precios e impuestos de catálogo:** El `precioBase` de un plato debe ser estrictamente mayor a `0` y tener máximo `2` decimales. La `tasaImpuesto` es una fracción entre `0` y `1` (ej. `0.16` = 16 %). |
| **RN-03** | **Costo de modificadores:** Un modificador `EXTRA` debe tener `costoExtra >= 0` (máx. 2 decimales). Un modificador `EXCLUSION` siempre tiene `costoExtra = 0`. |
| **RN-04** | **Inmutabilidad histórica en líneas:** Al agregar una línea a una orden, se copian `precioUnitario`, `tasaImpuestoAplicada` y `costoExtraAplicado` de los modificadores. Editar o eliminar posteriormente platos/modificadores del menú no altera órdenes ya existentes. |
| **RN-05** | **Apertura de orden:** Toda orden requiere obligatoriamente un `tipo` (`SALA`, `PARA_LLEVAR`, `DELIVERY`) y una `referencia` no vacía; nace en estado `ABIERTA`. |
| **RN-06** | **Regla de edición/cancelación de líneas:** Solo se pueden agregar, modificar o cancelar líneas si la orden está `ABIERTA` y (para modificar/cancelar) la línea está en `PENDIENTE`. La cantidad debe ser entero `>= 1`. |
| **RN-07** | **Exclusión de líneas canceladas:** Una línea en estado `CANCELADA` permanece registrada en la orden para trazabilidad, pero se excluye del tablero de cocina y del cálculo de totales. |
| **RN-08** | **Flujo unidireccional de cocina:** Una línea activa avanza estrictamente `PENDIENTE → EN_COCCION → LISTO` y solo mientras su orden permanezca `ABIERTA`. |
| **RN-09** | **Cancelación de orden:** Una orden solo puede cancelarse si está `ABIERTA`, no tiene ningún pago `APROBADO` y ninguna de sus líneas activas salió de `PENDIENTE` (ni `EN_COCCION` ni `LISTO`). Al cancelar la orden, todas sus líneas activas pasan a `CANCELADA`. |
| **RN-10** | **Cálculo determinista de totales:** `subtotal` = suma de `redondear((precioUnitario + suma(costoExtraAplicado)) * cantidad)` de líneas activas. `descuento >= 0` y `descuento <= subtotal`. `propina >= 0`. `baseGravable = subtotal - descuento`. `impuestos` = suma por línea activa de `redondear(importeLinea * tasaImpuestoAplicada * baseGravable / subtotal)` (0 si `subtotal == 0`). `total = redondear(baseGravable + impuestos + propina)`. Todo redondeo es `HALF_UP` a 2 decimales. |
| **RN-11** | **Condición de paso a Caja:** Para cobrar una orden debe estar `ABIERTA`, tener al menos una línea activa y (regla operativa de la vista Caja) todas sus líneas activas deben estar en `LISTO`. |
| **RN-12** | **Regla de pagos y cierre:** Se permite como máximo un pago `APROBADO` por orden. La orden pasa a `CERRADA` únicamente cuando recibe un pago `APROBADO` cuyo monto cubra el total calculado, fijando en ese momento los totales cobrados. |
| **RN-13** | **Simulación de pasarelas:** `EFECTIVO` siempre se aprueba (`EFE-*`). `TARJETA` (`TAR-*`) y `PASARELA_DIGITAL` (`PAS-*`) se aprueban si `monto <= 20000.00` y se rechazan (`RECHAZADO`) si exceden `$20,000.00`. Ante rechazo, la orden sigue `ABIERTA` y admite reintentos. |
| **RN-14** | **Seguridad de roles:** Ningún usuario sin sesión puede operar la API de negocio. Un usuario autenticado solo puede invocar operaciones autorizadas para su rol (excepto lecturas compartidas estrictamente necesarias, como lectura del catálogo de menú por Mesero o lectura de tablero/exclusiones por Cocinero). |

---

## 4. Supuestos para Validación

| ID | Supuesto propuesto | Impacto si cambia |
|---|---|---|
| **S-01** | El registro público (`registro.html`) asigna el rol `MESERO` por defecto (principio de mínimo privilegio), mientras que el `ADMINISTRADOR` puede gestionar roles de usuarios desde su panel y `seed.sql` crea 4 cuentas iniciales (una por rol) para pruebas inmediatas. | Si prefieres que en el formulario de registro el usuario elija uno de los roles operativos (o todos), se ajusta en `registro.html` y `auth/register.php`. |
| **S-02** | Se usan páginas HTML independientes en `/public` (`login.html`, `registro.html`, `ordenes.html`, `cocina.html`, `caja.html`, `menu.html`, `acceso-denegado.html`, `index.html` como redirección inteligente) en lugar de una sola SPA con hash, manteniendo los módulos JS de vistas y componentes existentes. | Cambia el enrutamiento hash (`app.js`) por navegación real multipágina (`window.location`), lo cual hace explícito el mapa de sitio y las redirecciones HTTP/JS. |
| **S-03** | El código Java actual (`src/` y `pom.xml`) se conserva intacto en la rama `main` (y opcionalmente como carpeta de referencia o removido en `refactor/stack-actualizado` según indiques) mientras el sistema operativo completo corre en PHP + MySQL. | Define si en `refactor/stack-actualizado` dejamos `src/` como histórico o dejamos únicamente el nuevo stack limpio. |
| **S-04** | Para evitar que el `COCINERO` dependa de endpoints del `MESERO` (`/api/ordenes`) y del `ADMINISTRADOR` (`/api/modificadores`), el backend PHP expondrá el estado completo del tablero de cocina (`PENDIENTE`, `EN_COCCION`, `LISTO` + marca de exclusión) en el endpoint de cocina. | Mantiene el aislamiento estricto de permisos por rol sin romper el tablero actual de 3 columnas ni el resaltado en rojo de exclusiones. |
| **S-05** | El modo `?mock` de `js/api/mock.js` se retira o deshabilita por defecto al migrar a PHP + MySQL para garantizar persistencia real y control de seguridad en servidor. | Si deseas conservar `?mock` solo para demos sin servidor, se puede mantener desacoplado sin interferir con auth real. |
