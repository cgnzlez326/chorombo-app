---
name: pull-request
description: Use when creating or describing a pull request in the Chorombo project. Defines the required PR body with summary, what was done, why and how to test it.
---

# Pull Request

Todo Pull Request debe tener un cuerpo claro y verificable. Usar esta plantilla:

```markdown
## Resumen
Qué cambia este PR en una o dos frases.

## Qué se hizo
- Cambio concreto 1
- Cambio concreto 2

## Por qué se hizo
Motivo o problema que resuelve. Referencia al requerimiento cuando aplique.

## Cómo probarlo
1. Paso a paso para reproducir la verificación.
2. Comando o endpoint de ejemplo.

## Evidencia
Resultados de pruebas (capturas, salidas de curl o colección Postman).

## Notas
Decisiones tomadas, limitaciones o pendientes.
```

## Checklist antes de abrir el PR

- [ ] El branch parte de `main` o del branch indicado.
- [ ] `php -l` pasa en todos los archivos modificados.
- [ ] Los endpoints nuevos o modificados están reflejados en `public/openapi.yaml`.
- [ ] La colección `tests/postman_collection.json` cubre el cambio cuando corresponde.
- [ ] `AGENTS.md` está actualizado si cambió alguna regla o aprendizaje.
- [ ] No hay archivos subidos ni secretos en el diff.

## Creación

Usar `gh` para crear el PR y devolver la URL:

```powershell
gh pr create --title "Título corto" --body-file pr_body.md
```
