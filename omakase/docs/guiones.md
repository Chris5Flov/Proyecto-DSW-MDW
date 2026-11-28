# Guiones de Pantallas y Casos de Uso

Este documento describe cada pantalla del sistema (propósito, elementos, acciones, validaciones y mensajes) y los guiones de casos de uso principales basados en la lógica real del sistema.

---

## Parte A: Guiones de Pantallas

### 1. Pantalla: Inicio de Sesión (`/login.html`)
* **Propósito:** Autenticar al personal del restaurante para iniciar su jornada en su módulo asignado.
* **Acceso:** Pública (si el usuario ya tiene sesión, redirige a su pantalla principal).
* **Elementos:**
  * Encabezado de marca ("Sistema de Pedidos") y pie de página institucional (`<footer>`).
  * Tarjeta centrada con formulario `<form>` accesible:
    * Campo **Correo electrónico o usuario** (`<input type="text" required autocomplete="username">`).
    * Campo **Contraseña** (`<input type="password" required minlength="6" autocomplete="current-password">`).
    * Botón de envío **Iniciar sesión** (`type="submit"`).
    * Contenedor de alerta de error (`role="alert"`).
    * Enlace secundario: *"¿No tienes cuenta? Regístrate aquí"* → `/registro.html`.
    * *(Opcional en desarrollo)* Nota informativa con roles de prueba del `seed.sql` o sin credenciales en producción.
* **Acciones del usuario:** Llenar credenciales, presionar *Iniciar sesión* o navegar a registro.
* **Validaciones:**
  * **Cliente (HTML + JS):** Campos obligatorios no vacíos, contraseña mínimo 6 caracteres; deshabilitar botón durante la petición (`aria-busy="true"`).
  * **Servidor (PHP):** Verificar que el usuario exista, esté activo (`activo = 1`) y `password_verify()` coincida con el hash guardado; regenerar ID de sesión.
* **Mensajes:**
  * Éxito: Toast *"Bienvenido, {nombre}"* y redirección inmediata a la vista del rol.
  * Error validación: *"Completa usuario y contraseña."*
  * Error credenciales (`401`): *"Credenciales inválidas o usuario inactivo."*

---

### 2. Pantalla: Registro de Usuario (`/registro.html`)
* **Propósito:** Permitir la creación de una cuenta nueva en el sistema.
* **Acceso:** Pública (**Supuesto S-01:** crea cuenta con rol `MESERO` por defecto).
* **Elementos:**
  * Encabezado de marca y `<footer>`.
  * Formulario `<form>`:
    * **Nombre completo** (`type="text" required minlength="3" maxlength="100"`).
    * **Correo electrónico** (`type="email" required maxlength="120"`).
    * **Usuario** (`type="text" required pattern="[a-zA-Z0-9_.-]{3,30}"`).
    * **Contraseña** (`type="password" required minlength="8"`).
    * **Confirmar contraseña** (`type="password" required minlength="8"`).
    * Botón **Crear cuenta** (`type="submit"`).
    * Enlace *"¿Ya tienes cuenta? Inicia sesión"* → `/login.html`.
* **Acciones del usuario:** Capturar datos, confirmar contraseña, enviar formulario.
* **Validaciones:**
  * **Cliente:** Formato de correo válido, patrón de usuario sin espacios, longitud mínima de contraseña (8 caracteres) y coincidencia exacta entre contraseña y confirmación.
  * **Servidor:** Sanitizar textos, validar correo (`filter_var`), unicidad de `usuario` y `correo`, hash con `password_hash(..., PASSWORD_DEFAULT)`.
* **Mensajes:**
  * Éxito (`201`): *"Cuenta creada correctamente. Ahora puedes iniciar sesión."* → redirige a `/login.html`.
  * Error duplicado (`409`/`400`): *"El correo o nombre de usuario ya está registrado."*
  * Error confirmación: *"Las contraseñas no coinciden."*

---

