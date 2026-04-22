<?php

namespace App\Models;

use App\Enums\EstadoCatalogo;
use App\Enums\TipoBeneficioConvenio;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['codigo_con', 'nombre_con', 'descripcion_con', 'tipo_beneficio_con', 'monto_descuento_con', 'porcentaje_descuento_con', 'estado_con'])]
#[Table('tbl_convenio', key: 'id_con')]
class Convenio extends LegacyModel
{
    public static function generarCodigoCorrelativo(?CarbonInterface $fecha = null): string
    {
        $fechaReferencia = $fecha ?? now();
        $prefijo = $fechaReferencia->format('y');

        $ultimoCodigo = static::query()
            ->withTrashed()
            ->lockForUpdate()
            ->where('codigo_con', 'like', "{$prefijo}%")
            ->orderByDesc('codigo_con')
            ->value('codigo_con');

        $correlativo = $ultimoCodigo !== null ? ((int) substr($ultimoCodigo, 2)) + 1 : 1;

        return $prefijo.str_pad((string) $correlativo, 4, '0', STR_PAD_LEFT);
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tipo_beneficio_con' => TipoBeneficioConvenio::class,
            'estado_con' => EstadoCatalogo::class,
        ]);
    }

    public function conveniosProceso(): HasMany
    {
        return $this->hasMany(ConvenioProceso::class, 'id_convenio', 'id_con');
    }
}
