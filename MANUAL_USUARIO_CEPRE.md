# UNIVERSIDAD NACIONAL DE UCAYALI
## CENTRO PREUNIVERSITARIO - CEPRE UNU
### MANUAL DE USUARIO DEL SISTEMA DE INSCRIPCIONES

**Versión:** 1.4  
**Documento:** Manual de usuario funcional  
**Dirigido a:** SuperAdministrador y Gestor de Inscripciones CEPRE  
**Fecha:** Noviembre 2026

> **Cambios en la versión 1.2:** el pago se valida primero y el asistente pasa a tener 4 pasos; la modalidad se asigna sola según el año de egreso; cada proceso define su propia oferta (modalidades y carreras por sede); se agregaron los pagos manuales, el portal del postulante con revisión de las inscripciones en línea, las fotos, la ficha de matrícula, el carnet con QR y los códigos de Admisión. Se retiraron los expedientes, los convenios, los requisitos, los lugares de inscripción y las ofertas base. Los diagramas del flujo completo están en `FLUJO_PROCESOS_CEPRE.md`.

> **Cambios en la versión 1.3:** se renovó la interfaz. Las pantallas cambian sin recargar la página, los formularios se abren en un panel lateral a la derecha, las acciones delicadas (eliminar, anular, cambiar de estado) piden confirmación en un diálogo y los mensajes aparecen abajo a la derecha. El menú lateral agrupa los módulos en secciones que se pueden plegar, y el tema claro u oscuro se elige con el botón de la barra superior. Los módulos, permisos y procedimientos no cambian.

> **Cambios en la versión 1.4:** se actualizaron los nombres de botones y menús de la nueva interfaz. El inicio y el reporte de inscripciones muestran gráficos (con vista de tabla), la recaudación y la ocupación de aulas. Las validaciones las hace el servidor y marcan en rojo el campo con error. En el paso 1, si no hay un pago libre con los datos ingresados aparece un aviso; con 8 dígitos el tipo de documento DNI se elige solo; la lengua materna incluye `CASTELLANO`; el buscador de ubigeo ofrece primero Ucayali; el listado de inscripciones muestra la foto del postulante; y la consulta del portal avisa cuando el pago existe pero falta inscribirse.

---

## Índice

1. Introducción
2. Finalidad del documento
3. Alcance
4. Perfiles de usuario
5. Credenciales de referencia para entorno demo
6. Recomendaciones para la elaboración del documento final
7. Flujo general del sistema
8. Consideraciones previas al uso del sistema
9. Panel principal o inicio
10. Manual del perfil SuperAdministrador
11. Módulo Seguridad
12. Módulo Mantenedores
13. Módulo Configuración Académica
14. Manual del perfil Gestor de Inscripciones CEPRE
15. Módulo Pagos
16. Módulo Inscripciones
17. Módulo Fotos
18. Ficha de matrícula y carnet
19. Módulo Validar pagos
20. Módulo Reportes
21. Portal del postulante
22. Caso de uso integral del sistema
23. Buenas prácticas operativas
24. Errores operativos frecuentes
25. Datos demo cargados para el manual
26. Procedimiento para recargar datos de demostración
27. Conclusiones
28. Anexos sugeridos

---

## 1. Introducción

El presente manual de usuario describe de manera ordenada y funcional el uso del sistema CEPRE UNU para el proceso de inscripciones. Está pensado como material de capacitación, consulta operativa y apoyo institucional para el personal que administra y ejecuta las inscripciones.

El sistema permite gestionar usuarios, permisos, la oferta de cada proceso, aulas, pagos, inscripciones, fotos, fichas de matrícula y carnets, e incluye un portal público donde el postulante puede inscribirse en línea y consultar su inscripción.

El manual se organiza en dos perfiles funcionales:

- `SuperAdministrador`
- `Gestor de Inscripciones CEPRE`

Cada perfil tiene accesos y responsabilidades distintas dentro del sistema.

## 2. Finalidad del documento

La finalidad de este documento es:

1. Brindar una guía clara para el uso correcto del sistema.
2. Estandarizar el procedimiento de trabajo de los usuarios.
3. Facilitar la inducción de nuevo personal.
4. Servir como base para un manual institucional con capturas de pantalla.
5. Reducir errores operativos derivados del uso inadecuado de los módulos.

## 3. Alcance

Este manual comprende la descripción funcional de los siguientes módulos:

- inicio;
- seguridad;
- mantenedores;
- configuración académica;
- pagos;
- inscripciones;
- fotos, ficha de matrícula y carnet;
- validación de pagos;
- reportes;
- portal del postulante.

No forma parte de este documento la documentación técnica, la estructura de la base de datos, la instalación ni la administración de infraestructura. Esos temas están en `MANUAL_INSTALACION_CEPRE.md`.

## 4. Perfiles de usuario

### 4.1. SuperAdministrador

El perfil `SuperAdministrador` tiene acceso a todos los módulos. Su función es configurar, controlar y administrar la plataforma.

Entre sus responsabilidades se encuentran:

- administrar usuarios, roles y permisos;
- mantener los catálogos base: carreras, sedes, modalidades, turnos, aulas, conceptos de pago y colegios;
- crear cada proceso y configurar su oferta, sus aulas y sus reglas de pago;
- configurar los datos del carnet;
- registrar los códigos de Admisión al final del proceso;
- supervisar inscripciones, pagos y reportes.

### 4.2. Gestor de Inscripciones CEPRE

El perfil `Gestor de Inscripciones CEPRE` es el usuario operativo que ejecuta el proceso diario de inscripción.

Entre sus responsabilidades se encuentran:

- cargar el TXT del banco y registrar los pagos manuales;
- registrar nuevas inscripciones;
- revisar y aprobar las inscripciones hechas en el portal;
- subir las fotos de los postulantes;
- entregar la ficha de matrícula y el carnet;
- validar las cuotas siguientes;
- emitir reportes de inscripciones.

Este perfil no administra seguridad ni configuración académica.

## 5. Credenciales de referencia para entorno demo

Para las actividades de demostración y captura, se consideran las siguientes credenciales:

- `superadmin` / `password`
- `gestor.cepre` / `password`

En producción, la contraseña inicial de `superadmin` debe cambiarse al primer ingreso.

## 6. Recomendaciones para la elaboración del documento final

Para convertir este archivo en un documento institucional final en Word o PDF, se recomienda:

1. conservar la portada institucional;
2. incluir el logotipo institucional en la primera página;
3. insertar salto de página después de la portada;
4. generar una tabla de contenido automática;
5. incorporar capturas por cada módulo;
6. numerar todas las figuras;
7. usar encabezado y pie de página institucional.

### 6.1. Convención sugerida para capturas

Se recomienda utilizar la siguiente codificación:

- `Figura SA-01`, `Figura SA-02`, `Figura SA-03` para capturas asociadas al perfil `SuperAdministrador`.
- `Figura GI-01`, `Figura GI-02`, `Figura GI-03` para capturas asociadas al perfil `Gestor de Inscripciones CEPRE`.
- `Figura PP-01`, `Figura PP-02` para capturas del portal del postulante.

