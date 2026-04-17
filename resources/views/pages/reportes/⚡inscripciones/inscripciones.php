<?php

use App\Enums\EstadoInscripcion;
use App\Enums\EstadoObligacionPago;
use App\Enums\EstadoRevisionExpediente;
use App\Exports\InscripcionesFormatoCepreExport;
use App\Models\Inscripcion;
use App\Models\LugarInscripcion;
use App\Models\ModalidadAdmision;
use App\Models\Proceso;
use App\Models\Semestre;
use App\Models\Turno;
use App\Traits\AuthorizesPermisos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

new
#[Layout('layouts.app')]
#[Title('REPORTES DE INSCRIPCIONES')]
class extends Component
{
    use AuthorizesPermisos;

    #[Url(as: 'semestre')]
    public string $idSemestre = '';

    #[Url(as: 'proceso')]
    public string $idProceso = '';

    #[Url(as: 'modalidad')]
    public string $idModalidad = '';

    #[Url(as: 'lugar')]
    public string $idLugarInscripcion = '';

    #[Url(as: 'turno')]
    public string $idTurno = '';

    #[Url(as: 'estado')]
    public string $estadoInscripcion = '';

    public function mount(): void
    {
        $this->autorizarPermiso('reporte_inscripcion', 'indice');

        if ($this->idSemestre === '') {
            $this->idSemestre = $this->resolverSemestreActualPorDefecto();
        }
    }

    public function updatedIdSemestre(): void
    {
        $this->idProceso = '';
    }

    public function limpiarFiltros(): void
    {
        $this->idSemestre = $this->resolverSemestreActualPorDefecto();
        $this->idProceso = '';
        $this->idModalidad = '';
        $this->idLugarInscripcion = '';
        $this->idTurno = '';
        $this->estadoInscripcion = '';
    }

    public function estadosInscripcionDisponibles(): array
    {
        return EstadoInscripcion::cases();
    }

    #[Computed]
    public function semestresDisponibles(): Collection
    {
        return Semestre::query()
            ->whereHas('procesos.procesosOferta.inscripciones')
            ->orderByDesc('fecha_inicio_inscripcion_sem')
            ->orderByDesc('id_sem')
            ->get();
    }

    #[Computed]
    public function procesosDisponibles(): Collection
    {
        return Proceso::query()
            ->with('semestre')
            ->whereHas('procesosOferta.inscripciones')
            ->when(filled($this->idSemestre), fn (Builder $query) => $query->where('id_semestre', (int) $this->idSemestre))
            ->orderByDesc('id_pro')
            ->get();
    }

    #[Computed]
    public function modalidadesDisponibles(): Collection
    {
        return ModalidadAdmision::query()
            ->whereHas('modalidadesLugarInscripcion.ofertasBase.procesosOferta.inscripciones', function (Builder $query): void {
                $this->aplicarFiltrosRelacionados($query);
            })
            ->orderBy('nombre_mad')
            ->get();
    }

    #[Computed]
    public function lugaresInscripcionDisponibles(): Collection
    {
        return LugarInscripcion::query()
            ->whereHas('modalidadesLugarInscripcion.ofertasBase.procesosOferta.inscripciones', function (Builder $query): void {
                $this->aplicarFiltrosRelacionados($query);
            })
            ->orderBy('nombre_lin')
            ->get();
    }

    #[Computed]
    public function turnosDisponibles(): Collection
    {
        return Turno::query()
            ->whereHas('procesosOferta.inscripciones', function (Builder $query): void {
                $this->aplicarFiltrosTurno($query);
            })
            ->orderBy('nombre_tur')
            ->get();
    }

    #[Computed]
    public function inscripcionesFiltradas(): Collection
    {
        return $this->baseInscripcionesQuery()
            ->with([
                'procesoOferta.proceso.semestre',
                'procesoOferta.ofertaBase.modalidadLugarInscripcion.modalidadAdmision',
                'procesoOferta.ofertaBase.modalidadLugarInscripcion.lugarInscripcion',
                'procesoOferta.ofertaBase.sedeCarrera.carrera',
                'procesoOferta.turno',
                'convenioProceso.convenio',
                'obligacionesPago',
                'expedientes',
            ])
            ->get();
    }

