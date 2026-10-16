# UNIVERSIDAD NACIONAL DE UCAYALI
## CENTRO PREUNIVERSITARIO - CEPRE UNU
### MANUAL DE USUARIO DEL SISTEMA DE INSCRIPCIONES

**Versión:** 1.0  
**Documento:** Manual de usuario funcional  
**Dirigido a:** SuperAdministrador y Gestor de Inscripciones CEPRE  
**Fecha:** Junio 2026

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
- configurar semestres y procesos;
- gestionar ofertas, aulas por proceso, convenios y reglas de pago;
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
3. si corresponde, el `SuperAdministrador` configura el proceso académico;
4. el `Gestor de Inscripciones CEPRE` registra la inscripción del postulante;
5. se valida el pago correspondiente;
6. se revisan los expedientes;
7. la inscripción queda activa si cumple las condiciones requeridas;
8. finalmente, se generan reportes para control y seguimiento.

Referencias de imagen:
`[Figura SA-01: Pantalla de inicio de sesión]`
`[Figura SA-02: Panel principal del sistema]`

## 8. Consideraciones previas al uso del sistema

Antes de operar el sistema, se debe verificar:

1. que exista un semestre habilitado;
2. que exista un proceso vigente;
3. que las ofertas estén configuradas y habilitadas;
4. que existan aulas asignadas al proceso;
5. que las reglas de pago se encuentren vigentes;
6. que los requisitos de expediente estén definidos;
7. que los usuarios tengan el rol adecuado.

Estas validaciones son importantes porque, de no cumplirse, el sistema podría impedir el avance normal del proceso de inscripción.

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

Este módulo contiene catálogos de apoyo para la operación del sistema.

### 12.1. Submódulo Aulas

#### 12.1.1. Objetivo

Registrar y administrar las aulas que luego serán utilizadas en la distribución de postulantes por proceso y turno.

#### 12.1.2. Procedimiento

1. ingresar a `Mantenedores > Aulas`;
2. revisar el listado de aulas;
3. crear una nueva aula cuando corresponda;
4. completar:
   - nombre;
   - lugar;
   - piso;
   - orden;
   - estado;
5. guardar el registro.

#### 12.1.3. Resultado esperado

El aula debe quedar disponible para asociarse posteriormente a un proceso.

Referencia de imagen:
`[Figura SA-10: Módulo de aulas]`

### 12.2. Submódulo Colegios

#### 12.2.1. Objetivo

Gestionar la información de colegios utilizada durante el registro de datos generales de los postulantes.

#### 12.2.2. Procedimiento

1. ingresar a `Mantenedores > Colegios`;
2. buscar por nombre, código modular o ubicación;
3. registrar o editar información del colegio si es necesario;
4. validar:
   - nombre;
   - tipo de colegio;
   - ubigeo;
   - estado.

#### 12.2.3. Resultado esperado

El colegio debe poder ser encontrado y utilizado en el paso de datos generales de la inscripción.

Referencia de imagen:
`[Figura SA-11: Módulo de colegios]`

### 12.3. Submódulo Tipos de Expediente

#### 12.3.1. Objetivo

Definir los tipos documentales que podrán ser requeridos a los postulantes.

#### 12.3.2. Procedimiento

1. ingresar a `Mantenedores > Tipos de Expediente`;
2. crear o editar un tipo de expediente;
3. registrar:
   - nombre;
   - descripción;
   - obligatoriedad;
   - estado;
4. guardar la información.

#### 12.3.3. Resultado esperado

Los tipos de expediente deben quedar disponibles para ser incorporados a los requisitos del proceso.

Referencia de imagen:
`[Figura SA-12: Tipos de expediente]`

## 13. Módulo Configuración Académica

Este módulo permite preparar el proceso de inscripción antes de su uso operativo.

### 13.1. Submódulo Semestres

#### 13.1.1. Objetivo

Definir el periodo académico base en el cual se ejecutará el proceso de inscripción.

#### 13.1.2. Procedimiento

1. ingresar a `Configuración Académica > Semestres`;
2. crear un nuevo semestre;
3. registrar:
   - código;
   - nombre;
   - fecha de inicio de inscripción;
   - fecha de fin de inscripción;
   - estado;
4. guardar.

#### 13.1.3. Resultado esperado

El semestre debe quedar habilitado para asociarse a procesos académicos.

Referencia de imagen:
`[Figura SA-13: Configuración del semestre]`

### 13.2. Submódulo Procesos

#### 13.2.1. Objetivo

Definir el proceso específico de inscripción que será usado por los postulantes.

#### 13.2.2. Procedimiento

1. ingresar a `Configuración Académica > Procesos`;
2. crear un nuevo proceso;
3. asociarlo al semestre correspondiente;
4. registrar:
   - código;
   - nombre;
   - fecha de inicio;
   - fecha de fin;
   - estado;
5. guardar.

#### 13.2.3. Resultado esperado

El proceso debe quedar disponible, vigente y correctamente asociado al semestre.

Referencia de imagen:
`[Figura SA-14: Configuración del proceso]`

### 13.3. Submódulo Ofertas

#### 13.3.1. Objetivo

Permitir la asociación de modalidad, lugar y carrera para conformar las opciones de postulación.

