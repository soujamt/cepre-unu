<?php

namespace App\Models;

use App\Enums\EstadoCatalogo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['codigo_sed', 'nombre_sed', 'id_ubigeo', 'direccion_sed', 'estado_sed'])]
#[Table('tbl_sede', key: 'id_sed')]
class Sede extends LegacyModel
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'estado_sed' => EstadoCatalogo::class,
        ]);
    }

    public function ubigeo(): BelongsTo
    {
        return $this->belongsTo(Ubigeo::class, 'id_ubigeo', 'id_ubi');
    }

    public function lugaresInscripcion(): HasMany
    {
        return $this->hasMany(LugarInscripcion::class, 'id_sede', 'id_sed');
    }

    public function sedeCarreras(): HasMany
    {
        return $this->hasMany(SedeCarrera::class, 'id_sede', 'id_sed');
    }
}
