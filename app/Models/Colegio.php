<?php

namespace App\Models;

use App\Enums\EstadoCatalogo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['id_col', 'codigo_modular_col', 'nombre_col', 'id_tipo_colegio', 'id_ubigeo', 'direccion_col', 'estado_col'])]
#[Table('tbl_colegio', key: 'id_col', keyType: 'string', incrementing: false)]
class Colegio extends LegacyModel
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'estado_col' => EstadoCatalogo::class,
        ]);
    }

    public function tipoColegio(): BelongsTo
    {
        return $this->belongsTo(TipoColegio::class, 'id_tipo_colegio', 'id_tco');
    }

    public function ubigeo(): BelongsTo
    {
        return $this->belongsTo(Ubigeo::class, 'id_ubigeo', 'id_ubi');
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class, 'id_colegio', 'id_col');
    }
}
