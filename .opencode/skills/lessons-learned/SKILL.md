---
name: lessons-learned
description: Use when a bug, unexpected behavior or important decision is discovered in the Chorombo project. Defines how to record the lesson in AGENTS.md so it is available in every session.
---

# Lessons learned

El proyecto mantiene un registro de aprendizajes en la sección **Lessons learned** de
`AGENTS.md`. Cada sesión carga ese archivo, por lo que es la base de conocimiento del agente.

## Cuándo registrar

- Se descubre un comportamiento inesperado del entorno (PHP, MySQL, Apache, Windows).
- Una decisión de diseño evita repetir un problema.
- Se documenta una restricción que no es evidente leyendo el código.

## Cómo registrar

Agregar una viñeta al final de la sección, con una frase concreta y accionable:

```markdown
- <síntoma o situación>: <causa>; <solución o regla a seguir>.
```

## Reglas

- Escribir la lección pensando en el próximo agente, no en la sesión actual.
- No duplicar lecciones existentes; si una cambia, editarla.
- Mantener las viñetas cortas (una o dos líneas).
- Si la lección implica una regla permanente, agregarla también en las reglas del proyecto.
