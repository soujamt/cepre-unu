<?php

namespace App\Http\Controllers\Inscripciones;

use App\Enums\EstadoCatalogo;
use App\Enums\EstadoInscripcion;
use App\Enums\EstadoObligacionPago;
use App\Exports\InscripcionesFormatoCepreExport;
use App\Http\Controllers\Controller;
use App\Models\FotoPostulante;
use App\Models\Inscripcion;
use App\Models\ModalidadAdmision;
use App\Models\Proceso;
use App\Models\ProcesoAula;
use App\Models\Sede;
use App\Models\Turno;
use App\Support\AsignadorAulaInscripcion;
use App\Support\DocumentosInscripcion;
use App\Support\Drive\SincronizadorDrive;
use App\Support\InscripcionPagoManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Listado de inscripciones con sus filtros, revisión de las del portal, anulación,
 * asignación de aula, exportación a Excel y carnets en lote.
 */
class InscripcionController extends Controller
{
    /** Valor del filtro de proceso para ver todos los procesos. */
    public const TODOS_LOS_PROCESOS = 'todos';

    public function index(Request $request, SincronizadorDrive $drive): Response
    {
        $filtros = $this->filtros($request);
        $consulta = $this->consulta($filtros);
        $porProceso = fn (Builder $query) => $query->when($filtros['proceso'] !== '', fn (Builder $q) => $q->where('id_proceso', (int) $filtros['proceso']));

        $inscripciones = (clone $consulta)
            ->with(['persona', 'proceso', 'carrera', 'sede', 'modalidadAdmision', 'turno', 'procesoAula.aula', 'obligacionesPago'])
            ->orderByDesc('id_ins')
            ->paginate($filtros['porPagina'])
            ->withQueryString();
        $fotos = FotoPostulante::urlsDeDocumentos($inscripciones->getCollection()->pluck('persona.numero_documento_per'));

        $inscripciones->through(fn (Inscripcion $inscripcion): array => [
            'id' => encode($inscripcion->id_ins),
            'codigo' => $inscripcion->codigo_ins,
            'postulante' => $inscripcion->nombrePostulante(),
            'iniciales' => mb_strtoupper(mb_substr((string) $inscripcion->persona?->nombres_per, 0, 1).mb_substr((string) $inscripcion->persona?->apellido_paterno_per, 0, 1), 'UTF-8'),
            'foto' => $fotos[(string) $inscripcion->persona?->numero_documento_per] ?? null,
            'dni' => $inscripcion->persona?->numero_documento_per,
            'proceso' => $inscripcion->proceso?->nombre_pro,
            'carrera' => $inscripcion->carrera?->nombre_car,
            'sede' => $inscripcion->sede?->nombre_sed,
            'modalidad' => $inscripcion->modalidadAdmision?->nombre_mad,
            'turno' => $inscripcion->turno?->nombre_tur,
            'aula' => $inscripcion->procesoAula?->aula?->nombre_aul,
            'estado' => $inscripcion->estado_ins->value,
            'enRevision' => $inscripcion->enRevision(),
            'pago' => $inscripcion->estadoPagoGeneral(),
            'fecha' => $inscripcion->fecha_inscripcion_ins?->format('d/m/Y h:i a'),
        ]);

        return Inertia::render('inscripciones/inscripciones', [
            'inscripciones' => $inscripciones,
            'filtros' => $filtros,
            'resumen' => $this->resumen($consulta),
            'drive' => [
                'configurado' => $drive->configurado(),
                'ultima' => $drive->ultimaSincronizacion()?->format('d/m/Y h:i a'),
            ],
            'totalEnRevision' => Inscripcion::query()
                ->where('requiere_revision_ins', true)
                ->where('estado_ins', '!=', EstadoInscripcion::Anulado->value)
                ->when($filtros['proceso'] !== '', fn (Builder $query) => $query->where('id_proceso', (int) $filtros['proceso']))
                ->count(),
            'opciones' => [
                'procesos' => Proceso::query()->whereHas('inscripciones')->orderByDesc('id_pro')->get()
                    ->map(fn (Proceso $proceso): array => ['valor' => (string) $proceso->id_pro, 'nombre' => $proceso->nombre_pro]),
                'modalidades' => ModalidadAdmision::query()->whereHas('inscripciones', $porProceso)->orderBy('nombre_mad')->get()
                    ->map(fn (ModalidadAdmision $modalidad): array => ['valor' => (string) $modalidad->id_mad, 'nombre' => $modalidad->nombre_mad]),
                'sedes' => Sede::query()->whereHas('inscripciones', $porProceso)->orderBy('nombre_sed')->get()
                    ->map(fn (Sede $sede): array => ['valor' => (string) $sede->id_sed, 'nombre' => $sede->nombre_sed]),
                'turnos' => Turno::query()->whereHas('inscripciones', $porProceso)->orderBy('nombre_tur')->get()
                    ->map(fn (Turno $turno): array => ['valor' => (string) $turno->id_tur, 'nombre' => $turno->nombre_tur]),
                'aulas' => ProcesoAula::query()
                    ->with(['aula', 'turno'])
                    ->when($filtros['proceso'] !== '', fn (Builder $q) => $q->where('id_proceso', (int) $filtros['proceso']))
                    ->when($filtros['turno'] !== '', fn (Builder $q) => $q->where('id_turno', (int) $filtros['turno']))
                    ->whereHas('inscripciones')
                    ->orderBy('id_pau')
                    ->get()
                    ->map(fn (ProcesoAula $procesoAula): array => ['valor' => (string) $procesoAula->id_pau, 'nombre' => trim(($procesoAula->aula?->nombre_aul ?? 'AULA').' · '.($procesoAula->turno?->nombre_tur ?? ''))]),
                'estados' => array_map(fn (EstadoInscripcion $estado): array => ['valor' => $estado->value, 'nombre' => $estado->getNombre()], EstadoInscripcion::cases()),
                'pagos' => array_map(fn (EstadoObligacionPago $estado): array => ['valor' => $estado->value, 'nombre' => $estado->value], EstadoObligacionPago::cases()),
            ],
        ]);
    }