    #[Computed]
    public function resumenGeneral(): array
    {
        $inscripciones = $this->inscripcionesFiltradas;

        return [
            'total' => $inscripciones->count(),
            'activas' => $inscripciones->where('estado_ins', EstadoInscripcion::Activo)->count(),
            'pendientes' => $inscripciones->where('estado_ins', EstadoInscripcion::Pendiente)->count(),
            'anuladas' => $inscripciones->where('estado_ins', EstadoInscripcion::Anulado)->count(),
            'pago_pendiente' => $inscripciones->filter(fn (Inscripcion $inscripcion): bool => $this->tienePagoPendiente($inscripcion))->count(),
            'pago_parcial' => $inscripciones->filter(fn (Inscripcion $inscripcion): bool => $this->tienePagoParcial($inscripcion))->count(),
            'docs_observados' => $inscripciones->filter(fn (Inscripcion $inscripcion): bool => $this->tieneExpedienteObservado($inscripcion))->count(),
            'docs_rechazados' => $inscripciones->filter(fn (Inscripcion $inscripcion): bool => $this->tieneExpedienteRechazado($inscripcion))->count(),
        ];
    }

    #[Computed]
    public function reportePorCarrera(): Collection
    {
        return $this->inscripcionesFiltradas
            ->groupBy(fn (Inscripcion $inscripcion): string => $inscripcion->procesoOferta?->ofertaBase?->sedeCarrera?->carrera?->nombre_car ?? 'SIN CARRERA')
            ->map(function (Collection $inscripciones, string $carrera): array {
                return [
                    'carrera' => $carrera,
                    'total' => $inscripciones->count(),
                    'activas' => $inscripciones->where('estado_ins', EstadoInscripcion::Activo)->count(),
                    'pendientes' => $inscripciones->where('estado_ins', EstadoInscripcion::Pendiente)->count(),
                    'anuladas' => $inscripciones->where('estado_ins', EstadoInscripcion::Anulado)->count(),
                ];
            })
            ->sortByDesc('total')
            ->values();
    }

    #[Computed]
    public function reportePorModalidad(): Collection
    {
        return $this->inscripcionesFiltradas
            ->groupBy(fn (Inscripcion $inscripcion): string => $inscripcion->procesoOferta?->ofertaBase?->modalidadLugarInscripcion?->modalidadAdmision?->nombre_mad ?? 'SIN MODALIDAD')
            ->map(function (Collection $inscripciones, string $modalidad): array {
                return [
                    'modalidad' => $modalidad,
                    'total' => $inscripciones->count(),
                    'pago_pendiente' => $inscripciones->filter(fn (Inscripcion $inscripcion): bool => $this->tienePagoPendiente($inscripcion))->count(),
                    'docs_observados' => $inscripciones->filter(fn (Inscripcion $inscripcion): bool => $this->tieneExpedienteObservado($inscripcion))->count(),
                ];
            })
            ->sortByDesc('total')
            ->values();
    }

    #[Computed]
    public function reportePorConvenio(): Collection
    {
        return $this->inscripcionesFiltradas
            ->groupBy(fn (Inscripcion $inscripcion): string => (string) ($inscripcion->id_convenio_proceso ?? 'sin-convenio'))
            ->map(function (Collection $inscripciones): array {
                $primeraInscripcion = $inscripciones->first();
                $convenioProceso = $primeraInscripcion?->convenioProceso;
                $nombreConvenio = $convenioProceso?->convenio?->nombre_con ?? 'SIN CONVENIO';
                $cupoMaximo = $convenioProceso?->cupo_maximo_cpr;
                $total = $inscripciones->count();

                return [
                    'convenio' => $nombreConvenio,
                    'total' => $total,
                    'cupo_maximo' => $cupoMaximo,
                    'cupo_disponible' => $cupoMaximo !== null ? max(0, (int) $cupoMaximo - $total) : null,
                ];
            })
            ->sortByDesc('total')
            ->values();
    }

    private function baseInscripcionesQuery(): Builder
    {
        return Inscripcion::query()
            ->when(filled($this->idSemestre), function (Builder $query): void {
                $query->whereHas('procesoOferta.proceso', fn (Builder $builder) => $builder->where('id_semestre', (int) $this->idSemestre));
            })
            ->when(filled($this->idProceso), function (Builder $query): void {
                $query->whereHas('procesoOferta', fn (Builder $builder) => $builder->where('id_proceso', (int) $this->idProceso));
            })
            ->when(filled($this->idModalidad), function (Builder $query): void {
                $query->whereHas(
                    'procesoOferta.ofertaBase.modalidadLugarInscripcion',
                    fn (Builder $builder) => $builder->where('id_modalidad_admision', (int) $this->idModalidad)
                );
            })
            ->when(filled($this->idLugarInscripcion), function (Builder $query): void {
                $query->whereHas(
                    'procesoOferta.ofertaBase.modalidadLugarInscripcion',
                    fn (Builder $builder) => $builder->where('id_lugar_inscripcion', (int) $this->idLugarInscripcion)
                );
            })
            ->when(filled($this->idTurno), function (Builder $query): void {
                $query->whereHas('procesoOferta', fn (Builder $builder) => $builder->where('id_turno', (int) $this->idTurno));
            })
            ->when(filled($this->estadoInscripcion), fn (Builder $query) => $query->where('estado_ins', $this->estadoInscripcion));
    }

