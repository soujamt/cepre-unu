<?php

namespace App\Models;

use App\Enums\EstadoCatalogo;
use App\Enums\SexoPersona;
use App\Enums\TipoLenguaMaternaPersona;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['id_tipo_documento', 'numero_documento_per', 'solo_un_apellido_per', 'apellido_paterno_per', 'apellido_materno_per', 'apellido_casada_per', 'nombres_per', 'fecha_nacimiento_per', 'sexo_per', 'id_estado_civil', 'id_pais_nacimiento', 'id_nacionalidad', 'id_ubigeo_nacimiento', 'id_ubigeo_domicilio', 'direccion_per', 'celular_per', 'telefono_per', 'correo_electronico_per', 'discapacidad_per', 'id_tipo_discapacidad', 'tipo_lengua_materna_per', 'id_identidad_etnica', 'id_lengua_nativa', 'id_lengua_extranjera', 'observacion_per', 'estado_per'])]
#[Table('tbl_persona', key: 'id_per')]
class Persona extends LegacyModel
{
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'fecha_nacimiento_per' => 'date',
            'solo_un_apellido_per' => 'boolean',
            'sexo_per' => SexoPersona::class,
            'discapacidad_per' => 'boolean',
            'tipo_lengua_materna_per' => TipoLenguaMaternaPersona::class,
            'estado_per' => EstadoCatalogo::class,
        ];
    }

    public function tipoDocumento(): BelongsTo
    {
        return $this->belongsTo(TipoDocumento::class, 'id_tipo_documento', 'id_tdo');
    }

    public function paisNacimiento(): BelongsTo
    {
        return $this->belongsTo(Pais::class, 'id_pais_nacimiento', 'id_pai');
    }

    public function estadoCivil(): BelongsTo
    {
        return $this->belongsTo(EstadoCivil::class, 'id_estado_civil', 'id_eci');
    }

    public function nacionalidad(): BelongsTo
    {
        return $this->belongsTo(Nacionalidad::class, 'id_nacionalidad', 'id_nac');
    }

    public function ubigeoNacimiento(): BelongsTo
    {
        return $this->belongsTo(Ubigeo::class, 'id_ubigeo_nacimiento', 'id_ubi');
    }

    public function ubigeoDomicilio(): BelongsTo
    {
        return $this->belongsTo(Ubigeo::class, 'id_ubigeo_domicilio', 'id_ubi');
    }

    public function tipoDiscapacidad(): BelongsTo
    {
        return $this->belongsTo(TipoDiscapacidad::class, 'id_tipo_discapacidad', 'id_tdi');
    }

    public function identidadEtnica(): BelongsTo
    {
        return $this->belongsTo(IdentidadEtnica::class, 'id_identidad_etnica', 'id_iet');
    }

    public function lenguaNativa(): BelongsTo
    {
        return $this->belongsTo(LenguaNativa::class, 'id_lengua_nativa', 'id_lna');
    }

    public function lenguaExtranjera(): BelongsTo
    {
        return $this->belongsTo(LenguaExtranjera::class, 'id_lengua_extranjera', 'id_lex');
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class, 'id_persona', 'id_per');
    }
}
