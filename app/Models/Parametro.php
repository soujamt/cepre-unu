<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;

/**
 * Parámetros generales del sistema guardados como clave y valor.
 */
#[Fillable(['clave_par', 'valor_par'])]
#[Table('tbl_parametro', key: 'id_par')]
class Parametro extends LegacyModel
{
    public static function valor(string $clave, ?string $porDefecto = null): ?string
    {
        return static::query()->where('clave_par', $clave)->value('valor_par') ?? $porDefecto;
    }

    public static function guardar(string $clave, ?string $valor): void
    {
        static::query()->updateOrCreate(['clave_par' => $clave], ['valor_par' => $valor]);
    }
}
