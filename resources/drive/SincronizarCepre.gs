/**
 * CEPRE UNU · Receptor de la sincronización con el Drive.
 *
 * El sistema (php artisan cepre:sincronizar-drive o el botón "Drive" del listado de
 * inscripciones) envía las hojas del proceso y este script las escribe en una
 * subcarpeta con el nombre del proceso, dentro de la carpeta principal (CARPETA_ID):
 *
 *   DATA/
 *   └── 2027-II/
 *       ├── DATA CEPRE-UNU 2027-II   pestaña DATA (inscritos + AULA, TURNO y ESTADO al final)
 *       └── PAGOS 2027-II            pestaña PAGOS
 *
 * La subcarpeta se crea sola la primera vez. Si la hoja ya existe se reescribe su
 * pestaña (mismo archivo y mismo enlace, así el buscador de estudiantes sigue
 * funcionando). Si la hoja estaba suelta en la carpeta principal, se mueve a la
 * subcarpeta del proceso sin cambiar su enlace. Si no existe, se crea.
 *
 * Instalación paso a paso: CONFIGURACION_DRIVE_CEPRE.md (en la carpeta del sistema).
 *   1. En https://script.google.com cree un proyecto con la cuenta de CEPRE UNU virtual
 *      y pegue este archivo completo.
 *   2. Complete TOKEN (la misma clave que DRIVE_SYNC_TOKEN del .env) y CARPETA_ID
 *      (el identificador de la carpeta DATA, que aparece en su URL después de /folders/).
 *   3. Implementar > Nueva implementación > Aplicación web.
 *      Ejecutar como: Yo. Quién tiene acceso: Cualquier usuario.
 *   4. Copie la URL de la aplicación web (termina en /exec) en DRIVE_SYNC_URL del .env.
 * Cada vez que cambie este código, use Implementar > Gestionar implementaciones > Editar
 * > Nueva versión, para que la URL siga siendo la misma.
 */

/** Clave compartida con el sistema (DRIVE_SYNC_TOKEN). Use una larga y difícil de adivinar. */
const TOKEN = 'CAMBIE-ESTA-CLAVE';

/** Carpeta principal del Drive (por ejemplo DATA). Vacío = Mi unidad. */
const CARPETA_ID = '';

/** true = una subcarpeta por proceso (DATA/2027-II/...); false = todo suelto en la carpeta principal. */
const CARPETA_POR_PROCESO = true;

function doPost(e) {
  let datos;

  try {
    datos = JSON.parse(e.postData.contents);
  } catch (error) {
    return responder({ ok: false, error: 'el contenido enviado no es JSON' });
  }

  if (!datos || datos.token !== TOKEN) {
    return responder({ ok: false, error: 'clave incorrecta (revise DRIVE_SYNC_TOKEN)' });
  }

  const bloqueo = LockService.getScriptLock();

  if (!bloqueo.tryLock(60000)) {
    return responder({ ok: false, error: 'hay otra sincronización en curso' });
  }

  try {
    const raiz = CARPETA_ID ? DriveApp.getFolderById(CARPETA_ID) : DriveApp.getRootFolder();
    const carpeta = CARPETA_POR_PROCESO && datos.proceso ? subcarpeta(raiz, String(datos.proceso)) : raiz;
    const hojas = (datos.hojas || []).map(function (hoja) {
      return escribirHoja(hoja, carpeta, raiz);
    });

    return responder({ ok: true, proceso: datos.proceso, hojas: hojas });
  } catch (error) {
    return responder({ ok: false, error: String(error && error.message ? error.message : error) });
  } finally {
    bloqueo.releaseLock();
  }
}

/** Para probar la URL en el navegador: debe mostrar {"ok":true,...}. */
function doGet() {
  return responder({ ok: true, mensaje: 'Receptor CEPRE UNU activo' });
}

function escribirHoja(hoja, carpeta, raiz) {
  const libro = buscarOCrearLibro(hoja.archivo, carpeta, raiz);
  const pestana = libro.getSheetByName(hoja.pestana) || libro.insertSheet(hoja.pestana);
  const columnas = hoja.encabezados.length;
  const valores = [hoja.encabezados].concat(hoja.filas.map(function (fila) {
    return completar(fila, columnas);
  }));

  pestana.clearContents();

  // Formatos antes de escribir: texto para no perder ceros a la izquierda (DNI, ubigeo).
  (hoja.formatos || []).forEach(function (formato, indice) {
    pestana.getRange(1, indice + 1, valores.length, 1).setNumberFormat(formato);
  });

  pestana.getRange(1, 1, valores.length, columnas).setValues(valores);
  pestana.getRange(1, 1, 1, columnas).setFontWeight('bold');
  pestana.setFrozenRows(1);

  // Quita filas sobrantes de una sincronización anterior más larga (Google exige dejar
  // al menos una fila sin congelar, por eso el mínimo es 2).
  const ultimaFila = Math.max(valores.length, 2);

  if (pestana.getMaxRows() > ultimaFila) {
    pestana.deleteRows(ultimaFila + 1, pestana.getMaxRows() - ultimaFila);
  }

  return { archivo: hoja.archivo, carpeta: carpeta.getName(), filas: hoja.filas.length, url: libro.getUrl() };
}

/** Subcarpeta del proceso (por ejemplo "2027-II") dentro de la principal; la crea si falta. */
function subcarpeta(raiz, nombre) {
  const existentes = raiz.getFoldersByName(nombre);

  return existentes.hasNext() ? existentes.next() : raiz.createFolder(nombre);
}

/**
 * Busca la hoja en la carpeta del proceso; si estaba suelta en la carpeta principal la
 * mueve (conserva su enlace); si no existe, la crea.
 */
function buscarOCrearLibro(nombre, carpeta, raiz) {
  const enCarpeta = hojaDeCalculo(carpeta, nombre);

  if (enCarpeta) {
    return SpreadsheetApp.openById(enCarpeta.getId());
  }

  const suelta = carpeta.getId() !== raiz.getId() ? hojaDeCalculo(raiz, nombre) : null;

  if (suelta) {
    suelta.moveTo(carpeta);

    return SpreadsheetApp.openById(suelta.getId());
  }

  const libro = SpreadsheetApp.create(nombre);
  DriveApp.getFileById(libro.getId()).moveTo(carpeta);

  return libro;
}

function hojaDeCalculo(carpeta, nombre) {
  const archivos = carpeta.getFilesByName(nombre);

  while (archivos.hasNext()) {
    const archivo = archivos.next();

    if (archivo.getMimeType() === MimeType.GOOGLE_SHEETS) {
      return archivo;
    }
  }

  return null;
}

function completar(fila, columnas) {
  const copia = fila.slice(0, columnas);

  while (copia.length < columnas) {
    copia.push('');
  }

  return copia;
}

function responder(objeto) {
  return ContentService.createTextOutput(JSON.stringify(objeto)).setMimeType(ContentService.MimeType.JSON);
}
