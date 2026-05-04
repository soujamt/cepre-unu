<?php

namespace App\Models\Concerns;

use App\Models\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait HasLegacyAuditRelations
{
    public static function bootHasLegacyAuditRelations(): void
    {
        static::creating(function (Model $model): void {
            $userId = self::authenticatedUserId();

            if ($userId === null) {
                return;
            }

            $model->setAttribute('id_usuario_creador', $model->getAttribute('id_usuario_creador') ?: $userId);
            $model->setAttribute('id_usuario_actualizador', $userId);
        });

        static::updating(function (Model $model): void {
            $userId = self::authenticatedUserId();

            if ($userId === null) {
                return;
            }

            $model->setAttribute('id_usuario_actualizador', $userId);
        });

        static::deleting(function (Model $model): void {
            $userId = self::authenticatedUserId();

            if ($userId === null) {
                return;
            }

            $model->forceFill([
                'id_usuario_eliminador' => $userId,
                'id_usuario_actualizador' => $userId,
            ])->saveQuietly();
        });
    }

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

    private static function authenticatedUserId(): ?int
    {
        $userId = Auth::id();

        return is_numeric($userId) ? (int) $userId : null;
    }
}