### 3. Pantalla: Órdenes (`/ordenes.html`)
* **Propósito:** Permitir al **Mesero** abrir comandas, agregar o ajustar platillos/modificadores mientras estén pendientes, y cancelar líneas u órdenes completas.
* **Acceso:** Protegida (Rol `MESERO`).
* **Elementos:**
  * `<header>` común con marca, `<nav>` filtrado por rol, indicador de usuario/rol y botón **Cerrar sesión**.
  * Columna izquierda:
    * Tarjeta **Nueva orden**: `<select>` Tipo (`SALA`, `PARA_LLEVAR`, `DELIVERY`), `<input type="text" required>` Referencia (ej. *"Mesa 5"*), botón **Abrir**.
    * Tarjeta **Órdenes**: `<select>` filtro de estado (`Abiertas`, `Cerradas`, `Canceladas`, `Todas`) y lista `<ul class="lista-ordenes">` de tarjetas interactivas.
  * Columna derecha (**Detalle de orden**):
    * Encabezado con referencia, fecha de creación e insignias de tipo y estado.
    * Tabla de líneas (`Plato`, `Cantidad`, `Precio unitario`, `Modificadores`, `Estado`, `Acciones`: botones *Editar* y *Cancelar* solo si `editable === true`).
    * Formulario **Agregar línea** (visible si `ABIERTA`): `<select>` Plato, `<input type="number" min="1" step="1" required>` Cantidad, `<fieldset>` de checkboxes Modificadores, botón **Agregar**.
    * Resumen `<dl>` de totales (`Subtotal`, `Descuento`, `Impuestos`, `Propina`, `Total`).
    * Historial de pagos (si existen intentos).
    * Botón **Cancelar orden** (visible si `ABIERTA`), con modal de confirmación.
  * `<footer>` común.
* **Validaciones:**
  * Cliente: Referencia no vacía, cantidad entero `>= 1`.
  * Servidor: Orden existente y `ABIERTA`; plato/modificadores válidos y activos; modificación/cancelación de línea solo si `estadoCocina === 'PENDIENTE'`; cancelación de orden solo sin líneas en cocina/listas y sin pago `APROBADO`.
* **Mensajes:**
  * Éxito: *"Orden \"Mesa 5\" abierta"*, *"Línea agregada"*, *"Línea actualizada"*, *"Línea cancelada"*, *"Orden \"Mesa 5\" cancelada"*.
  * Error: Mensajes exactos de regla de negocio (ej. *"No se puede cancelar la orden: hay líneas en cocina o listas"*).

---

### 4. Pantalla: Cocina (`/cocina.html`)
* **Propósito:** Permitir al **Cocinero** visualizar en tiempo real las comandas por preparar y avanzar su estado en tres columnas.
* **Acceso:** Protegida (Rol `COCINERO`).
* **Elementos:**
  * `<header>` común con usuario, rol y **Cerrar sesión**.
  * Encabezado de vista: título *"Cocina"*, marca de hora de última actualización (`aria-live="polite"`) y botón **Actualizar** (además de refresco automático cada `5 s`).
  * Tablero de 3 columnas:
    1. **En fila** (`PENDIENTE`): tarjetas con referencia de orden, `plato × cantidad`, lista de modificadores (resaltando en rojo y negrita las `EXCLUSION`) y botón **Iniciar cocción**.
    2. **En cocción** (`EN_COCCION`): tarjetas con borde naranja y botón **Listo para entrega**.
    3. **Listo para entrega** (`LISTO`): tarjetas con borde verde y texto *"Esperando cobro en caja"*.
  * `<footer>` común.
* **Validaciones:**
  * Servidor (con transacción): la orden debe seguir `ABIERTA`; solo `PENDIENTE → EN_COCCION` y `EN_COCCION → LISTO`.
* **Mensajes:**
  * Éxito: *"Cocción iniciada: {plato} ({referencia})"*, *"{plato} ({referencia}) listo para entrega"*.
  * Error: *"Solo se puede iniciar la cocción de una línea PENDIENTE..."* o error de conexión en actualización manual.

---

### 5. Pantalla: Caja (`/caja.html`)
* **Propósito:** Permitir al **Cajero** revisar órdenes terminadas en cocina, aplicar descuentos y propinas, cobrar por método de pago y cerrar la orden.
* **Acceso:** Protegida (Rol `CAJERO`).
* **Elementos:**
  * `<header>` común.
  * Columna izquierda: lista **Listas para entrega** (órdenes `ABIERTA` con ≥ 1 línea activa y todas en `LISTO`; refresco automático cada `5 s`).
  * Columna derecha:
    * Encabezado con referencia, tipo y estado.
    * Tabla resumen de líneas activas.
    * Formulario de cobro (visible si `ABIERTA`):
      * **Descuento** (`type="number"` / decimal `>= 0`, `step="0.01"`).
      * **Propina** (`type="number"` / decimal `>= 0`, `step="0.01"`).
      * Botones de propina rápida: **10 %**, **15 %**, **20 %**.
      * Desglose de totales calculado por servidor (`Subtotal`, `Descuento`, `Impuestos`, `Propina`, `Total`).
      * Selector de método de pago (`EFECTIVO`, `TARJETA`, `PASARELA_DIGITAL`).
      * Botón **Cobrar $X.XX**.
    * Panel de resultado del último intento (`APROBADO` en verde o `RECHAZADO` en rojo) e historial de pagos de la orden.
