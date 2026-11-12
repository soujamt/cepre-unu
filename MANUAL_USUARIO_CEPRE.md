# UNIVERSIDAD NACIONAL DE UCAYALI
## CENTRO PREUNIVERSITARIO - CEPRE UNU
### MANUAL DE USUARIO DEL SISTEMA DE INSCRIPCIONES

**Versión:** 1.1  
**Documento:** Manual de usuario funcional  
**Dirigido a:** SuperAdministrador y Gestor de Inscripciones CEPRE  
**Fecha:** Noviembre 2026

> **Cambios en la versión 1.1:** se retiró el submódulo Semestres (el proceso se crea directamente con sus fechas), se documentaron los mantenedores Carreras, Sedes, Modalidades, Lugares de inscripción, Ofertas base, Turnos y Conceptos de pago, y se describió la importación del archivo TXT del banco. Los diagramas del flujo completo están en `FLUJO_PROCESOS_CEPRE.md`.

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
15. Módulo Inscripciones
16. Módulo Pagos
17. Módulo Validar pagos
18. Módulo Reportes
19. Caso de uso integral del sistema
20. Buenas prácticas operativas
21. Errores operativos frecuentes
22. Datos demo cargados para el manual
23. Procedimiento para recargar datos de demostración
24. Conclusiones
25. Anexos sugeridos

---

## 1. Introducción

El presente manual de usuario ha sido elaborado con la finalidad de describir, de manera ordenada, detallada y funcional, el uso del sistema CEPRE UNU orientado al proceso de inscripciones. El documento está diseñado para servir como material de capacitación, consulta operativa y apoyo institucional para el personal responsable de la administración y ejecución del proceso de inscripción.

El sistema permite gestionar usuarios, permisos, configuraciones académicas, ofertas, aulas, pagos, expedientes e inscripciones, integrando en una sola plataforma los componentes necesarios para la operación del proceso de admisión del Centro Preuniversitario de la Universidad Nacional de Ucayali.

El manual se ha organizado considerando dos perfiles funcionales claramente diferenciados:

- `SuperAdministrador`
- `Gestor de Inscripciones CEPRE`

Cada uno de estos perfiles dispone de accesos y responsabilidades distintas dentro del sistema.

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
- inscripciones;
- pagos;
- validación de pagos;
- reportes.

No forma parte de este documento la documentación técnica del sistema, la estructura interna de base de datos, la instalación del entorno ni la administración de infraestructura.

## 4. Perfiles de usuario

### 4.1. SuperAdministrador

El perfil `SuperAdministrador` tiene acceso integral a todos los módulos del sistema. Su función principal es garantizar la correcta configuración, control y administración general de la plataforma.

Entre sus responsabilidades se encuentran:

- administrar usuarios;
- administrar roles;
- administrar permisos;
- mantener catálogos operativos;
- mantener los catálogos base: carreras, sedes, modalidades, lugares de inscripción, ofertas base, turnos, aulas, conceptos de pago y tipos de expediente;
- configurar procesos;
- gestionar ofertas, aulas por proceso, convenios, reglas de pago y requisitos;
- supervisar inscripciones, pagos, expedientes y reportes.

### 4.2. Gestor de Inscripciones CEPRE

El perfil `Gestor de Inscripciones CEPRE` corresponde al usuario operativo encargado de ejecutar el proceso diario de inscripción.

Entre sus responsabilidades se encuentran:

- registrar nuevas inscripciones;
- continuar inscripciones pendientes;
- revisar el detalle de inscripciones;
- consultar pagos;
- validar pagos;
- emitir reportes de inscripciones.

Este perfil no administra seguridad ni configuración académica.

## 5. Credenciales de referencia para entorno demo

Para las actividades de demostración y captura, se consideran las siguientes credenciales:

- `superadmin` / `password`
- `gestor.cepre` / `password`

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

Ejemplos:

- `Figura SA-01: Pantalla de inicio de sesión`
- `Figura SA-08: Asignación de permisos al rol`
- `Figura GI-06: Paso 4 - Registro y validación del pago`

## 7. Flujo general del sistema

El flujo funcional del sistema puede resumirse de la siguiente manera:

1. el usuario accede al sistema con sus credenciales;
2. revisa el panel principal;
3. el `SuperAdministrador` mantiene los catálogos base (carreras, sedes, modalidades, lugares de inscripción, ofertas base, turnos, aulas, conceptos de pago y tipos de expediente);
4. el `SuperAdministrador` configura el proceso de inscripción: proceso, ofertas, aulas, convenios, reglas de pago y requisitos;
5. el `Gestor de Inscripciones CEPRE` importa el archivo TXT del banco con los depósitos recibidos;
6. el `Gestor de Inscripciones CEPRE` registra la inscripción del postulante y asocia su depósito en el paso de pago;
7. se revisan los expedientes;
8. la inscripción queda activa cuando el pago está verificado y todos los expedientes obligatorios están aprobados;
9. finalmente, se generan reportes para control y seguimiento.

