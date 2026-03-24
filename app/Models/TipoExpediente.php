<?php

namespace App\Models;

use App\Enums\EstadoCatalogo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['codigo_tex', 'nombre_tex', 'descripcion_tex', 'obligatorio_tex', 'estado_tex'])]
#[Table('tbl_tipo_expediente', key: 'id_tex')]
class TipoExpediente extends LegacyModel
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'obligatorio_tex' => 'boolean',
            'estado_tex' => EstadoCatalogo::class,
        ]);
    }

    public function requisitos(): HasMany
    {
        return $this->hasMany(RequisitoExpediente::class, 'id_tipo_expediente', 'id_tex');
    }

    public function inscripcionesExpediente(): HasMany
    {
        return $this->hasMany(InscripcionExpediente::class, 'id_tipo_expediente', 'id_tex');
    }
}
