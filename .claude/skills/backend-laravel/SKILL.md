---
name: backend-laravel
description: Arquitectura limpia en Laravel, PostgreSQL, Máquinas de Estados de CPUs y Portátiles, Trazabilidad, RBAC y Testing con Pest.
---

# Skill: Backend Laravel + PostgreSQL (Zero-Trust & Clean Architecture)

## 1. Reglas de Base de Datos (PostgreSQL)
- Tablas núcleo: `inventario_general`, `equipos`, `soplado_registros` (con FK `equipo_id`), `garantia_portatil`.
- Indexa siempre `placa_id` (Identificador 1), `serial` (Identificador 2) y `num_traslado`.
- Al escanear una `placa_id`, el backend retorna automáticamente su `num_traslado`, ubicación y estado actual para evitar digitación manual.
- Migraciones quirúrgicas: los nuevos campos se agregan con `Schema::table(...)->addColumn` (nunca se reescriben las migraciones fundacionales), preservando los datos existentes.
- En `equipos`: columnas nullable `motivo_baja` (text) y `serial_disco` (string) para la rama de retiro de CPUs.
- En `garantia_portatil`: columnas nullable `pieza_es_nueva` (boolean), `subtipo_intervencion` (`CAMBIO_PIEZA` | `REPOTENCIACION`) y `especificacion_repotenciacion` (text).

## 2. Máquina de Estados de CPUs (`CpuStateMachineService`)
Valida estrictamente que la `ubicacion_destino` sea coherente con las respuestas y retorna el estado automático:
- **NOVEDAD** (exige `observaciones`):
  - `requiere_soplado_o_pasta == false` -> Destinos `['DBAN', 'ALMACEN']` -> `COMPLETADO` (`intervenido = 1`).
  - `requiere_soplado_o_pasta == true` -> Destino `['SALA_DE_GARANTIA']` -> `POR_SOPLAR` (`intervenido = 0`).
- **DIAGNOSTICO** (sin la pregunta `es_obsoleta`, que se trasladó a BAJA):
  - `esta_funcional == false` y `tiene_garantia == true` -> `['SALA_DE_GARANTIA']` -> `PENDIENTE_GARANTIA` (`intervenido = 0`).
  - `esta_funcional == false` y `tiene_garantia == false` -> exige `motivo_baja` y `serial_disco` -> `['SALA_DE_BAJA']` -> `BAJA_NO_SIRVE` (`intervenido = 1`).
  - `esta_funcional == true`:
    - `requiere_soplado_o_pasta == false` -> `['DBAN', 'ALMACEN']` -> `COMPLETADO` (`intervenido = 1`).
    - `requiere_soplado_o_pasta == true` -> `['SALA_DE_GARANTIA']` -> `POR_SOPLAR` (`intervenido = 0`).
- **INTERVENCION** (sin cambios):
  - `hay_pieza == false` -> `['SALA_DE_GARANTIA']` -> `POR_REACONDICIONAR` (`intervenido = 0`).
  - `hay_pieza == true`:
    - `requiere_soplado_o_pasta == false` -> `['SALA_DE_GARANTIA']` -> `INTERVENIDO` (`intervenido = 1`).
    - `requiere_soplado_o_pasta == true` -> `['SALA_DE_GARANTIA']` -> `POR_SOPLAR` (`intervenido = 0`).
- **BAJA** (retiro directo de CPUs):
  - `esta_funcional == false` (daño/quemada) -> exige `motivo_baja` y `serial_disco` -> `['SALA_DE_BAJA']` -> `BAJA_NO_SIRVE` (`intervenido = 1`).
  - `esta_funcional == true` (retiro por vida útil) -> auto `es_obsoleta = true`, `motivo_baja = 'OBSOLETA'`, exige `serial_disco` -> `['SALA_DE_BAJA']` -> `BAJA_NO_SIRVE` (`intervenido = 1`).
  - Los campos automáticos los devuelve `camposAutomaticos()` y el controlador los fusiona con los datos validados.
- **Cierre Transaccional desde `/soplado`:** solo acepta `POR_SOPLAR`; exige destino `['ALMACEN', 'DBAN']` y evidencia `foto_ruta`. En `DB::transaction` guarda en `soplado_registros`, actualiza `equipos` a `COMPLETADO` (Novedad/Diagnóstico) o `INTERVENIDO` (Intervención), y marca `intervenido = 1`.

## 3. Máquina de Estados de Portátiles (`PortatilStateMachineService`)
Cada laptop vive en un **Registro Único** de `garantia_portatil` que se crea con el diagnóstico y se muta en el cierre hacia Almacén. El `estado` y la `ubicacion_destino` se derivan exclusivamente de la Matriz de Clasificación (el analista nunca los elige libremente):
- **FUNCIONAL** -> `['ALMACEN']` -> `COMPLETADO` (`intervenido = 1`).
- **NOVEDAD** (exige `gravedad_novedad` y `observaciones` en ambas gravedades):
  - `MINIMA` -> `['ALMACEN']` -> `COMPLETADO` (`intervenido = 1`).
  - `PRONUNCIADA` (`1 <= cantidad_danos <= 4` + `piezas_solicitar`; `partes_fru` opcional) -> `['SALA_DE_GARANTIA']` -> `POR_REACONDICIONAR` (`intervenido = 0`).
