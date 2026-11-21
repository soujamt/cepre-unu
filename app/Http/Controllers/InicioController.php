<?php

namespace App\Http\Controllers;

use App\Enums\EstadoImportacionBancoDetalle;
use App\Enums\EstadoInscripcion;
use App\Enums\EstadoProceso;
use App\Enums\EstadoValidacionPago;
use App\Models\ImportacionBancoDetalle;
use App\Models\Inscripcion;
use App\Models\InscripcionPago;
use App\Models\Proceso;
use App\Models\ProcesoAula;
use App\Support\Reportes\EstadisticasInscripciones;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Panel de inicio: proceso vigente y su avance, cifras clave, recaudación, gráficos de
 * inscripciones (por día, estado, pago, carrera, turno y sede), ocupación de aulas,
 * pendientes por atender y últimas inscripciones.
 */
class InicioController extends Controller
{
    /**
     * Días que muestra el gráfico de inscripciones por día.
     */
    private const DIAS_DEL_GRAFICO = 14;

    public function __invoke(): Response
    {
        $proceso = Proceso::query()
            ->where('estado_pro', EstadoProceso::Habilitado->value)
            ->latest('id_pro')
            ->first();

        if ($proceso === null) {
            return Inertia::render('inicio', [
                'proceso' => null,
                'resumen' => null,
                'graficos' => null,
                'actividadHoy' => ['inscripciones' => 0, 'pagosValidados' => 0],
                'alertas' => [],
                'recientes' => [],
            ]);
        }

        $inscripciones = Inscripcion::query()->where('id_proceso', $proceso->id_pro)->with(EstadisticasInscripciones::relaciones())->get();
        $estadisticas = new EstadisticasInscripciones($inscripciones);
        $resumen = $estadisticas->resumen();

        return Inertia::render('inicio', [
            'proceso' => $this->datosProceso($proceso),
            'resumen' => $resumen,
            'graficos' => [
                'recaudacion' => $estadisticas->recaudacion(),
                'porDia' => $estadisticas->porDia(now()->subDays(self::DIAS_DEL_GRAFICO - 1), now()),
                'porEstado' => $estadisticas->porEstado(),
                'porEstadoPago' => $estadisticas->porEstadoPago(),
                'porCarrera' => $estadisticas->agrupar(fn (Inscripcion $inscripcion): string => $inscripcion->carrera?->nombre_car ?? 'SIN CARRERA'),
                'porTurno' => $estadisticas->agrupar(fn (Inscripcion $inscripcion): string => $inscripcion->turno?->nombre_tur ?? 'SIN TURNO'),
                'porSede' => $estadisticas->agrupar(fn (Inscripcion $inscripcion): string => $inscripcion->sede?->nombre_sed ?? 'SIN SEDE'),
                'ocupacion' => $this->ocupacionAulas($proceso),
            ],
            'actividadHoy' => $this->actividadHoy($proceso),
            'alertas' => $this->alertas($proceso, $resumen),
            'recientes' => $this->recientes($proceso),
        ]);
    }

    /**
     * @return array{nombre: string, apertura: ?string, cierre: ?string, avance: ?int, diasRestantes: ?int}
     */
    private function datosProceso(Proceso $proceso): array
    {
        $inicio = $proceso->fecha_inicio_pro;
        $fin = $proceso->fecha_fin_pro;
        $avance = null;
        $diasRestantes = null;

        if ($inicio !== null && $fin !== null && $fin->gt($inicio)) {
            $avance = (int) round(min(100, max(0, $inicio->diffInSeconds(now(), false) / $inicio->diffInSeconds($fin) * 100)));
            $diasRestantes = (int) max(0, ceil(now()->diffInDays($fin, false)));
        }

        return [
            'nombre' => $proceso->nombre_pro,
            'apertura' => $inicio?->format('d/m/Y h:i a'),
            'cierre' => $fin?->format('d/m/Y h:i a'),
            'avance' => $avance,
            'diasRestantes' => $diasRestantes,
        ];
    }

    /**
     * Vacantes de las aulas del proceso por turno frente a las inscripciones con aula.
     *
     * @return list<array{nombre: string, capacidad: int, asignadas: int}>
     */
    private function ocupacionAulas(Proceso $proceso): array
    {
        return ProcesoAula::query()
            ->with('turno')
            ->withCount(['inscripciones as asignadas' => fn (Builder $query) => $query->where('estado_ins', '!=', EstadoInscripcion::Anulado->value)])
            ->where('id_proceso', $proceso->id_pro)
            ->get()
            ->groupBy(fn (ProcesoAula $aula): string => $aula->turno?->nombre_tur ?? 'SIN TURNO')
            ->map(fn (Collection $aulas, string $turno): array => [
                'nombre' => $turno,
                'capacidad' => (int) $aulas->sum('capacidad_pau'),
                'asignadas' => (int) $aulas->sum('asignadas'),
            ])
            ->sortBy('nombre')
            ->values()
            ->all();
    }

