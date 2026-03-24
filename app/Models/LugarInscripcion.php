<?php

namespace App\Models;

use App\Enums\EstadoCatalogo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['codigo_lin', 'nombre_lin', 'id_sede', 'estado_lin'])]
#[Table('tbl_lugar_inscripcion', key: 'id_lin')]
class LugarInscripcion extends LegacyModel
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'estado_lin' => EstadoCatalogo::class,
        ]);
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'id_sede', 'id_sed');
    }

    public function ofertasBase(): HasMany
    {
        return $this->hasMany(OfertaBase::class, 'id_lugar_inscripcion', 'id_lin');
    }
}
