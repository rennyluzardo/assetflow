---
description: Orquesta el desarrollo completo de un módulo (Backend + Tests de Dominio + Frontend Nórdico + E2E Playwright)
---

Actúa como el Arquitecto Orquestador para implementar o refactorizar la funcionalidad: **$ARGUMENTS**.

No escribas código a ciegas en todas las capas a la vez. Ejecuta estrictamente esta secuencia por fases y detente si una validación falla:

### Fase 1: Contrato de Datos y Backend (Lee `.claude/skills/backend-laravel/SKILL.md`)
1. Verifica o crea las migraciones en PostgreSQL y modelos relacionados (`inventario_general`, `equipos`, `soplado_registros`, `garantia_portatil`)[cite: 1].
2. Implementa el `FormRequest` (incluyendo validación de permisos RBAC), el `Service` de dominio (ej. `CpuStateMachineService`) dentro de `DB::transaction` y el `Controller` delgado (máx. 60 líneas)[cite: 1, 2, 5].
3. **Puerta de Calidad 1:** Escribe y ejecuta el test en Pest PHP (`cd backend && ./vendor/bin/pest`) validando todas las ramas de estado y restricciones de ubicación antes de tocar el frontend[cite: 5].

### Fase 2: Interfaz Escandinava y Enrutamiento (Lee `.claude/skills/frontend-nordic/SKILL.md`)
1. Define los tipos en TypeScript reflejando exactamente el `JsonResource` del backend.
2. Crea o actualiza la ruta en TanStack Router (`/inventario`, `/cargue-masivo`, `/equipos`, `/soplado`, `/portatiles`, `/trazabilidad`, `/dashboard`)[cite: 1].
3. Implementa la UI bajo diseño nórdico minimalista, asegurando soporte de pistola lectora de códigos (`autoFocus` + `Enter`) y cierre correcto de modales[cite: 2].
4. **Puerta de Calidad 2:** Ejecuta `npm run typecheck` y corrige cualquier error de tipos.

### Fase 3: Verificación E2E de Taller (Lee `.claude/skills/e2e-playwright/SKILL.md`)
1. Escribe el test E2E en Playwright para el flujo crítico de usuario recién construido.
2. **Puerta de Calidad 3:** Ejecuta `npx playwright test` y entrega un resumen de las pruebas superadas.