Ejemplos:

- `Figura SA-01: Pantalla de inicio de sesión`
- `Figura SA-08: Asignación de permisos al rol`
- `Figura GI-04: Paso 1 - Validación de pago`

## 7. Flujo general del sistema

El flujo funcional del sistema puede resumirse así:

1. el `SuperAdministrador` mantiene los catálogos base (carreras, sedes, modalidades, turnos, aulas, conceptos de pago y colegios);
2. el `SuperAdministrador` crea el proceso y configura su oferta, sus aulas, sus reglas de pago y el carnet;
3. el postulante paga en el banco o en una caja de la UNU; **sin pago no hay inscripción**;
4. el `Gestor de Inscripciones CEPRE` carga el TXT del banco del día o registra el pago manual; cada pago queda `LIBRE`;
5. la inscripción se registra de dos formas:
   - el gestor la registra en el asistente de 4 pasos, empezando por el pago; queda `ACTIVA`;
   - el postulante se inscribe en el portal; queda `EN REVISIÓN` hasta que un gestor la aprueba;
6. el pago usado pasa a `REGISTRADO` y el sistema asigna un aula automáticamente;
7. el gestor sube la foto y entrega la ficha de matrícula y el carnet con QR;
8. las cuotas siguientes, si las hay, se validan desde `Pagos` o `Validar pagos`;
9. se generan reportes y, al final, la exportación con los códigos de Admisión.

Los diagramas de este flujo, con el detalle de cada estado, se encuentran en `FLUJO_PROCESOS_CEPRE.md`.

Referencias de imagen:
`[Figura SA-01: Pantalla de inicio de sesión]`
`[Figura SA-02: Panel principal del sistema]`

## 8. Consideraciones previas al uso del sistema

Antes de abrir las inscripciones, se debe verificar:

1. que exista un proceso `HABILITADO` cuya fecha y hora de apertura ya pasó y cuya fecha y hora de cierre aún no llega;
2. que el proceso tenga el año de egreso máximo para Ordinario correcto;
3. que la oferta del proceso tenga habilitada una modalidad para egresados, otra para estudiantes en colegio, y las carreras de cada sede;
4. que cada turno tenga aulas habilitadas con capacidad suficiente;
5. que existan reglas de pago habilitadas y vigentes en la fecha actual;
6. que el carnet tenga configurados el director, el cargo y la firma;
7. que el TXT del banco del día esté cargado;
8. que los usuarios tengan el rol adecuado.

Si no se cumplen, el sistema impide avanzar:

- sin proceso vigente con oferta y reglas de pago, el asistente no permite empezar;
- sin el pago cargado, no se encuentra en el paso 1;
- si el proceso no tiene habilitada la modalidad que corresponde al año de egreso, no se puede guardar la postulación;
- sin aula con cupo en el turno elegido, no se puede guardar la postulación.

## 9. Panel principal o inicio

El panel principal se muestra después de iniciar sesión. Resume cómo va el proceso vigente y da acceso a los módulos autorizados desde el menú lateral. El menú lateral muestra abierta solo la sección de la pantalla actual; las demás se despliegan con un clic.

### 9.1. Información que muestra

- **cabecera del proceso:** nombre, fechas de apertura y cierre, avance del periodo de inscripción y días que faltan, total de inscritos, inscripciones de hoy y pagos validados hoy;
- **indicadores:** `Activas` (con su porcentaje), `En revisión` (inscripciones del portal), `Con saldo pendiente` y `Recaudado`, con una barra de lo recaudado frente a lo esperado;
- **gráficos:**
  - `Inscripciones por día` de los últimos 14 días (la columna resaltada es hoy);
  - `Estado de las inscripciones` (activas, pendientes y anuladas);
  - `Carreras con más inscritos` (las 8 primeras, por estado);
  - `Estado del pago` y `Por turno y sede`;
- `Para atender`: inscripciones en línea por aprobar, pagos por regularizar, inscripciones activas sin aula y pagos del banco sin usar, cada una con su cantidad y un enlace a la pantalla donde se resuelve;
- `Ocupación de aulas`: inscripciones con aula frente a las vacantes de cada turno; la barra cambia de color al llegar al 80 % y al pasarse;
- `Últimas inscripciones`, con la foto del postulante (o sus iniciales si no tiene foto);
- `Accesos rápidos` a las pantallas más usadas.

Al pasar el mouse sobre un gráfico se ve el detalle de cada barra o sector, y el botón `Tabla` muestra las cifras exactas.

### 9.2. Procedimiento de revisión

1. ingresar al sistema;
2. revisar la cabecera del proceso y los indicadores;
3. atender los pendientes de `Para atender`, entrando con su enlace;
4. revisar la ocupación de aulas antes de que un turno se llene;
5. ingresar al módulo requerido desde el menú lateral o desde `Accesos rápidos`.

### 9.3. Resultado esperado

El usuario debe comprender rápidamente el estado actual de las inscripciones y acceder al módulo necesario.

Referencia de imagen:
`[Figura SA-03: Panel principal con indicadores]`

## 10. Manual del perfil SuperAdministrador

El perfil `SuperAdministrador` administra la estructura funcional del sistema. Su intervención se concentra en la configuración y el control general.

Este perfil utiliza principalmente los siguientes grupos de módulos:

1. Seguridad.
2. Mantenedores.
3. Configuración Académica.
4. Supervisión operativa.

## 11. Módulo Seguridad

El módulo Seguridad controla el acceso al sistema y la distribución de permisos.

### 11.1. Submódulo Usuarios

#### 11.1.1. Objetivo

Registrar, editar, activar y desactivar usuarios del sistema.

#### 11.1.2. Procedimiento

1. ingresar a `Seguridad > Usuarios`;
2. revisar el listado de usuarios registrados;
3. utilizar el buscador para localizar usuarios específicos;
4. seleccionar `Nuevo`;
5. completar:
   - nombre;
   - correo;
   - contraseña;
   - roles;
6. guardar la información;
7. verificar que el usuario aparezca en el listado;
8. editar o cambiar el estado del usuario cuando sea necesario.

#### 11.1.3. Resultado esperado

El usuario queda registrado y puede ingresar con los permisos de sus roles.

Referencias de imagen:
`[Figura SA-04: Listado de usuarios]`
`[Figura SA-05: Formulario de creación o edición de usuario]`

### 11.2. Submódulo Roles

#### 11.2.1. Objetivo

Crear perfiles funcionales agrupando permisos según las responsabilidades de cada tipo de usuario.

#### 11.2.2. Procedimiento

1. ingresar a `Seguridad > Roles`;
2. crear un rol con `Nuevo` o editar uno existente;
3. registrar nombre y descripción;
4. guardar la información;
5. abrir la asignación de permisos del rol;
6. marcar los permisos que correspondan;
7. guardar la asignación.

El rol `ADMINISTRADOR` tiene acceso total y no necesita permisos asignados. Para un gestor operativo se recomiendan los permisos de:

