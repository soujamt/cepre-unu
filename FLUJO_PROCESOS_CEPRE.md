# UNIVERSIDAD NACIONAL DE UCAYALI
## CENTRO PREUNIVERSITARIO - CEPRE UNU
### FLUJO DE PROCESOS DEL SISTEMA DE INSCRIPCIONES

Este documento resume, con diagramas, **qué se tiene que hacer y en qué orden** para que el sistema funcione de punta a punta: desde la puesta en marcha hasta que una inscripción queda activa y aparece en los reportes.

Complementa a:

- `MANUAL_INSTALACION_CEPRE.md`: detalle técnico de instalación.
- `MANUAL_USUARIO_CEPRE.md`: detalle pantalla por pantalla.

> Los diagramas usan Mermaid. Se ven renderizados en GitHub, GitLab, VS Code (con vista previa Markdown) y la mayoría de editores modernos.

---

## Índice

1. Mapa general
2. Puesta en marcha técnica
3. Configuración: orden obligatorio
4. Checklist antes de abrir inscripciones
5. Flujo de una inscripción (asistente de 5 pasos)
6. Flujo de pagos con el TXT del banco
7. Expedientes y activación de la inscripción
8. Estados del sistema
9. Reglas automáticas que conviene conocer
10. Observaciones y mejoras detectadas

---

## 1. Mapa general

```mermaid
flowchart LR
    A["1. Puesta en marcha<br/>técnica"] --> B["2. Seguridad<br/>usuarios, roles, permisos"]
    B --> C["3. Mantenedores<br/>catálogos base"]
    C --> D["4. Configuración académica<br/>del proceso"]
    D --> E["5. Inscripciones<br/>asistente de 5 pasos"]
    F["Importar TXT<br/>del banco"] --> E
    E --> G["6. Revisión de<br/>expedientes"]
    G --> H["7. Inscripción ACTIVA"]
    H --> I["8. Reportes<br/>y exportación Excel"]
```

| Etapa | Responsable | Frecuencia |
|---|---|---|
| Puesta en marcha técnica | Soporte TI | Una vez por servidor |
| Seguridad | SuperAdministrador | Al inicio y cuando ingresa personal |
| Mantenedores | SuperAdministrador | Al inicio; se actualiza ocasionalmente |
| Configuración académica | SuperAdministrador | **Cada nuevo proceso de inscripción** |
| Importar TXT del banco | Gestor de Inscripciones | Diario durante el proceso |
| Inscripciones y expedientes | Gestor de Inscripciones | Diario durante el proceso |
| Reportes | Ambos perfiles | A demanda |

---

## 2. Puesta en marcha técnica

```mermaid
flowchart TD
    A[Clonar repositorio] --> B["composer install<br/>npm install"]
    B --> C["Copiar .env.example a .env<br/>configurar APP_URL y DB_*"]
    C --> D[php artisan key:generate]
    D --> E["Crear base de datos MySQL<br/>utf8mb4"]
    E --> F[php artisan migrate]
    F --> G[php artisan db:seed]
    G --> H{"¿Entorno de<br/>desarrollo?"}
    H -- Sí --> I["php artisan db:seed<br/>--class=DevelopmentDataSeeder"]
    H -- No --> J
    I --> J[php artisan storage:link]
    J --> K["npm run build<br/>o npm run dev"]
    K --> L["Ingresar con superadmin"]
```

Puntos clave:

- `db:seed` carga los catálogos base (ubigeo, países, colegios, tipos de documento, carreras, sedes, modalidades, etc.), los permisos y el usuario inicial `superadmin`.
- `DevelopmentDataSeeder` agrega datos de demostración (proceso, ofertas, inscripciones, usuario `gestor.cepre`). **No usar en producción.**
- Las sesiones y la caché deben usar `file` o `redis` (ver sección 10.1).
- En producción la cola usa `QUEUE_CONNECTION=database` y requiere `queue:work` (ver manual de instalación, sección 14).

---

## 3. Configuración: orden obligatorio

Cada pantalla depende de datos creados en pantallas anteriores. Si se salta un paso, el siguiente no tendrá opciones para seleccionar.

