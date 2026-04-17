<?php

use App\Enums\EstadoInscripcion;
use App\Enums\EstadoObligacionPago;
use App\Enums\EstadoProceso;
use App\Enums\EstadoRevisionExpediente;
use App\Models\Inscripcion;
use App\Models\Proceso;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts::app')]
#[Title('INICIO')]
class extends Component
{
    #[Computed]
    public function procesoActual(): ?Proceso
    {
        return Proceso::query()
            ->with('semestre')
            ->where('estado_pro', EstadoProceso::Habilitado->value)
            ->latest('id_pro')
            ->first();
    }

    #[Computed]
    public function estadisticas(): array
    {
        $proceso = $this->procesoActual;

        if ($proceso === null) {
            return ['total' => 0, 'activas' => 0, 'pendientes' => 0, 'anuladas' => 0, 'pago_pendiente' => 0, 'docs_observados' => 0];
        }

        $base = Inscripcion::query()
            ->whereHas('procesoOferta', fn (Builder $q) => $q->where('id_proceso', $proceso->id_pro));

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
            'docs_observados' => (clone $base)
                ->whereHas('expedientes', fn (Builder $q) => $q->whereIn('estado_revision_iex', [
                    EstadoRevisionExpediente::Observado->value,
                    EstadoRevisionExpediente::Rechazado->value,
                ]))
                ->count(),
        ];
    }
};
