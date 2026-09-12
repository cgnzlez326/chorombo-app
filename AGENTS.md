# AGENTS.md

Reglas y contexto para trabajar en este proyecto. Leer siempre antes de programar.

## Contexto

Backend (API) de un **Gestor Documental** para la **Escuela Básica G-733 Chorombo Bajo**.
Permite administrar documentos institucionales: memos, oficios, citaciones de apoderados,
acuerdos de apoderados, reuniones comunales y permisos administrativos.

Este repositorio corresponde **solo a la capa Backend**. La capa Frontend es otro proyecto
que consume estos endpoints.

## Reglas del proyecto (obligatorias)

1. **PHP puro**: no usar Laravel, CodeIgniter ni frameworks equivalentes.
2. **Arquitectura MVC**.
3. **Base de datos MySQL**.
4. **Respetar SOLID**.
5. **Mantenerlo simple**: sin sobreingeniería ni dependencias externas.
   Única excepción: `swagger-ui`, que se clona desde su repositorio (no usar CDN).
6. CRUD completo de `Documento` y catálogo de tipos de documento.
7. Validar datos obligatorios antes de persistir.
8. Respuestas consistentes y utilizables desde el Frontend.
9. Manejo de errores claro, sin respuestas ambiguas ni fallas no controladas.
10. Documentar la API con Swagger/OpenAPI, verificando que coincida con el comportamiento real.
11. Cada nuevo desarrollo parte desde `main` (o desde el branch de desarrollo indicado).

## Stack y entorno

- PHP 8.2 (`C:\xampp\php\php.exe`)
- MySQL/MariaDB de XAMPP (`C:\xampp\mysql\bin\mysql.exe`), base `chorombo`, usuario `root` sin password.
- Apache de XAMPP. La app se sirve en `http://localhost/chorombo-app`.
- Sin Composer: autoload propio en `src/autoload.php` (PSR-4 para `App\`).

## Estructura

```
public/index.php      Front controller (delgado)
public/.htaccess      Rewrite hacia index.php
public/openapi.yaml   Especificación OpenAPI
public/swagger-ui/    Swagger UI clonado (vendorizado)
routes/api.php        Definición de rutas
src/bootstrap.php     Arranque + wiring de dependencias (repos -> services -> controllers)
src/autoload.php      Autoload PSR-4
src/Core/             Database, Router, Request, Response, Validator, FileStorage, RateLimiter, Cors, ErrorHandler
src/Controllers/      Controladores delgados
src/Services/         Validación y lógica de negocio
src/Repositories/     Interfaces + implementaciones PDO
src/Models/           Entidades
src/Support/          Presenters (formato de salida, ej. archivo_url)
src/Exceptions/       HttpException, NotFoundException, ValidationException
config/config.php     DB, CORS, paginación, rate limit y subida de archivos
database/schema.sql   DDL + seed de tipos de documento
storage/uploads/      Archivos subidos (ignorados por git)
tests/                Runner PHP (tests/run.php) + colección Postman + fixtures
```

## Endpoints

- `GET    /api/documentos` (filtro `?tipo_documento_id=`, paginación `?page=`/`?per_page=`)
- `GET    /api/documentos/{id}`
- `POST   /api/documentos` (multipart/form-data)
- `PUT    /api/documentos/{id}` / `PATCH /api/documentos/{id}` (multipart/form-data)
- `DELETE /api/documentos/{id}`
- `GET    /api/documentos/{id}/archivo`
- `GET    /api/tipos-documento`

## Convenciones de código

- `declare(strict_types=1);` en todos los archivos PHP.
- Namespace raíz `App\`, alineado con la carpeta en `src/`.
- El `Router` solo resuelve rutas; las rutas concretas viven en `routes/api.php`.
- El front controller (`public/index.php`) no debe contener lógica: solo arranca y despacha.
- El wiring de dependencias va en `src/bootstrap.php`.
- Los controladores no acceden a la base de datos: pasan por `Services` y `Repositories`.
- Depender de interfaces de repositorio (DIP), no de implementaciones.
- No agregar comentarios salvo que aporten claridad real.
- Formato de respuesta:
  - Éxito: `{ "success": true, "data": ... }` (y `"message"` cuando corresponda).
  - Listado: `{ "success": true, "data": [...], "total": n }`.
  - Error: `{ "success": false, "error": { "code", "message", "details"? } }`.
- Códigos HTTP: 200, 201, 404, 405, 409 (duplicado), 413 (cuerpo muy grande), 415, 422
  (validación), 429 (rate limit), 500.

## Comandos útiles

```powershell
# Verificar sintaxis PHP de todo el proyecto
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { & "C:\xampp\php\php.exe" -l $_.FullName }

# Recrear la base de datos (ojo: borra datos)
& "C:\xampp\mysql\bin\mysql.exe" -u root --default-character-set=utf8mb4 -e "source C:/xampp/htdocs/chorombo-app/database/schema.sql"

# Probar un endpoint
curl.exe -s http://localhost/chorombo-app/api/tipos-documento
```

- Swagger UI: `http://localhost/chorombo-app/swagger-ui/`
- Pruebas manuales: importar `tests/postman_collection.json` en Postman.

## Flujo de trabajo

- Ver skill `branch-workflow`: cada desarrollo parte desde `main` o desde el branch indicado.
- Ver skill `pull-request`: todo PR debe incluir resumen, qué se hizo, por qué y cómo probarlo.
- Ver skill `lessons-learned`: registrar aprendizajes relevantes en este archivo.

## Lessons learned

(Agregar aquí cada aprendizaje relevante para el proyecto.)

- PHP no rellena `$_POST` ni `$_FILES` en `PUT`/`PATCH`; el cuerpo se parsea manualmente en
  `src/Core/Request.php` (JSON, urlencoded y multipart) y los temporales se mueven con fallback
  a `rename`/`copy` en `FileStorage`, porque `move_uploaded_file` solo acepta subidas HTTP.
- Al importar `schema.sql` con el cliente `mysql` en Windows hay que usar
  `--default-character-set=utf8mb4`, o los acentos quedan mal codificados.
- La unicidad de documentos se controla con `archivo_hash` (SHA-256) e índice `UNIQUE`, no
  iterando archivos: la verificación es una consulta indexada. El pre-chequeo da el `409` con
  detalle y el índice único cubre la carrera entre requests simultáneos.
- Los `Services` dependen de interfaces (`ValidatorInterface`, `FileStorageInterface`,
  `TransactionManagerInterface`) para poder probarse con dobles en memoria sin Composer. El
  runner de pruebas es `tests/run.php` y los fakes viven en `tests/Support/Fakes.php`.
- El rate limiting es por IP con ventana fija y almacenamiento en archivos (`flock`), sin
  Redis ni APCu, y se aplica en `bootstrap.php` antes del router. Límites configurables en
  `config/config.php` (`rate_limit`).
- El repositorio GitHub pasó de llamarse `ipss-backend` a `chorombo-app`
  (`https://github.com/cgnzlez326/chorombo-app`). El remote local puede seguir apuntando al
  nombre anterior: GitHub redirige, pero conviene actualizarlo con
  `git remote set-url origin https://github.com/cgnzlez326/chorombo-app.git`.