```mermaid
flowchart TD
    subgraph SEG["Seguridad"]
        S1[Permisos] --> S2[Roles] --> S3[Usuarios]
    end

    subgraph MAN["Mantenedores: catálogos base"]
        M1[Sedes] --> M3["Sede ↔ Carrera"]
        M2[Carreras] --> M3
        M4[Modalidades] --> M6["Lugar ↔ Modalidad"]
        M5[Lugares de inscripción] --> M6
        M3 --> M7[Ofertas base]
        M6 --> M7
        M8[Turnos]
        M9[Aulas]
        M10[Conceptos de pago]
        M11[Tipos de expediente]
        M12[Colegios]
    end

    subgraph CFG["Configuración académica: por cada proceso"]
        C1["Proceso<br/>fechas de inicio y fin"] --> C2["Ofertas del proceso<br/>proceso + turno + ofertas base"]
        C1 --> C3["Aulas por proceso<br/>proceso + turno + aula + capacidad"]
        C1 --> C4["Convenios<br/>asociados al proceso con vigencia"]
        C1 --> C5["Reglas de pago<br/>proceso + concepto + monto + cuotas"]
        C4 -.opcional.-> C5
        C1 --> C6["Requisitos<br/>tipos de expediente obligatorios"]
    end

    SEG --> MAN
    M7 --> C2
    M8 --> C2
    M8 --> C3
    M9 --> C3
    M10 --> C5
    M4 -.opcional.-> C5
    M11 --> C6
```

### 3.1. Paso a paso de configuración de un nuevo proceso

| # | Menú | Qué registrar | Depende de |
|---|---|---|---|
| 1 | Configuración Académica › Procesos | Nombre, fecha/hora de inicio y fin | — |
| 2 | Configuración Académica › Ofertas › Asignar | Proceso, turno y las ofertas base que se abren | Proceso, Turnos, Ofertas base |
| 3 | Configuración Académica › Aulas › Asignar | Proceso, turno, aula y capacidad | Proceso, Turnos, Aulas |
| 4 | Configuración Académica › Convenios *(opcional)* | Convenio, beneficio y vigencia dentro del proceso | Proceso |
| 5 | Configuración Académica › Reglas de Pago | Concepto, monto, cuotas, vigencia; opcionalmente modalidad o convenio | Proceso, Conceptos de pago |
| 6 | Configuración Académica › Requisitos | Tipos de expediente y cuáles son obligatorios | Proceso, Tipos de expediente |

> **Importante:** cada combinación **proceso + turno** ofertada en el paso 2 necesita al menos un aula con capacidad en el paso 3. Si no, el asistente no podrá guardar la postulación.

---

## 4. Checklist antes de abrir inscripciones

- [ ] Hay un **proceso HABILITADO** cuya fecha de inicio ya pasó y cuya fecha de fin no ha llegado.
- [ ] El proceso tiene al menos **una oferta HABILITADA**.
- [ ] Cada turno ofertado tiene **aulas habilitadas con capacidad** suficiente.
- [ ] Existe al menos una **regla de pago habilitada y vigente hoy** para el proceso (general, o por modalidad/convenio).
- [ ] Existen **requisitos de expediente** y al menos uno está marcado como **obligatorio**.
- [ ] Los **convenios**, si aplican, están vigentes en las fechas del proceso.
- [ ] El personal operativo tiene el rol **Gestor de Inscripciones CEPRE**.
- [ ] Se cuenta con el **TXT del banco** para conciliar los pagos.

---

## 5. Flujo de una inscripción (asistente de 5 pasos)

Ruta: **Inscripciones › Nueva inscripción**. Un borrador puede retomarse con **Continuar** desde el listado.

```mermaid
flowchart TD
    START([Nueva inscripción]) --> P0{"¿Hay proceso activo<br/>con ofertas habilitadas?"}
    P0 -- No --> X0["Bloqueado: el sistema indica<br/>cuándo cerró o cuándo inicia"]
    P0 -- Sí --> P1

    P1["Paso 1 · Información personal<br/>DNI con consulta RENIEC, contacto,<br/>ubigeo, lengua, discapacidad"] --> P2
    P2["Paso 2 · Información general<br/>colegio, año de graduación,<br/>postulaciones previas"] --> P3
    P3["Paso 3 · Postulación<br/>modalidad, lugar, turno,<br/>oferta, convenio"] --> V3{"¿Hay aula con cupo<br/>para proceso + turno?"}
    V3 -- No --> X3[Error: sin aulas disponibles]
    V3 -- Sí --> S3["Se crea la inscripción PENDIENTE<br/>+ código correlativo<br/>+ aula asignada automáticamente"]
    S3 --> P4

    P4["Paso 4 · Pago<br/>elegir regla de pago y buscar<br/>el depósito por DNI / operación"] --> V4{"¿Depósito encontrado<br/>en un TXT importado?"}
    V4 -- No --> W4["Queda PENDIENTE de pago<br/>se retoma luego con Continuar"]
    V4 -- Sí --> S4[Pago VERIFICADO y asociado]
    S4 --> P5

    P5["Paso 5 · Expedientes<br/>cargar documentos y marcar revisión"] --> V5{"¿Todos los obligatorios<br/>APROBADOS?"}
    V5 -- No --> W5["Sigue PENDIENTE<br/>se completa desde Detalle"]
    V5 -- Sí --> OK([Inscripción ACTIVA])
```