Los diagramas de este flujo, con el detalle de cada estado, se encuentran en el documento `FLUJO_PROCESOS_CEPRE.md`.

Referencias de imagen:
`[Figura SA-01: Pantalla de inicio de sesión]`
`[Figura SA-02: Panel principal del sistema]`

## 8. Consideraciones previas al uso del sistema

Antes de operar el sistema, se debe verificar:

1. que exista un proceso `HABILITADO` cuya fecha y hora de apertura ya pasó y cuya fecha y hora de cierre aún no llega;
2. que el proceso tenga al menos una oferta habilitada;
3. que cada turno ofertado tenga aulas habilitadas con capacidad suficiente;
4. que exista al menos una regla de pago habilitada y vigente en la fecha actual;
5. que los requisitos de expediente estén definidos y al menos uno sea obligatorio;
6. que los convenios, si se usarán, estén vigentes;
7. que el archivo TXT del banco con los depósitos esté importado;
8. que los usuarios tengan el rol adecuado.

Estas validaciones son importantes porque, de no cumplirse, el sistema impide el avance normal del proceso de inscripción:

- sin proceso vigente con ofertas, el asistente no permite pasar del paso 1;
- sin aula con cupo para el turno elegido, no se puede guardar la postulación;
- sin el TXT importado, no se encuentra el depósito en el paso de pago;
- si el proceso no tiene requisitos obligatorios, la inscripción no llega al estado `ACTIVO`.

## 9. Panel principal o inicio

El panel principal es la pantalla que se muestra después de un inicio de sesión exitoso. Su función es ofrecer una visión general del estado operativo del sistema y facilitar el acceso rápido a los módulos más utilizados.

### 9.1. Información que puede mostrar

En esta pantalla, el usuario puede visualizar:

- actividad reciente;
- cantidad de nuevas inscripciones;
- estado de pagos;
- estado de expedientes;
- accesos rápidos a módulos autorizados.

### 9.2. Procedimiento de revisión

1. ingresar al sistema;
2. revisar los indicadores visibles en la pantalla principal;
3. identificar alertas o actividades pendientes;
4. ingresar al módulo requerido desde los accesos rápidos.

### 9.3. Resultado esperado

El usuario debe comprender rápidamente el estado actual de operación y acceder al módulo necesario sin dificultad.

Referencia de imagen:
`[Figura SA-03: Dashboard principal con indicadores]`

## 10. Manual del perfil SuperAdministrador

El perfil `SuperAdministrador` administra la estructura funcional del sistema. Su intervención se concentra en la configuración, habilitación y control general de todos los procesos.

Este perfil utiliza principalmente los siguientes grupos de módulos:

1. Seguridad.
2. Mantenedores.
3. Configuración Académica.
4. Supervisión operativa.

## 11. Módulo Seguridad

El módulo Seguridad tiene como finalidad controlar el acceso al sistema y la distribución de permisos.

### 11.1. Submódulo Usuarios

#### 11.1.1. Objetivo

Permitir el registro, edición, activación y desactivación de usuarios del sistema.

#### 11.1.2. Procedimiento

1. ingresar a `Seguridad > Usuarios`;
2. revisar el listado de usuarios registrados;
3. utilizar el buscador para localizar usuarios específicos;
4. seleccionar `Nuevo usuario` cuando se requiera crear un registro;
5. completar los datos del formulario:
   - nombre de usuario;
   - correo electrónico;
   - nombres completos;
   - estado;
6. guardar la información;
7. verificar que el usuario aparezca en el listado;
8. editar o cambiar el estado del usuario cuando sea necesario.

#### 11.1.3. Resultado esperado

El sistema debe mostrar el usuario correctamente registrado y disponible para su uso o administración.

Referencias de imagen:
`[Figura SA-04: Listado de usuarios]`
`[Figura SA-05: Formulario de creación o edición de usuario]`

### 11.2. Submódulo Roles

#### 11.2.1. Objetivo

Permitir la creación de perfiles funcionales agrupando permisos según las responsabilidades de cada tipo de usuario.

#### 11.2.2. Procedimiento

1. ingresar a `Seguridad > Roles`;
2. revisar el listado de roles existentes;
3. crear un nuevo rol o editar uno existente;
4. registrar:
   - nombre del rol;
   - descripción;
   - estado;
5. guardar la información;
6. seleccionar la opción `Asignar permisos`;
7. marcar los permisos que correspondan al rol;
8. guardar la asignación.

#### 11.2.3. Resultado esperado