- inscripciones (ver, crear, editar, cambiar estado y exportar);
- pagos (ver, crear, editar y cambiar estado);
- fotos (ver, subir, eliminar y exportar);
- reportes de inscripciones (ver y exportar).

El permiso de **cambiar el estado de la inscripción** es el que permite aprobar las inscripciones del portal y anular inscripciones.

#### 11.2.3. Resultado esperado

El rol queda disponible para asociarse a usuarios y controlar sus funciones.

Referencias de imagen:
`[Figura SA-06: Listado de roles]`
`[Figura SA-07: Formulario del rol]`
`[Figura SA-08: Asignación de permisos]`

### 11.3. Submódulo Permisos

#### 11.3.1. Objetivo

Consultar y controlar los accesos por módulo y tipo de acción.

#### 11.3.2. Procedimiento

1. ingresar a `Seguridad > Permisos`;
2. revisar el listado de permisos;
3. utilizar el buscador para ubicar permisos específicos;
4. verificar las acciones de cada permiso y que esté habilitado.

Los permisos de cada módulo se crean junto con el sistema; normalmente no hace falta crear permisos nuevos.

#### 11.3.3. Resultado esperado

Los permisos quedan disponibles para asignarse a los roles.

Referencia de imagen:
`[Figura SA-09: Listado de permisos]`

## 12. Módulo Mantenedores

Este módulo contiene los catálogos base. Se configuran una vez y se actualizan solo cuando cambia la oferta institucional. Las carreras que se ofrecen en cada sede ya no se definen aquí, sino en cada proceso (ver 13.2).

Orden recomendado:

1. Carreras.
2. Sedes.
3. Modalidades.
4. Turnos.
5. Aulas.
6. Conceptos de pago.
7. Colegios, solo para corregir o completar el catálogo precargado.

En todos los submódulos, el estado del registro (habilitado o deshabilitado) se cambia desde la columna `Acciones`. Un registro deshabilitado deja de mostrarse como opción en las pantallas que dependen de él.

### 12.1. Submódulo Carreras

#### 12.1.1. Objetivo

Registrar las carreras profesionales a las que se puede postular.

#### 12.1.2. Procedimiento

1. ingresar a `Mantenedores > Carreras`;
2. buscar por código o nombre para verificar que la carrera no exista;
3. seleccionar `Nueva`;
4. registrar código y nombre;
5. guardar.

#### 12.1.3. Resultado esperado

La carrera queda disponible para habilitarse en las sedes de cada proceso.

Referencia de imagen:
`[Figura SA-21: Módulo de carreras]`

### 12.2. Submódulo Sedes

#### 12.2.1. Objetivo

Registrar las sedes donde funciona el CEPRE-UNU, por ejemplo `PUCALLPA - CALLERÍA`.

#### 12.2.2. Procedimiento

1. ingresar a `Mantenedores > Sedes`;
2. seleccionar `Nueva` o editar una existente;
3. registrar código, nombre, ubigeo y dirección;
4. guardar.

#### 12.2.3. Resultado esperado

La sede queda disponible para configurar sus carreras en la oferta de cada proceso.

Referencia de imagen:
`[Figura SA-22: Módulo de sedes]`

### 12.3. Submódulo Modalidades

#### 12.3.1. Objetivo

Registrar las modalidades propias del CEPRE-UNU, por ejemplo `ORDINARIO` y `EXTRAORDINARIO O RESERVA`.

#### 12.3.2. Procedimiento

1. ingresar a `Mantenedores > Modalidades`;
2. seleccionar `Nueva` o editar una existente;
3. registrar:
   - código;
   - nombre;
   - descripción;
   - `Aplica a`: `EGRESADOS` (ya terminaron el colegio) o `ESTUDIANTES EN COLEGIO` (están en 4.º o 5.º de secundaria);
4. guardar.

#### 12.3.3. Resultado esperado

La modalidad queda disponible para habilitarse en los procesos. El campo `Aplica a` es el que permite al sistema elegir la modalidad automáticamente según el año de egreso del postulante. Una modalidad sin `Aplica a` no se puede usar en la oferta del proceso.

Referencia de imagen:
`[Figura SA-24: Módulo de modalidades]`

### 12.4. Submódulo Turnos

#### 12.4.1. Objetivo

Registrar los turnos de estudio con su horario, por ejemplo mañana (7:00 a 12:00), tarde (13:00 a 17:00) o noche virtual (17:00 a 22:00).

#### 12.4.2. Procedimiento

1. ingresar a `Mantenedores > Turnos`;
2. seleccionar `Nuevo` o editar uno existente;
3. registrar código, nombre, hora de inicio y hora de fin;
4. guardar.

#### 12.4.3. Resultado esperado

El turno queda disponible para asignar aulas a un proceso. En el asistente solo aparecen los turnos con aulas en el proceso.

Referencia de imagen:
`[Figura SA-28: Módulo de turnos]`

### 12.5. Submódulo Aulas

#### 12.5.1. Objetivo

Registrar las aulas físicas que luego se asignan a cada proceso y turno.

#### 12.5.2. Procedimiento

1. ingresar a `Mantenedores > Aulas`;
2. seleccionar `Nueva`;
3. completar nombre, lugar, piso y orden;
4. guardar.

#### 12.5.3. Resultado esperado

El aula queda disponible para asignarse a un proceso. El lugar, el piso y el nombre definen el orden en que el sistema llena las aulas.

Referencia de imagen:
`[Figura SA-10: Módulo de aulas]`

### 12.6. Submódulo Conceptos de Pago

#### 12.6.1. Objetivo

Registrar los conceptos por los que se cobra al postulante, por ejemplo la inscripción en cuota única o en cuotas.

#### 12.6.2. Procedimiento

1. ingresar a `Mantenedores > Conceptos de Pago`;
2. seleccionar `Nuevo` o editar uno existente;
3. registrar código, nombre y descripción;
4. guardar.

#### 12.6.3. Resultado esperado

El concepto queda disponible para crear reglas de pago en cada proceso.

Referencia de imagen:
`[Figura SA-29: Módulo de conceptos de pago]`

### 12.7. Submódulo Colegios

#### 12.7.1. Objetivo

Gestionar el catálogo de colegios que se usa en el paso de información general.

#### 12.7.2. Procedimiento

1. ingresar a `Mantenedores > Colegios`;
2. buscar por nombre, código modular o ubicación;
3. registrar o corregir el colegio si es necesario:
   - código y código modular;
   - nombre;
   - tipo de colegio;
   - ubigeo y dirección;
4. guardar.

#### 12.7.3. Resultado esperado

El colegio puede encontrarse y seleccionarse durante la inscripción.

Referencia de imagen:
`[Figura SA-11: Módulo de colegios]`

## 13. Módulo Configuración Académica

Este módulo prepara cada proceso antes de abrir las inscripciones. Se repite en cada nuevo proceso, en este orden: procesos, oferta del proceso, aulas, reglas de pago y carnet. Los códigos de Admisión se registran al final.

### 13.1. Submódulo Procesos

#### 13.1.1. Objetivo

Definir el proceso, el periodo en que estará abierto y el corte de año de egreso para las modalidades.

