<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['id_rol', 'id_accion'])]
#[Table('tbl_rol_accion', key: 'id_rac')]
class RolAccion extends LegacyModel
{
    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'id_rol', 'id_rol');
    }

    public function accion(): BelongsTo
    {
        return $this->belongsTo(Accion::class, 'id_accion', 'id_acc');
    }
}