Notas:

- El aula se asigna sola, recorriendo las aulas del proceso y turno por lugar, piso y nombre, y tomando la primera con cupo. Luego puede cambiarse manualmente desde el listado (**Asignar aula**).
- Un postulante no puede pasar al paso 5 sin un pago verificado.

---

## 6. Flujo de pagos con el TXT del banco

El sistema **no registra pagos manualmente**: todo pago proviene de un archivo TXT del banco importado previamente.

La obligación de pago se crea en el **paso 4 del asistente**, así que el primer pago siempre se asocia ahí. "Validar pagos pendientes" y la pantalla "Validar pagos" solo encuentran obligaciones que ya existen, es decir, sirven para las cuotas siguientes.

```mermaid
sequenceDiagram
    autonumber
    actor G as Gestor
    participant P as Inscripciones › Pagos
    participant B as Detalles del banco
    participant O as Obligación de pago
    participant I as Inscripción

    G->>P: Sube el TXT del banco
    P->>P: Valida cabecera 01, detalle y cierre 03<br/>cuadre de registros y montos
    G->>P: Confirma la importación
    P->>B: Crea detalles en estado PENDIENTE

    alt Primer pago
        G->>P: Paso 4 del asistente
        P->>O: Crea la obligación según la regla elegida
        P->>B: Busca por DNI, N° de operación y fecha
    else Cuotas siguientes, automático
        G->>P: "Validar pagos pendientes"
        P->>B: Agrupa por DNI y busca obligaciones con saldo
    else Cuotas siguientes, manual
        G->>P: Validar pagos
        P->>B: Busca por DNI, N° de operación y fecha
    end

    B->>O: Asocia el depósito: pago VERIFICADO
    B->>B: Detalle pasa a ASOCIADO
    O->>O: Recalcula pagado y saldo<br/>PENDIENTE → PARCIAL → PAGADO
    O-->>I: Habilita el paso 5 · Expedientes
```

Validaciones del monto:

- El DNI del depósito debe coincidir con el del postulante.
- El monto no puede superar el saldo pendiente.
- Si la regla **no permite cuotas**, el primer pago debe cubrir el total.
- Si permite cuotas, la primera debe alcanzar el **monto mínimo de primera cuota**.

---

## 7. Expedientes y activación de la inscripción

Ruta: **Inscripciones › Detalle** (o paso 5 del asistente).

```mermaid
flowchart TD
    A[Expediente cargado] --> B{Revisión}
    B -- Aprobar --> C[APROBADO]
    B -- Observar con motivo --> D["OBSERVADO<br/>se corrige y se vuelve a revisar"]
    B -- Rechazar con motivo --> E[RECHAZADO]
    D --> B
    C --> F{"¿Todos los obligatorios<br/>aprobados y<br/>pago verificado?"}
    F -- Sí --> G([Inscripción ACTIVA])
    F -- No --> H[Sigue PENDIENTE]
```

---

## 8. Estados del sistema

### 8.1. Inscripción

```mermaid
stateDiagram-v2
    [*] --> PENDIENTE: Paso 3 guardado
    PENDIENTE --> ACTIVO: Pago verificado y expedientes obligatorios aprobados
    ACTIVO --> ANULADO: Anular con motivo (mín. 10 caracteres), libera el aula
    ANULADO --> [*]
```

### 8.2. Obligación de pago

```mermaid
stateDiagram-v2
    [*] --> PENDIENTE: Se elige la regla de pago
    PENDIENTE --> PARCIAL: Cuota verificada
    PENDIENTE --> PAGADO: Pago total verificado
    PARCIAL --> PAGADO: Saldo cubierto
```

### 8.3. Proceso

```mermaid
stateDiagram-v2
    [*] --> HABILITADO: Se crea
    HABILITADO --> DESHABILITADO: Manual
    DESHABILITADO --> HABILITADO: Manual
    HABILITADO --> CERRADO: Automático al pasar la fecha de fin
    DESHABILITADO --> CERRADO: Automático al pasar la fecha de fin
```

