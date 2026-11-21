<?php

namespace App\Support\Reportes;

use App\Enums\EstadoInscripcion;
use App\Enums\EstadoObligacionPago;
use App\Enums\EstadoValidacionPago;
use App\Enums\SexoPersona;
use App\Models\Inscripcion;
use App\Models\InscripcionObligacionPago;
use App\Models\InscripcionPago;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Cifras y series de un conjunto de inscripciones para los gráficos del inicio y del
 * reporte: estados, pagos, recaudación, inscripciones por día y repartos por grupo.
 *
 * Las inscripciones deben venir con las relaciones de relaciones().
 */
class EstadisticasInscripciones
{
    /**
     * @param  Collection<int, Inscripcion>  $inscripciones
     */
    public function __construct(private Collection $inscripciones) {}

    /**
     * @return list<string>
     */
    public static function relaciones(): array
    {
        return ['persona', 'carrera', 'modalidadAdmision', 'turno', 'sede', 'obligacionesPago.pagos'];
    }

    /**
     * @return array{total: int, activas: int, pendientes: int, anuladas: int, enRevision: int, conSaldo: int, pagoParcial: int, desdePortal: int}
     */
    public function resumen(): array
    {
        return [
            'total' => $this->inscripciones->count(),
            'activas' => $this->contarEstado($this->inscripciones, EstadoInscripcion::Activo),
            'pendientes' => $this->contarEstado($this->inscripciones, EstadoInscripcion::Pendiente),
            'anuladas' => $this->contarEstado($this->inscripciones, EstadoInscripcion::Anulado),
            'enRevision' => $this->inscripciones->filter(fn (Inscripcion $inscripcion): bool => $inscripcion->enRevision())->count(),
            'conSaldo' => $this->vigentes()->filter(fn (Inscripcion $inscripcion): bool => $this->tieneSaldo($inscripcion))->count(),
            'pagoParcial' => $this->vigentes()->filter(fn (Inscripcion $inscripcion): bool => $inscripcion->obligacionesPago->contains('estado_iop', EstadoObligacionPago::Parcial))->count(),
            'desdePortal' => $this->inscripciones->where('origen_ins', 'PORTAL')->count(),
        ];
    }

    /**
     * Lo que se debe cobrar (obligaciones de las inscripciones no anuladas) y lo ya validado.
     *
     * @return array{esperado: float, recaudado: float, porcentaje: int}
     */
    public function recaudacion(): array
    {
        $obligaciones = $this->vigentes()
            ->flatMap(fn (Inscripcion $inscripcion): Collection => $inscripcion->obligacionesPago)
            ->reject(fn (InscripcionObligacionPago $obligacion): bool => $obligacion->estado_iop === EstadoObligacionPago::Anulado);

        $esperado = round((float) $obligaciones->sum(fn (InscripcionObligacionPago $obligacion): float => (float) $obligacion->monto_total_iop), 2);
        $recaudado = round((float) $obligaciones
            ->flatMap(fn (InscripcionObligacionPago $obligacion): Collection => $obligacion->pagos)
            ->filter(fn (InscripcionPago $pago): bool => $pago->estado_validacion_ipa === EstadoValidacionPago::Verificado || $pago->estado_validacion_ipa === EstadoValidacionPago::Verificado->value)
            ->sum(fn (InscripcionPago $pago): float => (float) $pago->monto_ipa), 2);

        return [
            'esperado' => $esperado,
            'recaudado' => $recaudado,
            'porcentaje' => $esperado > 0 ? (int) min(100, round($recaudado / $esperado * 100)) : 0,
        ];
    }

    /**
     * @return list<array{clave: string, nombre: string, total: int}>
     */
    public function porEstado(): array
    {
        return array_map(fn (EstadoInscripcion $estado): array => [
            'clave' => $estado->value,
            'nombre' => $estado->getNombre(),
            'total' => $this->contarEstado($this->inscripciones, $estado),
        ], [EstadoInscripcion::Activo, EstadoInscripcion::Pendiente, EstadoInscripcion::Anulado]);
    }

    /**
     * Estado del pago de las inscripciones no anuladas (solo los estados que aparecen).
     *
     * @return list<array{clave: string, nombre: string, total: int}>
     */
    public function porEstadoPago(): array
    {
        $conteos = $this->vigentes()->countBy(fn (Inscripcion $inscripcion): string => $inscripcion->estadoPagoGeneral());

        return collect(['PAGADO', 'PARCIAL', 'PENDIENTE', 'EXONERADO', 'SIN REGLA'])
            ->filter(fn (string $estado): bool => ($conteos[$estado] ?? 0) > 0)
            ->map(fn (string $estado): array => ['clave' => $estado, 'nombre' => $estado, 'total' => (int) $conteos[$estado]])
            ->values()
            ->all();
    }

