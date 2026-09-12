# Gestor Documental - Escuela Básica G-733 Chorombo Bajo

API REST (backend) para administrar documentos institucionales: memos, oficios,
citaciones y acuerdos de apoderados, reuniones comunales y permisos administrativos.

Construida en **PHP puro** (sin frameworks) con arquitectura **MVC**, **PDO/MySQL** y
principios **SOLID**. No usa Composer: el autoload es propio.

## Requisitos

- PHP 8.2 (XAMPP)
- MySQL/MariaDB (XAMPP), base `chorombo`, usuario `root` sin password
- Apache con `mod_rewrite`

## Puesta en marcha

```powershell
# 1. Crear la base de datos (borra datos existentes)
& "C:\xampp\mysql\bin\mysql.exe" -u root --default-character-set=utf8mb4 -e "source C:/xampp/htdocs/chorombo-app/database/schema.sql"

# 2. Verificar un endpoint
curl.exe -s http://localhost/chorombo-app/api/tipos-documento
```

La configuración (DB, CORS y subida de archivos) está en `config/config.php`.

- API: `http://localhost/chorombo-app/api`
- Swagger UI: `http://localhost/chorombo-app/swagger-ui/`
- OpenAPI: `public/openapi.yaml`

## Estructura

```
public/            Front controller, .htaccess, openapi.yaml y swagger-ui/
routes/api.php     Definición de rutas
src/bootstrap.php  Arranque y wiring (repos -> services -> controllers)
src/Core/          Database, Router, Request, Response, Validator, FileStorage, Cors, ErrorHandler
src/Controllers/   Controladores delgados
src/Services/      Validación y lógica de negocio
src/Repositories/  Interfaces + implementación PDO
src/Models/        Entidades (Documento, TipoDocumento)
src/Exceptions/    HttpException, NotFoundException, ValidationException
config/            Configuración de DB, CORS y uploads
database/          schema.sql (DDL + seed de tipos de documento)
storage/uploads/   Archivos subidos (ignorados por git)
tests/             Colección Postman + fixture de prueba
```

## Endpoints

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/api/documentos` | Lista documentos (filtro `?tipo_documento_id=`, paginación `?page=` y `?per_page=`) |
| GET | `/api/documentos/{id}` | Obtiene un documento |
| POST | `/api/documentos` | Crea un documento (multipart/form-data) |
| PUT / PATCH | `/api/documentos/{id}` | Actualiza un documento (multipart/form-data) |
| DELETE | `/api/documentos/{id}` | Elimina el documento y su archivo |
| GET | `/api/documentos/{id}/archivo` | Descarga el archivo asociado |
| GET | `/api/tipos-documento` | Lista los tipos de documento disponibles |

Tipos de documento: Memo, Oficio, Citación de apoderados, Acuerdo de apoderados,
Reunión comunal, Permiso administrativo.

## Respuestas

```json
// Éxito
{ "success": true, "data": { }, "message": "Documento creado correctamente." }

// Listado
{ "success": true, "data": [ ], "total": 42, "page": 1, "per_page": 20, "last_page": 3 }

// Error
{ "success": false, "error": { "code": "VALIDATION_ERROR", "message": "…", "details": { } } }
```

Códigos HTTP: `200`, `201`, `204` (preflight), `404`, `405`, `409`, `413`, `422`, `429`, `500`.

## Validaciones

- `titulo`: obligatorio, texto, máximo 255 caracteres.
- `tipo_documento_id`: obligatorio, entero y debe existir en el catálogo.
- `fecha`: obligatoria, formato `YYYY-MM-DD`.
- `descripcion`: opcional, texto, máximo 2000 caracteres.
- `archivo`: opcional; extensiones `pdf, doc, docx, xls, xlsx, png, jpg, jpeg`, máximo 5 MB,
  con verificación del contenido real (MIME). Se guarda con nombre único en `storage/uploads/`
  y se elimina al borrar el documento. El cuerpo completo de la solicitud (incluido en
  `PUT`/`PATCH`) está limitado a 6 MB; si se supera, responde `413`.

## Límite de solicitudes

La API aplica rate limiting por IP con ventana fija (`config/config.php`, sección
`rate_limit`): 120 solicitudes por minuto en general y 60 por minuto para operaciones de
escritura (`POST`, `PUT`, `PATCH`, `DELETE`). Al superarlo responde `429` con el header
`Retry-After`. El estado se guarda en archivos dentro de `storage/cache/ratelimit/`.

## Pruebas

### Automatizadas (sin Composer)

```powershell
& "C:\xampp\php\php.exe" tests/run.php
```

Cubren `Validator`, `Router` y `DocumentoService` con repositorios, almacenamiento y
transacciones en memoria (`tests/Support/Fakes.php`).

### Manuales con Postman

Importar `tests/postman_collection.json` en Postman (variable `baseUrl =
http://localhost/chorombo-app`). Incluye el flujo CRUD, casos inválidos (422, 404, 405) y
paginación (metadata y `per_page`/`page` inválidos).

```powershell
# Verificar sintaxis de todo el proyecto
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { & "C:\xampp\php\php.exe" -l $_.FullName }
```

## Documentación y convenciones

Las reglas de trabajo, convenciones y aprendizajes del proyecto están en
[`AGENTS.md`](AGENTS.md).
