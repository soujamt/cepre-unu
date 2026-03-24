<?php

namespace App\Models\Concerns;

use App\Models\Usuario;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasLegacyAuditRelations
{
    public function usuarioCreador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_creador', 'id_usu');
    }

    public function usuarioActualizador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_actualizador', 'id_usu');
    }

    public function usuarioEliminador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_eliminador', 'id_usu');
    }
}
