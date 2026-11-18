<?php

use App\Models\FotoPostulante;
use App\Models\Inscripcion;
use App\Models\Persona;
use App\Models\Proceso;
use App\Support\ProcesadorFotoPostulante;
use App\Traits\AuthorizesPermisos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Gestión de fotos: el personal sube las fotos en lote, nombradas con el DNI,
 * antes de inscribir; la ficha y el carnet las toman de aquí.
 */
new
#[Layout('layouts.app')]
#[Title('Fotos')]
class extends Component
{
    use AuthorizesPermisos;
    use WithFileUploads;
    use WithPagination;

    #[Url(as: 'proceso')]
    public string $idProceso = '';

    #[Url(as: 'q')]
    public string $buscar = '';

    /**
     * @var array<int, TemporaryUploadedFile>
     */
    public array $archivos = [];

    /**
     * DNI de cada archivo subido, tomado del nombre y editable antes de guardar.
     *
     * @var array<int, string>
     */
    public array $documentos = [];

    public function mount(): void
    {
        $this->autorizarPermiso('foto', 'indice');
    }

    public function updatedIdProceso(): void
    {
        $this->resetPage();
    }

    public function updatedBuscar(): void
    {
        $this->resetPage();
    }

    public function updatedArchivos(): void
    {
        $this->resetValidation();
        $this->documentos = collect($this->archivos)
            ->map(fn (TemporaryUploadedFile $archivo): string => preg_replace('/\D+/', '', pathinfo($archivo->getClientOriginalName(), PATHINFO_FILENAME)) ?? '')
            ->all();
    }

    public function quitarArchivo(int $indice): void
    {
        unset($this->archivos[$indice], $this->documentos[$indice]);
        $this->archivos = array_values($this->archivos);
        $this->documentos = array_values($this->documentos);
    }

    public function subirFotos(ProcesadorFotoPostulante $procesador): void
    {
        $this->autorizarPermiso('foto', 'crear');

        $this->validate([
            'archivos' => ['required', 'array', 'min:1', 'max:50'],
            'archivos.*' => ['image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'documentos.*' => ['required', 'regex:/^\d{8,12}$/', 'distinct'],
        ], [
            'archivos.required' => 'Seleccione al menos una foto.',
            'archivos.max' => 'Suba como máximo 50 fotos a la vez.',
            'archivos.*.image' => 'El archivo debe ser una imagen.',
            'archivos.*.mimes' => 'La foto debe ser JPG o PNG.',
            'archivos.*.max' => 'Cada foto debe pesar como máximo 2 MB.',
            'documentos.*.required' => 'Escriba el DNI de la foto.',
            'documentos.*.regex' => 'El DNI debe tener entre 8 y 12 dígitos.',
            'documentos.*.distinct' => 'Hay dos fotos con el mismo DNI.',
        ]);

        $guardadas = 0;

        foreach ($this->archivos as $indice => $archivo) {
            try {
                $procesador->guardar($archivo, $this->documentos[$indice]);
                $guardadas++;
            } catch (RuntimeException $exception) {
                $this->addError("archivos.{$indice}", $exception->getMessage());

                return;
            }
        }

        $this->reset(['archivos', 'documentos']);
        unset($this->fotos, $this->totalFotos);
        $this->modal('modal-subir-fotos')->close();
        $this->dispatch('toast', message: "{$guardadas} foto(s) guardada(s) correctamente.", type: 'success');
    }

    #[On('foto-eliminar')]
    public function eliminar(?int $id_foto = null): void
    {
        $this->autorizarPermiso('foto', 'eliminar');

        $foto = FotoPostulante::query()->find($id_foto);

        if ($foto === null) {
            return;
        }

        app(ProcesadorFotoPostulante::class)->eliminar($foto);
        unset($this->fotos, $this->totalFotos);
        $this->dispatch('toast', message: 'Foto eliminada correctamente.', type: 'success');
    }

    /**
     * ZIP con las fotos filtradas, nombradas DNI.jpg, para la imprenta.
     */
    public function exportarFotos(): BinaryFileResponse
    {
        $this->autorizarPermiso('foto', 'exportar_excel');

        $ruta = tempnam(sys_get_temp_dir(), 'fotos').'.zip';
        $zip = new ZipArchive;
        $zip->open($ruta, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $disco = Storage::disk(FotoPostulante::DISCO);

        foreach ($this->baseFotosQuery()->get() as $foto) {
            if ($disco->exists($foto->archivo_fot)) {
                $zip->addFromString("{$foto->numero_documento_fot}.jpg", $disco->get($foto->archivo_fot));
            }
        }

        $zip->close();

        return response()->download($ruta, 'fotos-cepre-'.now()->format('Ymd-His').'.zip')->deleteFileAfterSend();
    }

    #[Computed]
    public function procesos(): Collection
    {
        return Proceso::query()->orderByDesc('fecha_inicio_pro')->get();
    }

    #[Computed]
    public function totalFotos(): int
    {
        return $this->baseFotosQuery()->count();
    }

    #[Computed]
    public function fotos(): LengthAwarePaginator
    {
        return $this->baseFotosQuery()->orderByDesc('actualizado_en')->paginate(24);
    }

    /**
     * Nombres de las personas de las fotos visibles, por DNI.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function nombres(): array
    {
        return Persona::query()
            ->whereIn('numero_documento_per', $this->fotos->pluck('numero_documento_fot'))
            ->get(['numero_documento_per', 'apellido_paterno_per', 'apellido_materno_per', 'nombres_per'])
            ->mapWithKeys(fn (Persona $persona): array => [
                $persona->numero_documento_per => trim("{$persona->apellido_paterno_per} {$persona->apellido_materno_per}, {$persona->nombres_per}"),
            ])
            ->all();
    }

    private function baseFotosQuery(): Builder
    {
        return FotoPostulante::query()
            ->when(filled($this->buscar), fn (Builder $query) => $query->where('numero_documento_fot', 'like', '%'.trim($this->buscar).'%'))
            ->when(filled($this->idProceso), fn (Builder $query) => $query->whereIn(
                'numero_documento_fot',
                Inscripcion::query()
                    ->where('id_proceso', (int) $this->idProceso)
                    ->join('tbl_persona', 'tbl_persona.id_per', '=', 'tbl_inscripcion.id_persona')
                    ->select('tbl_persona.numero_documento_per'),
            ));
    }
};