El rol debe quedar disponible para asociarse a usuarios y controlar sus funciones dentro del sistema.

Referencias de imagen:
`[Figura SA-06: Listado de roles]`
`[Figura SA-07: Formulario del rol]`
`[Figura SA-08: Asignación de permisos]`

### 11.3. Submódulo Permisos

#### 11.3.1. Objetivo

Permitir el control detallado de accesos por módulo y tipo de acción.

#### 11.3.2. Procedimiento

1. ingresar a `Seguridad > Permisos`;
2. revisar el listado de permisos existentes;
3. utilizar el buscador para ubicar permisos específicos;
4. crear o editar permisos cuando la operación funcional lo requiera;
5. verificar las acciones relacionadas con cada permiso;
6. asegurar que el permiso se encuentre habilitado.

#### 11.3.3. Resultado esperado

Los permisos deben quedar disponibles para su uso en la configuración de roles.

Referencia de imagen:
`[Figura SA-09: Listado de permisos]`

## 12. Módulo Mantenedores

Este módulo contiene los catálogos de apoyo para la operación del sistema. Se configuran una vez y se actualizan solo cuando cambia la oferta institucional.

Varios catálogos dependen de otros, por lo que se recomienda registrarlos en el siguiente orden:

1. Carreras.
2. Sedes, y en cada sede, las carreras que ofrece.
3. Modalidades.
4. Lugares de inscripción, y en cada lugar, las modalidades que atiende.
5. Ofertas base, que combinan sede, carrera, lugar y modalidad.
6. Turnos.
7. Aulas.
8. Conceptos de pago.
9. Tipos de expediente.
10. Colegios, solo para corregir o completar el catálogo precargado.

En todos los submódulos, el estado del registro (habilitado o deshabilitado) se cambia desde la columna `Acciones` del listado. Un registro deshabilitado deja de mostrarse como opción en las pantallas que dependen de él.

### 12.1. Submódulo Carreras

#### 12.1.1. Objetivo

Registrar las carreras profesionales a las que se puede postular.

#### 12.1.2. Procedimiento

1. ingresar a `Mantenedores > Carreras`;
2. buscar por código o nombre para verificar que la carrera no exista;
3. crear una nueva carrera;
4. registrar:
   - código;
   - nombre;
5. guardar el registro.

#### 12.1.3. Resultado esperado

La carrera debe quedar disponible para asignarse a una o más sedes.

Referencia de imagen:
`[Figura SA-21: Módulo de carreras]`

### 12.2. Submódulo Sedes

#### 12.2.1. Objetivo

Registrar las sedes de la universidad y definir qué carreras se ofrecen en cada una.

#### 12.2.2. Procedimiento

1. ingresar a `Mantenedores > Sedes`;
2. crear una nueva sede o editar una existente;
3. registrar:
   - código;
   - nombre;
   - ubigeo;
   - dirección;
4. guardar el registro;
5. en la fila de la sede, seleccionar `Carreras` (o el contador de carreras);
6. marcar las carreras que ofrece la sede, usando el filtro si es necesario;
7. guardar la asignación.

#### 12.2.3. Resultado esperado

La sede debe mostrar la cantidad de carreras habilitadas. Cada par sede–carrera queda disponible para conformar ofertas base.

Referencias de imagen:
`[Figura SA-22: Módulo de sedes]`
`[Figura SA-23: Asignación de carreras a la sede]`

### 12.3. Submódulo Modalidades

#### 12.3.1. Objetivo

Registrar las modalidades de admisión, por ejemplo ordinario o exoneración.

#### 12.3.2. Procedimiento

1. ingresar a `Mantenedores > Modalidades`;
2. crear una nueva modalidad o editar una existente;
3. registrar:
   - código;
   - nombre;
   - descripción;
4. guardar el registro.

#### 12.3.3. Resultado esperado

La modalidad debe quedar disponible para asignarse a los lugares de inscripción y, de ser necesario, a reglas de pago específicas.

Referencia de imagen:
`[Figura SA-24: Módulo de modalidades]`

### 12.4. Submódulo Lugares de Inscripción

#### 12.4.1. Objetivo

Registrar los lugares donde se realizan las inscripciones y definir qué modalidades atiende cada uno.

#### 12.4.2. Procedimiento

1. ingresar a `Mantenedores > Lugares de Inscripción`;
2. crear un nuevo lugar o editar uno existente;
3. registrar:
   - código;
   - nombre;
   - sede a la que pertenece;
4. guardar el registro;
5. en la fila del lugar, seleccionar `Asignar modalidades`;
6. marcar las modalidades que se atienden en ese lugar;
7. guardar la asignación.

#### 12.4.3. Resultado esperado

El listado debe mostrar las modalidades asignadas a cada lugar. Si aparece `Sin modalidades`, el lugar no podrá usarse en ofertas base.

