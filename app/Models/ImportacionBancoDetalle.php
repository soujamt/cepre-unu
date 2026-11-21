<?php

namespace App\Models;

use App\Enums\EstadoImportacionBancoDetalle;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['id_importacion_banco', 'origen_ibd', 'numero_documento_ibd', 'nombre_depositante_ibd', 'referencia_ibd', 'numero_operacion_raw_ibd', 'numero_operacion_visible_ibd', 'numero_operacion_ibd', 'codigo_canal_ibd', 'codigo_oficina_ibd', 'fecha_pago_ibd', 'monto_ibd', 'ubicacion_ibd', 'texto_linea_ibd', 'estado_ibd', 'observacion_ibd'])]
#[Table('tbl_importacion_banco_detalle', key: 'id_ibd')]
class ImportacionBancoDetalle extends LegacyModel
{
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'fecha_pago_ibd' => 'date',
            'monto_ibd' => 'decimal:2',
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

    /**
     * Un pago registrado a mano se puede corregir o eliminar mientras siga libre; los del
     * TXT y los ya usados en una inscripción solo admiten observación.
     */
    public function esEditableAMano(): bool
    {
        return $this->origen_ibd === 'MANUAL'
            && $this->estado_ibd === EstadoImportacionBancoDetalle::Pendiente
            && ! $this->inscripcionPago()->exists();
    }
}