#### 13.1.2. Procedimiento

1. ingresar a `Configuración académica > Procesos`;
2. seleccionar `Nuevo`;
3. registrar:
   - nombre, con el año y el periodo en romanos: `2027-I`, `2027-II` o `2027-III` (si se escribe `2027-2`, el sistema lo guarda como `2027-II`). De él salen los primeros dígitos del código de inscripción;
   - apertura (fecha y hora);
   - cierre (fecha y hora);
   - año de egreso máximo para Ordinario;
4. guardar.

El proceso se crea en estado `HABILITADO`.

**Año de egreso máximo para Ordinario.** Quien egresó hasta ese año inclusive es egresado y postula en la modalidad para `EGRESADOS` (ORDINARIO). Quien egresa después está en el colegio y postula en la modalidad para `ESTUDIANTES EN COLEGIO` (EXTRAORDINARIO O RESERVA). Por ejemplo, en un proceso que inscribe en 2026: hasta 2025 es ORDINARIO y desde 2026 es RESERVA. El corte sube un año en el proceso siguiente. Si se deja vacío, se usa el año anterior al de apertura.

#### 13.1.3. Resultado esperado

Las inscripciones solo pueden registrarse entre la apertura y el cierre. Cuando pasa la fecha de cierre, el sistema cambia el proceso a `CERRADO` automáticamente.

Si hay más de un proceso habilitado dentro de su periodo, el sistema usa el de apertura más antigua. Se recomienda no superponer fechas entre procesos.

Referencia de imagen:
`[Figura SA-14: Configuración del proceso]`

### 13.2. Submódulo Oferta del proceso

#### 13.2.1. Objetivo

Definir qué modalidades y qué carreras de cada sede se abren en el proceso.

#### 13.2.2. Procedimiento

1. ingresar a `Configuración académica > Oferta del proceso`;
2. seleccionar el proceso;
3. marcar las modalidades habilitadas: al menos una para egresados y una para estudiantes en colegio;
4. en cada sede, marcar las carreras que se ofrecen; `Marcar todas` y `Quitar todas` ayudan a hacerlo por sede;
5. seleccionar `Guardar oferta`.

Si el proceso no tiene aulas asignadas, la pantalla lo advierte; desde `Ver aulas por proceso` se puede ir a asignarlas.

#### 13.2.3. Resultado esperado

En el paso de postulación solo aparecen las sedes y carreras marcadas, y la modalidad se elige sola entre las habilitadas.

Referencia de imagen:
`[Figura SA-15: Oferta del proceso]`

### 13.3. Submódulo Aulas por proceso

#### 13.3.1. Objetivo

Definir las aulas disponibles en cada turno del proceso y su capacidad.

#### 13.3.2. Procedimiento

1. ingresar a `Configuración académica > Aulas por proceso`;
2. seleccionar `Asignar aulas`;
3. elegir el proceso y el turno;
4. marcar las aulas que se usarán (`Seleccionar todas` y `Limpiar selección` ayudan) y registrar la capacidad de cada una;
5. seleccionar `Asignar aulas`.

La capacidad de un aula ya asignada se cambia desde el listado (`Editar aula asignada`).

#### 13.3.3. Resultado esperado

Al guardar una postulación, el sistema asigna la primera aula con cupo del turno elegido. Si no hay ninguna, la postulación no se puede guardar.

Referencia de imagen:
`[Figura SA-17: Aulas por proceso]`

### 13.4. Submódulo Reglas de pago

#### 13.4.1. Objetivo

Definir las modalidades de pago del proceso: cuánto se paga, en cuántas cuotas y si se necesita un documento que respalde el beneficio.

#### 13.4.2. Procedimiento

1. ingresar a `Configuración académica > Reglas de pago`;
2. seleccionar `Nuevo` o editar una regla;
3. registrar:
   - proceso;
   - concepto de pago;
   - modalidad (opcional: solo si la regla aplica a una modalidad específica);
   - monto total;
   - número máximo de cuotas y monto mínimo de la primera cuota, si se paga en cuotas;
   - documento requerido (opcional), por ejemplo `N° de resolución` o `N° de constancia de RRHH`;
   - vigente desde y vigente hasta;
4. guardar.

Ejemplos, como en el sistema anterior:

| Modalidad de pago | Monto mínimo para inscribir | Documento requerido |
|---|---|---|
| Cuota única | S/ 500 | — |
| Fraccionado en 2 cuotas | S/ 275 | — |
| Parentesco UNU | S/ 0 | N° de constancia de RRHH |
| Beca académica | S/ 50 (libros) | N° de resolución |
| Media beca | S/ 250 | N° de resolución |

#### 13.4.3. Resultado esperado

En el paso 1 del asistente aparecen las reglas habilitadas y vigentes en la fecha del día. Al inscribir, el sistema verifica que:

- la suma de los pagos marcados alcance el mínimo de la regla (el total, o la primera cuota si se paga en cuotas);
- si la regla pide un documento, se haya ingresado.

Se acepta pagar de más (por ejemplo, S/ 501 sobre S/ 500).

Referencia de imagen:
`[Figura SA-18: Reglas de pago]`

### 13.5. Submódulo Carnet

#### 13.5.1. Objetivo

Registrar los datos fijos que aparecen en todos los carnets.

#### 13.5.2. Procedimiento

1. ingresar a `Configuración académica > Carnet`;
2. registrar el nombre del director y su cargo;
3. registrar la nota al pie del carnet (opcional), por ejemplo "Este carnet es válido exclusivamente para el ingreso al campus universitario.";
4. subir la imagen de la firma y el sello: PNG de hasta 1 MB, de preferencia con fondo transparente;
5. revisar la vista previa de la firma;
6. seleccionar `Guardar`;
7. seleccionar `Ver carnet de muestra (PDF)` para revisar el resultado.

#### 13.5.3. Resultado esperado

Los carnets se generan con la firma, el director, el cargo y la nota configurados.

Referencia de imagen:
`[Figura SA-19: Configuración del carnet]`

### 13.6. Submódulo Códigos de Admisión

#### 13.6.1. Objetivo

Registrar los códigos que entrega la Oficina de Admisión para cada sede, modalidad y carrera del proceso. Admisión los entrega al final del proceso y cambian cada vez, por eso no se usan para inscribir; solo se usan en la exportación de inscritos. Este paso es opcional.

#### 13.6.2. Procedimiento

1. ingresar a `Configuración académica > Códigos de Admisión`;
2. seleccionar el proceso; aparecen las combinaciones de su oferta;
3. registrar los códigos de una de estas dos formas:
   - escribirlos en la tabla y seleccionar `Guardar códigos`;
   - seleccionar `Descargar plantilla`, enviarla a Admisión para que llene solo la columna `CODIGO_ADMISION` sin tocar la columna `CLAVE`, y cargarla con `Cargar plantilla`;
4. si la plantilla tiene filas con errores, el sistema las lista en `Filas de la plantilla que no se cargaron`.

Un código vacío borra el código de esa combinación.

#### 13.6.3. Resultado esperado