Referencias de imagen:
`[Figura SA-25: Módulo de lugares de inscripción]`
`[Figura SA-26: Asignación de modalidades al lugar]`

### 12.5. Submódulo Ofertas Base

#### 12.5.1. Objetivo

Definir las combinaciones de sede, carrera, lugar de inscripción y modalidad que podrán abrirse en un proceso.

#### 12.5.2. Procedimiento

1. ingresar a `Mantenedores > Ofertas Base`;
2. filtrar por sede o modalidad para revisar las ofertas existentes;
3. crear una nueva oferta base;
4. seleccionar, en este orden:
   - sede;
   - lugar de inscripción;
   - modalidad;
   - carrera (solo aparecen las carreras asignadas a la sede);
5. registrar el código;
6. guardar el registro.

#### 12.5.3. Resultado esperado

La oferta base debe quedar disponible para ser asignada a un proceso desde `Configuración Académica > Ofertas`.

Referencia de imagen:
`[Figura SA-27: Módulo de ofertas base]`

### 12.6. Submódulo Turnos

#### 12.6.1. Objetivo

Registrar los turnos de estudio, por ejemplo mañana o tarde.

#### 12.6.2. Procedimiento

1. ingresar a `Mantenedores > Turnos`;
2. crear un nuevo turno o editar uno existente;
3. registrar:
   - código;
   - nombre;
4. guardar el registro.

#### 12.6.3. Resultado esperado

El turno debe quedar disponible para asignar ofertas y aulas a un proceso.

Referencia de imagen:
`[Figura SA-28: Módulo de turnos]`

### 12.7. Submódulo Aulas

#### 12.7.1. Objetivo

Registrar y administrar las aulas que luego serán utilizadas en la distribución de postulantes por proceso y turno.

#### 12.7.2. Procedimiento

1. ingresar a `Mantenedores > Aulas`;
2. revisar el listado de aulas;
3. crear una nueva aula cuando corresponda;
4. completar:
   - nombre;
   - lugar;
   - piso;
   - orden;
5. guardar el registro.

#### 12.7.3. Resultado esperado

El aula debe quedar disponible para asociarse posteriormente a un proceso. El lugar, el piso y el nombre definen el orden en que el sistema llena las aulas al asignarlas automáticamente.

Referencia de imagen:
`[Figura SA-10: Módulo de aulas]`

### 12.8. Submódulo Conceptos de Pago

#### 12.8.1. Objetivo

Registrar los conceptos por los que se cobra al postulante, por ejemplo el derecho de inscripción.

#### 12.8.2. Procedimiento

1. ingresar a `Mantenedores > Conceptos de Pago`;
2. crear un nuevo concepto o editar uno existente;
3. registrar:
   - código;
   - nombre;
   - descripción;
   - si permite cuotas;
4. guardar el registro.

#### 12.8.3. Resultado esperado

El concepto debe quedar disponible para crear reglas de pago en cada proceso.

Referencia de imagen:
`[Figura SA-29: Módulo de conceptos de pago]`

### 12.9. Submódulo Tipos de Expediente

#### 12.9.1. Objetivo

Definir los tipos documentales que podrán ser requeridos a los postulantes.

#### 12.9.2. Procedimiento

1. ingresar a `Mantenedores > Tipos de Expediente`;
2. crear o editar un tipo de expediente;
3. registrar:
   - código;
   - nombre;
   - descripción;
   - si es obligatorio por defecto;
4. guardar la información.

#### 12.9.3. Resultado esperado

Los tipos de expediente deben quedar disponibles para ser incorporados a los requisitos del proceso.

Referencia de imagen:
`[Figura SA-12: Tipos de expediente]`

### 12.10. Submódulo Colegios

#### 12.10.1. Objetivo

Gestionar la información de colegios utilizada durante el registro de la postulación.

#### 12.10.2. Procedimiento

1. ingresar a `Mantenedores > Colegios`;
2. buscar por nombre, código modular o ubicación;
3. registrar o editar información del colegio si es necesario;
4. validar:
   - nombre;
   - tipo de colegio;
   - ubigeo;
   - estado.

#### 12.10.3. Resultado esperado

El colegio debe poder ser encontrado y utilizado durante la inscripción.

Referencia de imagen:
`[Figura SA-11: Módulo de colegios]`

## 13. Módulo Configuración Académica

Este módulo permite preparar cada proceso de inscripción antes de su uso operativo. Se repite por cada nuevo proceso, en el siguiente orden: procesos, ofertas, aulas, convenios, reglas de pago y requisitos.

### 13.1. Submódulo Procesos

#### 13.1.1. Objetivo

Definir el proceso de inscripción y el periodo en que estará abierto.

#### 13.1.2. Procedimiento

