# UNIVERSIDAD NACIONAL DE UCAYALI
## CENTRO PREUNIVERSITARIO - CEPRE UNU
### CONFIGURACIÓN DE LA SINCRONIZACIÓN CON EL DRIVE

**Versión:** 1.0  
**Dirigido a:** responsable del sistema y de la cuenta de Google de CEPRE UNU virtual  
**Fecha:** Noviembre 2026

---

## 1. Para qué sirve

El sistema funciona solo en la red local del CEPRE (la PC Windows). Los postulantes no pueden entrar desde su casa, así que consultan su aula en el **buscador de estudiantes**, que lee hojas de Google del Drive de **CEPRE UNU virtual**.

Antes, esas hojas se llenaban exportando y pegando a mano. Ahora el sistema las mantiene al día solo:

- **cada 10 minutos**, mientras el sistema está encendido;
- y **al momento**, con el botón **Drive** del listado de inscripciones.

## 2. Cómo funciona

```
PC Windows (sistema CEPRE UNU, red local)
   │  cada 10 minutos o con el botón "Drive"
   │  envía los datos del proceso + una clave secreta
   ▼
Apps Script "Sincronización CEPRE" (en la cuenta de Google de CEPRE UNU virtual)
   │  comprueba la clave y escribe las hojas
   ▼
Carpeta principal del Drive (por ejemplo DATA)
   ├── 2026-II/
   │   ├── DATA CEPRE-UNU 2026-II
   │   └── PAGOS 2026-II
   └── 2027-I/
       ├── DATA CEPRE-UNU 2027-I      ← pestaña DATA
       └── PAGOS 2027-I               ← pestaña PAGOS
   ▲
   │  lee las hojas
Buscador de estudiantes (el script que ya usan)
```

- El **Apps Script** es un pequeño programa que se pega una sola vez en la cuenta de Google. El sistema no puede escribir directamente en el Drive: el script recibe los datos y los escribe.
- La **clave secreta** (`TOKEN`) la conocen solo el script y el sistema. Sin ella nadie puede escribir en las hojas.
- **Cada proceso tiene su subcarpeta** con el nombre del proceso (`2026-I`, `2026-II`, `2027-III`…). Se crea sola la primera vez que se sincroniza ese proceso.

### 2.1. Qué contiene cada hoja

| Hoja | Pestaña | Contenido |
|---|---|---|
| `DATA CEPRE-UNU {proceso}` | `DATA` | Inscripciones **no anuladas** del proceso, en orden de registro. Columnas: `NRO_REG` (código de inscripción), `FECHA_Y_HORA`, las columnas codificadas del formato CEPRE y, al final, `AULA`, `TURNO` y `ESTADO` |
| `PAGOS {proceso}` | `PAGOS` | Pagos usados en inscripciones del proceso y pagos libres hechos después de que terminó el proceso anterior. Columnas: `NRO.`, `FECHA`, `DNI`, `REFERENCIA`, `IMPORTE`, `N° OPERACIÓN`, `UBICACIÓN`, `ESTADO`, `N° REG.`, `SEDE`, `OBSERVACIÓN` |

`NRO_REG` de la hoja DATA y `N° REG.` de la hoja PAGOS son el mismo número (el código de inscripción), así que se pueden cruzar.

### 2.2. Qué proceso se sincroniza

- **Automático (cada 10 minutos):** el proceso **vigente**, es decir, el que está abierto según sus fechas. Si no hay ninguno abierto, el más reciente que tenga inscripciones.
- **Botón Drive:** el proceso elegido en el filtro del listado de inscripciones.
- **Consola:** `php artisan cepre:sincronizar-drive --proceso=ID` sincroniza el proceso con ese ID.

---

## 3. Requisitos

- La PC Windows con el sistema **actualizado** a la versión que incluye la sincronización (`git pull origin main` y `actualizar-sistema.bat`).
- **Internet** en la PC Windows.
- Acceso a la **cuenta de Google de CEPRE UNU virtual**, dueña de la carpeta donde van las hojas.

---

## 4. Paso 1: crear el Apps Script (en Google, una sola vez)

Se puede hacer desde cualquier computadora.

