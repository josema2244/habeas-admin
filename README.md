# Habeas Admin

Trabajo de Ingeniería Web (UDLA). Es el módulo de administración de Habeas, un sistema para llevar el cumplimiento de la Ley Orgánica de Protección de Datos Personales de Ecuador. El core del sistema calcula el riesgo de cada trámite que maneja datos personales. Esta parte sirve para administrar lo que usa ese core: los usuarios, los trámites y las tarifas de riesgo.

El frontend está hecho en Angular, el backend en PHP y la base de datos en PostgreSQL (Supabase).

## Qué hace

- Login con token JWT. Solo el rol Administrador puede entrar a este módulo.
- Usuarios: crear, editar y activar o desactivar. Cada usuario tiene un rol y un área.
- Trámites: crear, editar y activar o desactivar. Cada trámite tiene categoría de datos, área y responsable.
- Tarifas de riesgo: cambiar el valor de cada regla. Cada vez que se cambia una, sube la versión de las reglas.

Todavía no está el cálculo de riesgo de las declaraciones ni el reporte por fechas, porque eso es el core y no entra en esta entrega.

## Validación en el servidor

La cédula se valida en el backend (PHP) antes de guardarla en la base, con el algoritmo de la cédula ecuatoriana. En Angular no se valida, así que si la cédula está mal el error lo manda la API. Además no se puede repetir.

También se validan en el servidor el correo (formato y que no esté repetido), la contraseña (mínimo 8 caracteres con letras y números) y los valores de las tarifas (número entero entre 0 y 100, y el umbral Bajo tiene que ser menor que el Medio).

## Listas desplegables

Ningún formulario pide una clave foránea escrita a mano. El rol, el área, la categoría de datos y el tipo de regla se eligen de listas que salen de la base de datos.

Hay dos listas en cascada:

- En Trámites, cuando elijo el área, la lista de responsables se llena solo con los responsables de esa área. El servidor también rechaza un responsable que no sea de esa área.
- En Tarifas, cuando elijo el tipo de regla, la lista de reglas muestra solo las de ese tipo.

Video de la explicación: 

## Cómo está organizado

Seguí el patrón MVC separado por capas.

- Vista: los componentes de Angular en `frontend/src/app/` (login, usuarios, trámites y tarifas).
- Controlador: los archivos de `backend/src/controllers/`. Reciben la petición, validan, consultan la base y responden en JSON.
- Modelo: las validaciones están en `backend/src/validators.php` y la conexión a la base en `backend/src/db.php`. Las consultas van con parámetros.
- Rutas: `backend/public/index.php`.
- Seguridad: `backend/src/jwt.php` crea y revisa el token, y la función `exigirRol()` bloquea a quien no tenga el rol permitido. En Angular, `auth.guard.ts` protege las rutas y `auth.interceptor.ts` manda el token en cada petición.

## Cómo ejecutarlo

Necesito tener instalado PHP 8.3 con la extensión `pdo_pgsql`, Node.js y el CLI de Angular. También una cuenta de Supabase.

1. Clonar el repositorio:

   ```
   git clone https://github.com/josema2244/habeas-admin.git
   cd habeas-admin
   ```

2. En Supabase, abrir el SQL Editor y ejecutar en orden los archivos de la carpeta `database`: `01_habeas.sql`, `02_datos_demo.sql` y `03_admin.sql`.

3. Generar la contraseña de prueba:

   ```
   php -r "echo password_hash('Admin2026!', PASSWORD_BCRYPT);"
   ```

   Con el resultado, ejecutar en el SQL Editor:

   ```sql
   UPDATE users SET password_hash = 'EL_HASH_GENERADO';
   ```

4. Backend. Copiar `backend/.env.example` como `backend/.env` y poner los datos de conexión de Supabase (en Connect, Session pooler). Después:

   ```
   cd backend
   php -S localhost:8000 -t public public/index.php
   ```

   Si abro `http://localhost:8000/health` debe decir `"estado":"OK"`.

5. Frontend:

   ```
   cd frontend
   npm install
   ng serve
   ```

   Se abre en `http://localhost:4200`. La URL de la API está en `frontend/src/app/config.ts`.

Para probar: `admin@habeas.com` con la contraseña `Admin2026!`. Los otros dos usuarios de prueba no pueden entrar a este módulo porque no son administradores.

## Publicado en

- Aplicación: TU-URL-DEL-FRONTEND

El backend está en Render con Docker y el frontend en Render como sitio estático. En el plan gratis el backend se duerme si no lo usan, así que la primera vez puede demorar casi un minuto en responder.

## Tecnologías

Angular, PHP 8.3, PostgreSQL (Supabase), JWT, Docker y Render.

## Autor

José Jáuregui