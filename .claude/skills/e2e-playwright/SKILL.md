---
name: e2e-playwright
description: Pruebas End-to-End con Playwright enfocadas en flujos críticos de taller, pistolas de escaneo y modales.
---

# Skill: Testing E2E con Playwright para Flujos de Taller

## 1. Qué SÍ probar y qué NO probar en Playwright
- **NO uses Playwright** para probar las 20 permutaciones matemáticas de la máquina de estados (eso le corresponde a Pest PHP en el backend).
- **SÍ usa Playwright** para validar los 5 flujos críticos de principio a fin (Critical User Journeys) en el navegador:
  1. **Ingesta (`/cargue-masivo` -> `/inventario`):** Subida de archivo `.csv`/`.xlsx` con un `num_traslado` y verificación de que los activos aparecen en `/inventario` con `intervenido = 0`[cite: 1, 2].
  2. **Flujo de Escaneo con Pistola:** Simular la escritura rápida en el input de `placa_id` seguida de `keyboard.press('Enter')`, comprobando que se autocompletan el serial, la ubicación y el número de traslado, y que aparece la alerta en verde[cite: 2].
  3. **Cadena Completa CPU + Soplado (`/equipos` -> `/soplado`):**
     - En `/equipos`, diagnosticar una CPU como sucia -> Guardar con destino `Sala de Garantia` -> Verificar que queda en `Por soplar`[cite: 5].
     - Navegar a `/soplado`, escanear la misma `placa_id`, marcar limpieza/pasta térmica, adjuntar foto mock, seleccionar destino `Almacen` o `Dban` y guardar -> Verificar que el estado muta a `Completado` o `Intervenido`[cite: 1, 5].
  4. **Cierre de Modal en Portátiles (`/portatiles`):** Registrar un diagnóstico de portátil y comprobar que la modal se cierra limpiamente y el estado queda finalizado sin requerir soplado[cite: 2].
  5. **Línea de Tiempo (`/trazabilidad`):** Buscar la `placa_id` intervenida y verificar que el *timeline* renderiza cronológicamente el ingreso de inventario, el diagnóstico, el soplado, el nombre del analista y la evidencia fotográfica[cite: 1, 2].

## 2. Convenciones Técnicas de Playwright
- Utiliza el patrón **Page Object Model (POM)** o *fixtures* limpios por estación (`EquiposPage`, `SopladoPage`, `PortatilesPage`).
- Usa selectores accesibles y resilientes (`getByRole`, `getByLabel`, `getByTestId('scanner-input')`), nunca selectores frágiles por clases de Tailwind.