- **GARANTIA** (exclusivamente de fábrica): origen auto `FABRICA`, exige `test_lenovo` y `ticket_proveedor` (`serial_disco` opcional) -> `['SALA_DE_GARANTIA']` -> `PENDIENTE_GARANTIA` (`intervenido = 0`).
- **BAJA** (exige `causal_baja` `OBSOLETO`|`DANO_TOTAL` y `serial_disco` obligatorio) -> `['SALA_DE_BAJA']` -> `BAJA_NO_SIRVE` (`intervenido = 1`).
- **INTERVENCION** (exige `pieza_es_nueva`, `subtipo_intervencion` y `piezas_solicitar`):
  - `CAMBIO_PIEZA` -> `['ALMACEN']` -> `INTERVENIDO` (`intervenido = 1`).
  - `REPOTENCIACION` (exige además `especificacion_repotenciacion`) -> `['ALMACEN']` -> `REPOTENCIADO` (`intervenido = 1`).
- **Punto de Control de Cierre:** solo desde `PENDIENTE_GARANTIA` o `POR_REACONDICIONAR`, destino obligatorio `ALMACEN`, exige `reparado_por` (foto opcional) -> `INTERVENIDO` (`intervenido = 1`), mutando el mismo registro (nunca crea una fila nueva).

## 4. Restricción de Re-intervención (`intervenido = 1`)
- En `POST /api/equipos` y `POST /api/portatiles`, si el activo ya tiene `intervenido = true` y el usuario autenticado **no** tiene el rol `admin`, se bloquea con `422` y el mensaje: *"Este equipo ya fue intervenido. Solo un administrador puede autorizar una nueva gestión."*
- Implementado en `EquipoService::asegurarIntervenible()` y `PortatilService::asegurarIntervenible()`, invocados desde los controladores antes de resolver el estado.

## 5. Módulo de Trazabilidad (`TrazabilidadService`)
- `GET /trazabilidad?q=` busca por `placa_id` **o** `serial` y consolida los eventos de las cuatro estaciones (`inventario_general`, `equipos`, `soplado_registros`, `garantia_portatil`) en una línea de tiempo cronológica ascendente.
- El evento de portátil usa `updated_at`: coincide con el diagnóstico si está abierto y con el cierre si está cerrado (Registro Único que muta en el tiempo).

## 6. Seguridad y RBAC
- Aplica middlewares/policies para: `inventario.ver`, `cargue_masivo.ejecutar`, `cpus.ver_bitacora`, `cpus.registrar`, `soplado.ver_bitacora`, `soplado.registrar`, `portatiles.ver_bitacora`, `portatiles.registrar`, `trazabilidad.ver`, `dashboard.ver_solo_propio`.
- `GET /portatiles/buscar/{placaId}` usa `portatiles.registrar` (no `ver_bitacora`) porque está acoplado a iniciar el diagnóstico.
- Valida MIME types reales en subidas de Excel/CSV y fotografías, guardándolas con nombres UUID.
- Mantén la ingesta aislada en un servicio preparado para cambiar el cargue de Excel por el cliente HTTP de la API de Teleperformance.

## 7. Pruebas de Backend (Pest PHP)
- Por cada endpoint o regla de estado modificada, crea un Feature/Unit Test en `tests/Feature/` o `tests/Unit/CpuStateMachineTest.php` que pruebe tanto las transiciones válidas como el rechazo (`422 Unprocessable Entity`) de combinaciones ilegales de ubicación/estado.
- Cubre la matriz completa de portátiles con datasets (`->with([...])`) y verifica el Registro Único con `assertDatabaseCount('garantia_portatil', 1)` tras el cierre.
- Los tests corren en SQLite `:memory:` (ver `phpunit.xml`): no requieren PostgreSQL ni Docker.

## 8. Listados paginados (patrón `{data, meta}`)
Referencia canónica: `GET /api/inventario` → `InventarioController` → `InventarioService::listar()` (espejo en `PortatilService::listar()`). Cualquier listado nuevo debe replicarlo:
- `FormRequest` valida `page` (`integer|min:1`), `per_page` (`integer|min:1|max:1000`), filtros de texto/enum, `sort` contra un enum whitelist y `direction` con `Rule::in(['asc','desc'])`.
- El service resuelve `sort`/`direction` en un `OrdenInventarioService` (o equivalente) que aplica ORDER BY seguro **con tiebreaker estable `orderByDesc('id')`** para paginación determinista, y cierra con `->paginate((int) $request->validated('per_page', 10))`.
- Devuelve SIEMPRE el mismo envoltorio: `['data' => $paginator->items(), 'meta' => [current_page, last_page, per_page, total, from, to]]` (sin `links`); el frontend depende de esa forma.
- Mantén el default de `per_page` alineado con el frontend (10).