#### 13.3.2. Procedimiento

1. ingresar a `Configuración Académica > Ofertas`;
2. revisar el listado de ofertas existentes;
3. seleccionar `Asignar oferta`;
4. completar:
   - modalidad;
   - lugar de inscripción;
   - sede;
   - carrera;
   - estado;
5. guardar la oferta.

#### 13.3.3. Resultado esperado

La oferta debe quedar visible y habilitada para su selección en el proceso de inscripción.

Referencias de imagen:
`[Figura SA-15: Listado de ofertas]`
`[Figura SA-16: Asignación de oferta]`

### 13.4. Submódulo Aulas por Proceso

#### 13.4.1. Objetivo

Definir las aulas disponibles para un proceso y turno determinado, así como su capacidad.

#### 13.4.2. Procedimiento

1. ingresar a `Configuración Académica > Aulas`;
2. revisar las asignaciones existentes;
3. seleccionar `Asignar aula`;
4. elegir:
   - proceso;
   - turno;
   - aula;
   - capacidad;
   - estado;
5. guardar la asignación.

#### 13.4.3. Resultado esperado

Las aulas deben quedar listas para la asignación automática o funcional de las inscripciones.

Referencia de imagen:
`[Figura SA-17: Aulas por proceso]`

### 13.5. Submódulo Reglas de Pago

#### 13.5.1. Objetivo

Definir las condiciones de pago aplicables a las inscripciones.

#### 13.5.2. Procedimiento

1. ingresar a `Configuración Académica > Reglas de Pago`;
2. crear una nueva regla o editar una existente;
3. registrar:
   - concepto de pago;
   - proceso;
   - monto total;
   - si permite cuotas;
   - monto mínimo de primera cuota;
   - número máximo de cuotas;
   - vigencia;
   - estado;
4. guardar la regla.

#### 13.5.3. Resultado esperado

La regla de pago debe quedar disponible para el paso de pago del asistente de inscripción.

Referencia de imagen:
`[Figura SA-18: Reglas de pago]`

### 13.6. Submódulo Convenios

#### 13.6.1. Objetivo

Registrar beneficios especiales como descuentos o exoneraciones.

#### 13.6.2. Procedimiento

1. ingresar a `Configuración Académica > Convenios`;
2. crear el convenio;
3. registrar:
   - código;
   - nombre;
   - descripción;
   - tipo de beneficio;
   - monto o porcentaje;
   - estado;
4. guardar.

#### 13.6.3. Resultado esperado

El convenio debe quedar disponible para asociarse a un proceso cuando corresponda.

Referencia de imagen:
`[Figura SA-19: Convenios]`

### 13.7. Submódulo Requisitos

#### 13.7.1. Objetivo

Definir los expedientes que serán exigidos a los postulantes en un proceso determinado.

#### 13.7.2. Procedimiento

1. ingresar a `Configuración Académica > Requisitos`;
2. seleccionar el proceso;
3. agregar los tipos de expediente requeridos;
4. marcar cuáles son obligatorios;
5. guardar la configuración.

#### 13.7.3. Resultado esperado

Los requisitos deben aparecer en el paso de expedientes durante la inscripción.

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

Registrar y validar el pago asociado a la inscripción.

#### 15.6.2. Procedimiento

1. seleccionar la regla de pago;
2. verificar el número de documento del postulante;
3. ingresar el número de operación;
4. ingresar la fecha de pago;
5. buscar el detalle bancario;
6. seleccionar el pago correcto;
7. confirmar el paso.

#### 15.6.3. Resultado esperado

El pago debe quedar asociado a la obligación de la inscripción.

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

Permitir la consulta de obligaciones y pagos relacionados con las inscripciones.

### 16.2. Procedimiento

1. ingresar a `Inscripciones > Pagos`;
2. aplicar filtros por proceso, documento o estado;
3. revisar:
   - monto total;
   - monto pagado;
   - saldo;
   - estado de la obligación;
4. confirmar la situación económica del postulante.

### 16.3. Resultado esperado

El operador debe obtener una visualización clara del estado de pago del postulante.

Referencia de imagen:
`[Figura GI-09: Consulta de pagos]`

## 17. Módulo Validar pagos

### 17.1. Objetivo

Permitir la revisión y asociación de pagos bancarios importados al sistema.

### 17.2. Procedimiento

1. ingresar a `Inscripciones > Validar pagos`;
2. revisar los pagos pendientes;
3. verificar documento, monto, fecha y número de operación;
4. asociar el pago a la obligación correcta;
5. confirmar la validación.

### 17.3. Resultado esperado

El pago debe quedar validado y disponible dentro del flujo de inscripción.

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
2. el `SuperAdministrador` configura semestre y proceso;
3. el `SuperAdministrador` configura ofertas, aulas, reglas de pago y requisitos;
4. el `Gestor de Inscripciones CEPRE` registra la inscripción del postulante;
5. el `Gestor de Inscripciones CEPRE` valida el pago;
6. el `Gestor de Inscripciones CEPRE` revisa los expedientes;
7. la inscripción queda activa;
8. se generan reportes para seguimiento.

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
6. asignar permisos excesivos a usuarios operativos.

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
