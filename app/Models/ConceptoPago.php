<?php

namespace App\Models;

use App\Enums\EstadoCatalogo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['codigo_cpg', 'nombre_cpg', 'descripcion_cpg', 'permite_cuotas_cpg', 'estado_cpg'])]
#[Table('tbl_concepto_pago', key: 'id_cpg')]
class ConceptoPago extends LegacyModel
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'permite_cuotas_cpg' => 'boolean',
            'estado_cpg' => EstadoCatalogo::class,
        ]);
    }

    public function reglasPago(): HasMany
    {
        return $this->hasMany(ReglaPago::class, 'id_concepto_pago', 'id_cpg');
    }
}
