<?php

namespace App\Http\Controllers;

use App\Enums\EstadoInscripcion;
use App\Enums\EstadoObligacionPago;
use App\Enums\EstadoProceso;
use App\Enums\EstadoValidacionPago;
use App\Models\Inscripcion;
use App\Models\InscripcionPago;
use App\Models\Proceso;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Panel de inicio: proceso vigente, actividad del día, resumen de inscripciones,
 * últimas inscripciones y alertas.
 */
class InicioController extends Controller
{
    public function __invoke(): Response
    {
        $proceso = Proceso::query()
            ->where('estado_pro', EstadoProceso::Habilitado->value)
            ->latest('id_pro')
            ->first();

        if ($proceso === null) {
            return Inertia::render('inicio', [
                'proceso' => null,
                'estadisticas' => null,
                'actividadHoy' => ['inscripciones' => 0, 'pagosValidados' => 0],
                'alertas' => [],
                'recientes' => [],
            ]);
        }

        $estadisticas = $this->estadisticas($proceso);

        return Inertia::render('inicio', [
            'proceso' => [
                'nombre' => $proceso->nombre_pro,
                'apertura' => $proceso->fecha_inicio_pro?->format('d/m/Y h:i a'),
                'cierre' => $proceso->fecha_fin_pro?->format('d/m/Y h:i a'),
            ],
            'estadisticas' => $estadisticas,
            'actividadHoy' => $this->actividadHoy($proceso),
            'alertas' => $this->alertas($estadisticas),
            'recientes' => $this->recientes($proceso),
        ]);
    }

    /**
     * @return array{total: int, activas: int, pendientes: int, anuladas: int, pagoPendiente: int}
     */
    private function estadisticas(Proceso $proceso): array
    {
        $base = Inscripcion::query()->where('id_proceso', $proceso->id_pro);

        $conteos = (clone $base)->selectRaw('
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
            'total' => (int) $conteos->total,
            'activas' => (int) $conteos->activas,
            'pendientes' => (int) $conteos->pendientes,
            'anuladas' => (int) $conteos->anuladas,
            'pagoPendiente' => (clone $base)
                ->whereHas('obligacionesPago', fn (Builder $query) => $query->whereIn('estado_iop', [
                    EstadoObligacionPago::Pendiente->value,
                    EstadoObligacionPago::Parcial->value,
                ]))
                ->count(),
        ];
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
     * @param  array{pagoPendiente: int}  $estadisticas
     * @return list<array{titulo: string, detalle: string, tono: string}>
     */
    private function alertas(array $estadisticas): array
    {
        $pendientes = $estadisticas['pagoPendiente'];

        if ($pendientes === 0) {
            return [];
        }

        return [[
            'titulo' => 'Pagos por regularizar',
            'detalle' => $pendientes === 1
                ? '1 inscripción con saldo pendiente o pago parcial.'
                : "{$pendientes} inscripciones con saldo pendiente o pago parcial.",
            'tono' => 'warning',
        ]];
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
