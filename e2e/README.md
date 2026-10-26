# AssetFlow — Pruebas End-to-End (Playwright)

Suite de Playwright para los **Critical User Journeys** del taller, según
`.claude/skills/e2e-playwright/SKILL.md`: login y redirección por permisos,
cadena CPU → Soplado, flujo de Portátiles y línea de tiempo de trazabilidad.

## Prerrequisitos

Los tests mutan estado compartido en PostgreSQL, así que solo hace falta sembrar
**una vez** y mantener los servidores levantados:

```bash
# 1. Base de datos y servidores (una única vez)
cd backend && php artisan migrate:fresh --seed
php artisan serve                    # http://localhost:8000

# 2. Frontend (otra terminal)
cd frontend && npm run dev           # http://localhost:5173 (proxy /api → :8000)
```

> La idempotencia la garantiza `php artisan e2e:reset-fixtures`, invocado desde
> `global-setup.ts` antes de cada corrida: devuelve las placas reservadas
> (CPUs `AF-100001`..`AF-100015` y portátiles `AF-100101`..`AF-100110`) a su
> estado pendiente (`intervenido = 0`, `estado_actual = null`), sin tocar el
> resto de la base de datos.

## Instalación y ejecución

```bash
cd e2e
npm install
npx playwright install chromium
npx playwright test            # headless (idempotente: puede correrse N veces)
npm run test:headed            # con navegador visible
```

## Convenciones

- **Page Object / helpers** en `tests/fixtures.ts` (`login`, `escanear`).
- Selectores accesibles (`getByRole`, `getByLabel`, `getByTestId`) y nunca
  clases de Tailwind.
- Las placas de prueba provienen de `database/seeders/InventarioMockSeeder.php`.
