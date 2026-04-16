<?php

use App\Enums\EstadoInscripcion;
use App\Enums\EstadoObligacionPago;
use App\Enums\EstadoRevisionExpediente;
use App\Models\Inscripcion;
use App\Models\Proceso;
use App\Traits\AuthorizesPermisos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('layouts.app')]
#[Title('INSCRIPCIONES')]
class extends Component
{
    use AuthorizesPermisos;
    use WithPagination;

    #[Url(as: 'q')]
    public string $buscar = '';

    #[Url(as: 'proceso')]
    public string $idProceso = '';

    #[Url(as: 'pagina')]
    public int $porPagina = 10;

    /**
     * @var array<string, bool>
     */
    #[Url(as: 'estado')]
    public array $filtroEstado = [];

    public function mount(): void
    {
        $this->autorizarPermiso('inscripcion', 'indice');
    }

    public function updatedBuscar(): void
    {
        $this->resetPage();
    }

    public function updatedIdProceso(): void
    {
        $this->resetPage();
    }

    public function updatedPorPagina(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroEstado(): void
    {
        $this->resetPage();
    }

    public function estados(): array
    {
        return EstadoInscripcion::cases();
    }

    #[Computed]
    public function estadosSeleccionados(): array
    {
        return collect($this->filtroEstado)
            ->filter(fn (bool $seleccionado): bool => $seleccionado)
            ->keys()
            ->values()
            ->all();
    }

    #[Computed]
    public function procesosDisponibles(): Collection
    {
        return Proceso::query()
            ->with('semestre')
            ->orderByDesc('id_pro')
            ->get();
    }

    #[Computed]
    public function resumen(): array
    {
        $query = $this->baseInscripcionesQuery();

        $counts = (clone $query)->selectRaw('
            COUNT(*) as total,
            SUM(estado_ins = ?) as activas,
            SUM(estado_ins = ?) as pendientes,
            SUM(estado_ins = ?) as anuladas
        ', [
            EstadoInscripcion::Activo->value,
            EstadoInscripcion::Pendiente->value,
            EstadoInscripcion::Anulado->value,
        ])->first();

        return [
            'total' => (int) $counts->total,
            'activas' => (int) $counts->activas,
            'pendientes' => (int) $counts->pendientes,
            'pago_pendiente' => (clone $query)
                ->whereHas('obligacionesPago', fn (Builder $q) => $q->whereIn('estado_iop', [
                    EstadoObligacionPago::Pendiente->value,
                    EstadoObligacionPago::Parcial->value,
                ]))
                ->count(),
            'docs_observados' => (clone $query)
                ->whereHas('expedientes', fn (Builder $q) => $q->whereIn('estado_revision_iex', [
                    EstadoRevisionExpediente::Observado->value,
                    EstadoRevisionExpediente::Rechazado->value,
                ]))
                ->count(),
            'anuladas' => (int) $counts->anuladas,
        ];
    }

    #[Computed]
    public function inscripciones(): LengthAwarePaginator
    {
        return $this->baseInscripcionesQuery()
            ->with([
                'persona',
                'procesoOferta.proceso.semestre',
                'procesoOferta.proceso.requisitosExpediente',
                'procesoOferta.ofertaBase.sedeCarrera.carrera',
                'procesoOferta.ofertaBase.sedeCarrera.sede',
                'procesoOferta.ofertaBase.modalidadLugarInscripcion.modalidadAdmision',
                'procesoOferta.turno',
                'obligacionesPago',
                'expedientes',
            ])
            ->orderByDesc('id_ins')
            ->paginate($this->porPagina);
    }

    public function limpiarFiltroEstado(): void
    {
        $this->filtroEstado = [];
    }

    public function limpiarFiltroProceso(): void
    {
        $this->idProceso = '';
    }

    public function descripcionPostulacion(Inscripcion $inscripcion): string
    {
        $proceso = $inscripcion->procesoOferta?->proceso?->nombre_pro ?? 'SIN PROCESO';
        $carrera = $inscripcion->procesoOferta?->ofertaBase?->sedeCarrera?->carrera?->nombre_car ?? 'SIN CARRERA';
        $sede = $inscripcion->procesoOferta?->ofertaBase?->sedeCarrera?->sede?->nombre_sed ?? 'SIN SEDE';

        return "{$proceso} | {$carrera} | {$sede}";
    }

    public function puedeContinuarRegistro(Inscripcion $inscripcion): bool
    {
        return $inscripcion->estado_ins === EstadoInscripcion::Pendiente;
    }

    public function puedeVerDetalleRegistro(Inscripcion $inscripcion): bool
    {
        return $inscripcion->estado_ins === EstadoInscripcion::Activo;
    }

    public function tienePagoPorValidar(Inscripcion $inscripcion): bool
    {
        return $inscripcion->obligacionesPago->contains(
            fn ($obligacion) => in_array($obligacion->estado_iop, [EstadoObligacionPago::Pendiente, EstadoObligacionPago::Parcial], true)
        );
    }

