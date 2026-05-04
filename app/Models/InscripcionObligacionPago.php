<?php

namespace App\Models;

use App\Enums\EstadoObligacionPago;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['id_inscripcion', 'id_regla_pago', 'monto_total_iop', 'numero_cuotas_iop', 'estado_iop'])]
#[Table('tbl_inscripcion_obligacion_pago', key: 'id_iop')]
class InscripcionObligacionPago extends LegacyModel
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'estado_iop' => EstadoObligacionPago::class,
        ]);
    }

    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(Inscripcion::class, 'id_inscripcion', 'id_ins');
    }

    public function reglaPago(): BelongsTo
    {
        return $this->belongsTo(ReglaPago::class, 'id_regla_pago', 'id_rpa');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(InscripcionPago::class, 'id_inscripcion_obligacion_pago', 'id_iop');
    }
}
