<?php

namespace App\Models;

use App\Enums\EstadoCatalogo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre_rol', 'descripcion_rol', 'estado_rol'])]
#[Table('tbl_rol', key: 'id_rol')]
class Rol extends LegacyModel
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'estado_rol' => EstadoCatalogo::class,
        ]);
    }

    public function usuarioRoles(): HasMany
    {
        return $this->hasMany(UsuarioRol::class, 'id_rol', 'id_rol');
    }

    public function rolAcciones(): HasMany
    {
        return $this->hasMany(RolAccion::class, 'id_rol', 'id_rol');
    }

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(Usuario::class, 'tbl_usuario_rol', 'id_rol', 'id_usuario', 'id_rol', 'id_usu');
    }

    public function acciones(): BelongsToMany
    {
        return $this->belongsToMany(Accion::class, 'tbl_rol_accion', 'id_rol', 'id_accion', 'id_rol', 'id_acc');
    }
}
