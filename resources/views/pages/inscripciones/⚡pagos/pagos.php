<?php

use App\Enums\EstadoImportacionBanco;
use App\Enums\EstadoImportacionBancoDetalle;
use App\Models\ImportacionBanco;
use App\Models\ImportacionBancoDetalle;
use App\Support\BancoTxtParser;
use App\Support\InscripcionPagoManager;
use App\Traits\AuthorizesPermisos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new
#[Layout('layouts.app')]
#[Title('PAGOS')]
class extends Component
{
    use AuthorizesPermisos;
    use WithFileUploads;
    use WithPagination;

    public string $buscar = '';

    /**
     * @var array<string, bool>
     */
    public array $filtroEstado = [];

    public ?TemporaryUploadedFile $archivo = null;

    public ?int $detalleImportacionId = null;

    /**
     * @var array<string, mixed>
     */
    public array $previsualizacionImportacion = [];

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
        $this->previsualizacionImportacion = [];

        try {
            $rutaArchivo = $data['archivo']->getRealPath();

            if (! is_string($rutaArchivo) || $rutaArchivo === '') {
                throw new RuntimeException('No se pudo ubicar el archivo temporal.');
            }

            $content = file_get_contents($rutaArchivo);

            if ($content === false) {
                throw new RuntimeException('No se pudo leer el archivo temporal.');
            }

            $parsed = $parser->parse($content);
            $hashArchivo = hash('sha256', $content);

            $importacionExistente = ImportacionBanco::query()
                ->where('hash_archivo_iba', $hashArchivo)
                ->first();

            if ($importacionExistente !== null) {
                $this->detalleImportacionId = $importacionExistente->id_iba;
                unset($this->detalleImportacionSeleccionada);
                $this->archivo = null;

                $this->dispatch('toast', message: 'Este archivo TXT ya fue importado anteriormente.', type: 'warning');

                return;
            }

            $this->previsualizacionImportacion = $this->crearPrevisualizacionImportacion(
                $data['archivo']->getClientOriginalName(),
                $hashArchivo,
                $parsed,
            );

            $this->modal('modal-previsualizar-importacion')->show();
        } catch (Throwable $throwable) {
            Log::error('Fallo al importar TXT bancario.', [
                'archivo' => $data['archivo']?->getClientOriginalName(),
                'mensaje' => $throwable->getMessage(),
                'clase' => $throwable::class,
                'linea' => $throwable->getLine(),
                'archivo_origen' => $throwable->getFile(),
            ]);
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo importar el archivo TXT. '.$throwable->getMessage(), type: 'error');

            return;
        }
    }

    public function confirmarImportacion(): void
    {
        $this->autorizarPermiso('pago', 'crear');

        if ($this->previsualizacionImportacion === []) {
            $this->dispatch('toast', message: 'No hay una importacion lista para confirmar.', type: 'error');

            return;
        }

        if (! ($this->previsualizacionImportacion['valido'] ?? false)) {
            $this->dispatch('toast', message: 'No se puede importar un TXT con observaciones.', type: 'warning');

            return;
        }

        try {
            DB::transaction(function (): void {
                $importacion = ImportacionBanco::query()->create([
                    'nombre_archivo_iba' => $this->previsualizacionImportacion['nombre_archivo'],
                    'hash_archivo_iba' => $this->previsualizacionImportacion['hash_archivo'],
                    'moneda_iba' => $this->previsualizacionImportacion['moneda'],
                    'fecha_importacion_iba' => now(),
                    'fecha_lote_iba' => $this->previsualizacionImportacion['fecha_lote'],
                    'total_registros_iba' => $this->previsualizacionImportacion['total_registros'],
                    'total_registros_reportado_iba' => $this->previsualizacionImportacion['total_registros_reportado'],
                    'total_monto_iba' => $this->previsualizacionImportacion['total_monto'],
                    'total_monto_reportado_iba' => $this->previsualizacionImportacion['total_monto_reportado'],
                    'estado_iba' => EstadoImportacionBanco::Procesado->value,
                    'observacion_iba' => null,
                ]);

                collect($this->previsualizacionImportacion['detalles'])->each(function (array $detail) use ($importacion): void {
                    ImportacionBancoDetalle::query()->create([
                        'id_importacion_banco' => $importacion->id_iba,
                        'numero_documento_ibd' => $detail['numero_documento_normalizado'] ?? $detail['numero_documento'],
                        'nombre_depositante_ibd' => $detail['nombre_depositante'],
                        'numero_operacion_raw_ibd' => $detail['numero_operacion_normalizado'] ?? $detail['numero_operacion'],
                        'numero_operacion_visible_ibd' => $detail['numero_operacion_visible'],
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
        } catch (Throwable $throwable) {
            Log::error('Fallo al confirmar importacion TXT bancario.', [
                'archivo' => $this->previsualizacionImportacion['nombre_archivo'] ?? null,
                'mensaje' => $throwable->getMessage(),
                'clase' => $throwable::class,
                'linea' => $throwable->getLine(),
                'archivo_origen' => $throwable->getFile(),
            ]);
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo confirmar la importacion del TXT.', type: 'error');

            return;
        }

        unset($this->importaciones);
        unset($this->detalleImportacionSeleccionada);
        $this->limpiarPrevisualizacionImportacion();
        $this->modal('modal-previsualizar-importacion')->close();

        $this->dispatch('toast', message: 'Archivo TXT importado correctamente.', type: 'success');
    }

    public function cancelarPrevisualizacionImportacion(): void
    {
        $this->limpiarPrevisualizacionImportacion();
        $this->modal('modal-previsualizar-importacion')->close();
    }

    public function verDetalle(int $idImportacion): void
    {
        $this->detalleImportacionId = $idImportacion;
    }

    #[On('pago-validar-pendientes')]
    public function validarPagosPendientes(?int $id_importacion = null): void
    {
        $this->autorizarPermiso('pago', 'crear');

        if ($id_importacion === null) {
            $this->dispatch('toast', message: 'ID de importacion no proporcionado.', type: 'error');

            return;
        }

        $resultado = app(InscripcionPagoManager::class)->validarPendientesDeImportacion($id_importacion);

        unset($this->importaciones);
        unset($this->detalleImportacionSeleccionada);

        if ($resultado['asociados'] === 0) {
            $this->dispatch(
                'toast',
                message: 'No se asociaron pagos. Revise que existan pendientes con DNI y saldo coincidente.',
                type: 'warning',
            );

            return;
        }

        $this->dispatch(
            'toast',
            message: "{$resultado['asociados']} pago(s) pendiente(s) asociado(s). {$resultado['pendientes']} quedan por revisar.",
            type: 'success',
        );
    }

    #[Computed]
    public function importaciones(): LengthAwarePaginator
    {
        return ImportacionBanco::query()
            ->withCount([
                'detalles',
                'detalles as detalles_pendientes_count' => fn (Builder $query) => $query
                    ->where('estado_ibd', EstadoImportacionBancoDetalle::Pendiente->value)
                    ->whereDoesntHave('inscripcionPago'),
            ])
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
            $this->formatearDocumentoIdentidad($detalle->numero_documento_ibd),
            $detalle->numero_operacion_visible_ibd ?: $detalle->numero_operacion_ibd ?: 'SIN OPERACION',
        ];

        if ($detalle->codigo_oficina_ibd || $detalle->codigo_canal_ibd) {
            $partes[] = trim(($detalle->codigo_oficina_ibd ?? '').' '.($detalle->codigo_canal_ibd ?? ''));
        }

        return Str::of(implode(' | ', $partes))->trim()->value();
    }

    private function formatearDocumentoIdentidad(?string $numeroDocumento): string
    {
        if ($numeroDocumento === null || trim($numeroDocumento) === '') {
            return 'SIN DOCUMENTO';
        }

        $numeroDocumento = trim($numeroDocumento);

        if (preg_match('/^\d{1,8}$/', $numeroDocumento) === 1) {
            return str_pad($numeroDocumento, 8, '0', STR_PAD_LEFT);
        }

        return $numeroDocumento;
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @return array<string, mixed>
     */
    private function crearPrevisualizacionImportacion(string $nombreArchivo, string $hashArchivo, array $parsed): array
    {
        $observaciones = [];
        $duplicados = $this->detectarPagosDuplicados($parsed['details']);

        if (! $parsed['summary']['cuadra_registros']) {
            $observaciones[] = "La cantidad de operaciones no cuadra: se leyeron {$parsed['summary']['cantidad_detalles']} y el banco reporta {$parsed['trailer']['total_registros']}.";
        }

        if (! $parsed['summary']['cuadra_montos']) {
            $observaciones[] = 'El monto total no cuadra: se leyo S/ '
                .number_format((float) $parsed['summary']['total_detalles'], 2)
                .' y el banco reporta S/ '
                .number_format((float) $parsed['trailer']['total_monto'], 2).'.';
        }

        if ($duplicados !== []) {
            $observaciones[] = 'Se encontraron '.count($duplicados).' pago(s) duplicado(s). Revise las operaciones marcadas antes de importar.';
        }

        return [
            'nombre_archivo' => $nombreArchivo,
            'hash_archivo' => $hashArchivo,
            'moneda' => $parsed['header']['moneda'],
            'fecha_lote' => $parsed['header']['fecha_lote'],
            'total_registros' => $parsed['summary']['cantidad_detalles'],
            'total_registros_reportado' => $parsed['trailer']['total_registros'],
            'total_monto' => $parsed['summary']['total_detalles'],
            'total_monto_reportado' => $parsed['trailer']['total_monto'],
            'detalles' => $parsed['details'],
            'detalles_muestra' => array_slice($parsed['details'], 0, 8),
            'duplicados' => $duplicados,
            'observaciones' => $observaciones,
            'valido' => $observaciones === [],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $detalles
     * @return list<array{tipo: string, documento: ?string, operacion: ?string, fecha_pago: ?string, monto: float}>
     */
    private function detectarPagosDuplicados(array $detalles): array
    {
        $duplicados = [];
        $clavesVistas = [];

        foreach ($detalles as $detalle) {
            $clave = $this->clavePagoDetalle($detalle);

            if ($clave === null) {
                continue;
            }

            if (isset($clavesVistas[$clave])) {
                $duplicados[$clave.'|archivo'] = $this->resumenDuplicado($detalle, 'Duplicado en el TXT');

                continue;
            }

            $clavesVistas[$clave] = true;

            if ($this->existePagoImportado($detalle)) {
                $duplicados[$clave.'|base'] = $this->resumenDuplicado($detalle, 'Ya importado');
            }
        }

        return array_values($duplicados);
    }

    /**
     * @param  array<string, mixed>  $detalle
     */
    private function clavePagoDetalle(array $detalle): ?string
    {
        $documento = (string) ($detalle['numero_documento_normalizado'] ?? $detalle['numero_documento'] ?? '');
        $operacion = (string) ($detalle['numero_operacion_normalizado'] ?? $detalle['numero_operacion'] ?? '');
        $fechaPago = (string) ($detalle['fecha_pago'] ?? '');
        $monto = number_format((float) ($detalle['monto'] ?? 0), 2, '.', '');

        if ($documento === '' || $operacion === '' || $fechaPago === '' || $monto === '0.00') {
            return null;
        }

        return implode('|', [$documento, $operacion, $fechaPago, $monto]);
    }

    /**
     * @param  array<string, mixed>  $detalle
     */
    private function existePagoImportado(array $detalle): bool
    {
        return ImportacionBancoDetalle::query()
            ->where('numero_documento_ibd', $detalle['numero_documento_normalizado'] ?? $detalle['numero_documento'])
            ->where('numero_operacion_ibd', $detalle['numero_operacion_normalizado'] ?? $detalle['numero_operacion'])
            ->whereDate('fecha_pago_ibd', (string) $detalle['fecha_pago'])
            ->where('monto_ibd', (float) $detalle['monto'])
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $detalle
     * @return array{tipo: string, documento: ?string, operacion: ?string, fecha_pago: ?string, monto: float}
     */
    private function resumenDuplicado(array $detalle, string $tipo): array
    {
        return [
            'tipo' => $tipo,
            'documento' => $detalle['numero_documento_normalizado'] ?? $detalle['numero_documento'] ?? null,
            'operacion' => $detalle['numero_operacion_visible'] ?? $detalle['numero_operacion_normalizado'] ?? $detalle['numero_operacion'] ?? null,
            'fecha_pago' => $detalle['fecha_pago'] ?? null,
            'monto' => (float) ($detalle['monto'] ?? 0),
        ];
    }

    private function limpiarPrevisualizacionImportacion(): void
    {
        $this->previsualizacionImportacion = [];
        $this->archivo = null;
    }
};