1. ingresar a `Configuración Académica > Procesos`;
2. crear un nuevo proceso;
3. registrar:
   - nombre;
   - apertura (fecha y hora);
   - cierre (fecha y hora);
4. guardar.

El código del proceso se genera automáticamente a partir del nombre, y el proceso se crea en estado `HABILITADO`.

#### 13.1.3. Resultado esperado

El proceso debe quedar habilitado. Las inscripciones solo pueden registrarse entre la fecha y hora de apertura y la de cierre. Cuando pasa la fecha de cierre, el sistema cambia el proceso a `CERRADO` automáticamente.

Si hay más de un proceso habilitado dentro de su periodo, el sistema usa el de apertura más antigua. Se recomienda no superponer fechas entre procesos.

Referencia de imagen:
`[Figura SA-14: Configuración del proceso]`

### 13.2. Submódulo Ofertas

#### 13.2.1. Objetivo

Abrir en el proceso las ofertas base que estarán disponibles, indicando el turno.

#### 13.2.2. Procedimiento

1. ingresar a `Configuración Académica > Ofertas`;
2. revisar el listado de ofertas asignadas;
3. seleccionar `Asignar oferta`;
4. elegir el proceso y el turno;
5. filtrar por modalidad o lugar de inscripción si es necesario;
6. marcar las ofertas base que se abrirán;
7. guardar la asignación.

Si una misma oferta base se dicta en dos turnos, debe asignarse una vez por cada turno.

#### 13.2.3. Resultado esperado

Las ofertas deben quedar habilitadas para su selección en el paso de postulación del asistente de inscripción.

Referencias de imagen:
`[Figura SA-15: Listado de ofertas]`
`[Figura SA-16: Asignación de oferta]`

### 13.3. Submódulo Aulas por Proceso

#### 13.3.1. Objetivo

Definir las aulas disponibles para un proceso y turno determinado, así como su capacidad.

#### 13.3.2. Procedimiento

1. ingresar a `Configuración Académica > Aulas`;
2. revisar las asignaciones existentes;
3. seleccionar `Asignar aula`;
4. elegir el proceso y el turno;
5. marcar las aulas que se usarán;
6. registrar la capacidad de cada aula marcada;
7. guardar la asignación.

#### 13.3.3. Resultado esperado

Cada turno ofertado en el proceso debe tener aulas con capacidad suficiente. Al guardar la postulación, el sistema asigna automáticamente la primera aula con cupo; si no hay ninguna, la postulación no se puede guardar.

Referencia de imagen:
`[Figura SA-17: Aulas por proceso]`

### 13.4. Submódulo Convenios

#### 13.4.1. Objetivo

Registrar beneficios especiales como descuentos o exoneraciones, asociados a un proceso.

#### 13.4.2. Procedimiento

1. ingresar a `Configuración Académica > Convenios`;
2. crear el convenio;
3. registrar:
   - nombre;
   - descripción;
   - tipo de beneficio: descuento, exoneración parcial o exoneración total;
   - monto o porcentaje de descuento, según el tipo;
   - proceso;
   - fecha de inicio y fecha de fin de vigencia;
   - cupo máximo, si corresponde;
4. guardar.

El código del convenio se genera automáticamente.

#### 13.4.3. Resultado esperado

El convenio debe quedar disponible para seleccionarse en el paso de postulación mientras esté vigente.

**Importante:** el sistema no descuenta automáticamente el monto del convenio. Para que el postulante pague el monto con beneficio, se debe crear una regla de pago asociada a ese convenio (ver 13.5).

Referencia de imagen:
`[Figura SA-19: Convenios]`

### 13.5. Submódulo Reglas de Pago

#### 13.5.1. Objetivo

Definir cuánto y cómo debe pagar el postulante.

#### 13.5.2. Procedimiento

1. ingresar a `Configuración Académica > Reglas de Pago`;
2. crear una nueva regla o editar una existente;
3. registrar:
   - concepto de pago;
   - proceso;
   - modalidad (opcional: solo si la regla aplica a una modalidad específica);
   - convenio (opcional: solo si la regla aplica a postulantes con ese convenio);
   - monto total;
   - si permite cuotas;
   - monto mínimo de la primera cuota;
   - número máximo de cuotas;
   - si requiere pago completo;
   - vigente desde y vigente hasta;
4. guardar la regla.

#### 13.5.3. Resultado esperado

En el paso de pago, el asistente muestra las reglas habilitadas y vigentes en la fecha del día que correspondan al proceso. Una regla sin modalidad ni convenio se aplica a todos los postulantes.

Al asociar un depósito, el sistema verifica que:

- el monto no supere el saldo pendiente;
- si la regla no permite cuotas o requiere pago completo, el depósito cubra el total;
- si permite cuotas, la primera alcance el monto mínimo definido.

