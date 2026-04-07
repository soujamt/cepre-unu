<?php

use App\Enums\EstadoImportacionBanco;
use App\Enums\EstadoImportacionBancoDetalle;
use App\Models\ImportacionBanco;
use App\Models\ImportacionBancoDetalle;
use App\Support\BancoTxtParser;
use App\Traits\AuthorizesPermisos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new
#[Layout('layouts.app')]
#[Title('PAGOS')]
class extends Component
{
    use AuthorizesPermisos;
    use WithFileUploads;
    use \Livewire\WithPagination;

    public string $buscar = '';

    /**
     * @var array<string, bool>
     */
    public array $filtroEstado = [];

    public ?TemporaryUploadedFile $archivo = null;

    public ?int $detalleImportacionId = null;

    public function mount(): void
    {
        $this->autorizarPermiso('pago', 'indice');
    }

    public function updatedBuscar(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroEstado(): void
    {
        $this->resetPage();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'archivo' => ['required', 'file', 'extensions:txt', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'archivo.required' => 'Debe seleccionar un archivo TXT.',
            'archivo.extensions' => 'El archivo debe tener extension .txt.',
            'archivo.max' => 'El archivo no debe exceder los 5 MB.',
        ];
    }

    public function estados(): array
    {
        return EstadoImportacionBanco::cases();
    }

    #[Computed]
    public function estadosSeleccionados(): array
    {
        return collect($this->filtroEstado)
            ->filter(fn (bool $selected): bool => $selected)
            ->keys()
            ->values()
            ->all();
    }

    public function limpiarFiltroEstado(): void
    {
        $this->filtroEstado = [];
    }

    public function importar(BancoTxtParser $parser): void
    {
        $this->autorizarPermiso('pago', 'crear');
        $data = $this->validate();

        try {
            $rutaArchivo = $data['archivo']->getRealPath();

            if (! is_string($rutaArchivo) || $rutaArchivo === '') {
                throw new \RuntimeException('No se pudo ubicar el archivo temporal.');
            }

            $content = file_get_contents($rutaArchivo);

            if ($content === false) {
                throw new \RuntimeException('No se pudo leer el archivo temporal.');
            }

            $parsed = $parser->parse($content);

            DB::transaction(function () use ($data, $parsed): void {
                $importacion = ImportacionBanco::query()->create([
                    'nombre_archivo_iba' => $data['archivo']->getClientOriginalName(),
                    'moneda_iba' => $parsed['header']['moneda'],
                    'fecha_importacion_iba' => now(),
                    'fecha_lote_iba' => $parsed['header']['fecha_lote'],
                    'total_registros_iba' => $parsed['summary']['cantidad_detalles'],
                    'total_registros_reportado_iba' => $parsed['trailer']['total_registros'],
                    'total_monto_iba' => $parsed['summary']['total_detalles'],
                    'total_monto_reportado_iba' => $parsed['trailer']['total_monto'],
                    'estado_iba' => $parsed['summary']['cuadra_registros'] && $parsed['summary']['cuadra_montos']
                        ? EstadoImportacionBanco::Procesado->value
                        : EstadoImportacionBanco::Observado->value,
                    'observacion_iba' => $parsed['summary']['cuadra_registros'] && $parsed['summary']['cuadra_montos']
                        ? null
                        : 'El archivo importado no cuadra con los totales reportados por el banco.',
                ]);

                collect($parsed['details'])->each(function (array $detail) use ($importacion): void {
                    ImportacionBancoDetalle::query()->create([
                        'id_importacion_banco' => $importacion->id_iba,
                        'numero_documento_ibd' => $detail['numero_documento_normalizado'] ?? $detail['numero_documento'],
                        'nombre_depositante_ibd' => $detail['nombre_depositante'],
                        'numero_operacion_ibd' => $detail['numero_operacion_normalizado'] ?? $detail['numero_operacion'],
                        'codigo_canal_ibd' => $detail['codigo_canal'],
                        'codigo_oficina_ibd' => $detail['codigo_oficina'],
                        'fecha_pago_ibd' => $detail['fecha_pago'],
                        'monto_ibd' => $detail['monto'],
                        'texto_linea_ibd' => $detail['texto_linea'],
                        'estado_ibd' => EstadoImportacionBancoDetalle::Pendiente->value,
                    ]);
                });

                $this->detalleImportacionId = $importacion->id_iba;
            });
        } catch (\Throwable $throwable) {
            Log::error('Fallo al importar TXT bancario.', [
                'archivo' => $data['archivo']?->getClientOriginalName(),
                'mensaje' => $throwable->getMessage(),
                'clase' => $throwable::class,
                'linea' => $throwable->getLine(),
                'archivo_origen' => $throwable->getFile(),
            ]);
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo importar el archivo TXT. Verifique el formato e intente nuevamente.', type: 'error');

            return;
        }

        unset($this->importaciones);
        unset($this->detalleImportacionSeleccionada);
        $this->archivo = null;

        $this->dispatch('toast', message: 'Archivo TXT importado correctamente.', type: 'success');
    }

    public function verDetalle(int $idImportacion): void
    {
        $this->detalleImportacionId = $idImportacion;
    }

    #[Computed]
    public function importaciones(): LengthAwarePaginator
    {
        return ImportacionBanco::query()
            ->withCount('detalles')
            ->when(filled($this->buscar), function (Builder $query): void {
                $search = trim($this->buscar);

                $query->where(function (Builder $innerQuery) use ($search): void {
                    $innerQuery->where('nombre_archivo_iba', 'like', "%{$search}%")
                        ->orWhere('moneda_iba', 'like', "%{$search}%")
                        ->orWhere('observacion_iba', 'like', "%{$search}%");
                });
            })
            ->when($this->estadosSeleccionados !== [], fn (Builder $query) => $query->whereIn('estado_iba', $this->estadosSeleccionados))
            ->orderByDesc('id_iba')
            ->paginate(10);
    }

    #[Computed]
    public function detalleImportacionSeleccionada(): ?ImportacionBanco
    {
        if ($this->detalleImportacionId === null) {
            return null;
        }

        return ImportacionBanco::query()
            ->with(['detalles' => fn ($query) => $query->orderBy('id_ibd')])
            ->find($this->detalleImportacionId);
    }

    public function estadoDetalleColor(ImportacionBancoDetalle $detalle): string
    {
        return $detalle->estado_ibd->getColor();
    }

    public function estadoDetalleNombre(ImportacionBancoDetalle $detalle): string
    {
        return $detalle->estado_ibd->getNombre();
    }

    public function detalleResumen(ImportacionBancoDetalle $detalle): string
    {
        $partes = [
            $detalle->numero_documento_ibd ?: 'SIN DOCUMENTO',
            $detalle->numero_operacion_ibd ?: 'SIN OPERACION',
        ];

        if ($detalle->codigo_oficina_ibd || $detalle->codigo_canal_ibd) {
            $partes[] = trim(($detalle->codigo_oficina_ibd ?? '').' '.($detalle->codigo_canal_ibd ?? ''));
        }

        return Str::of(implode(' | ', $partes))->trim()->value();
    }
};