La exportación de inscritos con el formato CEPRE lleva los códigos de Admisión. Sin código, lleva el nombre de la sede o de la carrera.

Referencia de imagen:
`[Figura SA-20: Códigos de Admisión]`

## 14. Manual del perfil Gestor de Inscripciones CEPRE

El perfil `Gestor de Inscripciones CEPRE` está orientado a la operación diaria: registrar los pagos, inscribir, revisar las inscripciones del portal y entregar la ficha y el carnet.

Los módulos que utiliza son:

1. Pagos.
2. Inscripciones.
3. Fotos.
4. Validar pagos.
5. Reportes.

## 15. Módulo Pagos

### 15.1. Objetivo

Registrar en el sistema los pagos de los postulantes antes de inscribirlos. Cada pago queda:

- `LIBRE`: todavía no se usó en ninguna inscripción;
- `REGISTRADO`: ya se usó en una inscripción o en una cuota y no puede volver a usarse.

Esta tarea debe hacerse antes de inscribir: si el pago no está en el sistema, no aparecerá en el paso 1 del asistente ni en el portal.

### 15.2. Importar el TXT del banco

1. ingresar a `Inscripciones > Pagos` y seleccionar `Importaciones TXT`;
2. seleccionar `Importar TXT` y elegir el archivo del banco (máximo 5 MB);
3. revisar la `Revisión del TXT bancario`:
   - datos de la cabecera;
   - registros y monto leídos frente a los que reporta el banco;
   - `Pagos duplicados`, si los hay, con su motivo;
   - `Primeras operaciones` del archivo;
4. si todo es correcto, `Confirmar importación`; si no, `Cancelar` (o `Entendido` cuando el archivo tiene errores).

El sistema valida que:

- el archivo tenga cabecera (línea `01`), detalle y cierre (línea `03`);
- la cantidad de registros y el monto total coincidan con el cierre;
- el archivo no se haya importado antes;
- el archivo no tenga observaciones.

La lista de importaciones se busca por archivo y se filtra por estado (`Pendiente`, `Procesado`, `Observado`). En el menú de cada importación, `Ver pagos del lote` muestra sus pagos.

### 15.3. Registrar un pago manual

Se usa para pagos que no llegan en el TXT: los hechos en la caja de Admisión, en Posgrado, en otra cuenta de la UNU o los casos de parentesco UNU.

1. ingresar a `Inscripciones > Pagos`;
2. seleccionar `Registrar pago`; el formulario se abre en un panel a la derecha;
3. registrar:
   - DNI del postulante;
   - N° de operación o de recibo;
   - fecha;
   - importe (S/);
   - ubicación: `ADMISIÓN`, `POSGRADO`, `PARENTESCO UNU` u `OTRA CUENTA UNU`;
   - referencia y observación, si corresponde;
4. seleccionar `Guardar`. Si falta un dato, el campo se marca en rojo con su mensaje.

En la lista se busca por DNI, operación o referencia, se filtra por estado (`LIBRE`, `REGISTRADO`, `OBSERVADO`) y se descarga con `Excel`.

### 15.4. Asociar pagos pendientes

Después de importar un TXT, la acción `Asociar pendientes` del menú de la importación asocia automáticamente los pagos libres a las inscripciones que ya tienen saldo por pagar (cuotas siguientes). Para cada pago busca, por DNI, una obligación con saldo. El sistema pide confirmar y, al terminar, informa cuántos pagos se asociaron y cuántos se omitieron.

### 15.5. Resultado esperado

Los pagos quedan en el sistema como `LIBRES`, listos para usarse en el paso 1 del asistente, en el portal o en `Validar pagos`.

Referencias de imagen:
`[Figura GI-01: Importación del TXT del banco]`
`[Figura GI-02: Registro de pago manual]`
`[Figura GI-03: Lista de pagos libres y registrados]`

## 16. Módulo Inscripciones

Este módulo permite registrar inscripciones, revisar las del portal, consultar el detalle y entregar la ficha y el carnet.

### 16.1. Listado de inscripciones

#### 16.1.1. Objetivo

Consultar y gestionar las inscripciones registradas.

#### 16.1.2. Procedimiento

1. ingresar a `Inscripciones > Inscripciones`;
2. revisar los indicadores: `Total`, `Activas`, `Pago pendiente` y `Anuladas`;
3. buscar por código, DNI, apellidos o carrera (la tecla `/` lleva al buscador y `Esc` lo limpia);
4. filtrar por proceso y por `Estado`; `Más filtros` agrega modalidad, sede, turno, aula y estado del pago. Los filtros aplicados se resaltan en azul;
5. si hay inscripciones del portal por aprobar, el aviso `En revisión (N)` tiene el botón `Ver solo en revisión`;
6. en el menú de cada fila (los tres puntos), elegir la acción:
   - `Ver detalle`;
   - `Continuar registro`, en las que quedaron a medio registrar;
   - `Aprobar revisión`, en las que están en revisión;
   - `Validar pago`, cuando tiene saldo por pagar;
   - `Editar postulación`, en las activas o pendientes (ver 16.11);
   - `Asignar aula`;
   - `Ficha (PDF)` (no aparece en las anuladas);
   - `Carnet (PDF)` (solo en las activas);
   - `Anular`, en las activas o en revisión.

Cada fila muestra la foto del postulante; si no tiene foto, sus iniciales. Al pie de la tabla se elige cuántas filas ver por página y se cambia de página.

Desde el listado también se puede:

- `Nueva inscripción`;
- `Excel`, con los filtros aplicados;
- `Drive`: actualiza al momento las hojas `DATA` y `PAGOS` del proceso en el Drive de CEPRE UNU virtual, que usa el buscador de estudiantes. El sistema también lo hace solo cada 10 minutos. Al pasar el mouse muestra la última sincronización. El botón aparece solo cuando soporte técnico la configuró (manual de instalación, 6.16.11);
- `Carnets`: un PDF con el carnet de todas las inscripciones activas que cumplen los filtros, uno por página.

#### 16.1.3. Resultado esperado

El usuario localiza cualquier inscripción, conoce su estado y accede a sus acciones.

Referencia de imagen:
`[Figura GI-04: Listado general de inscripciones]`

### 16.2. Nueva inscripción

La nueva inscripción se registra en un asistente de 4 pasos. **Primero se valida el pago**: sin un pago libre que alcance el mínimo no se puede continuar.

Referencia de imagen:
`[Figura GI-05: Inicio del asistente de inscripción]`

### 16.3. Paso 1 - Validación de pago

#### 16.3.1. Objetivo

Encontrar los pagos libres del postulante y elegir la modalidad de pago.

#### 16.3.2. Procedimiento

1. registrar el DNI o CE del postulante y el N° de operación;
2. seleccionar `Verificar pago`:
   - si no hay un pago libre con esos datos, aparece el aviso `No encontramos un pago libre con esos datos` con lo que hay que revisar;
   - si lo hay, se listan en `Pagos libres del postulante` todos los pagos libres de ese DNI, con el buscado ya marcado;