Referencia de imagen:
`[Figura SA-18: Reglas de pago]`

### 13.6. Submódulo Requisitos

#### 13.6.1. Objetivo

Definir los expedientes que serán exigidos a los postulantes en un proceso determinado.

#### 13.6.2. Procedimiento

1. ingresar a `Configuración Académica > Requisitos`;
2. seleccionar el proceso;
3. agregar los tipos de expediente requeridos;
4. marcar cuáles son obligatorios;
5. guardar la configuración.

#### 13.6.3. Resultado esperado

Los requisitos deben aparecer en el paso de expedientes durante la inscripción.

**Importante:** el proceso debe tener al menos un requisito obligatorio. La inscripción pasa a `ACTIVO` cuando todos los requisitos obligatorios están aprobados; si no hay ninguno, la inscripción queda en `PENDIENTE`.

Referencia de imagen:
`[Figura SA-20: Requisitos del proceso]`

## 14. Manual del perfil Gestor de Inscripciones CEPRE

El perfil `Gestor de Inscripciones CEPRE` está orientado a la operación diaria del proceso de inscripción. Su función principal es registrar, verificar y completar el expediente del postulante.

Los módulos principales que utiliza son:

1. Inscripciones.
2. Pagos.
3. Validar pagos.
4. Reportes.

## 15. Módulo Inscripciones

Este módulo permite registrar nuevas inscripciones, consultar registros existentes y revisar el detalle de cada postulante.

### 15.1. Listado de inscripciones

#### 15.1.1. Objetivo

Permitir la consulta y gestión general de las inscripciones registradas.

#### 15.1.2. Procedimiento

1. ingresar a `Inscripciones`;
2. revisar el resumen de registros;
3. aplicar filtros o utilizar el buscador;
4. identificar el registro requerido;
5. seleccionar `Detalle` para revisión completa;
6. seleccionar `Continuar` en caso de inscripciones pendientes.

#### 15.1.3. Resultado esperado

El usuario debe localizar fácilmente cualquier inscripción y conocer su estado.

Referencia de imagen:
`[Figura GI-01: Listado general de inscripciones]`

### 15.2. Nueva inscripción

La nueva inscripción se realiza mediante un asistente estructurado en pasos.

Referencia de imagen:
`[Figura GI-02: Inicio del asistente de inscripción]`

### 15.3. Paso 1 - Datos personales

#### 15.3.1. Objetivo

Registrar o recuperar la información básica del postulante.

#### 15.3.2. Procedimiento

1. seleccionar tipo de documento;
2. registrar número de documento;
3. buscar los datos del postulante;
4. completar o verificar:
   - apellidos;
   - nombres;
   - fecha de nacimiento;
   - sexo;
   - estado civil;
   - país de nacimiento;
   - nacionalidad;
   - ubigeo de nacimiento;
   - ubigeo de domicilio;
   - dirección;
   - celular;
   - teléfono;
   - correo electrónico;
   - información complementaria;
5. guardar el paso.

#### 15.3.3. Resultado esperado

La persona debe quedar registrada y asociada a la inscripción en construcción.

Referencia de imagen:
`[Figura GI-03: Paso 1 - Datos personales]`

### 15.4. Paso 2 - Datos generales

#### 15.4.1. Objetivo

Registrar la información académica general del postulante.

#### 15.4.2. Procedimiento

1. registrar año de graduación;
2. seleccionar país del colegio;
3. seleccionar tipo de colegio;
4. elegir el colegio de procedencia;
5. registrar número de postulaciones previas;
6. añadir observaciones si corresponde;
7. guardar el paso.

#### 15.4.3. Resultado esperado

La información general debe quedar almacenada para continuar con la postulación.

Referencia de imagen:
`[Figura GI-04: Paso 2 - Datos generales]`

### 15.5. Paso 3 - Postulación

#### 15.5.1. Objetivo

Seleccionar la modalidad y condiciones de postulación.

#### 15.5.2. Procedimiento

1. seleccionar modalidad de admisión;
2. seleccionar lugar de inscripción;
3. seleccionar turno;
4. elegir la oferta disponible;
5. seleccionar convenio si aplica;
6. guardar el paso.

#### 15.5.3. Resultado esperado

La inscripción debe quedar creada dentro del proceso vigente.

Referencia de imagen:
`[Figura GI-05: Paso 3 - Postulación]`

### 15.6. Paso 4 - Pago

#### 15.6.1. Objetivo

Asociar el depósito bancario del postulante a la inscripción.

El sistema no registra pagos manualmente: el depósito debe existir en un archivo TXT del banco previamente importado (ver sección 16). El primer pago de una inscripción siempre se asocia en este paso, porque aquí se crea la obligación de pago según la regla elegida.

