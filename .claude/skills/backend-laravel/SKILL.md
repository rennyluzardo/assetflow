---
name: backend-laravel
description: Arquitectura limpia en Laravel, PostgreSQL, Máquinas de Estados de CPUs y Portátiles, Trazabilidad, RBAC y Testing con Pest.
---

# Skill: Backend Laravel + PostgreSQL (Zero-Trust & Clean Architecture)

## 1. Reglas de Base de Datos (PostgreSQL)
- Tablas núcleo: `inventario_general`, `equipos`, `soplado_registros` (con FK `equipo_id`), `garantia_portatil`[cite: 1].
- Indexa siempre `placa_id` (Identificador 1), `serial` (Identificador 2) y `num_traslado`[cite: 1, 4].
- Al escanear una `placa_id`, el backend debe retornar automáticamente su `num_traslado`, ubicación y estado actual para evitar digitación manual[cite: 2].

## 2. Máquina de Estados de CPUs (`CpuStateMachineService`)
Valida estrictamente que la `ubicacion_destino` sea coherente con las respuestas del formulario y retorna el estado automático[cite: 5]:
- **NOVEDAD:**
  - `requiere_soplado_o_pasta == false` -> Destinos permitidos: `['DBAN', 'ALMACEN']` -> Estado: `COMPLETADO` (Cierra inventario: `intervenido = 1`)[cite: 1, 5].
  - `requiere_soplado_o_pasta == true` -> Destino obligatorio: `['SALA_DE_GARANTIA']` -> Estado: `POR_SOPLAR` (`intervenido = 0`)[cite: 1, 5].
- **DIAGNOSTICO:**
  - `esta_funcional == false` y `tiene_garantia == false` -> Destino: `['SALA_DE_BAJA']` -> Estado: `BAJA_NO_SIRVE` (`intervenido = 1`)[cite: 1, 5].
  - `esta_funcional == false` y `tiene_garantia == true` -> Destino: `['SALA_DE_GARANTIA']` -> Estado: `PENDIENTE_GARANTIA` (`intervenido = 0`). Al cerrar garantía -> Destino: `['DBAN', 'ALMACEN']` -> `COMPLETADO` (`intervenido = 1`)[cite: 1, 5].
  - `esta_funcional == true` y `es_obsoleta == true` -> Destino: `['SALA_DE_BAJA']` -> Estado: `BAJA_NO_SIRVE` (`intervenido = 1`)[cite: 1, 5].
  - `esta_funcional == true`, `es_obsoleta == false`:
    - Si `requiere_soplado_o_pasta == false` -> Destino: `['DBAN', 'ALMACEN']` -> `COMPLETADO` (`intervenido = 1`)[cite: 1, 5].
    - Si `requiere_soplado_o_pasta == true` -> Destino: `['SALA_DE_GARANTIA']` -> `POR_SOPLAR` (`intervenido = 0`)[cite: 1, 5].
- **INTERVENCION:**
  - `hay_pieza == false` -> Destino: `['SALA_DE_GARANTIA']` -> Estado: `POR_REACONDICIONAR` (`intervenido = 0`)[cite: 1, 5].
  - `hay_pieza == true` (o al reanudar desde `POR_REACONDICIONAR`):
    - Si `requiere_soplado_o_pasta == false` -> Destino: `['SALA_DE_GARANTIA']` -> Estado: `INTERVENIDO` (`intervenido = 1`)[cite: 1, 5].
    - Si `requiere_soplado_o_pasta == true` -> Destino: `['SALA_DE_GARANTIA']` -> Estado: `POR_SOPLAR` (`intervenido = 0`)[cite: 1, 5].
- **Cierre Transaccional desde `/soplado`:**
  - Solo acepta equipos en estado `POR_SOPLAR`. Exige destino `['ALMACEN', 'DBAN']` y evidencia `foto_ruta`[cite: 1, 5].
  - En un `DB::transaction`: guarda en `soplado_registros`, actualiza el registro padre en `equipos` a `COMPLETADO` (si vino de Novedad/Diagnostico) o `INTERVENIDO` (si vino de Intervención), y marca `intervenido = 1` en `inventario_general`[cite: 1, 5].