3. marcar los pagos que se usan para esta inscripción (se suman);
4. seleccionar la modalidad de pago;
5. si la modalidad pide un documento (por ejemplo, N° de resolución), registrarlo;
6. revisar el aviso `Marcado S/ … de un mínimo de S/ …`;
7. seleccionar `Validar y continuar`.

#### 16.3.3. Resultado esperado

Si lo marcado alcanza el mínimo, el asistente pasa al paso 2. Los pagos quedan `REGISTRADOS` recién al terminar la inscripción.

Si el pago no aparece (aviso `No encontramos un pago libre con esos datos`):

- verificar que el TXT del día esté importado o que el pago manual esté registrado;
- verificar que el DNI del pago sea el del postulante;
- verificar que el pago no esté ya `REGISTRADO` en otra inscripción.

Referencia de imagen:
`[Figura GI-06: Paso 1 - Validación de pago]`

### 16.4. Paso 2 - Información personal

#### 16.4.1. Objetivo

Registrar o recuperar los datos del postulante.

#### 16.4.2. Procedimiento

1. si el DNI ya está registrado, el sistema carga sus datos automáticamente; si no, se pueden buscar con `Buscar datos` en RENIEC (cuando el servicio está configurado) o escribirlos;
2. completar o verificar:
   - tipo y número de documento (si el número tiene 8 dígitos, el tipo DNI se elige solo);
   - apellidos y nombres (con las opciones `Solo un apellido` y `Tiene apellido de casado(a)`);
   - fecha de nacimiento, sexo y estado civil;
   - país de nacimiento, nacionalidad, ubigeo de nacimiento y de domicilio (al abrir el buscador se ofrecen los distritos de Ucayali; se puede escribir el distrito, la provincia o el código), y dirección;
   - celular, teléfono y correo electrónico;
   - nombre completo y celular del apoderado (ambos celulares con 9 dígitos y empezando en 9);
   - identidad étnica y lengua materna: `CASTELLANO`, `NATIVA` (se elige la lengua nativa) o `EXTRANJERA` (se elige la lengua extranjera); por ejemplo, un postulante mestizo cuya lengua materna es el castellano marca `MESTIZO` y `CASTELLANO`;
   - discapacidad, si corresponde;
3. seleccionar `Guardar y continuar`.

#### 16.4.3. Resultado esperado

Los datos de la persona quedan registrados o actualizados.

Referencia de imagen:
`[Figura GI-07: Paso 2 - Información personal]`

### 16.5. Paso 3 - Información general

#### 16.5.1. Objetivo

Registrar el colegio y el año de egreso, del que depende la modalidad.

#### 16.5.2. Procedimiento

1. registrar el año de egreso de secundaria;
2. seleccionar el país y el tipo de colegio;
3. buscar y seleccionar el colegio (si es del extranjero, escribir su nombre);
4. registrar las veces que postuló a la UNU y a otras universidades;
5. añadir una observación si corresponde;
6. seleccionar `Guardar y continuar`.

#### 16.5.3. Resultado esperado

El sistema determina la modalidad según el año de egreso y el corte del proceso (ver 13.1).

Referencia de imagen:
`[Figura GI-08: Paso 3 - Información general]`

### 16.6. Paso 4 - Postulación

#### 16.6.1. Objetivo

Elegir sede, carrera y turno, revisar el resumen y registrar la inscripción.

#### 16.6.2. Procedimiento

1. revisar la modalidad asignada automáticamente;
2. seleccionar la sede, la carrera profesional y el turno;
3. revisar la foto del postulante; si no tiene, se puede subir con `Subir foto` (o reemplazar con `Cambiar foto`);
4. revisar el resumen de todos los datos y marcar `Confirmo que los datos son correctos`;
5. seleccionar `Registrar inscripción`.

Si el postulante ya tiene una inscripción en el mismo proceso, el sistema avisa y permite decidir si continuar.

#### 16.6.3. Resultado esperado

La inscripción queda registrada con:

- un código correlativo del proceso (por ejemplo `2620001`);
- un aula asignada automáticamente;
- los pagos marcados en estado `REGISTRADO`;
- estado `ACTIVA`, si lo pagado alcanza el mínimo de la regla.

Al terminar, el sistema muestra el detalle de la inscripción, desde donde se pueden abrir la ficha y el carnet.

Referencia de imagen:
`[Figura GI-09: Paso 4 - Postulación]`

### 16.7. Detalle de inscripción

#### 16.7.1. Objetivo

Revisar en una sola pantalla toda la información del postulante.

#### 16.7.2. Procedimiento

1. en el listado, seleccionar `Ver detalle` (o hacer clic en el código de la inscripción);
2. revisar:
   - código, estado, origen, proceso y fecha;
   - `Datos del postulante` y `Postulación`, con el aula asignada;
   - `Pago`: lo pagado frente al total, las obligaciones y sus pagos;
3. usar `Ficha` y `Carnet` para abrir los documentos;
4. si la inscripción está en revisión, usar `Aprobar revisión`;
5. si tiene saldo, `Validar un pago` lleva a la pantalla de validación con la inscripción ya buscada;
6. `Editar postulación` cambia la sede, la carrera o el turno (ver 16.11). Los cambios hechos aparecen en `Historial de cambios`.

#### 16.7.3. Resultado esperado

El usuario verifica integralmente la inscripción.

Referencia de imagen:
`[Figura GI-10: Detalle de inscripción]`

### 16.8. Revisión de las inscripciones del portal

#### 16.8.1. Objetivo

Aprobar las inscripciones que los postulantes registraron en el portal (ver sección 21).

#### 16.8.2. Procedimiento

1. en el listado, en el aviso `En revisión (N)`, seleccionar `Ver solo en revisión` (también se llega desde `Para atender` en el inicio);
2. abrir cada inscripción con `Ver detalle` y verificar los datos y el pago;
3. si todo está bien, seleccionar `Aprobar revisión`;
4. si no corresponde, seleccionar `Anular` y registrar el motivo.

#### 16.8.3. Resultado esperado

Al aprobarla, la inscripción deja de estar en revisión y queda `ACTIVA` si el pago alcanza el mínimo. El postulante podrá descargar su carnet desde el portal.

Referencia de imagen:
`[Figura GI-11: Inscripciones en revisión]`

### 16.9. Anular una inscripción

1. en el menú de la fila, seleccionar `Anular`;
2. registrar el `Motivo de anulación` (mínimo 10 caracteres);
3. seleccionar `Anular` para confirmar.

Solo se anulan inscripciones activas o en revisión. Al anular, el cupo del aula se libera y el carnet pasa a figurar como `NO VÁLIDO` en la verificación del QR.

### 16.10. Asignar o cambiar el aula

1. en el menú de la fila, seleccionar `Asignar aula`;
2. elegir un aula habilitada del mismo proceso y turno; el sistema muestra cuántos inscritos tiene cada una y marca en rojo las llenas;
3. seleccionar `Asignar aula`.

### 16.11. Editar la postulación (sede, carrera o turno)

Sirve para cambios puntuales que pide el postulante después de inscribirse, por ejemplo pasar al turno tarde o cambiar de carrera. Requiere el permiso de editar inscripciones.