    /**
     * Inscripciones por día entre dos fechas (los días sin inscripciones van en cero).
     *
     * @return list<array{fecha: string, etiqueta: string, total: int}>
     */
    public function porDia(CarbonInterface $desde, CarbonInterface $hasta): array
    {
        $conteos = $this->inscripciones
            ->filter(fn (Inscripcion $inscripcion): bool => $inscripcion->fecha_inscripcion_ins !== null)
            ->countBy(fn (Inscripcion $inscripcion): string => $inscripcion->fecha_inscripcion_ins->toDateString());

        $dias = [];

        for ($dia = CarbonImmutable::parse($desde)->startOfDay(); $dia->lte($hasta); $dia = $dia->addDay()) {
            $dias[] = [
                'fecha' => $dia->toDateString(),
                'etiqueta' => $dia->format('d/m'),
                'total' => (int) ($conteos[$dia->toDateString()] ?? 0),
            ];
        }

        return $dias;
    }

    /**
     * Días del gráfico del reporte: del primer al último día con inscripciones, como
     * máximo los últimos $maximo días de ese rango.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}|null
     */
    public function rangoDeDias(int $maximo = 31): ?array
    {
        $fechas = $this->inscripciones->pluck('fecha_inscripcion_ins')->filter();

        if ($fechas->isEmpty()) {
            return null;
        }

        $hasta = CarbonImmutable::parse($fechas->max())->startOfDay();
        $desde = CarbonImmutable::parse($fechas->min())->startOfDay();

        return [$desde->max($hasta->subDays($maximo - 1)), $hasta];
    }

    /**
     * Agrupa por un dato (carrera, sede…) con el desglose por estado, de mayor a menor.
     *
     * @param  callable(Inscripcion): string  $clave
     * @return list<array{nombre: string, total: int, activas: int, pendientes: int, anuladas: int}>
     */
    public function agrupar(callable $clave): array
    {
        return $this->inscripciones
            ->groupBy($clave)
            ->map(fn (Collection $grupo, string $nombre): array => $this->filaGrupo($nombre, $grupo))
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    /**
     * @return list<array{clave: string, nombre: string, total: int}>
     */
    public function porSexo(): array
    {
        $conteos = $this->inscripciones->countBy(fn (Inscripcion $inscripcion): string => $inscripcion->persona?->sexo_per?->value ?? 'SIN DATO');

        return collect([SexoPersona::Femenino->value, SexoPersona::Masculino->value, SexoPersona::Otro->value, 'SIN DATO'])
            ->filter(fn (string $sexo): bool => ($conteos[$sexo] ?? 0) > 0)
            ->map(fn (string $sexo): array => ['clave' => $sexo, 'nombre' => $sexo, 'total' => (int) $conteos[$sexo]])
            ->values()
            ->all();
    }

    /**
     * @return list<array{clave: string, nombre: string, total: int}>
     */
    public function porOrigen(): array
    {
        $portal = $this->inscripciones->where('origen_ins', 'PORTAL')->count();
        $personal = $this->inscripciones->count() - $portal;

        return array_values(array_filter([
            ['clave' => 'SISTEMA', 'nombre' => 'PERSONAL', 'total' => $personal],
            ['clave' => 'PORTAL', 'nombre' => 'PORTAL EN LÍNEA', 'total' => $portal],
        ], fn (array $origen): bool => $origen['total'] > 0));
    }

    /**
     * @param  Collection<int, Inscripcion>  $grupo
     * @return array{nombre: string, total: int, activas: int, pendientes: int, anuladas: int}
     */
    private function filaGrupo(string $nombre, Collection $grupo): array
    {
        return [
            'nombre' => $nombre,
            'total' => $grupo->count(),
            'activas' => $this->contarEstado($grupo, EstadoInscripcion::Activo),
            'pendientes' => $this->contarEstado($grupo, EstadoInscripcion::Pendiente),
            'anuladas' => $this->contarEstado($grupo, EstadoInscripcion::Anulado),
        ];
    }

    /**
     * @param  Collection<int, Inscripcion>  $inscripciones
     */
    private function contarEstado(Collection $inscripciones, EstadoInscripcion $estado): int
    {
        return $inscripciones->filter(fn (Inscripcion $inscripcion): bool => $inscripcion->estado_ins === $estado)->count();
    }

    /**
     * @return Collection<int, Inscripcion>
     */
    private function vigentes(): Collection
    {
        return $this->inscripciones->reject(fn (Inscripcion $inscripcion): bool => $inscripcion->estado_ins === EstadoInscripcion::Anulado);
    }

    private function tieneSaldo(Inscripcion $inscripcion): bool
    {
        return $inscripcion->obligacionesPago->contains(fn (InscripcionObligacionPago $obligacion): bool => in_array($obligacion->estado_iop, [EstadoObligacionPago::Pendiente, EstadoObligacionPago::Parcial], true));
    }
}
