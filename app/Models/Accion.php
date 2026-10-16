<?php

namespace App\Models;

use App\Enums\EstadoCatalogo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre_accion', 'clave_accion', 'estado_accion'])]
#[Table('tbl_accion', key: 'id_acc')]
class Accion extends LegacyModel
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'estado_accion' => EstadoCatalogo::class,
        ]);
    }

    public function permisoAcciones(): HasMany
    {
        return $this->hasMany(PermisoAccion::class, 'id_accion', 'id_acc');
    }

    public function permisos(): BelongsToMany
    {
        return $this->belongsToMany(Permiso::class, 'tbl_permiso_accion', 'id_accion', 'id_permiso', 'id_acc', 'id_permiso');
    }

    public function scopeHabilitadas(Builder $query): void
    {
        $query->where('estado_accion', EstadoCatalogo::Habilitado->value);
    }
}
