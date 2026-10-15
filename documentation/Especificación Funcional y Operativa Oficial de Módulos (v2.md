# **Especificación Funcional y Operativa Oficial de Módulos (v2.0)**

**Proyecto:** Sistema de Gestión de Activos TI (ITAM) y Control de Mantenimiento de Equipos

**Stack Tecnológico:** PostgreSQL (Base de Datos), Laravel (Backend API RESTful bajo Clean Architecture y principios SOLID/KISS) y React \+ TanStack Router (Frontend SPA).

**Alcance Operativo:** Diseño optimizado para una concurrencia de 80 a 100 usuarios del departamento de garantías, preparado para operar inicialmente mediante cargues masivos (homologado con Cubit) y escalar a una integración directa vía API con el backend de Teleperformance.

## **1\. Módulo: Inventario General**

**(Ruta Frontend: /inventario | Controlador: InventarioController)**

### **1.1. Características de la Pantalla y UI**

* **Tabla Principal:** Lista los registros base desde la tabla inventario\_general. Homologa la estructura visual del sistema actual (Cubit), exponiendo: placa\_id (Identificador 1), serial (Identificador 2), referencia (Ref. Principal), descripcion (Tipo de equipo: CPU, Portátil), zona\_actual, ubicacion\_actual y num\_traslado.

* **Paginación y Búsqueda:** Soporta paginación dinámica (hasta 1000 registros, por defecto 250\) y buscador en tiempo real compatible con pistola lectora de códigos para filtrar por placa, serial, referencia o descripción.

* **Filtros Operativos:** Permite filtrar por número de traslado (num\_traslado), por condición general (pendientes vs intervenidos/cerrados) y por el estado específico de la máquina de estados (Por soplar, Pendiente garantía, Por reacondicionar, Completado, Intervenido, Baja (No sirve)).

* **Acciones:** Acceso al detalle profundo del equipo (con enlace directo a su trazabilidad y evidencias fotográficas) y botón administrativo para forzar la **sincronización/conciliación** del inventario.

### **1.2. Histórico, Trazabilidad y Base de Datos**

* Vincula cada activo con las bitácoras técnicas mediante placa\_id y serial.

* Almacena en tiempo real quién fue el último técnico en tocar el equipo (analista\_intervencion), en qué estación (modulo\_intervencion), en qué fecha/hora (fecha\_intervencion), su ubicacion\_actual (DBAN, ALMACEN, SALA\_DE\_GARANTIA, SALA\_DE\_BAJA) y su estado\_actual.

### **1.3. Flujo Operativo en Taller y Transición de Estados**

* **Proceso Físico:** Representa la "recepción administrativa" en el almacén y actúa como la lista maestra contra la cual opera el laboratorio.

* **Transición de Estados (Regla de Cierre):**

  * Cuando un equipo ingresa a una estación técnica (/equipos o /portatiles) pero queda en un **estado intermedio** (Por soplar, Pendiente garantía o Por reacondicionar), el inventario actualiza su ubicación y estado en curso, pero **mantiene el ciclo abierto (intervenido \= 0)**.

  * El campo intervenido solo muta de 0 a 1 cuando el equipo alcanza un **estado terminal**(Completado, Intervenido o Baja (No sirve)) tras asignarse su ubicación de destino final.

* **Permisos:** Requiere rol admin o permiso inventario.ver para lectura. La sincronización es exclusiva para administradores.

## **2\. Módulo: Cargue Masivo e Ingesta de Datos**

**(Ruta Frontend: /cargue-masivo | Controlador: CargueMasivoController)**

### **2.1. Características de la Pantalla y UI**

* **Interfaz Principal:** Formulario simplificado que solicita el Número de Traslado (num\_traslado / lote de recepción) y un selector de archivos.

* **Formatos Soportados:** Acepta archivos .xlsx, .xls, .csv o .txt exportados desde el aplicativo actual de Teleperformance (Cubit).

* **Acciones:** Botón dedicado para descargar la plantilla oficial de Excel con la estructura de columnas requerida.

### **2.2. Histórico, Trazabilidad y Automatización**

* Alimenta masivamente la tabla inventario\_general, estampando en cada registro el usuario\_cargue(obtenido automáticamente mediante el token de sesión del usuario autenticado) y el lote físico de origen (num\_traslado).

* **Eliminación de Error Humano:** Al asociar el num\_traslado a la placa\_id desde la ingesta, los técnicos de piso ya no deben anotar el traslado en papel ni digitarlo manualmente; al escanear la placa en cualquier módulo, el sistema recupera el número de traslado de forma automática.

### **2.3. Flujo Operativo en Taller y Evolución Arquitectónica**

* **Proceso Físico:** Se ejecuta cuando llega un lote o camión de equipos al área de garantías y el supervisor inyecta el manifiesto de carga en el sistema.

* **Transición de Estados:** Todos los activos se insertan en estado bruto inicial (intervenido \= 0, pendientes de gestión).

* **Preparación para API de Teleperformance (Fase 2):** La lógica de ingesta en Laravel estará encapsulada en un Servicio de Dominio desacoplado; cuando se apruebe el reemplazo definitivo de Cubit, este servicio consumirá los *endpoints* (getters) de la API de Teleperformance para poblar inventario\_general automáticamente sin modificar los módulos de frontend.

* **Permisos:** Uso exclusivo para roles administrativos o con el permiso cargue\_masivo.ejecutar.

## **3\. Módulo: Diagnóstico e Intervención de CPUs**

**(Ruta Frontend: /equipos | Controlador: EquiposController | Servicio: CpuStateMachineService)**

### **3.1. Características de la Pantalla y UI**

* **Bitácora (Tabla):** Lista las gestiones de CPUs con filtros por placa\_id, num\_traslado, tipo\_gestion, estado y técnico responsable.

* **Autocompletado por Escáner:** Al ingresar o escanear con pistola la placa\_id, el formulario consulta inventario\_general, rellena automáticamente los datos del computador (serial, referencia, ubicación origen, número de traslado) y muestra una alerta visual en verde si el equipo está listo para ser procesado.

* **Formulario Clínico Reactivo (Máquina de Estados):** En lugar de permitir que el técnico elija un estado arbitrario, el formulario en TanStack Router despliega preguntas dinámicas según el árbol de decisión. Antes de finalizar, el técnico selecciona la **Ubicación de destino** permitida por esa ruta y, al presionar **Guardar**, el sistema asigna automáticamente el **Estado** que le sigue en el flujo.

* **Acciones:** Subida de fotografía de evidencia del daño o pieza intervenida (foto\_equipo) y campo de observaciones técnicas.

### **3.2. Reglas de la Máquina de Estados (Árbol de Decisión de CPUs)**

El servicio CpuStateMachineService en Laravel y las validaciones del formulario en React imponen estrictamente las siguientes rutas:

1. **Tipo de Gestión: Novedad**

   * *¿Está sucia o requiere cambio de pasta térmica?*

     * **NO** $\rightarrow$ Habilita **Ubicación de destino:** Dban o Almacen $\rightarrow$ Al guardar, registra estado **Completado** *(Cierra flujo)*.

     * **SÍ** $\rightarrow$ Fija **Ubicación de destino:** Sala de Garantia $\rightarrow$ Al guardar, registra estado **Por soplar** *(Queda abierto esperando al módulo /soplado)*.

2. **Tipo de Gestión: Diagnostico**

   * *¿Está funcional?*

     * **NO** $\rightarrow$ *¿Tiene garantía?*

       * **NO** $\rightarrow$ Fija **Ubicación de destino:** Sala de Baja $\rightarrow$ Al guardar, registra estado **Baja (No sirve)** *(Cierra flujo)*.

       * **SÍ** $\rightarrow$ Fija **Ubicación de destino:** Sala de Garantia $\rightarrow$ Al guardar, registra estado **Pendiente garantía** *(Estado intermedio)*.

         * *Transición de cierre post-garantía:* Al resolverse la garantía, solicita **Ubicación de destino:** Dban o Almacen $\rightarrow$ Al guardar, pasa a estado **Completado***(Cierra flujo)*.

     * **SÍ** $\rightarrow$ *¿Es obsoleta?*

       * **SÍ** $\rightarrow$ Fija **Ubicación de destino:** Sala de Baja $\rightarrow$ Al guardar, registra estado **Baja (No sirve)** *(Cierra flujo)*.

       * **NO** $\rightarrow$ *¿Está sucia o requiere cambio de pasta térmica?*

         * **NO** $\rightarrow$ Habilita **Ubicación de destino:** Dban o Almacen $\rightarrow$ Al guardar, registra estado **Completado** *(Cierra flujo)*.

         * **SÍ** $\rightarrow$ Fija **Ubicación de destino:** Sala de Garantia $\rightarrow$ Al guardar, registra estado **Por soplar** *(Queda abierto esperando al módulo /soplado)*.

3. **Tipo de Gestión: Intervención**

   * *¿Hay la pieza?*

     * **NO** $\rightarrow$ Fija **Ubicación de destino:** Sala de Garantia $\rightarrow$ Al guardar, registra estado **Por reacondicionar** *(Estado intermedio)*.

       * *Transición de reanudación:* Cuando la pieza está disponible, el flujo retoma directamente en la evaluación *¿Está sucia o requiere cambio de pasta térmica?* descrita abajo.

     * **SÍ** $\rightarrow$ *¿Está sucia o requiere cambio de pasta térmica?*

       * **NO** $\rightarrow$ Fija **Ubicación de destino:** Sala de Garantia $\rightarrow$ Al guardar, registra estado **Intervenido** *(Cierra flujo)*.

       * **SÍ** $\rightarrow$ Fija **Ubicación de destino:** Sala de Garantia $\rightarrow$ Al guardar, registra estado **Por soplar** *(Queda abierto esperando al módulo /soplado)*.

### **3.3. Flujo Operativo en Taller y Permisos**

* **Proceso Físico:** Es la **primera estación** para CPUs que ingresan al taller. Si el árbol de decisión desemboca en un estado terminal (Completado, Intervenido, Baja (No sirve)), la modal cierra definitivamente la gestión del activo (intervenido \= 1 en inventario\_general). Si desemboca en Por soplar, la máquina se traslada físicamente a la estación de limpieza.

* **Permisos:** Regido por cpus.ver\_bitacora y cpus.registrar.

## **4\. Módulo: Mantenimiento Preventivo / Soplado**

**(Ruta Frontend: /soplado | Controlador: SopladoController)**

### **4.1. Arquitectura e Integración con /equipos**

* Se mantiene como una ruta (/soplado) y tabla independiente (soplado\_registros) para conservar una interfaz rápida adaptada al escaneo con pistola, separar los permisos de los operarios y contabilizar individualmente las métricas de productividad en el Dashboard.

* A nivel de base de datos y lógica de negocio, **está acoplado a la tabla equipos mediante llave foránea (equipo\_id)**, actuando como el ejecutor del paso final de la Máquina de Estados para todos los registros que quedaron en estado **Por soplar**.

### **4.2. Características de la Pantalla y UI**

* **Bitácora (Tabla):** Historial de limpiezas con paginación y filtros por placa, traslado y analista.

* **Validación Inteligente por Escáner:** Al escanear la placa\_id, el sistema verifica que el equipo tenga un registro activo en estado **Por soplar** (mostrando alerta en verde). Si el equipo ya fue cerrado como Completado/Intervenido porque estaba limpio desde Diagnóstico, el sistema alerta al técnico para evitar un reproceso.

* **Formulario de Registro y Despacho:**

  * Campos técnicos: Validación eléctrica preliminar (energiza, da video, acceso a BIOS), tipo de suciedad interna (polvo, cucarachas) y aplicación de pasta térmica.

  * Evidencia: Captura o subida obligatoria de fotografía del interior del equipo (foto\_ruta).

  * **Ubicación de Destino Post-Soplado:** Selector obligatorio con las opciones **Almacen** o **Dban**(según lo exige el diagrama de flujo debajo del estado Por soplar).

### **4.3. Flujo Operativo en Taller y Transición Transaccional de Estados**

* **Proceso Físico:** Representa la **última transición** del flujo operativo para equipos que requirieron mantenimiento físico.

* **Transición de Estados (Transacción Atómica en Laravel):** Al presionar **Guardar** en el formulario de Soplado, el backend ejecuta dentro de un DB::transaction:

  1. Inserta el registro de limpieza en soplado\_registros asociado al analista\_id autenticado.

  2. Invoca a CpuStateMachineService para actualizar el registro padre en equipos, cambiando su ubicacion\_destino a Almacen o Dban y mutando su estado de Por soplar a:

     * **Completado**, si la orden original en /equipos ingresó por Novedad o Diagnostico.

     * **Intervenido**, si la orden original en /equipos ingresó por Intervención.

  3. Actualiza inventario\_general marcando finalmente intervenido \= 1 y estableciendo la nueva ubicación actual del activo (Almacen o Dban).

* **Permisos:** Autorizado mediante soplado.ver\_bitacora y soplado.registrar.

## **5\. Módulo: Portátiles y Garantías**

**(Ruta Frontend: /portatiles | Controlador: PortatilesController)**

### **5.1. Características de la Pantalla y UI**

* **Bitácora (Tabla):** Listado histórico de laptops gestionadas en el taller.

* **Formulario Especializado:** Incluye validaciones exclusivas de portátiles: ejecución del "Test de Lenovo", captura de Serial de Disco, número de "Ticket" del proveedor, identificación de partes FRU y piezas a solicitar.

* **Evidencia Fotográfica:** Modal dedicado para adjuntar fotografías de evidencia del diagnóstico o de la reparación.

### **5.2. Histórico, Trazabilidad y Base de Datos**

* Almacena los registros en la tabla garantia\_portatil, vinculando el activo (placa\_id, serial) con los tickets de proveedores externos y los seriales de las piezas reemplazadas.

### **5.3. Flujo Operativo en Taller y Cierre de Modales**

* **Proceso Físico:** Es la estación única de procesamiento para laptops dentro del área de garantías, dado que operativamente **los portátiles no pasan por el área de soplado**.

* **Transición de Estados y Cierre en Modal:** Se corrige la lógica de las modales para que ningún portátil quede con estado colgado esperando soplado. De acuerdo con la directriz del formulario (Diagnóstico funcional, Intervención interna o Reparación por garantía del proveedor), el analista selecciona la **Ubicación de destino** correspondiente y, al guardar, la modal cierra directamente el ciclo del equipo (pasando a estado final como Completado, Reparado o Baja) y marcando intervenido \= 1 en inventario\_general.

* **Permisos:** Regido por portatiles.ver\_bitacora y portatiles.registrar.

## **6\. Módulo: Trazabilidad / Hoja de Vida**

**(Ruta Frontend: /trazabilidad | Controlador: TrazabilidadController)**

### **6.1. Características de la Pantalla y UI**

* **Interfaz de Consulta:** Buscador único de alta velocidad (compatible con escáner de código de barras) que consulta por placa\_id o serial.

* **Línea de Tiempo (*Timeline*):** Construye visualmente el árbol cronológico con cada estación, cambio de ubicación y transición de estado por la que ha pasado el activo.

### **6.2. Histórico y Agregación de Datos**

* Módulo agregador que unifica los eventos de inventario\_general (ingreso y número de traslado), equipos (evaluación clínica, ubicaciones asignadas y estados intermedios/finales), soplado\_registros(higiene, pasta térmica y despacho final) y garantia\_portatil.

* En cada hito del *timeline* expone: tipo de gestión, analista responsable, fecha y hora exacta, ubicación de destino asignada, estatus resultante y miniatura/visores de las fotografías de evidencia cargadas.

### **6.3. Flujo Operativo en Taller**

* **Proceso Físico:** Herramienta de auditoría y consulta rápida para supervisores y técnicos que reemplaza las consultas históricas de Cubit.

* **Transición de Estados:** 100% solo lectura; no altera registros.

* **Permisos:** Protegido bajo el permiso trazabilidad.ver.

## **7\. Dashboard de Control Operativo**

**(Ruta Frontend: /dashboard | Controlador: DashboardController)**

### **7.1. Características de la Pantalla y UI**

* **Tablero de Mandos (UI/UX Optimizada):** Tarjetas de indicadores clave (KPIs) y gráficos dinámicos con una jerarquía visual limpia para monitoreo en tiempo real.

* **Filtros Temporales y Operativos:** Segmentación por día, semana, mes o año, además de filtrado por módulo (Diagnóstico CPU, Soplado, Portátiles) y por estado de la máquina de estados.

* **Acciones:** Botón principal para **Exportar Bitácora** en formato .csv.

### **7.2. Histórico, Métricas y Exportaciones**

* Gracias a que equipos y soplado\_registros operan en tablas separadas pero vinculadas, el Dashboard acredita con exactitud la productividad individual a cada técnico en su respectiva estación (permitiendo auditar metas diarias, como las 10 intervenciones base por analista).

* El archivo .csv exportado cruza inventario\_general con las bitácoras técnicas incluyendo: ID/Radicado, Número de Traslado, Placa, Serial, Módulo Atendido, Marca/Referencia, Tipo de Gestión, Ubicación de Destino, Estado Actual y Analista interviniente.

### **7.3. Flujo Operativo y Permisos**

* **Proceso Físico:** Pantalla de monitoreo para supervisores (Cristian / Darwin) y pantallas de control en el laboratorio para detectar cuellos de botella (ej. acumulación de máquinas en estado Por soplar o Por reacondicionar).

* **Permisos (RBAC):** Si el usuario posee el permiso restringido dashboard.ver\_solo\_propio, las métricas y exportaciones filtran exclusivamente su propia productividad técnica; los roles admin y supervisores visualizan y exportan la operación completa del taller.

