<?php

namespace App\Support;

use App\Models\FotoPostulante;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Guarda la foto de un postulante recortada al formato carnet (3:4) y
 * escalada a 300 × 400 px, como JPEG en el disco privado.
 */
class ProcesadorFotoPostulante
{
    public const ANCHO = 300;

    public const ALTO = 400;

    public function guardar(UploadedFile $archivo, string $numeroDocumento): FotoPostulante
    {
        $numeroDocumento = trim($numeroDocumento);
        $original = @imagecreatefromstring((string) file_get_contents($archivo->getRealPath()));

        if ($original === false) {
            throw new RuntimeException("El archivo {$archivo->getClientOriginalName()} no es una imagen válida.");
        }

        $ancho = imagesx($original);
        $alto = imagesy($original);
        $proporcion = self::ANCHO / self::ALTO;

        // Recorte centrado a 3:4 antes de escalar.
        if ($ancho / $alto > $proporcion) {
            $anchoRecorte = (int) round($alto * $proporcion);
            $altoRecorte = $alto;
        } else {
            $anchoRecorte = $ancho;
            $altoRecorte = (int) round($ancho / $proporcion);
        }

        $destino = imagecreatetruecolor(self::ANCHO, self::ALTO);
        imagefill($destino, 0, 0, imagecolorallocate($destino, 255, 255, 255));
        imagecopyresampled(
            $destino,
            $original,
            0,
            0,
            (int) (($ancho - $anchoRecorte) / 2),
            (int) (($alto - $altoRecorte) / 2),
            self::ANCHO,
            self::ALTO,
            $anchoRecorte,
            $altoRecorte,
        );

        ob_start();
        imagejpeg($destino, null, 85);
        $contenido = (string) ob_get_clean();
        imagedestroy($original);
        imagedestroy($destino);

        $ruta = FotoPostulante::CARPETA."/{$numeroDocumento}.jpg";
        Storage::disk(FotoPostulante::DISCO)->put($ruta, $contenido);

        $foto = FotoPostulante::withTrashed()->firstOrNew(['numero_documento_fot' => $numeroDocumento]);

        if ($foto->trashed()) {
            $foto->restore();
        }

        $foto->archivo_fot = $ruta;
        $foto->actualizado_en = now();
        $foto->save();

        return $foto;
    }

    public function eliminar(FotoPostulante $foto): void
    {
        Storage::disk(FotoPostulante::DISCO)->delete($foto->archivo_fot);
        $foto->forceDelete();
    }
}