    private function resolverSemestreActualPorDefecto(): string
    {
        $semestre = Semestre::query()
            ->whereDate('fecha_inicio_inscripcion_sem', '<=', now()->toDateString())
            ->whereDate('fecha_fin_inscripcion_sem', '>=', now()->toDateString())
            ->orderByDesc('fecha_inicio_inscripcion_sem')
            ->orderByDesc('id_sem')
            ->first();

        if ($semestre !== null) {
            return (string) $semestre->id_sem;
        }

        return (string) (Semestre::query()
            ->whereHas('procesos.procesosOferta.inscripciones')
            ->orderByDesc('fecha_inicio_inscripcion_sem')
            ->orderByDesc('id_sem')
            ->value('id_sem') ?? '');
    }

    private function aplicarFiltrosRelacionados(Builder $query): void
    {
        if (filled($this->idSemestre)) {
            $query->whereHas('procesoOferta.proceso', fn (Builder $builder) => $builder->where('id_semestre', (int) $this->idSemestre));
        }

        if (filled($this->idProceso)) {
            $query->whereHas('procesoOferta', fn (Builder $builder) => $builder->where('id_proceso', (int) $this->idProceso));
        }
    }

    private function aplicarFiltrosTurno(Builder $query): void
    {
        if (filled($this->idSemestre)) {
            $query->whereHas('procesoOferta.proceso', fn (Builder $builder) => $builder->where('id_semestre', (int) $this->idSemestre));
        }

        if (filled($this->idProceso)) {
            $query->whereHas('procesoOferta', fn (Builder $builder) => $builder->where('id_proceso', (int) $this->idProceso));
        }

        if (filled($this->idModalidad)) {
            $query->whereHas(
                'procesoOferta.ofertaBase.modalidadLugarInscripcion',
                fn (Builder $builder) => $builder->where('id_modalidad_admision', (int) $this->idModalidad)
            );
        }

        if (filled($this->idLugarInscripcion)) {
            $query->whereHas(
                'procesoOferta.ofertaBase.modalidadLugarInscripcion',
                fn (Builder $builder) => $builder->where('id_lugar_inscripcion', (int) $this->idLugarInscripcion)
            );
        }
    }

    public function exportarExcel(): BinaryFileResponse
    {
        $this->autorizarPermiso('reporte_inscripcion', 'exportar_excel');

        $inscripciones = $this->baseInscripcionesQuery()
            ->with($this->relacionesExportacion())
            ->orderByDesc('id_ins')
            ->get();

        return Excel::download(
            new InscripcionesFormatoCepreExport($inscripciones),
            $this->nombreArchivoExportacion(),
        );
    }

    /**
     * @return array<int, string>
     */
    private function relacionesExportacion(): array
    {
        return [
            'persona.tipoDocumento',
            'persona.estadoCivil',
            'persona.paisNacimiento',
            'persona.nacionalidad',
            'persona.ubigeoNacimiento',
            'persona.ubigeoDomicilio',
            'persona.tipoDiscapacidad',
            'persona.identidadEtnica',
            'persona.lenguaNativa',
            'persona.lenguaExtranjera',
            'paisColegio',
            'colegio',
            'tipoColegio',
            'procesoOferta.ofertaBase.modalidadLugarInscripcion.lugarInscripcion',
        ];
    }

    private function nombreArchivoExportacion(): string
    {
        return 'reporte-inscripciones-'.now()->format('Ymd-His').'.xlsx';
    }

    private function tienePagoPendiente(Inscripcion $inscripcion): bool
    {
        return $inscripcion->obligacionesPago->contains(
            fn ($obligacion) => in_array($obligacion->estado_iop, [EstadoObligacionPago::Pendiente, EstadoObligacionPago::Parcial], true)
        );
    }

    private function tienePagoParcial(Inscripcion $inscripcion): bool
    {
        return $inscripcion->obligacionesPago->contains(
            fn ($obligacion) => $obligacion->estado_iop === EstadoObligacionPago::Parcial
        );
    }

    private function tieneExpedienteObservado(Inscripcion $inscripcion): bool
    {
        return $inscripcion->expedientes->contains(
            fn ($expediente) => $expediente->estado_revision_iex === EstadoRevisionExpediente::Observado
        );
    }

    private function tieneExpedienteRechazado(Inscripcion $inscripcion): bool
    {
        return $inscripcion->expedientes->contains(
            fn ($expediente) => $expediente->estado_revision_iex === EstadoRevisionExpediente::Rechazado
        );
    }
};
