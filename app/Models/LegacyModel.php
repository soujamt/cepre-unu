<?php

namespace App\Models;

use App\Models\Concerns\HasLegacyAuditRelations;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

abstract class LegacyModel extends Model
{
    use HasFactory;
    use HasLegacyAuditRelations;
    use SoftDeletes;

    public const CREATED_AT = 'creado_en';

    public const UPDATED_AT = 'actualizado_en';

    public const DELETED_AT = 'eliminado_en';

    protected function casts(): array
    {
        return [
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
            'eliminado_en' => 'datetime',
        ];
    }

    /**
     * @return class-string<Collection<int, static>>
     */
    public function resolveCollectionFromAttribute(): string
    {
        return Collection::class;
    }
}