    public function exportar(Request $request): BinaryFileResponse
    {
        $inscripciones = $this->consulta($this->filtros($request))
            ->with(InscripcionesFormatoCepreExport::RELACIONES)
            ->orderBy('id_ins')
            ->get();

        return Excel::download(new InscripcionesFormatoCepreExport($inscripciones), 'inscripciones-cepre-'.now()->format('Ymd-His').'.xlsx');
    }

    /**
     * Carnets de las inscripciones ACTIVAS del listado filtrado, uno por hoja.
     */
    public function carnets(Request $request, DocumentosInscripcion $documentos): StreamedResponse|RedirectResponse
    {
        $inscripciones = $this->consulta($this->filtros($request))
            ->where('estado_ins', EstadoInscripcion::Activo->value)
            ->with(DocumentosInscripcion::RELACIONES)
            ->orderBy('id_ins')
            ->get();

        if ($inscripciones->isEmpty()) {
            Inertia::flash('toast', ['type' => 'warning', 'message' => 'No hay inscripciones ACTIVAS con los filtros actuales.']);

            return back();
        }

        $contenido = $documentos->carnets($inscripciones->toBase())->output();

        return response()->streamDownload(function () use ($contenido): void {
            echo $contenido;
        }, 'carnets-'.now()->format('Ymd-His').'.pdf', ['Content-Type' => 'application/pdf']);
    }

    /**
     * Aprueba una inscripción hecha en el portal; se activa si ya alcanza el pago mínimo.
     */
    public function aprobar(Request $request, string $inscripcion, InscripcionPagoManager $pagos): RedirectResponse
    {
        $registro = $this->inscripcion($inscripcion);

        if (! $registro->enRevision()) {
            return $this->volverCon('La inscripción no está en revisión.', 'warning');
        }

        $pagos->aprobarRevision($registro, $request->user()?->id_usu);

        return $this->volverCon("Inscripción {$registro->codigo_ins} aprobada.");
    }

    public function anular(Request $request, string $inscripcion): RedirectResponse
    {
        $datos = $request->validate(['motivo' => ['required', 'string', 'min:10', 'max:500']], [
            'motivo.required' => 'El motivo de anulación es obligatorio.',
            'motivo.min' => 'El motivo debe tener al menos 10 caracteres.',
            'motivo.max' => 'El motivo no puede exceder 500 caracteres.',
        ]);

        $registro = $this->inscripcion($inscripcion);

        if ($registro->estado_ins !== EstadoInscripcion::Activo && ! $registro->enRevision()) {
            return $this->volverCon('Solo se pueden anular inscripciones activas o en revisión.', 'error');
        }

        $registro->update([
            'estado_ins' => EstadoInscripcion::Anulado->value,
            'id_proceso_aula' => null,
            'motivo_anulacion_ins' => trim($datos['motivo']),
        ]);

        return $this->volverCon('Inscripción anulada correctamente.');
    }

