<?php

use App\Enums\EstadoInscripcion;
use App\Enums\EstadoObligacionPago;
use App\Exports\InscripcionesFormatoCepreExport;
use App\Models\Inscripcion;
use App\Models\ModalidadAdmision;
use App\Models\Sede;
use App\Models\Proceso;
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
#[Title('Reportes de inscripciones')]
class extends Component
{
    use AuthorizesPermisos;

    #[Url(as: 'proceso')]
    public string $idProceso = '';

    #[Url(as: 'modalidad')]
    public string $idModalidad = '';

    #[Url(as: 'sede')]
    public string $idSede = '';

    #[Url(as: 'turno')]
    public string $idTurno = '';

    #[Url(as: 'estado')]
    public string $estadoInscripcion = '';

    public function mount(): void
    {
        $this->autorizarPermiso('reporte_inscripcion', 'indice');

        if ($this->idProceso === '') {
            $this->idProceso = $this->resolverProcesoActualPorDefecto();
        }
    }

    public function limpiarFiltros(): void
    {
        $this->idProceso = $this->resolverProcesoActualPorDefecto();
        $this->idModalidad = '';
        $this->idSede = '';
        $this->idTurno = '';
        $this->estadoInscripcion = '';
    }

    public function limpiarFiltrosSecundarios(): void
    {
        $this->idModalidad = '';
        $this->idSede = '';
        $this->idTurno = '';
    }

    public function filtrosSecundariosActivos(): int
    {
        return collect([
            $this->idModalidad,
            $this->idSede,
            $this->idTurno,
        ])->filter(fn (string $valor): bool => $valor !== '')->count();
    }

    public function estadosInscripcionDisponibles(): array
    {
        return EstadoInscripcion::cases();
    }

    #[Computed]
    public function procesosDisponibles(): Collection
    {
        return Proceso::query()
            ->whereHas('inscripciones')
            ->orderByDesc('id_pro')
            ->get();
    }

    #[Computed]
    public function modalidadesDisponibles(): Collection
    {
        return ModalidadAdmision::query()
            ->whereHas('inscripciones', function (Builder $query): void {
                $this->aplicarFiltrosRelacionados($query);
            })
            ->orderBy('nombre_mad')
            ->get();
    }

    #[Computed]
    public function sedesDisponibles(): Collection
    {
        return Sede::query()
            ->whereHas('inscripciones', function (Builder $query): void {
                $this->aplicarFiltrosRelacionados($query);
            })
            ->orderBy('nombre_sed')
            ->get();
    }

    #[Computed]
    public function turnosDisponibles(): Collection
    {
        return Turno::query()
            ->whereHas('inscripciones', function (Builder $query): void {
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
                'proceso',
                'modalidadAdmision',
                'sede',
                'carrera',
                'turno',
                'obligacionesPago',
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
        ];
    }

    #[Computed]
    public function reportePorCarrera(): Collection
    {
        return $this->inscripcionesFiltradas
            ->groupBy(fn (Inscripcion $inscripcion): string => $inscripcion->carrera?->nombre_car ?? 'SIN CARRERA')
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
            ->groupBy(fn (Inscripcion $inscripcion): string => $inscripcion->modalidadAdmision?->nombre_mad ?? 'SIN MODALIDAD')
            ->map(function (Collection $inscripciones, string $modalidad): array {
                return [
                    'modalidad' => $modalidad,
                    'total' => $inscripciones->count(),
                    'pago_pendiente' => $inscripciones->filter(fn (Inscripcion $inscripcion): bool => $this->tienePagoPendiente($inscripcion))->count(),
                    'activas' => $inscripciones->where('estado_ins', EstadoInscripcion::Activo)->count(),
                ];
            })
            ->sortByDesc('total')
            ->values();
    }

    #[Computed]
    public function reportePorTurno(): Collection
    {
        return $this->inscripcionesFiltradas
            ->groupBy(fn (Inscripcion $inscripcion): string => $inscripcion->turno?->nombre_tur ?? 'SIN TURNO')
            ->map(function (Collection $inscripciones, string $turno): array {
                return [
                    'turno' => $turno,
                    'total' => $inscripciones->count(),
                    'activas' => $inscripciones->where('estado_ins', EstadoInscripcion::Activo)->count(),
                    'pendientes' => $inscripciones->where('estado_ins', EstadoInscripcion::Pendiente)->count(),
                ];
            })
            ->sortByDesc('total')
            ->values();
    }

    private function baseInscripcionesQuery(): Builder
    {
        return Inscripcion::query()
            ->when(filled($this->idProceso), function (Builder $query): void {
                $query->where('id_proceso', (int) $this->idProceso);
            })
            ->when(filled($this->idModalidad), fn (Builder $query) => $query->where('id_modalidad_admision', (int) $this->idModalidad))
            ->when(filled($this->idSede), fn (Builder $query) => $query->where('id_sede', (int) $this->idSede))
            ->when(filled($this->idTurno), fn (Builder $query) => $query->where('id_turno', (int) $this->idTurno))
            ->when(filled($this->estadoInscripcion), fn (Builder $query) => $query->where('estado_ins', $this->estadoInscripcion));
    }

    /**
     * Proceso vigente según sus fechas o, si no hay, el más reciente con inscripciones.
     */
    private function resolverProcesoActualPorDefecto(): string
    {
        $procesoVigente = Proceso::query()
            ->where('fecha_inicio_pro', '<=', now())
            ->where('fecha_fin_pro', '>=', now())
            ->orderByDesc('fecha_inicio_pro')
            ->orderByDesc('id_pro')
            ->value('id_pro');

        if ($procesoVigente !== null) {
            return (string) $procesoVigente;
        }

        return (string) (Proceso::query()
            ->whereHas('inscripciones')
            ->orderByDesc('fecha_inicio_pro')
            ->orderByDesc('id_pro')
            ->value('id_pro') ?? '');
    }

    private function aplicarFiltrosRelacionados(Builder $query): void
    {
        if (filled($this->idProceso)) {
            $query->where('id_proceso', (int) $this->idProceso);
        }
    }

    private function aplicarFiltrosTurno(Builder $query): void
    {
        if (filled($this->idProceso)) {
            $query->where('id_proceso', (int) $this->idProceso);
        }

        if (filled($this->idModalidad)) {
            $query->where('id_modalidad_admision', (int) $this->idModalidad);
        }

        if (filled($this->idSede)) {
            $query->where('id_sede', (int) $this->idSede);
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
            'sede',
            'modalidadAdmision',
            'carrera',
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
};
