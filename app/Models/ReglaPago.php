<?php

namespace App\Models;

use App\Enums\EstadoCatalogo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['id_concepto_pago', 'id_proceso', 'id_modalidad_admision', 'monto_total_rpa', 'monto_minimo_primera_cuota_rpa', 'numero_maximo_cuotas_rpa', 'vigente_desde_rpa', 'vigente_hasta_rpa', 'estado_rpa'])]
#[Table('tbl_regla_pago', key: 'id_rpa')]
class ReglaPago extends LegacyModel
{
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'vigente_desde_rpa' => 'date',
            'vigente_hasta_rpa' => 'date',
            'estado_rpa' => EstadoCatalogo::class,
        ];
    }

    public function conceptoPago(): BelongsTo
    {
        return $this->belongsTo(ConceptoPago::class, 'id_concepto_pago', 'id_cpg');
    }

    public function proceso(): BelongsTo
    {
        return $this->belongsTo(Proceso::class, 'id_proceso', 'id_pro');
    }

    public function modalidadAdmision(): BelongsTo
    {
        return $this->belongsTo(ModalidadAdmision::class, 'id_modalidad_admision', 'id_mad');
    }

    public function obligacionesPago(): HasMany
    {
        return $this->hasMany(InscripcionObligacionPago::class, 'id_regla_pago', 'id_rpa');
    }
}
