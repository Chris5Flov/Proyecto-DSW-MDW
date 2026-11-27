# OMAKASE: Sistema de pedidos

Aplicación web para un restaurante de barrio (ficticio): órdenes de mesero, tablero de cocina, caja y administración de menú y usuarios, con inicio de sesión y permisos por rol.

Proyecto final de Desarrollo de Sitios Web.

## Tecnologías

HTML5, CSS (Mobile First), JavaScript puro, PHP 8 con PDO y MySQL/MariaDB. Sin frameworks.

## Requisitos

- PHP 8.x (por ejemplo, el de XAMPP)
- MySQL o MariaDB (por ejemplo, el de XAMPP)

## Instalación y ejecución

Desde la carpeta del proyecto (la que contiene `db`, `php` y `public`):

1. Copia la configuración:
   ```
   copy .env.example .env
   ```
2. Enciende MySQL (panel de XAMPP) e importa la base de datos:
   ```
   C:\xampp\mysql\bin\mysql.exe -u root < db\schema.sql
   C:\xampp\mysql\bin\mysql.exe -u root < db\seed.sql
   ```
3. Levanta el servidor (si el puerto 8000 está ocupado, usa otro):
   ```
   php -S 127.0.0.1:8000 -t public public/router.php
   ```
4. Abre http://127.0.0.1:8000 e inicia sesión.

## Usuarios de prueba

`admin`, `mesero`, `cocina` y `caja`. Las contraseñas están en el comentario al inicio de `db/seed.sql`.

## Estructura

```text
public/   Front y punto de entrada (páginas, css, js, img, api.php, router.php)
php/      Backend (config, core, repositories, controllers)
db/       schema.sql y seed.sql
docs/     Documentación del proyecto
```

## Documentación

Las decisiones del proyecto están en `docs/decisiones.md`. Los manuales y el contrato se entregan en el PDF final (LaTeX).