<?php

namespace App\Models;

use App\Enums\EstadoRevisionExpediente;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['id_inscripcion', 'id_tipo_expediente', 'archivo_url_iex', 'nombre_archivo_iex', 'estado_revision_iex', 'observacion_iex', 'revisado_en', 'id_usuario_revisor'])]
#[Table('tbl_inscripcion_expediente', key: 'id_iex')]
class InscripcionExpediente extends LegacyModel
{
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'revisado_en' => 'datetime',
            'estado_revision_iex' => EstadoRevisionExpediente::class,
        ];
    }

    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(Inscripcion::class, 'id_inscripcion', 'id_ins');
    }

    public function tipoExpediente(): BelongsTo
    {
        return $this->belongsTo(TipoExpediente::class, 'id_tipo_expediente', 'id_tex');
    }

    public function usuarioRevisor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_revisor', 'id_usu');
    }
}
