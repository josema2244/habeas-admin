# Habeas Admin

![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)
![Angular](https://img.shields.io/badge/Angular-22-DD0031?logo=angular&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-Supabase-4169E1?logo=postgresql&logoColor=white)

## Índice

- [Descripción](#descripción)
- [Estado del proyecto](#estado-del-proyecto)
- [Funcionalidades](#funcionalidades)
- [Cómo está organizado el MVC](#cómo-está-organizado-el-mvc)
- [Cómo ejecutarlo](#cómo-ejecutarlo)
- [Publicación](#publicación)
- [Tecnologías](#tecnologías)
- [Autor](#autor)
- [Licencia](#licencia)

## Descripción

Este proyecto es el módulo de administración de Habeas, mi trabajo de Ingeniería Web (ISWZ3101) en la UDLA. Habeas es un sistema para gestionar el cumplimiento de la Ley Orgánica de Protección de Datos Personales de Ecuador. Su core calcula el riesgo de cada trámite de tratamiento de datos.

La administración alimenta ese core. Desde aquí se gestionan los usuarios, los trámites que se evalúan y las tarifas con las que se calcula el puntaje de riesgo.

La interfaz está hecha en Angular y consume una API en PHP que responde JSON. Los datos viven en PostgreSQL, alojado en Supabase.

## Estado del proyecto

El módulo de administración está terminado. El cálculo de riesgo de las declaraciones y el reporte por rango de fechas pertenecen al core y no están incluidos en esta entrega.

## Funcionalidades

- Inicio de sesión con token JWT. Solo el rol Administrador entra a este módulo.
- Usuarios: crear, editar, activar y desactivar. Cada usuario tiene rol y área.
- Trámites: crear, editar, activar y desactivar. Cada trámite tiene categoría de datos, área y responsable.
- Tarifas de riesgo: editar el valor de cada regla. Cada cambio sube la versión de las reglas.

**Validación en el servidor de datos sensibles.** La cédula se valida en PHP antes de guardarla, con el algoritmo de la cédula ecuatoriana (módulo 10), y no puede repetirse. Angular no la valida: si la cédula es inválida, el error viene de la API. También se validan el correo (formato y unicidad), la contraseña (mínimo 8 caracteres con letras y números) y los valores de las tarifas (entero entre 0 y 100, con el umbral Bajo menor que el Medio).

**Relaciones con listas desplegables.** Ningún formulario pide una clave foránea escrita a mano. Rol, área, categoría de datos y tipo de regla se eligen de listas que salen de la base. Hay dos cascadas:

- En Trámites, al elegir el área, la lista de responsables se carga solo con los responsables de esa área. El servidor también rechaza un responsable que no pertenezca al área elegida.
- En Tarifas, al elegir el tipo de regla, la lista de reglas se carga solo con las de ese tipo.


Video de la explicación: ""

## Cómo está organizado el MVC

- **Vista:** `frontend/src/app/`. Cada pantalla es un componente de Angular (`usuarios.ts`, `tramites.ts`, `tarifas.ts`, `login.ts`).
- **Controlador:** `backend/src/controllers/`. Reciben la petición, validan, consultan la base y responden JSON.
- **Modelo:** las reglas de validación están en `backend/src/validators.php` y el acceso a PostgreSQL en `backend/src/db.php`, con consultas parametrizadas.
- **Rutas:** `backend/public/index.php`.
- **Seguridad:** `backend/src/jwt.php` crea y verifica el token, y `exigirRol()` bloquea a quien no tenga el rol permitido. En Angular, `auth.guard.ts` protege las rutas y `auth.interceptor.ts` envía el token en cada petición.

El recorrido de una petición es: Angular, interceptor, `index.php`, middleware de rol, controlador, PostgreSQL.

## Cómo ejecutarlo

Necesitas PHP 8.3 con la extensión `pdo_pgsql`, Node.js, el CLI de Angular (`npm install -g @angular/cli`) y una cuenta gratuita de Supabase.

1. Clona el repositorio:

   ```bash
   git clone https://github.com/josema2244/habeas-admin.git
   cd habeas-admin
   ```

2. Crea un proyecto en Supabase y, en el SQL Editor, ejecuta en este orden los archivos de la carpeta `database`: `01_habeas.sql`, `02_datos_demo.sql` y `03_admin.sql`.

3. Genera la contraseña de prueba y guárdala en los usuarios demo:

   ```bash
   php -r "echo password_hash('Admin2026!', PASSWORD_BCRYPT);"
   ```

   Copia el resultado y ejecútalo en el SQL Editor:

   ```sql
   UPDATE users SET password_hash = 'EL_HASH_GENERADO';
   ```

4. Configura y levanta el backend. Copia `backend/.env.example` como `backend/.env`, completa los datos de conexión de Supabase (Connect, Session pooler) y corre:

   ```bash
   cd backend
   php -S localhost:8000 -t public public/index.php
   ```

   Al abrir `http://localhost:8000/health` debe responder `"estado":"OK"`.

5. Levanta el frontend:

   ```bash
   cd frontend
   npm install
   ng serve
   ```

   Abre `http://localhost:4200`. La URL de la API está en `frontend/src/app/config.ts`.

Usuario de prueba: `admin@habeas.com` con la contraseña `Admin2026!`. Los otros dos usuarios demo no pueden entrar a este módulo.

## Publicación

- Aplicación: TU-URL-DEL-FRONTEND
- API: TU-URL-DEL-BACKEND
- Base de datos: Supabase (PostgreSQL)

El backend corre en Docker en Render y el frontend como sitio estático en Render. En el plan gratuito el backend se duerme tras unos minutos sin uso, así que la primera petición puede tardar casi un minuto.

## Tecnologías

- Angular con formularios reactivos y signals
- PHP 8.3 sin framework, con PDO
- PostgreSQL en Supabase
- JWT para la autenticación
- Docker y Render para el deploy

## Autor

José Jáuregui

## Licencia

Proyecto académico.