1. Entrar a **https://script.google.com** con la cuenta de **CEPRE UNU virtual**.
2. Seleccionar **Nuevo proyecto**. Arriba a la izquierda, cambiar el nombre *Proyecto sin título* por **Sincronización CEPRE**.
3. Borrar el contenido que trae el editor (`function myFunction() {...}`).
4. Abrir el archivo **`resources/drive/SincronizarCepre.gs`** de la carpeta del sistema (con el Bloc de notas), copiar **todo** su contenido y pegarlo en el editor.
5. Completar los valores del inicio del script:

   ```js
   const TOKEN = 'CAMBIE-ESTA-CLAVE';
   const CARPETA_ID = '';
   const CARPETA_POR_PROCESO = true;
   ```

   - **`TOKEN`**: inventar una clave larga, de 30 a 40 letras y números al azar, sin espacios. Ejemplo de forma (no use este): `Cepre2026xK9mPq4Lw7Zt2Rb8Ns5Vh3Jd6`. **Anotarla**, porque se usa en el paso 2.
   - **`CARPETA_ID`**: el identificador de la carpeta principal (por ejemplo, la carpeta `DATA`). Para obtenerlo, abrir la carpeta en el Drive y mirar la dirección del navegador:

     ```
     https://drive.google.com/drive/folders/1AbCdEfGhIjKlMnOpQrStUvWxYz
                                            └──────── CARPETA_ID ────────┘
     ```

     Si se deja vacío, las subcarpetas se crean en *Mi unidad*.
   - **`CARPETA_POR_PROCESO`**: dejarlo en `true` para tener una subcarpeta por proceso. Con `false`, las hojas quedan sueltas en la carpeta principal.

6. Seleccionar **Guardar** (ícono de disquete o `Ctrl + S`).
7. Seleccionar **Implementar** → **Nueva implementación**.
8. En *Seleccionar tipo* (ícono de engranaje), elegir **Aplicación web** y configurar:
   - **Descripción:** `Sincronización CEPRE`;
   - **Ejecutar como:** **Yo** (la cuenta de CEPRE UNU virtual);
   - **Quién tiene acceso:** **Cualquier usuario**. Es necesario para que el sistema pueda enviar los datos; sin la clave, el script rechaza cualquier envío.
9. Seleccionar **Implementar**. Google pedirá autorización:
   1. **Autorizar acceso** y elegir la cuenta de CEPRE UNU virtual;
   2. si aparece *"Google no ha verificado esta aplicación"*, seleccionar **Configuración avanzada** → **Ir a Sincronización CEPRE (no seguro)**. Es normal: el script es propio y no está publicado para otros;
   3. **Permitir** el acceso al Drive y a las hojas de cálculo.
10. Copiar la **URL de la aplicación web**. Tiene esta forma y termina en `/exec`:

    ```
    https://script.google.com/macros/s/AKfycbx....................../exec
    ```

11. **Comprobar:** pegar esa URL en el navegador. Debe mostrar:

    ```json
    {"ok":true,"mensaje":"Receptor CEPRE UNU activo"}
    ```

    Si en cambio pide iniciar sesión, en el punto 8 no se eligió *Cualquier usuario*.

---

## 5. Paso 2: configurar la PC Windows (una sola vez)

1. Abrir la carpeta del sistema en la PC Windows.
2. Abrir el archivo **`.env`** con el Bloc de notas. Si no se ve, activar en el Explorador *Vista → Mostrar → Elementos ocultos*.
3. Agregar al final estas dos líneas, con los valores del paso 1:

   ```env
   DRIVE_SYNC_URL=https://script.google.com/macros/s/AKfycbx....../exec
   DRIVE_SYNC_TOKEN=la-misma-clave-que-se-puso-en-TOKEN
   ```

   - Sin espacios antes ni después del `=`.
   - La clave debe ser **exactamente igual** a la del script, con mayúsculas y minúsculas.

4. Guardar el archivo.
5. Abrir una consola (`cmd`) en la carpeta del sistema y ejecutar:

   ```bat
   php artisan config:clear
   php artisan cepre:sincronizar-drive
   ```

   Si todo está bien, responde algo así:

   ```
   DATA CEPRE-UNU 2027-I: 46 filas.
   PAGOS 2027-I: 120 filas.
   ```

6. Revisar en el Drive que se creó la subcarpeta del proceso con las dos hojas y sus datos.
7. Si `iniciar-red-local` estaba abierto, cerrarlo con `Ctrl + C` y volver a abrirlo para que tome la configuración.

---

## 6. Paso 3: conectar el buscador de estudiantes

El buscador de estudiantes debe leer la hoja **`DATA CEPRE-UNU {proceso}`** de la subcarpeta del proceso:

- el **aula** está en la columna `AULA`, al final de la hoja (también vienen `TURNO` y `ESTADO`);
- el **DNI** está en `NUMERO_DOCUMENTO`;
- si el buscador lee las columnas por posición (A, B, C…), revisar que apunte a las correctas.

Si la hoja del proceso actual ya existía suelta en la carpeta principal con el mismo nombre (`DATA CEPRE-UNU 2027-I`), el script la **mueve** a la subcarpeta del proceso **sin cambiar su enlace**. El buscador que ya la usaba sigue funcionando.

En cada **proceso nuevo** se crea una hoja nueva (`DATA CEPRE-UNU 2027-II`). Hay que actualizar el buscador para que lea la hoja del proceso nuevo.

---

## 7. Uso diario