    /**
     * Aulas del proceso y turno de la inscripción, con su ocupación, para elegir una.
     */
    public function aulasDisponibles(string $inscripcion): JsonResponse
    {
        $registro = $this->inscripcion($inscripcion);

        if (! $this->admiteAula($registro)) {
            return response()->json(['message' => 'No se puede asignar aula a una inscripción anulada.'], 422);
        }

        $aulas = ProcesoAula::query()
            ->select('tbl_proceso_aula.*')
            ->join('tbl_aula', 'tbl_aula.id_aul', '=', 'tbl_proceso_aula.id_aula')
            ->with('aula')
            ->withCount(['inscripciones as inscripciones_count' => fn (Builder $query) => $query->where('estado_ins', '!=', EstadoInscripcion::Anulado->value)])
            ->where('tbl_proceso_aula.id_proceso', $registro->id_proceso)
            ->where('tbl_proceso_aula.id_turno', $registro->id_turno)
            ->where('tbl_proceso_aula.estado_pau', EstadoCatalogo::Habilitado->value)
            ->where('tbl_aula.estado_aul', EstadoCatalogo::Habilitado->value)
            ->orderBy('tbl_aula.lugar_aul')
            ->orderBy('tbl_aula.piso_aul')
            ->orderBy('tbl_aula.nombre_aul')
            ->get();

        return response()->json([
            'turno' => $registro->turno?->nombre_tur,
            'actual' => $registro->id_proceso_aula,
            'aulas' => $aulas->map(fn (ProcesoAula $procesoAula): array => [
                'id' => $procesoAula->id_pau,
                'nombre' => $procesoAula->aula?->nombre_aul,
                'piso' => $procesoAula->aula?->piso_aul?->getNombre(),
                'lugar' => $procesoAula->aula?->lugar_aul?->getNombre(),
                'ocupados' => $procesoAula->inscripciones_count,
                'capacidad' => $procesoAula->capacidad_pau,
            ])->values(),
        ]);
    }

    public function asignarAula(Request $request, string $inscripcion, AsignadorAulaInscripcion $asignador): RedirectResponse
    {
        $datos = $request->validate(['aula' => ['required', 'integer', 'exists:tbl_proceso_aula,id_pau']], ['aula.required' => 'Debe seleccionar un aula.']);
        $id = $this->inscripcion($inscripcion)->id_ins;

        try {
            DB::transaction(function () use ($datos, $id, $asignador): void {
                $procesoAula = ProcesoAula::query()->lockForUpdate()->withCount('inscripciones')->findOrFail((int) $datos['aula']);
                $registro = Inscripcion::query()->lockForUpdate()->findOrFail($id);

                if (! $this->admiteAula($registro)) {
                    throw new RuntimeException('No se puede asignar aula a una inscripción anulada.');
                }

                $asignador->validarCompatibilidad($procesoAula, $registro);
                $asignador->validarCupo($procesoAula, $registro);
                $registro->update(['id_proceso_aula' => $procesoAula->id_pau]);
            });
        } catch (RuntimeException $error) {
            return $this->volverCon($error->getMessage(), 'error');
        }

        return $this->volverCon('Aula asignada correctamente.');
    }

