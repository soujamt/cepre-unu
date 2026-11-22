/**
 * CEPRE UNU · Receptor de la sincronización con el Drive.
 *
 * El sistema (php artisan cepre:sincronizar-drive o el botón "Drive" del listado de
 * inscripciones) envía las hojas del proceso y este script las escribe en la carpeta
 * indicada:
 *   - "DATA CEPRE-UNU 2027-II", pestaña DATA (inscritos + AULA, TURNO y ESTADO al final);
 *   - "PAGOS 2027-II", pestaña PAGOS.
 * Si la hoja ya existe se reescribe su pestaña (mismo archivo y mismo enlace, así el
 * buscador de estudiantes sigue funcionando). Si no existe, se crea en la carpeta.
 *
 * Instalación (ver el manual de instalación, sección "Sincronización con el Drive"):
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

/** Carpeta del Drive donde están (o se crearán) las hojas. Vacío = Mi unidad. */
const CARPETA_ID = '';

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
    const hojas = (datos.hojas || []).map(escribirHoja);

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

function escribirHoja(hoja) {
  const libro = buscarOCrearLibro(hoja.archivo);
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

  return { archivo: hoja.archivo, filas: hoja.filas.length, url: libro.getUrl() };
}

function buscarOCrearLibro(nombre) {
  const carpeta = CARPETA_ID ? DriveApp.getFolderById(CARPETA_ID) : DriveApp.getRootFolder();
  const archivos = carpeta.getFilesByName(nombre);

  while (archivos.hasNext()) {
    const archivo = archivos.next();

    if (archivo.getMimeType() === MimeType.GOOGLE_SHEETS) {
      return SpreadsheetApp.openById(archivo.getId());
    }
  }

  const libro = SpreadsheetApp.create(nombre);
  DriveApp.getFileById(libro.getId()).moveTo(carpeta);

  return libro;
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
