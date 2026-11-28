# Diagrama de Navegación

Este diagrama describe los flujos de navegación entre pantallas públicas, protegidas y de error, contemplando autenticación, redirección por rol, cierre de sesión, sesión expirada (`401`) y falta de permisos (`403`).

```mermaid
flowchart TD
    START(["Usuario accede a / (index.html)"]) --> CHECK_AUTH{"¿Tiene sesión<br/>activa?"}

    CHECK_AUTH -- "No" --> LOGIN["/login.html<br/>(Pública)"]
    LOGIN <-- "Enlace Regístrate /<br/>Ya tengo cuenta" --> REG["/registro.html<br/>(Pública)"]
    REG -- "Registro exitoso (201)" --> LOGIN

    LOGIN -- "Login exitoso (200)<br/>Redirección por rol" --> ROLE_ROUTER{"Rol del usuario"}
    CHECK_AUTH -- "Sí" --> ROLE_ROUTER

    ROLE_ROUTER -- "MESERO" --> ORDENES["/ordenes.html"]
    ROLE_ROUTER -- "COCINERO" --> COCINA["/cocina.html"]
    ROLE_ROUTER -- "CAJERO" --> CAJA["/caja.html"]
    ROLE_ROUTER -- "ADMINISTRADOR" --> MENU["/menu.html"]

    ORDENES -. "Acceso directo por URL<br/>sin permiso (rol != MESERO)" .-> FORBIDDEN["/acceso-denegado.html<br/>(403)"]
    COCINA -. "Acceso directo por URL<br/>sin permiso (rol != COCINERO)" .-> FORBIDDEN
    CAJA -. "Acceso directo por URL<br/>sin permiso (rol != CAJERO)" .-> FORBIDDEN
    MENU -. "Acceso directo por URL<br/>sin permiso (rol != ADMINISTRADOR)" .-> FORBIDDEN

    FORBIDDEN -- "Botón: Ir a mi sección" --> ROLE_ROUTER
    FORBIDDEN -- "Botón: Cerrar sesión" --> LOGOUT["POST /api/auth/logout"]

    ORDENES -- "Clic Cerrar sesión /<br/>Sesión expirada (401)" --> LOGOUT
    COCINA -- "Clic Cerrar sesión /<br/>Sesión expirada (401)" --> LOGOUT
    CAJA -- "Clic Cerrar sesión /<br/>Sesión expirada (401)" --> LOGOUT
    MENU -- "Clic Cerrar sesión /<br/>Sesión expirada (401)" --> LOGOUT

    LOGOUT -- "Destruye sesión y redirige" --> LOGIN
```

---

## Reglas de Redirección

1. **Usuario anónimo intentando abrir una página protegida** (`/ordenes.html`, `/cocina.html`, `/caja.html`, `/menu.html`, `/acceso-denegado.html`):
   * El guardia de cliente (`js/auth/sesion.js`) consulta `GET /api/auth/me`. Al recibir `401 Unauthorized`, redirige inmediatamente a `/login.html`.
   * Cualquier llamada directa a la API protegida sin cookie de sesión devuelve `401 Unauthorized`.
2. **Usuario autenticado abriendo `/login.html` o `/registro.html`:**
   * Se consulta `GET /api/auth/me`. Si ya hay sesión válida, se redirige a la página principal de su rol.
3. **Usuario autenticado abriendo una página de otro rol:**
   * El guardia de página verifica `usuario.rol`. Si no está autorizado para esa página, redirige a `/acceso-denegado.html`.
   * En el servidor, cada endpoint verifica el rol en `$_SESSION` y responde `403 Forbidden` si un cliente intenta invocar una acción de otro rol.
4. **Expiración de sesión durante el uso o polling (Cocina/Caja):**
   * Si `http.js` recibe HTTP `401` en cualquier petición, limpia el estado local y redirige a `/login.html`.
5. **Cierre de sesión (Logout):**
   * Al pulsar **Cerrar sesión**, se invoca `POST /api/auth/logout`, PHP destruye `$_SESSION` y expira la cookie, y el navegador redirige a `/login.html`.
