<?php

namespace App\Models;

use App\Enums\EstadoCatalogo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['id_proceso', 'id_tipo_expediente', 'obligatorio_rex', 'estado_rex'])]
#[Table('tbl_requisito_expediente', key: 'id_rex')]
class RequisitoExpediente extends LegacyModel
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'obligatorio_rex' => 'boolean',
            'estado_rex' => EstadoCatalogo::class,
        ]);
    }

    public function proceso(): BelongsTo
    {
        return $this->belongsTo(Proceso::class, 'id_proceso', 'id_pro');
    }

    public function tipoExpediente(): BelongsTo
    {
        return $this->belongsTo(TipoExpediente::class, 'id_tipo_expediente', 'id_tex');
    }
}