#### 15.6.2. Procedimiento

1. seleccionar la regla de pago;
2. verificar el número de documento del postulante;
3. ingresar el número de operación;
4. ingresar la fecha de pago;
5. buscar el detalle bancario;
6. seleccionar el pago correcto;
7. confirmar el paso.

#### 15.6.3. Resultado esperado

El pago debe quedar asociado a la obligación de la inscripción en estado `VERIFICADO`, y el asistente habilita el paso 5.

Si el depósito no aparece, se debe verificar que el TXT del día esté importado y que el DNI del depósito coincida con el del postulante. La inscripción queda guardada y puede retomarse luego con `Continuar` desde el listado.

Referencia de imagen:
`[Figura GI-06: Paso 4 - Pago]`

### 15.7. Paso 5 - Expedientes

#### 15.7.1. Objetivo

Registrar el estado de cada requisito documental del postulante.

#### 15.7.2. Procedimiento

1. revisar la lista de expedientes requeridos;
2. verificar cuáles son obligatorios;
3. registrar el archivo o evidencia correspondiente;
4. seleccionar el estado de revisión;
5. agregar observación si corresponde;
6. guardar el paso final.

#### 15.7.3. Resultado esperado

Si todos los expedientes obligatorios están aprobados y el pago está validado, la inscripción debe quedar activa.

Referencia de imagen:
`[Figura GI-07: Paso 5 - Expedientes]`

### 15.8. Detalle de inscripción

#### 15.8.1. Objetivo

Permitir la revisión consolidada de toda la información del postulante.

#### 15.8.2. Procedimiento

1. ingresar al listado de inscripciones;
2. seleccionar `Detalle`;
3. revisar:
   - datos personales;
   - datos generales;
   - postulación;
   - aula asignada;
   - pagos;
   - expedientes;
   - estado final.

#### 15.8.3. Resultado esperado

El usuario debe poder verificar integralmente el expediente de la inscripción.

Referencia de imagen:
`[Figura GI-08: Detalle de inscripción]`

## 16. Módulo Pagos

### 16.1. Objetivo

Importar al sistema el archivo TXT que entrega el banco con los depósitos recibidos, para que luego puedan asociarse a las inscripciones.

Esta tarea debe realizarse antes de registrar las inscripciones del día: si el depósito del postulante no está importado, no aparecerá en el paso de pago.

### 16.2. Procedimiento de importación

1. ingresar a `Inscripciones > Pagos`;
2. seleccionar el archivo TXT del banco (máximo 5 MB);
3. revisar la previsualización que muestra el sistema:
   - datos de la cabecera;
   - cantidad de depósitos y monto total;
   - si cuadran los registros y los montos con el cierre del archivo;
   - observaciones por línea, si las hay;
4. si todo es correcto, confirmar la importación; si no, cancelar la previsualización o usar `Quitar archivo`.

El sistema valida que:

- el archivo tenga cabecera (línea `01`), detalle y cierre (línea `03`);
- la cantidad de registros y el monto total coincidan con el cierre;
- el archivo no haya sido importado anteriormente;
- el archivo no tenga observaciones.

### 16.3. Consulta de importaciones

1. buscar las importaciones en el listado o filtrarlas por estado;
2. seleccionar `Ver detalle` para revisar los depósitos de una importación;
3. verificar el estado de cada depósito:
   - `PENDIENTE`: aún no asociado a ninguna inscripción;
   - `ASOCIADO`: ya vinculado a una obligación de pago;
   - `OBSERVADO`: presenta inconsistencias.

### 16.4. Validar pagos pendientes

El botón `Validar pagos pendientes` intenta asociar automáticamente los depósitos `PENDIENTE` de una importación. Para cada depósito busca, por número de documento, una obligación de pago con saldo y aplica las mismas validaciones de monto que la asociación manual.

Esta opción sirve para **cuotas siguientes**: solo encuentra inscripciones cuya obligación de pago ya existe, es decir, que ya pasaron por el paso 4 del asistente. Al terminar, el sistema informa cuántos depósitos se asociaron y cuántos se omitieron.

### 16.5. Resultado esperado

Los depósitos del banco deben quedar importados y disponibles para asociarse en el paso 4 del asistente o desde `Validar pagos`.

Referencias de imagen:
`[Figura GI-09: Importación del TXT del banco]`
`[Figura GI-12: Detalle de una importación]`

## 17. Módulo Validar pagos

### 17.1. Objetivo

Asociar manualmente un depósito importado a una obligación de pago existente. Se usa principalmente para registrar cuotas siguientes, o cuando la validación automática omitió un depósito.

### 17.2. Procedimiento

