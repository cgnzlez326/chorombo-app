---
name: branch-workflow
description: Use when starting any new development, fix or feature in the Chorombo project. Defines which branch to start from, how to name it and when to open a pull request.
---

# Flujo de trabajo con branches

## Regla base

Todo desarrollo nuevo parte desde `main`, salvo que se indique explícitamente otro
branch de desarrollo. Antes de crear el branch, actualizar la base:

```powershell
git checkout main
git pull
```

## Nombre del branch

Usar prefijo según el tipo de trabajo, en minúsculas y con guiones:

- `feature/` para funcionalidad nueva (ej: `feature/crud-documentos`).
- `fix/` para corrección de errores (ej: `fix/validacion-fecha`).
- `docs/` para documentación (ej: `docs/openapi`).
- `chore/` para tareas de mantenimiento (ej: `chore/autoload`).

## Crear el branch

```powershell
git checkout -b feature/nombre-corto
```

## Durante el desarrollo

- Commits pequeños y con mensaje en imperativo (ej: `Agrega validación de tipo de documento`).
- No commitear archivos subidos (`storage/uploads/`) ni configuración local.
- Mantener el estilo y las reglas de `AGENTS.md`.

## Cierre

- Abrir un Pull Request hacia `main` (o hacia el branch de desarrollo indicado).
- El PR debe seguir la skill `pull-request`.
- No hacer merge sin que el PR describa cómo probar el cambio.
