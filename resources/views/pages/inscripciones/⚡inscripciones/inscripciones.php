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
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('layouts.app')]
#[Title('INSCRIPCIONES')]
class extends Component
{
    use AuthorizesPermisos;
    use WithPagination;

    public string $buscar = '';

    public string $idProceso = '';

    public int $porPagina = 10;

    /**
     * @var array<string, bool>
     */
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

        return [
            'total' => (clone $query)->count(),
            'activas' => (clone $query)->where('estado_ins', EstadoInscripcion::Activo->value)->count(),
            'pendientes' => (clone $query)->where('estado_ins', EstadoInscripcion::Pendiente->value)->count(),
            'anuladas' => (clone $query)->where('estado_ins', EstadoInscripcion::Anulado->value)->count(),
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

        return filled($normalizado) ? $normalizado : null;
    }
};