- Encender el sistema como siempre con **`iniciar-red-local.bat`**. Además de la ventana del servidor se abre una **ventana minimizada** llamada `schedule:work`:
  - mientras esté abierta, la sincronización se hace **sola cada 10 minutos**;
  - **no cerrarla**. Se cierra sola al detener el servidor con `Ctrl + C`. Si se cierra la ventana principal con la X, cerrar también esa.
- Para sincronizar **al momento** (por ejemplo, después de asignar aulas): **Inscripciones** → botón **Drive**. Al pasar el mouse sobre el botón se ve la fecha de la última sincronización.
  - El botón solo aparece si la PC tiene la configuración del paso 2 y el usuario tiene permiso para exportar inscripciones.
- Si la PC se queda **sin internet**, el botón muestra un aviso y el sistema sigue funcionando con normalidad en la red local. El Drive se pone al día en la siguiente sincronización con conexión.

### 7.1. Reglas para el Drive

- **No escribir a mano en las pestañas `DATA` y `PAGOS`**: se borran y se reescriben completas en cada sincronización. Para notas, fórmulas o resúmenes, usar **otra pestaña** del mismo archivo.
- **No cambiar el nombre** de las hojas ni de las subcarpetas. Si se cambian, el script crea otras nuevas.
- Las columnas van en formato texto para no perder los ceros a la izquierda del DNI y del ubigeo. `IMPORTE` va como número.

---

## 8. Mantenimiento

### 8.1. Cambiar la clave

1. En el script, cambiar `TOKEN` y guardar.
2. Publicar la nueva versión (8.2).
3. En la PC Windows, cambiar `DRIVE_SYNC_TOKEN` en el `.env` y ejecutar `php artisan config:clear`.

### 8.2. Actualizar el código del script

Si llega una versión nueva de `resources/drive/SincronizarCepre.gs`:

1. pegar el código nuevo en el editor del script, conservando los valores de `TOKEN`, `CARPETA_ID` y `CARPETA_POR_PROCESO`;
2. **Implementar** → **Gestionar implementaciones** → lápiz (editar) → *Versión*: **Nueva versión** → **Implementar**.

Así la URL `/exec` **no cambia** y no hay que tocar el `.env`. Si en lugar de eso se usa *Nueva implementación*, se genera **otra URL** y hay que actualizar `DRIVE_SYNC_URL`.

### 8.3. Desactivar la sincronización

Borrar o dejar vacías `DRIVE_SYNC_URL` y `DRIVE_SYNC_TOKEN` en el `.env` y ejecutar `php artisan config:clear`. El botón **Drive** desaparece y la tarea automática deja de ejecutarse.

---

## 9. Problemas frecuentes

| Mensaje del sistema | Causa | Solución |
|---|---|---|
| *La sincronización con el Drive no está configurada…* | Faltan `DRIVE_SYNC_URL` o `DRIVE_SYNC_TOKEN` en el `.env` | Completar el paso 2 y ejecutar `php artisan config:clear` |
| *No se pudo conectar con el Drive. Revise la conexión a internet de esta PC.* | Sin internet o la URL está mal escrita | Revisar la conexión y que la URL termine en `/exec` |
| *El Drive no respondió como se esperaba. Revise que el script esté publicado…* | El script no tiene acceso *Cualquier usuario*, o la URL no es la de la aplicación web | Revisar el punto 8 del paso 1; abrir la URL en el navegador debe mostrar `{"ok":true,...}` |
| *El Drive rechazó la sincronización: clave incorrecta…* | `TOKEN` y `DRIVE_SYNC_TOKEN` no coinciden | Copiar la misma clave en los dos lados y ejecutar `php artisan config:clear` |
| *El Drive rechazó la sincronización: hay otra sincronización en curso* | Coincidió el botón con la tarea automática | Esperar un minuto y volver a intentar |
| *El Drive rechazó la sincronización: …* con otro detalle | Error al escribir (por ejemplo, `CARPETA_ID` incorrecto o sin permiso sobre la carpeta) | Revisar `CARPETA_ID` y que la carpeta sea de la cuenta que ejecuta el script |
| El botón **Drive** no aparece | Falta la configuración o el usuario no tiene permiso de exportar | Revisar el paso 2 y el rol del usuario |
| Las hojas no se actualizan solas | La ventana `schedule:work` está cerrada | Reiniciar con `iniciar-red-local.bat` |
| Aparecen hojas duplicadas | Se cambió el nombre de una hoja o subcarpeta | Devolverle el nombre original o borrar la duplicada |

---

## 10. Seguridad

- Guardar el `TOKEN` como una contraseña: quien lo tenga puede escribir en esas hojas.
- El `.env` de la PC Windows contiene la clave: no compartirlo ni subirlo a internet.
- La aplicación web solo escribe hojas y no devuelve datos de los postulantes. Al abrir la URL en el navegador solo muestra `{"ok":true,...}`.
- El script se ejecuta con la cuenta de CEPRE UNU virtual. Si se cambia la cuenta dueña de la carpeta, hay que crear el script de nuevo con la cuenta nueva.
