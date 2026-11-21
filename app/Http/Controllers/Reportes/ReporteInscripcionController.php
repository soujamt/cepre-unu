<?php

namespace App\Http\Controllers\Reportes;

use App\Enums\EstadoInscripcion;
use App\Enums\EstadoObligacionPago;
use App\Exports\InscripcionesFormatoCepreExport;
use App\Http\Controllers\Controller;
use App\Models\Inscripcion;
use App\Models\ModalidadAdmision;
use App\Models\Proceso;
use App\Models\Sede;
use App\Models\Turno;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Reporte de inscripciones: totales por estado y pago, y cuadros por carrera, modalidad y turno.
 */
class ReporteInscripcionController extends Controller
{
    private const TODOS_LOS_PROCESOS = 'todos';

    public function index(Request $request): Response
    {
        $filtros = $this->filtros($request);
        $inscripciones = $this->consulta($filtros)->with(['carrera', 'modalidadAdmision', 'turno', 'obligacionesPago'])->get();
        $porProceso = fn (Builder $query) => $query->when($filtros['proceso'] !== '', fn (Builder $q) => $q->where('id_proceso', (int) $filtros['proceso']));
        $estado = fn (Collection $grupo, EstadoInscripcion $valor): int => $grupo->where('estado_ins', $valor)->count();

        return Inertia::render('reportes/inscripciones', [
            'filtros' => $filtros,
            'resumen' => [
                'total' => $inscripciones->count(),
                'activas' => $estado($inscripciones, EstadoInscripcion::Activo),
                'pendientes' => $estado($inscripciones, EstadoInscripcion::Pendiente),
                'anuladas' => $estado($inscripciones, EstadoInscripcion::Anulado),
                'pagoPendiente' => $inscripciones->filter(fn (Inscripcion $inscripcion): bool => $this->conSaldo($inscripcion))->count(),
                'pagoParcial' => $inscripciones->filter(fn (Inscripcion $inscripcion): bool => $inscripcion->obligacionesPago->contains('estado_iop', EstadoObligacionPago::Parcial))->count(),
            ],
            'porCarrera' => $this->agrupar($inscripciones, fn (Inscripcion $inscripcion): string => $inscripcion->carrera?->nombre_car ?? 'SIN CARRERA', fn (Collection $grupo): array => [
                'activas' => $estado($grupo, EstadoInscripcion::Activo),
                'pendientes' => $estado($grupo, EstadoInscripcion::Pendiente),
                'anuladas' => $estado($grupo, EstadoInscripcion::Anulado),
            ]),
            'porModalidad' => $this->agrupar($inscripciones, fn (Inscripcion $inscripcion): string => $inscripcion->modalidadAdmision?->nombre_mad ?? 'SIN MODALIDAD', fn (Collection $grupo): array => [
                'activas' => $estado($grupo, EstadoInscripcion::Activo),
                'pagoPendiente' => $grupo->filter(fn (Inscripcion $inscripcion): bool => $this->conSaldo($inscripcion))->count(),
            ]),
            'porTurno' => $this->agrupar($inscripciones, fn (Inscripcion $inscripcion): string => $inscripcion->turno?->nombre_tur ?? 'SIN TURNO', fn (Collection $grupo): array => [
                'activas' => $estado($grupo, EstadoInscripcion::Activo),
                'pendientes' => $estado($grupo, EstadoInscripcion::Pendiente),
            ]),
            'opciones' => [
                'procesos' => Proceso::query()->whereHas('inscripciones')->orderByDesc('id_pro')->get()
                    ->map(fn (Proceso $proceso): array => ['valor' => (string) $proceso->id_pro, 'nombre' => $proceso->nombre_pro]),
                'modalidades' => ModalidadAdmision::query()->whereHas('inscripciones', $porProceso)->orderBy('nombre_mad')->get()
                    ->map(fn (ModalidadAdmision $modalidad): array => ['valor' => (string) $modalidad->id_mad, 'nombre' => $modalidad->nombre_mad]),
                'sedes' => Sede::query()->whereHas('inscripciones', $porProceso)->orderBy('nombre_sed')->get()
                    ->map(fn (Sede $sede): array => ['valor' => (string) $sede->id_sed, 'nombre' => $sede->nombre_sed]),
                'turnos' => Turno::query()->whereHas('inscripciones', $porProceso)->orderBy('nombre_tur')->get()
                    ->map(fn (Turno $turno): array => ['valor' => (string) $turno->id_tur, 'nombre' => $turno->nombre_tur]),
                'estados' => array_map(fn (EstadoInscripcion $valor): array => ['valor' => $valor->value, 'nombre' => $valor->getNombre()], EstadoInscripcion::cases()),
            ],
        ]);
    }

