<?php

namespace App\Models;

use App\Enums\EstadoCatalogo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['id_ubi', 'codigo_departamento_ubi', 'codigo_provincia_ubi', 'codigo_distrito_ubi', 'nombre_departamento_ubi', 'nombre_provincia_ubi', 'nombre_distrito_ubi', 'estado_ubi'])]
#[Table('tbl_ubigeo', key: 'id_ubi', keyType: 'string', incrementing: false)]
class Ubigeo extends LegacyModel
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'estado_ubi' => EstadoCatalogo::class,
        ]);
    }

    /**
     * Texto con el que se muestra en los buscadores: "250101 | UCAYALI / CORONEL PORTILLO / CALLERIA".
     */
    public function etiqueta(): string
    {
        return "{$this->id_ubi} | {$this->nombre_departamento_ubi} / {$this->nombre_provincia_ubi} / {$this->nombre_distrito_ubi}";
    }

    public function sedes(): HasMany
    {
        return $this->hasMany(Sede::class, 'id_ubigeo', 'id_ubi');
    }

    public function colegios(): HasMany
    {
        return $this->hasMany(Colegio::class, 'id_ubigeo', 'id_ubi');
    }

    public function personasNacimiento(): HasMany
    {
        return $this->hasMany(Persona::class, 'id_ubigeo_nacimiento', 'id_ubi');
    }

    public function personasDomicilio(): HasMany
    {
        return $this->hasMany(Persona::class, 'id_ubigeo_domicilio', 'id_ubi');
    }
}
