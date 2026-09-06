# Cliente Feliz - API REST

Backend PHP (MVC, sin frameworks) para el sistema de **selección de personal** del Contact Center "Cliente Feliz". Expone una API REST en JSON que, en una etapa futura, se conectará con el sitio web corporativo de la empresa.

## ¿Qué hace?

La API permite gestionar el proceso de reclutamiento con dos perfiles de usuario:

- **Reclutador**: crea y administra ofertas laborales, revisa los postulantes de cada oferta y va avanzando el estado de cada postulación (Postulando → Revisando → Entrevista Psicológica → Entrevista Personal → Seleccionado / Descartado), dejando comentarios en cada cambio.
- **Candidato**: se registra, ve las ofertas activas, se postula a una oferta y consulta el estado de su postulación con los comentarios del reclutador.

## Endpoints principales

| Método | Ruta | Descripción |
| ------ | ---- | ----------- |
| POST | `/api/usuarios` | Registro de candidato (público) |
| GET/PUT/DELETE | `/api/usuarios[/{id}]` | Administración de usuarios |
| GET/POST | `/api/ofertas` | Listar / crear ofertas laborales |
| GET/PUT/DELETE | `/api/ofertas/{id}` | Ver, editar o dar de baja una oferta |
| POST | `/api/postulaciones` | El candidato se postula a una oferta |
| GET | `/api/postulaciones` | Postulaciones (candidato ve solo las suyas) |
| GET | `/api/postulaciones/{id}` | Postulación con estado e historial de comentarios |
| PUT | `/api/postulaciones/{id}` | Reclutador cambia el estado y agrega comentario |
| GET | `/api/estados` | Catálogo de estados de postulación |

La autenticación es por **HTTP Basic** (`Authorization: Basic base64(email:contraseña)`) y cada endpoint valida el perfil que puede usarlo.

## Tecnologías

- PHP 8 (sin frameworks) con estructura MVC.
- MySQL / MariaDB (XAMPP), usando *prepared statements*.
- Respuestas JSON con códigos HTTP correctos (201, 200, 404, 422, 401, 403, 500).

## Instalación

1. Copiar la carpeta del proyecto en el DocumentRoot de Apache: `C:\xampp\htdocs\cliente-feliz`.
2. Crear la base de datos (script idempotente, incluye datos de ejemplo):

   ```bat
   cmd /c "C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 < database.sql"
   ```

3. Acceder a la API en `http://localhost/cliente-feliz/api/`.

> Nota: se incluye un reclutador de ejemplo (`reclutador@clientefeliz.cl` / `reclutador123`).

## Documentación

- **Swagger UI** (spec OpenAPI interactiva): `http://localhost/cliente-feliz/swagger/`
- `INFORME-PROYECTO.md`: informe técnico del proyecto.
- `REGISTRO-PROYECTO.md`: bitácora de pasos y decisiones.
