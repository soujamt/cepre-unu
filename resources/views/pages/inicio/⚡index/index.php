<?php

use App\Enums\EstadoCatalogo;
use App\Enums\EstadoInscripcion;
use App\Enums\EstadoObligacionPago;
use App\Enums\EstadoProceso;
use App\Enums\EstadoValidacionPago;
use App\Models\Inscripcion;
use App\Models\InscripcionPago;
use App\Models\Proceso;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts::app')]
#[Title('Inicio')]
class extends Component
{
    #[Computed]
    public function procesoActual(): ?Proceso
    {
        return Proceso::query()
            ->where('estado_pro', EstadoProceso::Habilitado->value)
            ->latest('id_pro')
            ->first();
    }

    #[Computed]
    public function estadisticas(): array
    {
        $proceso = $this->procesoActual;

        if ($proceso === null) {
            return [
                'total' => 0,
                'activas' => 0,
                'pendientes' => 0,
                'anuladas' => 0,
                'pago_pendiente' => 0,
                'docs_observados' => 0,
            ];
        }

        $base = Inscripcion::query()
            ->where('id_proceso', $proceso->id_pro);

        $counts = (clone $base)->selectRaw('
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
            'anuladas' => (int) $counts->anuladas,
            'pago_pendiente' => (clone $base)
                ->whereHas('obligacionesPago', fn (Builder $q) => $q->whereIn('estado_iop', [
                    EstadoObligacionPago::Pendiente->value,
                    EstadoObligacionPago::Parcial->value,
                ]))
                ->count(),
        ];
    }

    #[Computed]
    public function actividadHoy(): array
    {
        $proceso = $this->procesoActual;

        if ($proceso === null) {
            return [
                'nuevas_inscripciones' => 0,
                'pagos_validados' => 0,
            ];
        }

        $fechaReferencia = now()->toDateString();

        return [
            'nuevas_inscripciones' => Inscripcion::query()
                ->whereDate('fecha_inscripcion_ins', $fechaReferencia)
                ->where('id_proceso', $proceso->id_pro)
                ->count(),
            'pagos_validados' => InscripcionPago::query()
                ->where('estado_validacion_ipa', EstadoValidacionPago::Verificado->value)
                ->whereDate('validado_en', $fechaReferencia)
                ->whereHas('obligacionPago.inscripcion', fn (Builder $query) => $query->where('id_proceso', $proceso->id_pro))
                ->count(),
        ];
    }

    #[Computed]
    public function alertasOperativas(): Collection
    {
        $proceso = $this->procesoActual;

        if ($proceso === null) {
            return collect();
        }

        $alertas = collect();

        if ($this->estadisticas['pago_pendiente'] > 0) {
            $alertas->push([
                'tipo' => 'pago',
                'titulo' => 'Pagos por regularizar',
                'detalle' => $this->estadisticas['pago_pendiente'] === 1
                    ? '1 inscripción con saldo pendiente o pago parcial.'
                    : $this->estadisticas['pago_pendiente'].' inscripciones con saldo pendiente o pago parcial.',
                'tono' => 'warning',
            ]);
        }

        return $alertas
            ->take(4)
            ->values();
    }

    #[Computed]
    public function inscripcionesRecientes(): Collection
    {
        $proceso = $this->procesoActual;

        if ($proceso === null) {
            return collect();
        }

        return Inscripcion::query()
            ->with([
                'persona',
                'carrera',
                'turno',
            ])
            ->where('id_proceso', $proceso->id_pro)
            ->latest('fecha_inscripcion_ins')
            ->limit(6)
            ->get();
    }
};