1. ingresar a `Inscripciones > Validar pagos`;
2. buscar la obligación por datos del postulante y seleccionarla;
3. revisar el resumen de la obligación: monto total, pagado y saldo;
4. buscar el depósito por número de documento y, si se conoce, número de operación y fecha;
5. seleccionar el depósito correcto;
6. confirmar el pago.

### 17.3. Resultado esperado

El pago debe quedar en estado `VERIFICADO`, el depósito pasa a `ASOCIADO` y la obligación recalcula su saldo, pasando a `PARCIAL` o `PAGADO` según corresponda.

Referencia de imagen:
`[Figura GI-10: Validación de pagos]`

## 18. Módulo Reportes

### 18.1. Objetivo

Permitir la consulta y exportación del listado de inscripciones.

### 18.2. Procedimiento

1. ingresar a `Reportes > Inscripciones`;
2. aplicar filtros si corresponde;
3. revisar la información en pantalla;
4. ejecutar la exportación;
5. verificar el archivo generado.

### 18.3. Resultado esperado

El sistema debe producir un reporte ordenado para control administrativo.

Referencia de imagen:
`[Figura GI-11: Reporte de inscripciones]`

## 19. Caso de uso integral del sistema

Para una operación ordenada, se recomienda seguir este flujo:

1. el `SuperAdministrador` crea o verifica usuarios y roles;
2. el `SuperAdministrador` verifica que los catálogos base estén completos (sección 12);
3. el `SuperAdministrador` crea el proceso con sus fechas de apertura y cierre;
4. el `SuperAdministrador` configura ofertas, aulas, convenios, reglas de pago y requisitos del proceso;
5. el `Gestor de Inscripciones CEPRE` importa el TXT del banco con los depósitos del día;
6. el `Gestor de Inscripciones CEPRE` registra la inscripción del postulante y asocia su depósito en el paso 4;
7. el `Gestor de Inscripciones CEPRE` revisa los expedientes;
8. la inscripción queda activa;
9. las cuotas siguientes, si las hay, se concilian desde `Pagos` o `Validar pagos`;
10. se generan reportes para seguimiento.

## 20. Buenas prácticas operativas

1. validar siempre el documento del postulante antes de guardar;
2. revisar cuidadosamente correo y celular;
3. confirmar que la oferta corresponda al proceso vigente;
4. validar el pago antes de aprobar expedientes finales;
5. usar el rol operativo para trabajo diario y reservar el rol administrador para configuración;
6. registrar observaciones cuando exista alguna inconsistencia;
7. generar reportes periódicamente para control interno.

## 21. Errores operativos frecuentes

1. seleccionar una modalidad incorrecta;
2. elegir una oferta que no corresponde al proceso deseado;
3. asociar un pago a un postulante equivocado;
4. aprobar expedientes sin revisar la evidencia;
5. trabajar fuera del proceso vigente;
6. asignar permisos excesivos a usuarios operativos;
7. registrar inscripciones antes de importar el TXT del banco del día;
8. asignar ofertas a un turno que no tiene aulas con capacidad en el proceso;
9. crear un proceso sin requisitos obligatorios, lo que impide que las inscripciones queden activas;
10. registrar un convenio de descuento sin crear la regla de pago asociada a ese convenio.

## 22. Datos demo cargados para el manual

Para facilitar la elaboración de capturas y demostraciones, se han cargado datos de prueba consistentes en:

- `50` inscripciones completas;
- modalidad `EXONERACIÓN - CEPREUNU`;
- lugar `PUCALLPA`;
- turno `MAÑANA`;
- personas con ubigeo de `UCAYALI / CORONEL PORTILLO`;
- pagos validados;
- expedientes aprobados;
- aulas asignadas;
- usuarios operativos de referencia.

## 23. Procedimiento para recargar datos de demostración

Si se requiere volver a cargar la información demo, puede utilizarse el siguiente comando:

```bash
php artisan db:seed --class=DevelopmentDataSeeder --no-interaction
```

## 24. Conclusiones

El sistema CEPRE UNU permite administrar de manera integral el proceso de inscripción, articulando los componentes de seguridad, configuración académica, registro de postulantes, control de pagos, validación documental y emisión de reportes.

La correcta utilización del sistema depende de dos factores principales:

1. una configuración adecuada por parte del `SuperAdministrador`;
2. una operación ordenada y precisa por parte del `Gestor de Inscripciones CEPRE`.

La presente guía puede ser utilizada como base para capacitación interna, control operativo y documentación institucional definitiva.

## 25. Anexos sugeridos

Para una versión formal de entrega, se recomienda añadir los siguientes anexos:

1. capturas del inicio de sesión;
2. capturas del dashboard principal;
3. capturas de cada módulo;
4. capturas del asistente de inscripción completo;
5. ejemplo de validación de pago;
6. ejemplo de reporte exportado;
7. glosario breve de términos del sistema.
