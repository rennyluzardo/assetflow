---
name: frontend-nordic
description: Desarrollo Frontend con React, TanStack Router, UX para escáner de taller y diseño escandinavo minimalista.
---

# Skill: Frontend Escandinavo Minimalista + TanStack Router

## 1. Sistema de Diseño Escandinavo Nórdico
- **Superficies:** Fondos claros de alto confort visual (`bg-[#F8FAFC]` para el canvas, `bg-white` para paneles, bordes finos `border border-slate-200/80`, cero sombras dramáticas).
- **Tipografía:** `Inter` o `Geist` para UI; `JetBrains Mono` (`font-mono`) obligatoria para `placa_id`, `serial`, `num_traslado`, tickets y fechas de trazabilidad[cite: 1, 4].
- **Semántica Visual de Estados (Tonos Nórdicos Suaves):**
  - *Alerta Verde (`bg-emerald-50 text-emerald-700 border-emerald-200`):* Equipo disponible para procesar al escanear placa, o estados cerrados (`Completado`, `Intervenido`, `Repotenciado`)[cite: 2, 5].
  - *Ámbar (`bg-amber-50 text-amber-700 border-amber-200`):* Estados abiertos en tránsito (`Por soplar`, `Pendiente garantía`, `Por reacondicionar`)[cite: 5].
  - *Terracota (`bg-rose-50 text-rose-700 border-rose-200`):* Estado `Baja (No sirve)` o bloqueo porque el equipo no requiere pasar por esa estación[cite: 2, 5].

## 2. Ergonomía de Taller (Pistola Lectora y Modales)
- **Inputs de Escaneo (`placa_id`):** Deben tener `autoFocus`, seleccionar su contenido al recibir foco y disparar la consulta inmediatamente al detectar `Enter` (comportamiento nativo de pistola de código de barras) sin recargar la página[cite: 1, 2].
- **Formularios Progresivos en `/equipos`:** Muestra las preguntas clínicas paso a paso según el árbol de decisión (incluye la rama `BAJA` de retiro directo). Filtra el selector de `ubicacion_destino` únicamente a las opciones válidas y muestra una píldora de previsualización: *"Al guardar pasará a estado: [ESTADO]"*. En `NOVEDAD` el campo `observaciones` es obligatorio; en `BAJA` y en el diagnóstico no-funcional-sin-garantía se piden `motivo_baja` y `serial_disco` como campos de texto condicionales.
- **Formulario de Portátiles en `/portatiles`:** Los portátiles NO pasan por `/soplado`; viven en un Registro Único. La pantalla sigue el patrón escáner → veredicto → formulario: clasificación (`FUNCIONAL`/`NOVEDAD`/`GARANTIA`/`BAJA`/`INTERVENCION`), campos técnicos (Test de Lenovo, serial de disco), sub-campos condicionales según la rama y un paso `DestinoStep` con la píldora *"Al guardar pasará a estado: [ESTADO]"*. La `GARANTIA` es exclusivamente de fábrica (sin selector de origen) y la `INTERVENCION` pide `pieza_es_nueva`, `subtipo_intervencion` y `piezas_solicitar` (más `especificacion_repotenciacion` si es `REPOTENCIACION`).
- **Cierre de Portátil sin modal automática:** al escanear un portátil con registro abierto (`PENDIENTE_GARANTIA`/`POR_REACONDICIONAR`) NO se abre `CierrePortatilModal` automáticamente; se muestra una alerta ámbar con los detalles y un botón **"Cerrar Registro / Enviar a Almacén"** que abre la modal cuando el técnico lo decida. La modal exige `reparado_por` y permite foto opcional; al cerrar refresca las queries de `inventario` y `portatiles`.
- **Re-intervención en el escáner:** si el activo ya está `intervenido` y el usuario no es `admin`, el escáner muestra en terracota *"Este equipo ya fue intervenido. Solo un administrador puede autorizar una nueva gestión."*; con rol `admin` se permite abrir una nueva gestión.
- **Trazabilidad de solo lectura en `/trazabilidad`:** Línea de tiempo vertical escandinava (`TrazabilidadTimeline`) con punto de color, estación, `StatusBadge`, fecha en `font-mono`, analista, ubicación y evidencia fotográfica. Soporta enlace profundo `?placa_id=` que dispara la consulta automáticamente[cite: 1, 4].

## 3. Arquitectura de Rutas en TanStack Router
Usa enrutamiento tipado (`createFileRoute`) con validación de `searchParams` (para filtros de tabla y paginación de 10 a 50 ítems; la tabla maestra llena el alto del viewport con cabecera sticky y scroll interno)[cite: 1]:
- `/dashboard`, `/cargue-masivo`, `/inventario`, `/equipos`, `/soplado`, `/portatiles`, `/trazabilidad`[cite: 1].
- `/trazabilidad` valida `placa_id` en `searchParams` (`validateSearch`) para permitir el enlace profundo desde otras estaciones[cite: 1].

## 4. Estándar de Tabla Maestra (listados paginados)
Referencia canónica: `/inventario` (carpeta `frontend/src/features/inventario/`). Cualquier tabla de listado nueva debe replicar este patrón y extraer la lógica a Custom Hooks (ningún componente >80 líneas).

**Layout a altura de viewport**
- Pantalla raíz: `flex h-dvh flex-col gap-4 overflow-hidden p-6`; header y barra de filtros con `shrink-0`; la tarjeta de tabla con `flex min-h-0 flex-1 flex-col overflow-hidden`.
- Dentro de la tarjeta: contenedor de scroll `min-h-0 flex-1 overflow-auto`; `<thead>` sticky con fondo sólido (`sticky top-0 z-10 bg-muted` en cada `<th>`, sin transparencias).

**Filas que rellenan el alto**
- Hook `useAlturaFilasInventario(cantidadFilas)` mide el contenedor con `ResizeObserver` y reparte el alto disponible entre las filas, clamp `[40px, 96px]`; devuelve `{ ref, altura }`.
- Aplica `style={{ height: altura }}` al `<tr>` y `align-middle` en los `<td>` para centrar el contenido en filas altas. Con más filas de las que caben se mantiene el scroll natural.

**Paginación y filtros desde la URL**
- Todo el estado (filtros, `page`, `per_page`, `sort`, `direction`) vive en los `searchParams` vía `validateSearch` (TanStack Router); sin estado local duplicado.
- Default `per_page = 10`; selector `[10, 25, 50]`. TanStack Table en `manualSorting` + `manualPagination` (la API ya devuelve el lote ordenado/paginado).
- Contrato de respuesta `{ data, meta }` con `meta = { current_page, last_page, per_page, total, from, to }`.

**Archivos a replicar por feature** (mismo nombre, misma responsabilidad): `columnas.tsx` (definiciones TanStack), `table/features.ts` (features), `useInventarioTable.ts`, `useInventarioUrlState.ts`, `useInventarioOrden.ts`, `orden.ts` (whitelist de columnas ordenables, espejo del enum backend), `params.ts` (searchParams → API), `api.ts` (useQuery + `keepPreviousData`), `types.ts`.