    public function exportar(Request $request): BinaryFileResponse
    {
        $inscripciones = $this->consulta($this->filtros($request))
            ->with([
                'persona.tipoDocumento', 'persona.estadoCivil', 'persona.paisNacimiento', 'persona.nacionalidad',
                'persona.ubigeoNacimiento', 'persona.ubigeoDomicilio', 'persona.tipoDiscapacidad', 'persona.identidadEtnica',
                'persona.lenguaNativa', 'persona.lenguaExtranjera', 'paisColegio', 'colegio', 'tipoColegio', 'sede',
                'modalidadAdmision', 'carrera',
            ])
            ->orderByDesc('id_ins')
            ->get();

        return Excel::download(new InscripcionesFormatoCepreExport($inscripciones), 'reporte-inscripciones-'.now()->format('Ymd-His').'.xlsx');
    }

    /**
     * @return array{proceso: string, procesoElegido: ?string, modalidad: string, sede: string, turno: string, estado: string}
     */
    private function filtros(Request $request): array
    {
        $id = fn (string $clave): string => ctype_digit((string) $request->query($clave)) ? (string) $request->query($clave) : '';
        $proceso = (string) $request->query('proceso', '');
        $estado = (string) $request->query('estado', '');

        return [
            'proceso' => match (true) {
                $proceso === self::TODOS_LOS_PROCESOS => '',
                ctype_digit($proceso) => $proceso,
                default => $this->procesoPorDefecto(),
            },
            'procesoElegido' => $proceso === self::TODOS_LOS_PROCESOS ? self::TODOS_LOS_PROCESOS : null,
            'modalidad' => $id('modalidad'),
            'sede' => $id('sede'),
            'turno' => $id('turno'),
            'estado' => in_array($estado, array_column(EstadoInscripcion::cases(), 'value'), true) ? $estado : '',
        ];
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function consulta(array $filtros): Builder
    {
        return Inscripcion::query()
            ->when($filtros['proceso'] !== '', fn (Builder $query) => $query->where('id_proceso', (int) $filtros['proceso']))
            ->when($filtros['modalidad'] !== '', fn (Builder $query) => $query->where('id_modalidad_admision', (int) $filtros['modalidad']))
            ->when($filtros['sede'] !== '', fn (Builder $query) => $query->where('id_sede', (int) $filtros['sede']))
            ->when($filtros['turno'] !== '', fn (Builder $query) => $query->where('id_turno', (int) $filtros['turno']))
            ->when($filtros['estado'] !== '', fn (Builder $query) => $query->where('estado_ins', $filtros['estado']));
    }

    /**
     * Agrupa las inscripciones y ordena los grupos de mayor a menor.
     *
     * @param  Collection<int, Inscripcion>  $inscripciones
     * @return list<array<string, mixed>>
     */
    private function agrupar(Collection $inscripciones, callable $clave, callable $columnas): array
    {
        return $inscripciones
            ->groupBy($clave)
            ->map(fn (Collection $grupo, string $nombre): array => ['nombre' => $nombre, 'total' => $grupo->count(), ...$columnas($grupo)])
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    private function conSaldo(Inscripcion $inscripcion): bool
    {
        return $inscripcion->obligacionesPago->contains(fn ($obligacion) => in_array($obligacion->estado_iop, [EstadoObligacionPago::Pendiente, EstadoObligacionPago::Parcial], true));
    }

    private function procesoPorDefecto(): string
    {
        $vigente = Proceso::query()
            ->where('fecha_inicio_pro', '<=', now())
            ->where('fecha_fin_pro', '>=', now())
            ->orderByDesc('fecha_inicio_pro')
            ->orderByDesc('id_pro')
            ->value('id_pro');

        return (string) ($vigente ?? Proceso::query()->whereHas('inscripciones')->orderByDesc('fecha_inicio_pro')->orderByDesc('id_pro')->value('id_pro') ?? '');
    }
}
