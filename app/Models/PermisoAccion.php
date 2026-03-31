<?php

namespace App\Models;

use App\Enums\EstadoCatalogo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['id_permiso', 'id_accion', 'clave_peracc', 'estado_peracc'])]
#[Table('tbl_permiso_accion', key: 'id_peracc')]
class PermisoAccion extends LegacyModel
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'estado_peracc' => EstadoCatalogo::class,
        ]);
    }

    public function permiso(): BelongsTo
    {
        return $this->belongsTo(Permiso::class, 'id_permiso', 'id_permiso');
    }

    public function accion(): BelongsTo
    {
        return $this->belongsTo(Accion::class, 'id_accion', 'id_acc');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'tbl_rol_permiso_accion', 'id_peracc', 'id_rol', 'id_peracc', 'id_rol');
    }
}
