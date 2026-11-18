<?php

use App\Models\Parametro;
use App\Traits\AuthorizesPermisos;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Datos fijos del carnet: director, cargo, nota e imagen del sello y la firma.
 */
new
#[Layout('layouts.app')]
#[Title('Configuración del carnet')]
class extends Component
{
    use AuthorizesPermisos;
    use WithFileUploads;

    public const RUTA_FIRMA = 'carnet/firma-sello.png';

    /**
     * @var array{director_nombre:string,director_cargo:string,nota:string}
     */
    public array $carnetForm = [];

    public ?TemporaryUploadedFile $firma = null;

    public function mount(): void
    {
        $this->autorizarPermiso('parametro', 'indice');

        $this->carnetForm = [
            'director_nombre' => (string) Parametro::valor('carnet_director_nombre', ''),
            'director_cargo' => (string) Parametro::valor('carnet_director_cargo', ''),
            'nota' => (string) Parametro::valor('carnet_nota', ''),
        ];
    }

    public function guardar(): void
    {
        $this->autorizarPermiso('parametro', 'editar');

        $this->validate([
            'carnetForm.director_nombre' => ['required', 'string', 'max:120'],
            'carnetForm.director_cargo' => ['required', 'string', 'max:120'],
            'carnetForm.nota' => ['nullable', 'string', 'max:200'],
            'firma' => ['nullable', 'image', 'mimes:png', 'max:1024'],
        ], [
            'carnetForm.director_nombre.required' => 'Ingrese el nombre del director.',
            'carnetForm.director_cargo.required' => 'Ingrese el cargo del director.',
            'firma.mimes' => 'La firma debe ser una imagen PNG.',
            'firma.max' => 'La imagen debe pesar como máximo 1 MB.',
        ]);

        Parametro::guardar('carnet_director_nombre', mb_strtoupper(trim($this->carnetForm['director_nombre']), 'UTF-8'));
        Parametro::guardar('carnet_director_cargo', trim($this->carnetForm['director_cargo']));
        Parametro::guardar('carnet_nota', trim($this->carnetForm['nota']) ?: null);

        if ($this->firma !== null) {
            Storage::disk('local')->put(self::RUTA_FIRMA, (string) file_get_contents($this->firma->getRealPath()));
            Parametro::guardar('carnet_firma_sello', self::RUTA_FIRMA);
            $this->firma = null;
        }

        unset($this->firmaActual);
        $this->dispatch('toast', message: 'Configuración del carnet guardada.', type: 'success');
    }

    /**
     * Imagen guardada de la firma como data URI, para mostrarla en la vista previa.
     */
    #[Computed]
    public function firmaActual(): ?string
    {
        $ruta = Parametro::valor('carnet_firma_sello');

        if ($ruta === null || ! Storage::disk('local')->exists($ruta)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode(Storage::disk('local')->get($ruta));
    }
};
