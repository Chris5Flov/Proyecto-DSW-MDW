# Estructura de Archivos Propuesta

Para cumplir con el stack objetivo (**HTML5 semántico + CSS Mobile First + JS puro + PHP 8 + MySQL/MariaDB**) sin frameworks externos, se propone organizar el proyecto en capas limpias separando archivos públicos web, lógica de servidor PHP, scripts de base de datos y documentación.

---

## 1. Árbol de Carpetas Propuesto

```text
restaurante-pedidos-hexagonal/
├── public/                             # Raíz pública servida por Apache/XAMPP o servidor PHP embebido
│   ├── index.html                      # Redirección inicial según estado de sesión y rol
│   ├── login.html                      # Página pública de inicio de sesión
│   ├── registro.html                   # Página pública de registro de usuario
│   ├── ordenes.html                    # Módulo Mesero (RF2, RF3)
│   ├── cocina.html                     # Módulo Cocinero (RF3)
│   ├── caja.html                       # Módulo Cajero (RF4, RF5)
│   ├── menu.html                       # Módulo Administrador: catálogo y usuarios (RF1)
│   ├── acceso-denegado.html            # Pantalla de error 403 por falta de permisos
│   ├── api.php                         # Front controller único para /api/* (enruta hacia /php)
│   ├── css/
│   │   └── estilos.css                 # Hoja de estilos Mobile First (base móvil + @media min-width)
│   └── js/
│       ├── auth/
│       │   ├── sesion.js               # Guardia de sesión, verificación de rol, navegación dinámica y logout
│       │   ├── login.js                # Lógica y validaciones de login.html
│       │   └── registro.js             # Lógica y validaciones de registro.html
│       ├── api/
│       │   ├── http.js                 # Cliente fetch hacia /api (manejo uniforme de 401/403/400/500)
│       │   └── api.js                  # Fachada de llamadas REST (auth, usuarios, menú, órdenes, cocina, caja)
│       ├── componentes/
│       │   ├── encabezado.js           # Barra superior semántica dinámica según usuario/rol + logout
│       │   ├── insignia.js             # Etiquetas visuales de estados y roles
│       │   ├── modal.js                # Diálogo modal accesible (sin alert/confirm/prompt)
│       │   └── notificaciones.js       # Avisos toast accesibles (aria-live)
│       ├── util/
│       │   ├── dom.js                  # Construcción segura del DOM sin innerHTML
│       │   └── formato.js              # Formato MXN, fechas ISO, porcentajes y etiquetas
│       └── vistas/
│           ├── vista-ordenes.js        # Controlador de pantalla ordenes.html
│           ├── vista-cocina.js         # Controlador de pantalla cocina.html
│           ├── vista-caja.js           # Controlador de pantalla caja.html
│           └── vista-menu.js           # Controlador de pantalla menu.html (+ gestión de usuarios)
├── php/                                # Código backend PHP (fuera de alcance directo si DocumentRoot apunta a /public)
│   ├── config/
│   │   ├── env.php                     # Carga de configuración / variables de entorno (.env opcional)
│   │   └── database.php                # Conexión PDO singleton con UTF-8, excepciones y emulación desactivada
│   ├── core/
│   │   ├── Respuesta.php               # Helpers de respuestas JSON uniformes (200, 201, 204, 400, 401, 403, 404, 500)
│   │   ├── Sesion.php                  # Manejo seguro de sesiones PHP, verificación auth y control RBAC por rol
│   │   ├── Validacion.php              # Sanitización, validación de decimales, enteros, enums y ReglaNegocioException
│   │   └── CalculadoraTotales.php      # Lógica determinista RF4 (subtotal, descuento, impuestos por línea, propina, total)
│   ├── repositories/
│   │   ├── UsuarioRepository.php       # Consultas PDO de usuarios y roles
│   │   ├── MenuRepository.php          # Consultas PDO de platos y modificadores (incluye borrado lógico)
│   │   ├── OrdenRepository.php         # Consultas PDO transaccionales de órdenes, líneas, modificadores aplicados y pagos
│   │   └── CocinaRepository.php        # Consultas PDO optimizadas para el tablero de cocina
│   └── controllers/
│       ├── AuthController.php          # register, login, logout, me
│       ├── UsuarioController.php       # listar usuarios, cambiar rol / estado (ADMINISTRADOR)
│       ├── MenuController.php          # CRUD platos y modificadores (RF1)
│       ├── OrdenController.php         # abrir, listar, consultar, líneas, cancelar (RF2, RF3)
│       ├── CocinaController.php        # tablero pendientes/cocción/listos y transiciones (RF3)
│       └── CajaController.php          # calcular totales y cobrar con procesadores simulados (RF4, RF5)
├── db/                                 # Scripts SQL para MySQL / MariaDB
│   ├── schema.sql                      # Creación de base de datos, tablas 3FN, FKs, CHECKs e índices
│   └── seed.sql                        # Datos iniciales (roles, usuarios demo, platos, modificadores y orden demo)
├── docs/                               # Documentación y modelado
│   ├── requisitos.md                   # RF, RNF, RN y supuestos
│   ├── mapa-de-sitio.md                # Jerarquía de páginas y matriz de acceso
│   ├── guiones.md                      # Guiones de pantallas y casos de uso
│   ├── diagrama-navegacion.md          # Flujo de navegación y redirecciones
│   ├── estructura-archivos.md          # Este documento
│   ├── base-de-datos.md                # Modelo ER (Mermaid) y diccionario de datos (Fase 3E)
│   ├── arquitectura-base.md            # Referencia de arquitectura original
│   ├── interfaz/contrato-api.md        # Contrato REST actualizado (incluyendo auth y usuarios)
│   └── diagramas/                      # Diagramas originales de referencia
├── tests/                              # Scripts de verificación PHP/HTTP (Fase 4)
├── .env.example                        # Plantilla de variables de entorno de BD
└── README.md                           # Guía actualizada de instalación, BD, ejecución y roles de prueba
```