1. en el menú de la fila o en el detalle, seleccionar `Editar postulación`;
2. elegir la nueva sede, carrera o turno. Solo se ofrecen las carreras habilitadas en esa sede del proceso, y cada turno muestra sus cupos libres (los turnos sin cupo no se pueden elegir);
3. seleccionar `Revisar y confirmar`;
4. revisar el cambio (antes → después), escribir el `Motivo del cambio` (mínimo 10 caracteres) y seleccionar `Confirmar cambio`;
5. volver a imprimir la ficha y el carnet.

Si cambia el turno, se libera el aula y se asigna una con cupo del nuevo turno; si ninguna tiene cupo, no se guarda nada. La modalidad no se cambia aquí, porque depende del año de egreso, y los pagos registrados se mantienen. Cada cambio queda en el `Historial de cambios` del detalle, con la fecha, el usuario y el motivo. Las inscripciones anuladas no se pueden editar.

## 17. Módulo Fotos

### 17.1. Objetivo

Registrar las fotos de los postulantes para la ficha y el carnet. La foto se guarda por DNI y sirve para todos los procesos.

### 17.2. Subir fotos en lote

1. ingresar a `Inscripciones > Fotos`;
2. seleccionar `Subir fotos`;
3. elegir los archivos: JPG o PNG, hasta 2 MB cada uno y hasta 50 por vez, nombrados con el DNI (por ejemplo `47940211.jpg`);
4. revisar el DNI que el sistema leyó de cada nombre y corregirlo si hace falta (cada archivo se puede quitar de la lista);
5. seleccionar `Guardar N foto(s)`.

Si una foto ya existe, se reemplaza. El sistema recorta y ajusta cada foto al tamaño del carnet (300 × 400 px).

También se puede subir la foto de un postulante en el paso 4 del asistente.

### 17.3. Consultar, eliminar y exportar

- el filtro de proceso muestra las fotos de los inscritos en ese proceso (`Todos los procesos` muestra todas);
- `Buscar por DNI` ubica una foto;
- cada foto puede eliminarse; el sistema pide confirmar;
- `Descargar ZIP` descarga las fotos del proceso, nombradas `DNI.jpg`, para la imprenta.

La foto también se muestra en el listado de inscripciones y en las últimas inscripciones del inicio.

### 17.4. Resultado esperado

La ficha y el carnet salen con la foto del postulante. Sin foto, salen con el recuadro vacío.

Referencia de imagen:
`[Figura GI-12: Módulo de fotos]`

## 18. Ficha de matrícula y carnet

### 18.1. Ficha de matrícula

Es un PDF A4 con:

- el logo, el proceso, la foto y el N° de registro (código de inscripción);
- la sede y la información CEPRE-UNU, académica, personal y de contacto;
- la información de pago y la tabla de pagos con el total;
- el parentesco, la observación y la declaración jurada;
- las líneas de firma del postulante y del apoderado.

Se abre con `Ficha (PDF)` desde el menú de la fila o con `Ficha` desde el detalle. No se genera para inscripciones anuladas.

### 18.2. Carnet

Es un PDF con:

- en la mitad izquierda, los datos del postulante (turno, modalidad, carrera, documento, apellidos y nombres), la foto, la sede y el proceso;
- en la mitad derecha, la firma y el sello, el nombre y cargo del director, el código QR y la nota.

Se genera solo para inscripciones `ACTIVAS`, una por una (`Carnet (PDF)` en el menú de la fila o `Carnet` en el detalle) o en lote (`Carnets` en el listado).

### 18.3. Verificación del carnet

El QR del carnet abre una página pública que muestra el nombre, el DNI parcialmente oculto, el proceso, la sede, la carrera y el turno, y si el carnet es:

- `VÁLIDO`: la inscripción está activa;
- `NO VÁLIDO`: la inscripción está anulada o pendiente.

Referencias de imagen:
`[Figura GI-13: Ficha de matrícula]`
`[Figura GI-14: Carnet]`
`[Figura GI-15: Verificación del carnet por QR]`

## 19. Módulo Validar pagos

### 19.1. Objetivo

Asociar un pago libre a una inscripción que ya tiene saldo por pagar. Se usa para las cuotas siguientes o cuando `Asociar pendientes` omitió un pago.

### 19.2. Procedimiento

La pantalla tiene dos partes: a la izquierda `1 Obligación de pago` y a la derecha `2 Pago del banco`.

1. ingresar a `Inscripciones > Validar pagos` (o usar `Validar pago` en el menú de la inscripción, que ya la trae buscada);
2. en `Obligación de pago`, buscar por código de inscripción, DNI o apellidos y seleccionar la obligación; cada una muestra el total, lo pagado y el saldo;
3. en `Pago del banco`, registrar el DNI, el N° de operación y la fecha de pago, y seleccionar `Buscar pago`. El sistema valida los datos: el DNI debe tener entre 8 y 12 dígitos, el número de operación solo dígitos y la fecha no puede ser posterior a hoy; si algo falta, el campo se marca en rojo;
4. elegir el pago correcto en la lista (los que ya se usaron aparecen como `Ya usado` y no se pueden elegir);
5. seleccionar `Validar y asociar pago`.

Para cambiar de inscripción basta con elegir otra obligación en la lista de la izquierda. `Pagos ya asociados` muestra los pagos que ya tiene la obligación.

### 19.3. Resultado esperado

El pago pasa a `REGISTRADO` y la obligación recalcula su saldo, pasando a `PARCIAL` o `PAGADO` según corresponda. Si la inscripción estaba pendiente y ahora alcanza el mínimo, queda `ACTIVA`.

Referencia de imagen:
`[Figura GI-16: Validación de pagos]`

## 20. Módulo Reportes

### 20.1. Objetivo

Analizar las inscripciones del proceso con indicadores y gráficos, y exportarlas.

### 20.2. Procedimiento

1. ingresar a `Inscripciones > Reporte de inscripciones`;
2. elegir el proceso (o `Todos los procesos`) y, si corresponde, la modalidad, la sede, el turno y el estado;
3. revisar los indicadores: `Inscritos` (y cuántos desde el portal), `Activas`, `Pendientes` (con saldo y pago parcial) y `Recaudado` frente a lo esperado;
4. revisar los gráficos:
   - `Inscripciones por día`, hasta los últimos 31 días con inscripciones;
   - `Estado de las inscripciones`;
   - `Estado del pago`, `Sexo` y `Origen` (personal o portal en línea);
   - `Por carrera`, `Por modalidad`, `Por turno` y `Por sede`, con barras divididas en activas, pendientes y anuladas;
5. al pasar el mouse sobre un gráfico se ve su detalle; el botón `Tabla` de cada gráfico muestra las cifras exactas;
6. seleccionar `Exportar Excel` para descargar el listado con los mismos filtros;
7. verificar el archivo generado.

