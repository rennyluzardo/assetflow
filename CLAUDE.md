# AssetFlow ITAM - Constitución del Proyecto

Sistema de Gestión de Activos TI y Control de Mantenimiento en Taller (reemplazo de Cubit para Teleperformance)[cite: 1, 2].
Arquitectura diseñada para 80-100 usuarios concurrentes bajo principios SOLID, KISS y Clean Architecture[cite: 2].

## Stack y Estructura del Monorepo
- `/backend`: Laravel 11+ (PHP 8.3+), PostgreSQL, Sanctum, Spatie Permissions[cite: 1, 2].
- `/frontend`: React 19, TypeScript estricto, TanStack Router, TanStack Query, Tailwind CSS.
- `/e2e`: Suite de pruebas End-to-End con Playwright.

## Comandos Esenciales
- Backend dev: `cd backend && php artisan serve`
- Backend test (Obligatorio al tocar lógica): `cd backend && ./vendor/bin/pest`
- Frontend dev: `cd frontend && npm run dev`
- Frontend typecheck: `cd frontend && npm run typecheck`
- E2E Playwright: `npx playwright test`

## Reglas Globales Inquebrantables
1. **Cero Código Espagueti:** Ningún controlador en Laravel ni componente visual en React debe superar las 60-80 líneas de responsabilidad directa; extrae a Services, Actions o Custom Hooks[cite: 2].
2. **Estado Determinista:** En `/equipos`, el campo `estado` NUNCA es elegido libremente por el usuario; se calcula en base a las respuestas clínicas y la `ubicacion_destino`[cite: 5].
3. **Cierre de Inventario (`intervenido`):** En `inventario_general`, `intervenido` solo pasa de `0` a `1` cuando el activo alcanza un estado terminal (`COMPLETADO`, `INTERVENIDO`, `BAJA_NO_SIRVE` o cierre en Portátiles)[cite: 1, 2, 5]. Los estados intermedios (`POR_SOPLAR`, `PENDIENTE_GARANTIA`, `POR_REACONDICIONAR`) mantienen el flujo abierto[cite: 5].
4. **Identidad Segura:** El analista que registra cualquier operación se extrae exclusivamente del token autenticado (`$request->user()->id`), nunca del payload del request[cite: 2].

## Enrutamiento de Skills (Carga Contextual)
Antes de escribir código, lee obligatoriamente la skill correspondiente según la capa que vayas a modificar:
- Si tocas `/backend`, migraciones, API o lógica de estados -> Lee `.claude/skills/backend-laravel/SKILL.md`
- Si tocas `/frontend`, rutas, formularios o estilos -> Lee `.claude/skills/frontend-nordic/SKILL.md`
- Si creas o modificas pruebas E2E -> Lee `.claude/skills/e2e-playwright/SKILL.md`