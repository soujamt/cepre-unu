<?php

namespace App\Traits\InscripcionCrear;

use App\Models\FotoPostulante;
use App\Support\ProcesadorFotoPostulante;
use Illuminate\Http\UploadedFile;

/**
 * Foto del postulante en la revisión del asistente, como "Actualizar foto" del sistema anterior.
 */
trait GestionaFotoPostulante
{
    public ?UploadedFile $fotoPostulante = null;

    public function fotoPostulanteActual(): ?FotoPostulante
    {
        return FotoPostulante::deDocumento($this->documentoParaFoto());
    }

    /**
     * Dirección de la imagen para mostrarla en la revisión.
     */
    public function fuenteFotoPostulante(FotoPostulante $foto): string
    {
        return $foto->url();
    }

    public function puedeCambiarFoto(): bool
    {
        return true;
    }

    public function updatedFotoPostulante(): void
    {
        $this->subirFotoPostulante(app(ProcesadorFotoPostulante::class));
    }

    public function subirFotoPostulante(ProcesadorFotoPostulante $procesador): void
    {
        $this->autorizarSubidaFoto();

        $this->validate(
            ['fotoPostulante' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048']],
            [
                'fotoPostulante.required' => 'Seleccione una foto.',
                'fotoPostulante.image' => 'El archivo debe ser una imagen.',
                'fotoPostulante.mimes' => 'La foto debe ser JPG o PNG.',
                'fotoPostulante.max' => 'La foto no debe pesar más de 2 MB.',
            ],
        );

        $documento = $this->documentoParaFoto();

        if ($documento === null) {
            $this->addError('fotoPostulante', 'Primero valide el pago con el DNI del postulante.');

            return;
        }

        $procesador->guardar($this->fotoPostulante, $documento);

        $this->reset('fotoPostulante');
        unset($this->fotoPostulanteActual);
        $this->dispatch('toast', message: 'Foto del postulante actualizada.', type: 'success');
    }

    /**
     * Quién puede subir o cambiar la foto desde el asistente.
     */
    abstract protected function autorizarSubidaFoto(): void;

    private function documentoParaFoto(): ?string
    {
        $documento = preg_replace('/\D+/', '', (string) ($this->personaForm['numero_documento'] ?? '')) ?: null;

        return $documento !== null && preg_match('/^\d{8,12}$/', $documento) === 1 ? $documento : null;
    }
}