### 8.4. Resumen de estados

| Entidad | Estados |
|---|---|
| Inscripción | PENDIENTE · ACTIVO · ANULADO |
| Obligación de pago | PENDIENTE · PARCIAL · PAGADO · EXONERADO · ANULADO |
| Validación de pago | PENDIENTE · VERIFICADO · RECHAZADO |
| Detalle del banco | PENDIENTE · ASOCIADO · OBSERVADO |
| Expediente | PENDIENTE · APROBADO · OBSERVADO · RECHAZADO |
| Proceso | HABILITADO · DESHABILITADO · CERRADO |

---

## 9. Reglas automáticas que conviene conocer

1. **Cierre de procesos:** cada vez que se resuelve el proceso activo, los procesos cuya fecha de fin ya pasó se marcan como `CERRADO`.
2. **Un solo proceso activo:** si hay varios habilitados en rango de fechas, se usa el de fecha de inicio más antigua.
3. **Código de inscripción:** `AA` + `N° de periodo` + correlativo de 4 dígitos por proceso. Las inscripciones eliminadas cuentan, para no reutilizar códigos.
4. **Capacidad de aulas:** se cuentan todas las inscripciones no anuladas del aula; al anular, el cupo se libera.
5. **Vigencias:** reglas de pago y convenios se validan contra la fecha del día; si vencen mientras un borrador está a medio camino, el asistente lo bloquea con un mensaje.

---

## 10. Observaciones y mejoras detectadas

Encontradas al contrastar el código con los manuales existentes.

### 10.1. Técnicas

| # | Observación | Impacto | Sugerencia |
|---|---|---|---|
| 1 | `.env.example` usa `SESSION_DRIVER=database` y `CACHE_STORE=database`, pero **no existen migraciones** para las tablas `sessions` ni `cache`. | Instalación nueva da error 500. | Crear las migraciones (`php artisan make:session-table`, `make:cache-table`) o cambiar el ejemplo a `file`. |
| 2 | El manual de instalación asume Windows + Herd Pro. | Equipos macOS o Herd gratuito no tienen guía. | Añadir nota para macOS / MySQL externo (DBngin, Homebrew). |

### 10.2. Funcionales

| # | Observación | Impacto | Sugerencia |
|---|---|---|---|
| 3 | Si un proceso **no tiene requisitos obligatorios**, ninguna inscripción llega a `ACTIVO`. | Inscripciones quedan pendientes para siempre. | Validarlo en el checklist del proceso o activar solo con el pago cuando no hay requisitos. |
| 4 | El estado `EXONERADO` se consulta pero **ningún flujo lo asigna**. | Un convenio de exoneración total no tiene cómo pasar el paso 4. | Definir el flujo de exoneración (por ejemplo, regla de pago con monto 0 que marque la obligación como exonerada). |
| 5 | El descuento de un convenio **no se calcula automáticamente**; se aplica creando una regla de pago asociada a ese convenio. | Riesgo de olvidar crear la regla. | Documentarlo en el manual o calcular el monto desde el convenio. |
| 6 | Solo se pueden anular inscripciones `ACTIVO`. | Los borradores `PENDIENTE` abandonados ocupan aula indefinidamente. | Permitir anular pendientes o liberar su aula al cerrar el proceso. |

### 10.3. Documentación

| # | Observación | Sugerencia |
|---|---|---|
| 7 | `MANUAL_USUARIO_CEPRE.md` menciona el submódulo **Semestres**, que ya no existe; el proceso se crea directamente con sus fechas. | Retirar la sección 13.1 y ajustar las secciones 7, 8 y 19. |
| 8 | El manual de usuario no documenta los nuevos mantenedores: **Carreras, Sedes, Modalidades, Lugares de inscripción, Ofertas base, Turnos y Conceptos de pago**. | Agregar una sección por cada uno, siguiendo el orden de la sección 3 de este documento. |
| 9 | El manual de usuario no explica la **importación del TXT del banco**, que es la base de la validación de pagos. | Agregar el flujo de la sección 6. |
| 10 | En el asistente, el paso 3 se describe como "Proceso, oferta y colegio", pero el colegio se registra en el paso 2. | Cambiar la descripción del paso 3 en `inscripcion-crear.php` a "Proceso, oferta y convenio". |

> Las observaciones 7, 8 y 9 ya se corrigieron en la versión 1.1 de `MANUAL_USUARIO_CEPRE.md`.
