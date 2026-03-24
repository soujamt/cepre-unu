<?php

namespace App\Models;

use App\Enums\EstadoImportacionBanco;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre_archivo_iba', 'fecha_importacion_iba', 'total_registros_iba', 'total_monto_iba', 'estado_iba', 'observacion_iba'])]
#[Table('tbl_importacion_banco', key: 'id_iba')]
class ImportacionBanco extends LegacyModel
{
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'fecha_importacion_iba' => 'datetime',
            'estado_iba' => EstadoImportacionBanco::class,
        ];
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(ImportacionBancoDetalle::class, 'id_importacion_banco', 'id_iba');
    }
}
