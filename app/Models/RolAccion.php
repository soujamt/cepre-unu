<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['id_rol', 'id_peracc'])]
#[Table('tbl_rol_permiso_accion', key: 'id_rpa')]
class RolAccion extends LegacyModel
{
    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'id_rol', 'id_rol');
    }

    public function permisoAccion(): BelongsTo
    {
        return $this->belongsTo(PermisoAccion::class, 'id_peracc', 'id_peracc');
    }
}