    /**
     * El proceso por defecto es el vigente; "todos" quita el filtro de proceso.
     *
     * @return array{buscar: string, proceso: string, procesoElegido: ?string, modalidad: string, sede: string, turno: string, aula: string, estado: list<string>, pago: string, revision: bool, porPagina: int}
     */
    private function filtros(Request $request): array
    {
        $id = fn (string $clave): string => ctype_digit((string) $request->query($clave)) ? (string) $request->query($clave) : '';
        $proceso = (string) $request->query('proceso', '');
        $pago = (string) $request->query('pago', '');
        $porPagina = (int) $request->query('porPagina', 10);

        return [
            'buscar' => trim((string) preg_replace('/\s+/u', ' ', (string) $request->query('buscar', ''))),
            'proceso' => match (true) {
                $proceso === self::TODOS_LOS_PROCESOS => '',
                ctype_digit($proceso) => $proceso,
                default => $this->procesoPorDefecto(),
            },
            'procesoElegido' => $proceso === self::TODOS_LOS_PROCESOS ? self::TODOS_LOS_PROCESOS : null,
            'modalidad' => $id('modalidad'),
            'sede' => $id('sede'),
            'turno' => $id('turno'),
            'aula' => $id('aula'),
            'estado' => collect((array) $request->query('estado', []))
                ->filter(fn (mixed $estado): bool => in_array($estado, array_column(EstadoInscripcion::cases(), 'value'), true))
                ->values()
                ->all(),
            'pago' => in_array($pago, array_column(EstadoObligacionPago::cases(), 'value'), true) ? $pago : '',
            'revision' => $request->boolean('revision'),
            'porPagina' => in_array($porPagina, [10, 25, 50, 100], true) ? $porPagina : 10,
        ];
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function consulta(array $filtros): Builder
    {
        $texto = str_replace(['%', '_'], ['\%', '\_'], $filtros['buscar']);

        return Inscripcion::query()
            ->when($texto !== '', fn (Builder $query) => $query->where(fn (Builder $interna) => $interna
                ->where('codigo_ins', 'like', "%{$texto}%")
                ->orWhereHas('persona', fn (Builder $persona) => $persona
                    ->where('numero_documento_per', 'like', "%{$texto}%")
                    ->orWhere('apellido_paterno_per', 'like', "%{$texto}%")
                    ->orWhere('apellido_materno_per', 'like', "%{$texto}%")
                    ->orWhere('nombres_per', 'like', "%{$texto}%"))
                ->orWhereHas('proceso', fn (Builder $proceso) => $proceso->where('nombre_pro', 'like', "%{$texto}%"))
                ->orWhereHas('carrera', fn (Builder $carrera) => $carrera->where('nombre_car', 'like', "%{$texto}%"))))
            ->when($filtros['proceso'] !== '', fn (Builder $query) => $query->where('id_proceso', (int) $filtros['proceso']))
            ->when($filtros['modalidad'] !== '', fn (Builder $query) => $query->where('id_modalidad_admision', (int) $filtros['modalidad']))
            ->when($filtros['sede'] !== '', fn (Builder $query) => $query->where('id_sede', (int) $filtros['sede']))
            ->when($filtros['estado'] !== [], fn (Builder $query) => $query->whereIn('estado_ins', $filtros['estado']))
            ->when($filtros['pago'] !== '', fn (Builder $query) => $query->whereHas('obligacionesPago', fn (Builder $obligacion) => $obligacion->where('estado_iop', $filtros['pago'])))
            ->when($filtros['revision'], fn (Builder $query) => $query->where('requiere_revision_ins', true)->where('estado_ins', '!=', EstadoInscripcion::Anulado->value))
            ->when($filtros['turno'] !== '', fn (Builder $query) => $query->where('id_turno', (int) $filtros['turno']))
            ->when($filtros['aula'] !== '', fn (Builder $query) => $query->where('id_proceso_aula', (int) $filtros['aula']));
    }

    /**
     * @return array{total: int, activas: int, pendientes: int, pagoPendiente: int, anuladas: int}
     */
    private function resumen(Builder $consulta): array
    {
        $conteo = (clone $consulta)->toBase()->selectRaw('COUNT(*) as total, SUM(estado_ins = ?) as activas, SUM(estado_ins = ?) as pendientes, SUM(estado_ins = ?) as anuladas', [
            EstadoInscripcion::Activo->value,
            EstadoInscripcion::Pendiente->value,
            EstadoInscripcion::Anulado->value,
        ])->first();

        return [
            'total' => (int) $conteo->total,
            'activas' => (int) $conteo->activas,
            'pendientes' => (int) $conteo->pendientes,
            'pagoPendiente' => (clone $consulta)
                ->whereHas('obligacionesPago', fn (Builder $query) => $query->whereIn('estado_iop', [EstadoObligacionPago::Pendiente->value, EstadoObligacionPago::Parcial->value]))
                ->count(),
            'anuladas' => (int) $conteo->anuladas,
        ];
    }

    /**
     * Proceso vigente según sus fechas o, si no hay, el más reciente con inscripciones.
     */
    private function procesoPorDefecto(): string
    {
        return (string) (Proceso::actual()?->id_pro ?? '');
    }

    private function inscripcion(string $codificado): Inscripcion
    {
        $id = decode($codificado);
        abort_if($id === null, 404);

        return Inscripcion::query()->findOrFail($id);
    }

    private function admiteAula(Inscripcion $inscripcion): bool
    {
        return in_array($inscripcion->estado_ins, [EstadoInscripcion::Activo, EstadoInscripcion::Pendiente], true);
    }

    private function volverCon(string $mensaje, string $tipo = 'success'): RedirectResponse
    {
        Inertia::flash('toast', ['type' => $tipo, 'message' => $mensaje]);

        return back();
    }
}
