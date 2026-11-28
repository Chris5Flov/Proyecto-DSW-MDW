# Decisiones del proyecto: OMAKASE

Fase 1 (decisiones y base) · Fecha de corte: 26 de noviembre de 2026

Este documento reúne las decisiones ya cerradas. Es la base de la sección de requisitos (Fase 7), del manual técnico y del contrato (Fase 8).

---

## 1. Datos del proyecto

| Concepto | Valor |
|---|---|
| Materia | Desarrollo de Sitios Web |
| Proyecto | Aplicación web de un negocio o emprendimiento propio |
| Equipo | 2 integrantes: Chris y Luis (nombres provisionales) |
| Ponderación | 40 % de la calificación final |
| Entrega | Semana del 30 de noviembre al 4 de diciembre de 2026 |
| Revisión | Presencial |
| Entregables | Código fuente (.zip) y documentación en PDF elaborada en LaTeX |
| Base | Proyecto del restaurante de otra materia, migrado a PHP + MySQL (la temática es libre; el profesor ya aprobó usarlo) |

## 2. Negocio y cliente

**Negocio (ficticio):** OMAKASE, restaurante de barrio con comida típica pero también refinada. "Desde 2026".

**Identidad visual:** el logo ya existe (PNG con fondo transparente, 488×443 px). Paleta tomada del logo (valores aproximados):

| Uso | Color |
|---|---|
| Marrón vino oscuro (encabezado, botones principales) | `#381820` |
| Beige (fondos, tarjetas, detalles) | `#DAC1A3` |
| Blanco hueso (texto sobre oscuro) | `#FAFAF7` |

**Contrato de prestación de servicios:**

| Parte | Datos |
|---|---|
| Cliente | Jorge Álvaro Díaz Rodriguez, persona física con actividad empresarial, propietario de OMAKASE (ficticio) |
| Prestador | Chris y Luis, como equipo de desarrollo (nombres provisionales; reemplazar por los reales) |
| Leyenda | Contrato ficticio, con fines académicos |

## 3. Stack y reglas técnicas

- **Front:** HTML5 semántico, CSS Mobile First (`min-width`) y JavaScript puro (ES Modules).
- **Backend:** PHP 8.x con PDO, sin frameworks.
- **Base de datos:** MySQL / MariaDB, normalizada en 3FN.
- **Sin frameworks** de ningún tipo (el enunciado no los pide).
- **Seguridad mínima:** contraseñas con `password_hash`, consultas preparadas, sesión PHP y verificación de rol en cada endpoint (el JS solo es UX).
- **Pagos:** simulados (efectivo, tarjeta, pasarela digital).

## 4. Estructura de carpetas final

```text
omakase/
├── public/        Front y punto de entrada (index, login, módulos, css/, js/, img/, api.php, router.php)
├── php/           Backend (config/, core/, repositories/, controllers/)
├── db/            schema.sql y seed.sql
├── docs/          Documentación (requisitos, mapa de sitio, guiones, BD, etc.)
├── .env.example
└── README.md
```

## 5. Roles y permisos

Cinco roles, con permisos **estrictos** (cada rol hace solo lo suyo; es más simple de defender).

| Acción | Admin | Gestor | Mesero | Cocinero | Cajero |
|---|:-:|:-:|:-:|:-:|:-:|
| Ver menú | ✔ | | ✔ | | |
| Editar menú y modificadores | ✔ | | | | |
| Crear y editar órdenes | | | ✔ | | |
| Ver y avanzar cocina | | | | ✔ | |
| Cobrar | | | | | ✔ |
| Gestionar usuarios | | ✔ | | | |

**Alta de usuarios:**
1. Una persona nueva se registra en `registro.html`.
2. Su cuenta queda **inactiva** (pendiente de aprobación).
3. El Gestor la activa y le asigna el rol.
4. El Gestor también puede crear cuentas y restablecer contraseñas.

**Usuarios de prueba:** `admin`, `mesero`, `cocina` y `caja` (contraseñas en el comentario de `db/seed.sql`). El usuario `gestor` se agrega al seed en la Fase 3 o 5.

## 6. Menú de OMAKASE

IVA del **16 %** en todos los platos (ver sección 7). Precios base.

| Categoría | Plato | Precio |
|---|---|---:|
| Del mar | Coctel de camarón | 150 |
| Del mar | Sushi | 135 |
| Del mar | Filete de pescado empanizado | 130 |
| Del mar | Empanada de camarón | 60 |
| Platos fuertes | Bistec encebollado | 155 |
| Platos fuertes | Enchiladas suizas | 110 |
| Platos fuertes | Pozole | 90 |
| Antojitos | Tacos (orden de 4) | 60 |
| Antojitos | Chalupas (orden de 5) | 55 |
| Antojitos | Gordita/memela (por pieza) | 23 |
| Postres | Fresas con crema | 60 |
| Bebidas | Cerveza | 45 |
| Bebidas | Refresco | 30 |
| Bebidas | Agua de jamaica/horchata (vaso) | 32 |

**Modificadores:**

| Modificador | Tipo | Costo |
|---|---|---:|
| Sin cebolla | Exclusión | 0 |
| Sin picante | Exclusión | 0 |
| Queso extra | Extra | 15 |
| Aguacate extra | Extra | 20 |

## 7. Decisión sobre el IVA

La tasa de **16 % viene incluida por defecto**: al registrar un plato ya no se teclea `0.16`. El sistema calcula el IVA automáticamente y lo suma al precio base.

- El esquema ya trae `DEFAULT 0.1600` en `platos.tasa_impuesto`.
- Pendiente: que el formulario de platos preseleccione el 16 % o que el backend lo asuma si no se envía (verificar en Fases 4 y 6).

## 8. Lo que sigue (fuera de la Fase 1)

| Pendiente | Fase |
|---|---|
| Menú OMAKASE y campo `categoria` en la BD y el seed | 3 |
| Rol Gestor, registro con cuenta inactiva, crear usuarios, `usuarios.html` | 5 |
| Marca OMAKASE (colores, logo, favicon, títulos) y parte pública | 6 |
| Actualizar requisitos, mapa de sitio, guiones y diagrama de navegación | 7 |
| Manual de usuario, manual técnico y contrato | 8 |
| PDF en LaTeX, prueba en instalación limpia y .zip | 9 |

## 9. Checklist de la rúbrica del profesor

| Requisito | Fase | Estado |
|---|---|---|
| Modelación: requisitos, mapa de sitio, guiones, diagrama de navegación, estructura de archivos | 7 | Borradores en `docs/`, por actualizar |
| Desarrollo: HTML + CSS Mobile First + JS (login/restricciones) + PHP + BD | 2 a 6 | Avanzado (backend, BD y login en pie) |
| Manual de usuario y manual técnico | 8 | Pendiente |
| Contrato de prestación de servicios | 8 | Pendiente |
| Código fuente en .zip y documentación en PDF (LaTeX) | 9 | Pendiente |

## 10. Checklist de la Fase 1

- [x] Stack confirmado (HTML + CSS + JS + PHP + MySQL, sin frameworks)
- [x] Negocio definido (OMAKASE)
- [x] Cliente ficticio definido
- [x] Roles y permisos definidos (5 roles, Admin estricto)
- [x] Alta de usuarios, categorías e IVA decididos
- [x] PHP y MySQL funcionando
- [ ] Respaldo y limpieza del Java (`limpiar-fase1.bat`)
- [ ] Carpeta renombrada a `omakase`
- [ ] Logo en `public/img/logo.png`
- [ ] `git --version` y `pdflatex --version` responden
- [ ] Repositorio coordinado con el compañero y primer commit