* **Validaciones:**
  * Cliente: Descuento y propina numéricos `>= 0`; deshabilitar botón mientras se recalculan totales.
  * Servidor: Revalidar que la orden siga `ABIERTA`, tenga líneas activas, no tenga pago `APROBADO` previo, `0 <= descuento <= subtotal` y `propina >= 0`.
* **Mensajes:**
  * Éxito: *"Pago aprobado: $XXX.XX"* y oculta formulario de cobro al cerrar la orden.
  * Rechazo (`200` con `estado: RECHAZADO`): *"Pago rechazado, intenta con otro método"* (tarjeta/pasarela > `$20,000.00`).
  * Cambio concurrente: *"La orden ya no está lista para entrega: tiene platillos pendientes en cocina"*.

---

### 6. Pantalla: Administración de Menú y Usuarios (`/menu.html`)
* **Propósito:** Permitir al **Administrador** gestionar el catálogo de platos y modificadores (y opcionalmente administrar usuarios/roles).
* **Acceso:** Protegida (Rol `ADMINISTRADOR`).
* **Elementos:**
  * `<header>` común.
  * Tarjeta **Platos**: tabla (`ID`, `Nombre`, `Precio`, `Impuesto`, `Acciones`: Editar/Eliminar en modal) + formulario **Registrar plato** (`id`, `nombre`, `precioBase > 0`, `tasaImpuesto 0..1`).
  * Tarjeta **Modificadores**: tabla (`ID`, `Nombre`, `Tipo`, `Costo extra`, `Acciones`: Editar/Eliminar en modal) + formulario **Registrar modificador** (`id`, `nombre`, `tipo`, `costoExtra >= 0` deshabilitado en `0` si es `EXCLUSION`).
  * Tarjeta **Usuarios del sistema** *(Supuesto S-01)*: tabla de usuarios con selector de rol y activación/desactivación.
* **Validaciones:**
  * Cliente: Campos obligatorios, `precioBase > 0` máx. 2 decimales, `tasaImpuesto` entre `0` y `1`, `costoExtra >= 0` (forzado a `0` en `EXCLUSION`).
  * Servidor: Mismas reglas + unicidad de ID. Eliminación lógica (`activo = 0`) si ya fue usado en órdenes para mantener integridad referencial.
* **Mensajes:**
  * Éxito: *"Plato \"{nombre}\" registrado/actualizado/eliminado"*, *"Modificador \"{nombre}\" registrado/actualizado/eliminado"*.
  * Error: *"Ya existe un plato con id ..."*, *"El precio debe ser mayor a cero"*, *"Una exclusión no puede tener costo extra"*.

---

### 7. Pantalla: Acceso Denegado (`/acceso-denegado.html`)
* **Propósito:** Informar al usuario autenticado que intentó acceder a una sección fuera de sus permisos.
* **Elementos:** Mensaje claro *"403 – No tienes permisos para acceder a esta sección"*, indicación de su rol actual, botón **Ir a mi pantalla principal** y botón **Cerrar sesión**.

---

## Parte B: Guiones de Casos de Uso

### CU-01: Iniciar sesión
* **Actor:** Cualquier usuario registrado (`Administrador`, `Mesero`, `Cocinero`, `Cajero`).
* **Precondiciones:** El usuario posee una cuenta activa en BD.
* **Flujo principal:**
  1. El usuario abre `/login.html` e ingresa usuario/correo y contraseña.
  2. El cliente valida campos no vacíos y envía `POST /api/auth/login`.
  3. El servidor busca el registro, verifica la contraseña con `password_verify()`, regenera la sesión y devuelve `{ usuario, rol, rutaInicio }`.
  4. El navegador redirige a la ruta correspondiente al rol (`/ordenes.html`, `/cocina.html`, `/caja.html` o `/menu.html`).
* **Flujos alternos:**
  * *A1 (Credenciales incorrectas o usuario inactivo):* El servidor responde `401` con `{ "error": "Credenciales inválidas" }`; la pantalla muestra el aviso sin recargar.

### CU-02: Registrar nueva cuenta
* **Actor:** Visitante.
* **Flujo principal:**
  1. En `/registro.html`, captura nombre, correo, usuario, contraseña y confirmación.
  2. El cliente valida formato, longitud mínima y coincidencia de contraseñas, y envía `POST /api/auth/register`.
  3. El servidor valida unicidad, genera hash con `password_hash()` y guarda el usuario con rol `MESERO` **[Supuesto S-01]**.
  4. Se muestra confirmación y se redirige a `/login.html`.