    /**
     * @return array{label:string,icon:string}
     */
    public function accionEstadoRegistro(Inscripcion $inscripcion): array
    {
        return match ($inscripcion->estado_ins) {
            EstadoInscripcion::Activo => [
                'label' => 'Disponible en detalle',
                'icon' => 'eye',
            ],
            EstadoInscripcion::Anulado => [
                'label' => 'Inscripcion anulada',
                'icon' => 'x-circle',
            ],
            default => [
                'label' => 'Registro en proceso',
                'icon' => 'clock',
            ],
        };
    }

    /**
     * @return array{label:string,color:string}
     */
    public function resumenPago(Inscripcion $inscripcion): array
    {
        $obligaciones = $inscripcion->obligacionesPago;

        if ($obligaciones->isEmpty()) {
            return ['label' => 'SIN REGLA', 'color' => 'zinc'];
        }

        if ($obligaciones->every(fn ($obligacion) => $obligacion->estado_iop === EstadoObligacionPago::Exonerado)) {
            return ['label' => 'EXONERADO', 'color' => 'violet'];
        }

        if ($obligaciones->every(fn ($obligacion) => in_array($obligacion->estado_iop, [EstadoObligacionPago::Pagado, EstadoObligacionPago::Exonerado], true))) {
            return ['label' => 'PAGADO', 'color' => 'green'];
        }

        if ($obligaciones->contains(fn ($obligacion) => $obligacion->estado_iop === EstadoObligacionPago::Parcial)) {
            return ['label' => 'PARCIAL', 'color' => 'sky'];
        }

        if ($obligaciones->contains(fn ($obligacion) => $obligacion->estado_iop === EstadoObligacionPago::Pendiente)) {
            return ['label' => 'PENDIENTE', 'color' => 'amber'];
        }

        return ['label' => 'REVISION', 'color' => 'zinc'];
    }

    /**
     * @return array{label:string,color:string,detalle:string}
     */
    public function resumenExpedientes(Inscripcion $inscripcion): array
    {
        $requisitos = $inscripcion->procesoOferta?->proceso?->requisitosExpediente?->count() ?? 0;
        $expedientes = $inscripcion->expedientes;

        if ($requisitos === 0) {
            return ['label' => 'SIN REQUISITOS', 'color' => 'zinc', 'detalle' => '0 / 0'];
        }

        if ($expedientes->isEmpty()) {
            return ['label' => 'SIN CARGA', 'color' => 'amber', 'detalle' => "0 / {$requisitos}"];
        }

        if ($expedientes->contains(fn ($expediente) => $expediente->estado_revision_iex === EstadoRevisionExpediente::Rechazado)) {
            return ['label' => 'RECHAZADO', 'color' => 'red', 'detalle' => "{$expedientes->count()} / {$requisitos}"];
        }

        if ($expedientes->contains(fn ($expediente) => $expediente->estado_revision_iex === EstadoRevisionExpediente::Observado)) {
            return ['label' => 'OBSERVADO', 'color' => 'sky', 'detalle' => "{$expedientes->count()} / {$requisitos}"];
        }

        if (
            $expedientes->count() >= $requisitos
            && $expedientes->every(fn ($expediente) => $expediente->estado_revision_iex === EstadoRevisionExpediente::Aprobado)
        ) {
            return ['label' => 'COMPLETO', 'color' => 'green', 'detalle' => "{$expedientes->count()} / {$requisitos}"];
        }

        return ['label' => 'PENDIENTE', 'color' => 'amber', 'detalle' => "{$expedientes->count()} / {$requisitos}"];
    }

    private function baseInscripcionesQuery(): Builder
    {
        return Inscripcion::query()
            ->when(filled($this->buscar), function (Builder $query): void {
                $texto = $this->normalizarTexto($this->buscar);

                $query->where(function (Builder $builder) use ($texto): void {
                    $builder->where('codigo_ins', 'like', "%{$texto}%")
                        ->orWhereHas('persona', function (Builder $personaQuery) use ($texto): void {
                            $personaQuery->where('numero_documento_per', 'like', "%{$texto}%")
                                ->orWhere('apellido_paterno_per', 'like', "%{$texto}%")
                                ->orWhere('apellido_materno_per', 'like', "%{$texto}%")
                                ->orWhere('nombres_per', 'like', "%{$texto}%");
                        })
                        ->orWhereHas('procesoOferta.proceso', fn (Builder $procesoQuery) => $procesoQuery
                            ->where('nombre_pro', 'like', "%{$texto}%"))
                        ->orWhereHas('procesoOferta.ofertaBase.sedeCarrera.carrera', fn (Builder $carreraQuery) => $carreraQuery
                            ->where('nombre_car', 'like', "%{$texto}%"));
                });
            })
            ->when(filled($this->idProceso), function (Builder $query): void {
                $query->whereHas('procesoOferta', fn (Builder $builder) => $builder->where('id_proceso', (int) $this->idProceso));
            })
            ->when($this->estadosSeleccionados !== [], function (Builder $query): void {
                $query->whereIn('estado_ins', $this->estadosSeleccionados);
            });
    }

    private function normalizarTexto(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $normalizado = preg_replace('/\s+/u', ' ', trim($valor));

        if (! filled($normalizado)) {
            return null;
        }

        return str_replace(['%', '_'], ['\%', '\_'], $normalizado);
    }
};