---

## 2. Justificación de la Organización

1. **Separación `/public` vs `/php` y `/db`:** Solo `/public` contiene recursos accesibles desde el navegador (`HTML`, `CSS`, `JS` y el punto de entrada único `api.php`). Las credenciales de base de datos, repositorios PDO y lógica de negocio quedan en `/php`, reduciendo la superficie de exposición.
2. **Front Controller `public/api.php`:** Permite que todas las rutas REST (`/api/...`) funcionen tanto con el servidor integrado de PHP (`php -S localhost:8080 -t public public/router.php` o URL `/api.php/...` en XAMPP) como con Apache + `.htaccess` sin depender de configuraciones complejas.
3. **Conservación del diseño modular en `/public/js`:** Se preservan `util/dom.js`, `util/formato.js`, `componentes/*` y las 4 vistas funcionales (`vista-ordenes.js`, `vista-cocina.js`, `vista-caja.js`, `vista-menu.js`), añadiendo únicamente `auth/*` y `componentes/encabezado.js` para sesiones y control de acceso.
4. **Separación de responsabilidades en `/php`:** Se mantiene el espíritu de la arquitectura original (controladores HTTP → validación/reglas de dominio → repositorios PDO), facilitando pruebas y mantenimiento sin frameworks.
5. **Carpeta `/db` autocontenida:** `schema.sql` y `seed.sql` permiten levantar la base de datos desde cero en cualquier instancia MySQL/MariaDB (XAMPP incluido) conservando los datos demo originales.

---

## 3. Decisión sobre el Código Java Existente (`src/` y `pom.xml`) **[Supuesto S-03]**

* En la rama original `main` permanece intacto el proyecto Java hexagonal.
* En la rama `refactor/stack-actualizado`, proponemos:
  * **Alternativa recomendada:** Conservar `src/` y `pom.xml` intactos como respaldo/referencia histórica dentro del repositorio (sin mezclar archivos) o bien retirarlos en un commit final limpio si prefieres que el repositorio contenga únicamente el stack PHP + MySQL. *Queda marcado como supuesto para que nos confirmes antes de finalizar la Fase 3.*