    /**
     * @return array{inscripciones: int, pagosValidados: int}
     */
    private function actividadHoy(Proceso $proceso): array
    {
        $hoy = now()->toDateString();

        return [
            'inscripciones' => Inscripcion::query()
                ->where('id_proceso', $proceso->id_pro)
                ->whereDate('fecha_inscripcion_ins', $hoy)
                ->count(),
            'pagosValidados' => InscripcionPago::query()
                ->where('estado_validacion_ipa', EstadoValidacionPago::Verificado->value)
                ->whereDate('validado_en', $hoy)
                ->whereHas('obligacionPago.inscripcion', fn (Builder $query) => $query->where('id_proceso', $proceso->id_pro))
                ->count(),
        ];
    }

    /**
     * Pendientes del proceso, de lo más urgente a lo informativo.
     *
     * @param  array{enRevision: int, conSaldo: int}  $resumen
     * @return list<array{titulo: string, detalle: string, tono: string, cantidad: int, enlace: string}>
     */
    private function alertas(Proceso $proceso, array $resumen): array
    {
        $sinAula = Inscripcion::query()
            ->where('id_proceso', $proceso->id_pro)
            ->where('estado_ins', EstadoInscripcion::Activo->value)
            ->whereNull('id_proceso_aula')
            ->count();
        $pagosLibres = ImportacionBancoDetalle::query()
            ->where('estado_ibd', EstadoImportacionBancoDetalle::Pendiente->value)
            ->whereDoesntHave('inscripcionPago')
            ->count();
        $texto = fn (int $cantidad, string $uno, string $varios): string => $cantidad === 1 ? $uno : str_replace(':n', (string) $cantidad, $varios);

        return array_values(array_filter([
            $resumen['enRevision'] > 0 ? [
                'titulo' => 'Inscripciones en línea por aprobar',
                'detalle' => $texto($resumen['enRevision'], '1 inscripción del portal espera la revisión del personal.', ':n inscripciones del portal esperan la revisión del personal.'),
                'tono' => 'danger',
                'cantidad' => $resumen['enRevision'],
                'enlace' => route('inscripciones.index', ['revision' => 1]),
            ] : null,
            $resumen['conSaldo'] > 0 ? [
                'titulo' => 'Pagos por regularizar',
                'detalle' => $texto($resumen['conSaldo'], '1 inscripción con saldo pendiente o pago parcial.', ':n inscripciones con saldo pendiente o pago parcial.'),
                'tono' => 'warning',
                'cantidad' => $resumen['conSaldo'],
                'enlace' => route('pagos.validar'),
            ] : null,
            $sinAula > 0 ? [
                'titulo' => 'Inscripciones activas sin aula',
                'detalle' => $texto($sinAula, '1 inscripción activa todavía no tiene aula asignada.', ':n inscripciones activas todavía no tienen aula asignada.'),
                'tono' => 'warning',
                'cantidad' => $sinAula,
                'enlace' => route('inscripciones.index'),
            ] : null,
            $pagosLibres > 0 ? [
                'titulo' => 'Pagos del banco sin usar',
                'detalle' => $texto($pagosLibres, '1 pago importado aún no se usa en una inscripción.', ':n pagos importados aún no se usan en una inscripción.'),
                'tono' => 'info',
                'cantidad' => $pagosLibres,
                'enlace' => route('pagos.index', ['estado' => EstadoImportacionBancoDetalle::Pendiente->value]),
            ] : null,
        ]));
    }

    /**
     * @return list<array{id: string, codigo: string, nombre: string, iniciales: string, carrera: ?string, estado: string, fecha: ?string, hace: ?string}>
     */
    private function recientes(Proceso $proceso): array
    {
        return Inscripcion::query()
            ->with(['persona', 'carrera'])
            ->where('id_proceso', $proceso->id_pro)
            ->latest('fecha_inscripcion_ins')
            ->limit(6)
            ->get()
            ->map(function (Inscripcion $inscripcion): array {
                $persona = $inscripcion->persona;

                return [
                    'id' => encode($inscripcion->id_ins),
                    'codigo' => (string) $inscripcion->codigo_ins,
                    'nombre' => trim($this->tipoTitulo($persona?->nombres_per).' '.$this->tipoTitulo($persona?->apellido_paterno_per).' '.$this->tipoTitulo($persona?->apellido_materno_per)),
                    'iniciales' => mb_strtoupper(mb_substr((string) $persona?->nombres_per, 0, 1).mb_substr((string) $persona?->apellido_paterno_per, 0, 1), 'UTF-8'),
                    'carrera' => $inscripcion->carrera ? $this->tipoTitulo($inscripcion->carrera->nombre_car) : null,
                    'estado' => $inscripcion->enRevision() ? 'En revisión' : $this->tipoTitulo($inscripcion->estado_ins?->getNombre()),
                    'fecha' => $inscripcion->fecha_inscripcion_ins?->format('d/m/Y h:i a'),
                    'hace' => $inscripcion->fecha_inscripcion_ins?->locale('es')->diffForHumans(),
                ];
            })
            ->all();
    }

    /**
     * "EDUCACIÓN DE LA SELVA" pasa a "Educación de la Selva": mayúscula inicial salvo los conectores.
     */
    private function tipoTitulo(?string $texto): string
    {
        return (string) preg_replace_callback(
            '/(?<=\s)(De|Del|La|Las|Los|Y|E|En|Con|Para)(?=\s)/u',
            fn (array $conector): string => mb_strtolower($conector[1], 'UTF-8'),
            Str::title(mb_strtolower((string) $texto, 'UTF-8')),
        );
    }
}
