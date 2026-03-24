<?php

namespace App\Models;

use App\Enums\EstadoImportacionBancoDetalle;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['id_importacion_banco', 'numero_documento_ibd', 'nombre_depositante_ibd', 'numero_operacion_ibd', 'fecha_pago_ibd', 'monto_ibd', 'texto_linea_ibd', 'estado_ibd'])]
#[Table('tbl_importacion_banco_detalle', key: 'id_ibd')]
class ImportacionBancoDetalle extends LegacyModel
{
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'fecha_pago_ibd' => 'date',
            'estado_ibd' => EstadoImportacionBancoDetalle::class,
        ];
    }

    public function importacionBanco(): BelongsTo
    {
        return $this->belongsTo(ImportacionBanco::class, 'id_importacion_banco', 'id_iba');
    }

    public function inscripcionPago(): HasOne
    {
        return $this->hasOne(InscripcionPago::class, 'id_importacion_banco_detalle', 'id_ibd');
    }
}