La exportación con el formato CEPRE incluye los códigos de Admisión cuando están registrados (ver 13.6). Sus dos primeras columnas son `NRO_REG` (el código de inscripción, el mismo `N° REG.` de la exportación de pagos) y `FECHA_Y_HORA`, y las filas salen en orden de registro. Así se puede pegar directamente en la hoja `DATA CEPRE-UNU` del Drive, igual que la exportación de pagos en la hoja `PAGOS`.

### 20.3. Resultado esperado

El sistema produce un reporte ordenado para control administrativo y para entregar a Admisión.

Referencia de imagen:
`[Figura GI-17: Reporte de inscripciones]`

## 21. Portal del postulante

El portal es la parte pública del sistema; el postulante no necesita usuario. Se ingresa en la dirección del sistema seguida de `/postulante`. El personal debe conocerlo para orientar a los postulantes.

### 21.1. Inscripción en línea

1. el postulante entra a `/postulante` y elige inscribirse;
2. ingresa su DNI y el N° de operación de su pago y selecciona `Verificar pago`:
   - si no hay un pago libre con esos datos, ve un aviso que le pide revisar el voucher; si pagó hoy, el pago puede tardar en aparecer;
   - si lo hay, ve sus pagos libres; marca los que usa y elige la modalidad de pago;
3. el panel `Tenga a la mano` le recuerda lo que necesita: documento, número de operación, colegio y año de egreso, y la sede, carrera y turno;
4. completa los mismos datos de los pasos 2 a 4 del asistente; el número de documento es el del pago y no se puede cambiar, y con 8 dígitos el tipo DNI se elige solo (si es otro documento, lo cambia);
5. al terminar recibe su código de inscripción y puede usar `Descargar ficha`.

Diferencias con el asistente del personal:

- solo se usan pagos y datos del DNI ingresado;
- no se permite una segunda inscripción en el mismo proceso;
- no se sube foto: se toma en el CEPRE-UNU;
- la inscripción queda **EN REVISIÓN** hasta que un gestor la aprueba (ver 16.8);
- hay un límite de intentos por minuto para evitar abusos.

### 21.2. Consultar la inscripción

1. el postulante entra a `/postulante` y elige consultar;
2. ingresa su DNI y el N° de operación de cualquiera de sus pagos;
3. el sistema muestra el estado de la inscripción y su aula;
4. puede usar `Descargar ficha` y, si la inscripción está activa, `Descargar carnet`.

Si el postulante pagó pero no terminó de inscribirse, la consulta muestra el aviso `Aún no completa su inscripción` con el botón `Inscribirme`, que lo lleva a la inscripción en línea. Si no hay pago ni inscripción con esos datos, indica que no se encontró la inscripción.

Los enlaces de descarga vencen a los 60 minutos; si vencen, basta con volver a consultar.

Referencias de imagen:
`[Figura PP-01: Portal del postulante]`
`[Figura PP-02: Inscripción en línea]`
`[Figura PP-03: Consulta de la inscripción]`

## 22. Caso de uso integral del sistema

Para una operación ordenada, se recomienda seguir este flujo:

1. el `SuperAdministrador` crea o verifica usuarios y roles;
2. el `SuperAdministrador` verifica los catálogos base (sección 12);
3. el `SuperAdministrador` crea el proceso con sus fechas y el año de egreso máximo para Ordinario;
4. el `SuperAdministrador` configura la oferta del proceso, las aulas, las reglas de pago y el carnet;
5. el `Gestor de Inscripciones CEPRE` carga el TXT del banco del día y registra los pagos manuales;
6. el `Gestor de Inscripciones CEPRE` inscribe a los postulantes que acuden al CEPRE-UNU y aprueba las inscripciones del portal;
7. el `Gestor de Inscripciones CEPRE` sube las fotos y entrega la ficha y el carnet;
8. las cuotas siguientes se validan desde `Pagos` o `Validar pagos`;
9. se generan reportes para seguimiento;
10. al final, el `SuperAdministrador` registra los códigos de Admisión y se exporta el listado para Admisión.

## 23. Buenas prácticas operativas

1. cargar el TXT del banco antes de empezar a inscribir cada día;
2. validar siempre el documento del postulante antes de guardar;
3. revisar cuidadosamente correo, celular y datos del apoderado;
4. verificar que el año de egreso sea correcto, porque de él depende la modalidad;
5. revisar diariamente las inscripciones `En revisión` del portal;
6. usar el rol operativo para el trabajo diario y reservar el rol administrador para la configuración;
7. registrar observaciones cuando exista alguna inconsistencia;
8. generar reportes periódicamente para control interno.

## 24. Errores operativos frecuentes

1. inscribir antes de cargar el TXT del banco del día;
2. registrar un pago manual con un DNI equivocado;
3. registrar mal el año de egreso, lo que asigna otra modalidad;
4. dejar el proceso sin el año de egreso máximo correcto al pasar de un proceso a otro;
5. no habilitar en la oferta del proceso una modalidad para egresados o para estudiantes en colegio;
6. dejar un turno sin aulas con capacidad en el proceso;
7. olvidar aprobar las inscripciones del portal;
8. entregar carnets sin haber configurado la firma y el director;
9. asignar permisos excesivos a usuarios operativos;
10. confundir la lengua materna: quien habla castellano desde niño marca `CASTELLANO`, no `NATIVA`;
11. ignorar los pendientes de `Para atender` en el inicio, sobre todo las inscripciones activas sin aula.

## 25. Datos demo cargados para el manual

Para elaborar capturas y demostraciones, los datos de prueba incluyen:

- el proceso `2026-I`;
- `50` inscripciones activas en la modalidad `ORDINARIO` y el turno `MAÑANA`;
- personas con ubigeo de `UCAYALI / CORONEL PORTILLO`;
- pagos registrados y aulas asignadas;
- el usuario `gestor.cepre` con el rol `GESTOR DE INSCRIPCIONES CEPRE` (inscripciones, pagos y reportes; los permisos de fotos se asignan aparte).

## 26. Procedimiento para recargar datos de demostración

Si se requiere volver a cargar la información demo, puede utilizarse el siguiente comando. **No usar en producción.**

```bash
php artisan db:seed --class=DevelopmentDataSeeder --no-interaction
```

## 27. Conclusiones

El sistema CEPRE UNU administra de manera integral el proceso de inscripción: seguridad, configuración de cada proceso, pagos, inscripción presencial y en línea, fotos, ficha de matrícula, carnet con verificación y reportes.

La correcta utilización del sistema depende de dos factores principales:

1. una configuración adecuada de cada proceso por parte del `SuperAdministrador`;
2. una operación ordenada y precisa por parte del `Gestor de Inscripciones CEPRE`.

La presente guía puede ser utilizada como base para capacitación interna, control operativo y documentación institucional definitiva.

## 28. Anexos sugeridos

Para una versión formal de entrega, se recomienda añadir los siguientes anexos:

1. capturas del inicio de sesión;
2. capturas del panel principal;
3. capturas de cada módulo;
4. capturas del asistente de inscripción completo;
5. ejemplo de ficha de matrícula y de carnet;
6. capturas del portal del postulante;
7. ejemplo de reporte exportado;
8. glosario breve de términos del sistema.
