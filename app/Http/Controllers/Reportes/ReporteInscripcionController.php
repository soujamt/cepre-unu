<?php

namespace App\Http\Controllers\Reportes;

use App\Enums\EstadoInscripcion;
use App\Exports\InscripcionesFormatoCepreExport;
use App\Http\Controllers\Controller;
use App\Models\Inscripcion;
use App\Models\ModalidadAdmision;
use App\Models\Proceso;
use App\Models\Sede;
use App\Models\Turno;
use App\Support\Reportes\EstadisticasInscripciones;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Reporte de inscripciones: cifras clave, recaudación y gráficos por día, estado, pago,
 * sexo, origen, carrera, modalidad, turno y sede, con los mismos filtros que el Excel.
 */
class ReporteInscripcionController extends Controller
{
    private const TODOS_LOS_PROCESOS = 'todos';

    public function index(Request $request): Response
    {
        $filtros = $this->filtros($request);
        $inscripciones = $this->consulta($filtros)->with(EstadisticasInscripciones::relaciones())->get();
        $estadisticas = new EstadisticasInscripciones($inscripciones);
        $porProceso = fn (Builder $query) => $query->when($filtros['proceso'] !== '', fn (Builder $q) => $q->where('id_proceso', (int) $filtros['proceso']));
        $rango = $estadisticas->rangoDeDias();

        return Inertia::render('reportes/inscripciones', [
            'filtros' => $filtros,
            'resumen' => $estadisticas->resumen(),
            'recaudacion' => $estadisticas->recaudacion(),
            'porDia' => $rango === null ? [] : $estadisticas->porDia(...$rango),
            'porEstado' => $estadisticas->porEstado(),
            'porEstadoPago' => $estadisticas->porEstadoPago(),
            'porSexo' => $estadisticas->porSexo(),
            'porOrigen' => $estadisticas->porOrigen(),
            'porCarrera' => $estadisticas->agrupar(fn (Inscripcion $inscripcion): string => $inscripcion->carrera?->nombre_car ?? 'SIN CARRERA'),
            'porModalidad' => $estadisticas->agrupar(fn (Inscripcion $inscripcion): string => $inscripcion->modalidadAdmision?->nombre_mad ?? 'SIN MODALIDAD'),
            'porTurno' => $estadisticas->agrupar(fn (Inscripcion $inscripcion): string => $inscripcion->turno?->nombre_tur ?? 'SIN TURNO'),
            'porSede' => $estadisticas->agrupar(fn (Inscripcion $inscripcion): string => $inscripcion->sede?->nombre_sed ?? 'SIN SEDE'),
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