* **Flujos alternos:**
  * *A1 (Usuario/correo duplicado):* El servidor devuelve `409`/`400` y se muestra el mensaje en pantalla.

### CU-03: Administrar catálogo del menú (RF1)
* **Actor:** Administrador.
* **Precondiciones:** Sesión activa con rol `ADMINISTRADOR`.
* **Flujo principal:**
  1. En `/menu.html`, registra un plato o modificador llenando el formulario correspondiente.
  2. El backend valida reglas de negocio (`precioBase > 0`, `0 <= tasa <= 1`, `costoExtra = 0` si es `EXCLUSION`, ID único) y persiste en MySQL.
  3. La tabla se actualiza con los registros ordenados alfabéticamente.
* **Flujos alternos:**
  * *A1 (Editar):* Abre modal con datos actuales; al guardar actualiza el registro sin alterar órdenes existentes.
  * *A2 (Eliminar):* Solicita confirmación en modal; marca el elemento como inactivo/eliminado para nuevas órdenes conservando el historial en órdenes previas.

### CU-04: Abrir orden y gestionar líneas (RF2, RF3)
* **Actor:** Mesero.
* **Precondiciones:** Sesión activa con rol `MESERO`.
* **Flujo principal:**
  1. Selecciona tipo (`SALA`, `PARA_LLEVAR`, `DELIVERY`) e ingresa referencia; envía `POST /api/ordenes`.
  2. En el detalle de la orden abierta, elige plato, cantidad y modificadores y envía `POST /api/ordenes/{id}/lineas`.
  3. El servidor copia `precioUnitario`, `tasaImpuesto` y `costoExtra` al momento de crear la línea en estado `PENDIENTE`.
* **Flujos alternos:**
  * *A1 (Editar o cancelar línea):* Si la línea sigue `PENDIENTE`, permite actualizar cantidad/modificadores (`PUT`) o cancelarla (`DELETE` → `CANCELADA`). Si ya pasó a `EN_COCCION` o `LISTO`, el botón no se muestra y el servidor rechaza con `400`.
  * *A2 (Cancelar orden):* Permitido solo si ninguna línea salió de `PENDIENTE` y no hay pago `APROBADO`.

### CU-05: Preparar platillos en cocina (RF3)
* **Actor:** Cocinero.
* **Precondiciones:** Sesión activa con rol `COCINERO`.
* **Flujo principal:**
  1. En `/cocina.html`, visualiza tarjetas en *En fila* (`PENDIENTE`).
  2. Presiona **Iniciar cocción** (`POST .../iniciar`); la tarjeta pasa a *En cocción* (`EN_COCCION`) y deja de ser editable para el mesero.
  3. Al terminar, presiona **Listo para entrega** (`POST .../lista`); la tarjeta pasa a *Listo para entrega* (`LISTO`).
* **Flujos alternos:**
  * *A1 (Orden cancelada concurrentemente antes de iniciar cocción):* El servidor devuelve `400` indicando que la orden ya no está abierta y el tablero se sincroniza.

### CU-06: Calcular totales y cobrar orden (RF4, RF5)
* **Actor:** Cajero.
* **Precondiciones:** Sesión activa con rol `CAJERO`; la orden está `ABIERTA` con todas sus líneas activas en `LISTO`.
* **Flujo principal:**
  1. Selecciona una orden de la lista *Listas para entrega*.
  2. Captura descuento y/o propina (o usa botones 10/15/20 %); el cliente consulta `GET /api/caja/ordenes/{id}/totales` y muestra el desglose del servidor.
  3. Elige método (`EFECTIVO`, `TARJETA`, `PASARELA_DIGITAL`) y presiona **Cobrar** (`POST /api/caja/ordenes/{id}/cobrar`).
  4. El servidor verifica dentro de transacción que la orden siga lista y sin pago aprobado, procesa el cobro, registra el `Pago` como `APROBADO`, fija `totales` cobrados y cambia la orden a `CERRADA`.
* **Flujos alternos:**
  * *A1 (Pago rechazado):* Para `TARJETA` o `PASARELA_DIGITAL` con total > `$20,000.00`, registra el pago `RECHAZADO`, muestra alerta *"Pago rechazado, intenta con otro método"* y deja la orden `ABIERTA` para reintentar (p. ej. con `EFECTIVO`).
  * *A2 (Mesero agregó un plato justo antes de cobrar):* El cliente y servidor detectan que hay líneas no listas y bloquean el cobro avisando al cajero.
