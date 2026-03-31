<?php

namespace App\Models;

use App\Enums\EstadoCatalogo;
use App\Enums\EstadoUsuario;
use App\Models\Concerns\HasLegacyAuditRelations;
use Database\Factories\UsuarioFactory;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

#[Table('tbl_usuario', key: 'id_usu')]
#[Fillable(['usuario_usu', 'clave_usu', 'correo_usu', 'nombres_usu', 'estado_usu', 'remember_token'])]
#[Hidden(['clave_usu', 'remember_token'])]
class Usuario extends Authenticatable
{
    /** @use HasFactory<UsuarioFactory> */
    use CanResetPassword;

    use HasFactory;
    use HasLegacyAuditRelations;
    use Notifiable;
    use SoftDeletes;

    public const CREATED_AT = 'creado_en';

    public const UPDATED_AT = 'actualizado_en';

    public const DELETED_AT = 'eliminado_en';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'clave_usu' => 'hashed',
            'estado_usu' => EstadoUsuario::class,
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
            'eliminado_en' => 'datetime',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->clave_usu;
    }

    public function getEmailForPasswordReset(): string
    {
        return $this->correo_usu ?? '';
    }

    public function getNameAttribute(): string
    {
        return $this->nombres_usu;
    }

    public function getEmailAttribute(): ?string
    {
        return $this->correo_usu;
    }

    public function getAvatarUrlAttribute(): string
    {
        $seed = $this->correo_usu ?: (string) ($this->getKey() ?: $this->nombres_usu);

        return sprintf(
            'https://avatars.laravel.cloud/%s?vibe=%s',
            rawurlencode($seed),
            'daybreak',
        );
    }

    public function usuarioRoles(): HasMany
    {
        return $this->hasMany(UsuarioRol::class, 'id_usuario', 'id_usu');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'tbl_usuario_rol', 'id_usuario', 'id_rol', 'id_usu', 'id_rol');
    }

    public function permisoAcciones(): Collection
    {
        if ($this->esAdministrador()) {
            return PermisoAccion::query()
                ->where('estado_peracc', EstadoCatalogo::Habilitado->value)
                ->whereHas('permiso', function ($query): void {
                    $query->where('estado_permiso', EstadoCatalogo::Habilitado->value);
                })
                ->whereHas('accion', function ($query): void {
                    $query->where('estado_accion', EstadoCatalogo::Habilitado->value);
                })
                ->get()
                ->unique('id_peracc')
                ->values();
        }

        return $this->roles()
            ->where('estado_rol', EstadoCatalogo::Habilitado->value)
            ->with([
                'rolPermisoAcciones' => function ($query): void {
                    $query->whereHas('permisoAccion', function ($permissionActionQuery): void {
                        $permissionActionQuery
                            ->where('estado_peracc', EstadoCatalogo::Habilitado->value)
                            ->whereHas('permiso', function ($permissionQuery): void {
                                $permissionQuery->where('estado_permiso', EstadoCatalogo::Habilitado->value);
                            })
                            ->whereHas('accion', function ($actionQuery): void {
                                $actionQuery->where('estado_accion', EstadoCatalogo::Habilitado->value);
                            });
                    })->with('permisoAccion');
                },
            ])
            ->get()
            ->pluck('rolPermisoAcciones')
            ->flatten()
            ->pluck('permisoAccion')
            ->filter()
            ->unique('id_peracc')
            ->values();
    }

    public function hasPermisoAccion(string $clave): bool
    {
        if ($this->esAdministrador()) {
            return true;
        }

        return $this->roles()
            ->where('estado_rol', EstadoCatalogo::Habilitado->value)
            ->whereHas('rolPermisoAcciones', function ($rolePermissionQuery) use ($clave): void {
                $rolePermissionQuery->whereHas('permisoAccion', function ($permissionActionQuery) use ($clave): void {
                    $permissionActionQuery
                        ->where('clave_peracc', $clave)
                        ->where('estado_peracc', EstadoCatalogo::Habilitado->value)
                        ->whereHas('permiso', function ($permissionQuery): void {
                            $permissionQuery->where('estado_permiso', EstadoCatalogo::Habilitado->value);
                        })
                        ->whereHas('accion', function ($actionQuery): void {
                            $actionQuery->where('estado_accion', EstadoCatalogo::Habilitado->value);
                        });
                });
            })
            ->exists();
    }

    public function esAdministrador(): bool
    {
        return $this->roles()
            ->where('estado_rol', EstadoCatalogo::Habilitado->value)
            ->where('nombre_rol', 'ADMINISTRADOR')
            ->exists();
    }

    public function pagosValidados(): HasMany
    {
        return $this->hasMany(InscripcionPago::class, 'id_usuario_validador', 'id_usu');
    }

    public function expedientesRevisados(): HasMany
    {
        return $this->hasMany(InscripcionExpediente::class, 'id_usuario_revisor', 'id_usu');
    }
}
