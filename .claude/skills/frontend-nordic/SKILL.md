---
name: frontend-nordic
description: Desarrollo Frontend con React, TanStack Router, UX para escáner de taller y diseño escandinavo minimalista.
---

# Skill: Frontend Escandinavo Minimalista + TanStack Router

## 1. Sistema de Diseño Escandinavo Nórdico
- **Superficies:** Fondos claros de alto confort visual (`bg-[#F8FAFC]` para el canvas, `bg-white` para paneles, bordes finos `border border-slate-200/80`, cero sombras dramáticas).
- **Tipografía:** `Inter` o `Geist` para UI; `JetBrains Mono` (`font-mono`) obligatoria para `placa_id`, `serial`, `num_traslado`, tickets y fechas de trazabilidad[cite: 1, 4].
- **Semántica Visual de Estados (Tonos Nórdicos Suaves):**
  - *Alerta Verde (`bg-emerald-50 text-emerald-700 border-emerald-200`):* Equipo disponible para procesar al escanear placa, o estados cerrados (`Completado`, `Intervenido`)[cite: 2, 5].
  - *Ámbar (`bg-amber-50 text-amber-700 border-amber-200`):* Estados abiertos en tránsito (`Por soplar`, `Pendiente garantía`, `Por reacondicionar`)[cite: 5].
  - *Terracota (`bg-rose-50 text-rose-700 border-rose-200`):* Estado `Baja (No sirve)` o bloqueo porque el equipo no requiere pasar por esa estación[cite: 2, 5].

## 2. Ergonomía de Taller (Pistola Lectora y Modales)
- **Inputs de Escaneo (`placa_id`):** Deben tener `autoFocus`, seleccionar su contenido al recibir foco y disparar la consulta inmediatamente al detectar `Enter` (comportamiento nativo de pistola de código de barras) sin recargar la página[cite: 1, 2].
- **Formularios Progresivos en `/equipos`:** Muestra las preguntas clínicas paso a paso según el árbol de decisión[cite: 5]. Filtra el selector de `ubicacion_destino` únicamente a las opciones válidas y muestra una píldora de previsualización: *"Al guardar pasará a estado: [ESTADO]"*[cite: 5].
- **Cierre Estricto de Modales en `/portatiles`:** Los portátiles NO pasan por `/soplado`; al guardar el formulario de portátil (con Test de Lenovo, serial de disco y FRU), la modal debe cerrar el estatus definitivamente y refrescar la query del inventario[cite: 1, 2].

## 3. Arquitectura de Rutas en TanStack Router
Usa enrutamiento tipado (`createFileRoute`) con validación de `searchParams` (para filtros de tabla y paginación de 250 a 1000 ítems)[cite: 1]:
- `/dashboard`, `/cargue-masivo`, `/inventario`, `/equipos`, `/soplado`, `/portatiles`, `/trazabilidad`[cite: 1].