## 3. Máquina de Estados de Portátiles (`PortatilStateMachineService`)
A diferencia de las CPUs (que encadenan varias estaciones), cada laptop vive en un **Registro Único** de `garantia_portatil`: se crea con el diagnóstico inicial y se **muta en el mismo registro** al cerrarlo hacia Almacén General. El `estado` y la `ubicacion_destino` se derivan exclusivamente de la Matriz de Clasificación de Laptops (el analista nunca los elige libremente)[cite: 1, 5]:
- **FUNCIONAL:** -> `ALMACEN` -> `COMPLETADO` (`intervenido = 1`).
- **NOVEDAD** (exige `gravedad_novedad`):
  - `MINIMA` -> `ALMACEN` -> `COMPLETADO` (`intervenido = 1`).
  - `PRONUNCIADA` (`cantidad_danos > 1` + `piezas_solicitar`) -> `SALA_DE_GARANTIA` -> `POR_REACONDICIONAR` (`intervenido = 0`).
- **GARANTIA** (exige `origen_dano_garantia`):
  - `FABRICA` (`ticket_proveedor`) -> `SALA_DE_GARANTIA` -> `PENDIENTE_GARANTIA` (`intervenido = 0`).
  - `INDUCIDO` (`piezas_solicitar`) -> `SALA_DE_GARANTIA` -> `POR_REACONDICIONAR` (`intervenido = 0`).
- **DANO_REPARABLE** (`cantidad_danos <= 3` + `piezas_solicitar` obligatorio; `partes_fru` opcional) -> `SALA_DE_GARANTIA` -> `POR_REACONDICIONAR` (`intervenido = 0`).
- **BAJA** (`causal_baja`) -> `SALA_DE_BAJA` -> `BAJA_NO_SIRVE` (`intervenido = 1`).
- **Punto de Control de Cierre (obligatorio):** solo desde `PENDIENTE_GARANTIA` o `POR_REACONDICIONAR`, destino obligatorio `ALMACEN`, exige `reparado_por` (foto opcional) -> `INTERVENIDO` (`intervenido = 1`), mutando el mismo registro (nunca crea una fila nueva)[cite: 1, 5].

## 4. Módulo de Trazabilidad (`TrazabilidadService`)
- `GET /trazabilidad?q=` busca por `placa_id` **o** `serial` y consolida los eventos de las cuatro estaciones (`inventario_general`, `equipos`, `soplado_registros`, `garantia_portatil`) en una línea de tiempo cronológica ascendente[cite: 1, 4].
- El evento de portátil usa `updated_at`: coincide con el diagnóstico si está abierto y con el cierre si está cerrado (Registro Único que muta en el tiempo)[cite: 1, 5].

## 5. Seguridad y RBAC
- Aplica middlewares/policies para: `inventario.ver`, `cargue_masivo.ejecutar`, `cpus.ver_bitacora`, `cpus.registrar`, `soplado.ver_bitacora`, `soplado.registrar`, `portatiles.ver_bitacora`, `portatiles.registrar`, `trazabilidad.ver`, `dashboard.ver_solo_propio`[cite: 1].
- `GET /portatiles/buscar/{placaId}` usa el permiso `portatiles.registrar` (no `ver_bitacora`), porque está acoplado a iniciar el diagnóstico[cite: 1].
- Valida MIME types reales en subidas de Excel/CSV y fotografías, guardándolas con nombres UUID[cite: 1].
- Mantén la ingesta aislada en un servicio preparado para cambiar el cargue de Excel por el cliente HTTP de la API de Teleperformance[cite: 2].

## 6. Pruebas de Backend (Pest PHP)
- Por cada endpoint o regla de estado modificada, crea un Feature/Unit Test en `tests/Feature/` o `tests/Unit/` que pruebe tanto las transiciones válidas como el rechazo (`422 Unprocessable Entity`) de combinaciones ilegales de ubicación/estado[cite: 5].
- Cubre la matriz completa de portátiles con datasets (`->with([...])`) y verifica el Registro Único con `assertDatabaseCount('garantia_portatil', 1)` tras el cierre[cite: 1, 5].

## 4. Pruebas de Backend (Pest PHP)
- Por cada endpoint o regla de estado modificada, crea un Feature/Unit Test en `tests/Feature/` o `tests/Unit/CpuStateMachineTest.php` que pruebe tanto las transiciones válidas como el rechazo (`422 Unprocessable Entity`) de combinaciones ilegales de ubicación/estado[cite: 5].