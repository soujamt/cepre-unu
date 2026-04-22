<?php

use App\Enums\EstadoCatalogo;
use App\Enums\EstadoInscripcion;
use App\Enums\EstadoObligacionPago;
use App\Enums\EstadoProceso;
use App\Enums\EstadoRevisionExpediente;
use App\Enums\EstadoValidacionPago;
use App\Models\ConvenioProceso;
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

    #[Computed]
    public function actividadHoy(): array
    {
        $proceso = $this->procesoActual;

        if ($proceso === null) {
            return [
                'nuevas_inscripciones' => 0,
                'pagos_validados' => 0,
                'expedientes_revisados' => 0,
            ];
        }

        $fechaReferencia = now()->toDateString();

        return [
            'nuevas_inscripciones' => Inscripcion::query()
                ->whereDate('fecha_inscripcion_ins', $fechaReferencia)
                ->whereHas('procesoOferta', fn (Builder $query) => $query->where('id_proceso', $proceso->id_pro))
                ->count(),
            'pagos_validados' => InscripcionPago::query()
                ->where('estado_validacion_ipa', EstadoValidacionPago::Verificado->value)
                ->whereDate('validado_en', $fechaReferencia)
                ->whereHas('obligacionPago.inscripcion.procesoOferta', fn (Builder $query) => $query->where('id_proceso', $proceso->id_pro))
                ->count(),
            'expedientes_revisados' => Inscripcion::query()
                ->whereHas('procesoOferta', fn (Builder $query) => $query->where('id_proceso', $proceso->id_pro))
                ->whereHas('expedientes', fn (Builder $query) => $query->whereDate('revisado_en', $fechaReferencia))
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

        $conveniosComprometidos = ConvenioProceso::query()
            ->withCount([
                'inscripciones as inscritos_activos_count' => fn (Builder $query) => $query
                    ->where('estado_ins', '!=', EstadoInscripcion::Anulado->value),
            ])
            ->with('convenio')
            ->where('id_proceso', $proceso->id_pro)
            ->where('estado_cpr', EstadoCatalogo::Habilitado->value)
            ->get()
            ->filter(function (ConvenioProceso $convenioProceso): bool {
                if ((int) $convenioProceso->cupo_maximo_cpr < 1) {
                    return false;
                }

                return ((int) $convenioProceso->inscritos_activos_count / (int) $convenioProceso->cupo_maximo_cpr) >= 0.8;
            })
            ->sortByDesc(fn (ConvenioProceso $convenioProceso): float => (int) $convenioProceso->inscritos_activos_count / max((int) $convenioProceso->cupo_maximo_cpr, 1))
            ->take(3)
            ->map(function (ConvenioProceso $convenioProceso): array {
                return [
                    'tipo' => 'convenio',
                    'titulo' => $convenioProceso->convenio?->nombre_con ?? 'CONVENIO',
                    'detalle' => "{$convenioProceso->inscritos_activos_count} / {$convenioProceso->cupo_maximo_cpr} cupos utilizados",
                    'tono' => ((int) $convenioProceso->inscritos_activos_count >= (int) $convenioProceso->cupo_maximo_cpr) ? 'danger' : 'warning',
                ];
            });

        $alertas = collect();

        if ($this->estadisticas['pago_pendiente'] > 0) {
            $alertas->push([
                'tipo' => 'pago',
                'titulo' => 'Pagos por regularizar',
                'detalle' => $this->estadisticas['pago_pendiente'].' inscripciones con saldo pendiente o pago parcial.',
                'tono' => 'warning',
            ]);
        }

        if ($this->estadisticas['docs_observados'] > 0) {
            $alertas->push([
                'tipo' => 'expediente',
                'titulo' => 'Expedientes observados',
                'detalle' => $this->estadisticas['docs_observados'].' inscripciones requieren revisión documental.',
                'tono' => 'info',
            ]);
        }

        return $alertas
            ->merge($conveniosComprometidos)
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
                'procesoOferta.ofertaBase.sedeCarrera.carrera',
                'procesoOferta.turno',
                'convenioProceso.convenio',
            ])
            ->whereHas('procesoOferta', fn (Builder $query) => $query->where('id_proceso', $proceso->id_pro))
            ->latest('fecha_inscripcion_ins')
            ->limit(6)
            ->get();
    }
};
