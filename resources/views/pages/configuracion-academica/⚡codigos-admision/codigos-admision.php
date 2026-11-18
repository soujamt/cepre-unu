<?php

use App\Exports\PlantillaCodigosAdmisionExport;
use App\Models\Proceso;
use App\Support\CodigosAdmisionProceso;
use App\Traits\AuthorizesPermisos;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Códigos que entrega Admisión al cierre de cada proceso. Son opcionales:
 * sin ellos, la exportación de inscritos lleva los nombres.
 */
new
#[Layout('layouts.app')]
#[Title('Códigos de Admisión')]
class extends Component
{
    use AuthorizesPermisos;
    use WithFileUploads;

    #[Url(as: 'proceso')]
    public string $idProceso = '';

    /**
     * Código escrito por combinación: [clave => código].
     *
     * @var array<string, string>
     */
    public array $codigos = [];

    public ?TemporaryUploadedFile $plantilla = null;

    /**
     * @var list<string>
     */
    public array $erroresPlantilla = [];

    public function mount(): void
    {
        $this->autorizarPermiso('codigo_admision', 'indice');

        if (blank($this->idProceso)) {
            $this->idProceso = (string) (Proceso::query()->orderByDesc('fecha_inicio_pro')->value('id_pro') ?? '');
        }

        $this->cargarCodigos();
    }

    public function updatedIdProceso(): void
    {
        $this->erroresPlantilla = [];
        unset($this->procesoSeleccionado, $this->combinaciones);
        $this->cargarCodigos();
    }

    #[Computed]
    public function procesosDisponibles(): Collection
    {
        return Proceso::query()->orderByDesc('fecha_inicio_pro')->get();
    }

    #[Computed]
    public function procesoSeleccionado(): ?Proceso
    {
        return blank($this->idProceso) ? null : Proceso::query()->find((int) $this->idProceso);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function combinaciones(): \Illuminate\Support\Collection
    {
        $proceso = $this->procesoSeleccionado;

        return $proceso === null ? collect() : app(CodigosAdmisionProceso::class)->combinaciones($proceso);
    }

    /**
     * @return array{total:int,con_codigo:int}
     */
    #[Computed]
    public function avance(): array
    {
        return [
            'total' => $this->combinaciones->count(),
            'con_codigo' => $this->combinaciones->filter(fn (array $fila): bool => filled($fila['codigo']))->count(),
        ];
    }

    public function guardar(CodigosAdmisionProceso $codigosAdmision): void
    {
        $this->autorizarPermiso('codigo_admision', 'editar');

        $proceso = $this->procesoSeleccionado;

        if ($proceso === null) {
            $this->addError('idProceso', 'Seleccione un proceso.');

            return;
        }

        $this->validate(
            ['codigos.*' => ['nullable', 'string', 'max:20']],
            ['codigos.*.max' => 'El código no debe superar 20 caracteres.'],
        );

        $guardados = $codigosAdmision->guardar($proceso, $this->codigos);

        unset($this->combinaciones, $this->avance);
        $this->cargarCodigos();
        $this->dispatch('toast', message: "Códigos guardados: {$guardados}.", type: 'success');
    }

    public function descargarPlantilla(CodigosAdmisionProceso $codigosAdmision): ?BinaryFileResponse
    {
        $proceso = $this->procesoSeleccionado;

        if ($proceso === null) {
            $this->addError('idProceso', 'Seleccione un proceso.');

            return null;
        }

        return Excel::download(
            new PlantillaCodigosAdmisionExport($codigosAdmision->combinaciones($proceso)),
            'codigos-admision-'.Str::slug($proceso->nombre_pro).'.xlsx',
        );
    }

    public function updatedPlantilla(CodigosAdmisionProceso $codigosAdmision): void
    {
        $this->autorizarPermiso('codigo_admision', 'editar');
        $this->erroresPlantilla = [];

        $this->validate(
            ['plantilla' => ['required', 'file', 'mimes:xlsx,xls', 'max:2048']],
            ['plantilla.mimes' => 'Suba la plantilla en Excel (.xlsx).', 'plantilla.max' => 'El archivo no debe pesar más de 2 MB.'],
        );

        $proceso = $this->procesoSeleccionado;

        if ($proceso === null) {
            $this->addError('idProceso', 'Seleccione un proceso.');

            return;
        }

        $lectura = $codigosAdmision->leerPlantilla($this->plantilla->getRealPath(), $proceso);
        $this->erroresPlantilla = $lectura['errores'];
        $this->reset('plantilla');

        if ($lectura['codigos'] === []) {
            $this->dispatch('toast', message: 'La plantilla no trae códigos para este proceso.', type: 'warning');

            return;
        }

        $guardados = $codigosAdmision->guardar($proceso, $lectura['codigos']);

        unset($this->combinaciones, $this->avance);
        $this->cargarCodigos();
        $this->dispatch('toast', message: "Plantilla cargada: {$guardados} código(s) guardado(s).", type: $this->erroresPlantilla === [] ? 'success' : 'warning');
    }

    private function cargarCodigos(): void
    {
        $this->resetErrorBag();
        $this->codigos = $this->combinaciones->pluck('codigo', 'clave')->map(fn (?string $codigo): string => (string) $codigo)->all();
    }
};
