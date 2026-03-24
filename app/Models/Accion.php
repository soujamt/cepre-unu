<?php

namespace App\Models;

use App\Enums\EstadoCatalogo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['codigo_acc', 'nombre_acc', 'modulo_acc', 'descripcion_acc', 'estado_acc'])]
#[Table('tbl_accion', key: 'id_acc')]
class Accion extends LegacyModel
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'estado_acc' => EstadoCatalogo::class,
        ]);
    }

    public function rolAcciones(): HasMany
    {
        return $this->hasMany(RolAccion::class, 'id_accion', 'id_acc');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'tbl_rol_accion', 'id_accion', 'id_rol', 'id_acc', 'id_rol');
    }
}
