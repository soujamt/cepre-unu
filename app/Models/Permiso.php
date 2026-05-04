<?php

namespace App\Models;

use App\Enums\EstadoCatalogo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre_permiso', 'clave_permiso', 'estado_permiso'])]
#[Table('tbl_permiso', key: 'id_permiso')]
class Permiso extends LegacyModel
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'estado_permiso' => EstadoCatalogo::class,
        ]);
    }

    public function permisoAcciones(): HasMany
    {
        return $this->hasMany(PermisoAccion::class, 'id_permiso', 'id_permiso');
    }

    public function acciones(): BelongsToMany
    {
        return $this->belongsToMany(Accion::class, 'tbl_permiso_accion', 'id_permiso', 'id_accion', 'id_permiso', 'id_acc');
    }

    public function scopeBuscar(Builder $query, ?string $search): void
    {
        if (blank($search)) {
            return;
        }

        $query->where(function (Builder $innerQuery) use ($search): void {
            $innerQuery->where('clave_permiso', 'like', "%{$search}%")
                ->orWhere('nombre_permiso', 'like', "%{$search}%");
        });
    }

    public function scopeConEstados(Builder $query, array $estados): void
    {
        if ($estados === []) {
            return;
        }

        $query->whereIn('estado_permiso', $estados);
    }
}
