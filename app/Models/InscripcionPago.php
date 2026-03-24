<?php

namespace App\Models;

use App\Enums\EstadoValidacionPago;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['id_inscripcion_obligacion_pago', 'id_importacion_banco_detalle', 'numero_cuota_ipa', 'monto_ipa', 'fecha_pago_ipa', 'numero_operacion_ipa', 'voucher_url_ipa', 'estado_validacion_ipa', 'observacion_validacion_ipa', 'validado_en', 'id_usuario_validador'])]
#[Table('tbl_inscripcion_pago', key: 'id_ipa')]
class InscripcionPago extends LegacyModel
{
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'fecha_pago_ipa' => 'date',
            'validado_en' => 'datetime',
            'estado_validacion_ipa' => EstadoValidacionPago::class,
        ];
    }

    public function obligacionPago(): BelongsTo
    {
        return $this->belongsTo(InscripcionObligacionPago::class, 'id_inscripcion_obligacion_pago', 'id_iop');
    }

    public function importacionBancoDetalle(): BelongsTo
    {
        return $this->belongsTo(ImportacionBancoDetalle::class, 'id_importacion_banco_detalle', 'id_ibd');
    }

    public function usuarioValidador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_validador', 'id_usu');
    }
}
