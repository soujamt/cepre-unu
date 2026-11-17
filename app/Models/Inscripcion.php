<?php

namespace App\Models;

use App\Enums\EstadoInscripcion;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['codigo_ins', 'id_persona', 'id_proceso', 'id_sede', 'id_modalidad_admision', 'id_carrera', 'id_turno', 'id_proceso_oferta', 'id_proceso_aula', 'fecha_inscripcion_ins', 'estado_ins', 'motivo_anulacion_ins', 'anio_graduacion_ins', 'id_pais_colegio', 'id_colegio', 'nombre_colegio_extranjero_ins', 'id_tipo_colegio', 'veces_postula_unu_ins', 'veces_postula_otras_ins', 'observacion_ins'])]
#[Table('tbl_inscripcion', key: 'id_ins')]
class Inscripcion extends LegacyModel
{
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'fecha_inscripcion_ins' => 'datetime',
            'estado_ins' => EstadoInscripcion::class,
        ];
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'id_persona', 'id_per');
    }

    public function proceso(): BelongsTo
    {
        return $this->belongsTo(Proceso::class, 'id_proceso', 'id_pro');
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'id_sede', 'id_sed');
    }

    public function modalidadAdmision(): BelongsTo
    {
        return $this->belongsTo(ModalidadAdmision::class, 'id_modalidad_admision', 'id_mad');
    }

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'id_carrera', 'id_car');
    }

    public function turno(): BelongsTo
    {
        return $this->belongsTo(Turno::class, 'id_turno', 'id_tur');
    }

    /**
     * Oferta del modelo anterior; solo se conserva como histórico.
     */
    public function procesoOferta(): BelongsTo
    {
        return $this->belongsTo(ProcesoOferta::class, 'id_proceso_oferta', 'id_pof');
    }

    public function paisColegio(): BelongsTo
    {
        return $this->belongsTo(Pais::class, 'id_pais_colegio', 'id_pai');
    }

    public function colegio(): BelongsTo
    {
        return $this->belongsTo(Colegio::class, 'id_colegio', 'id_col');
    }

    public function tipoColegio(): BelongsTo
    {
        return $this->belongsTo(TipoColegio::class, 'id_tipo_colegio', 'id_tco');
    }

    public function obligacionesPago(): HasMany
    {
        return $this->hasMany(InscripcionObligacionPago::class, 'id_inscripcion', 'id_ins');
    }

    public function procesoAula(): BelongsTo
    {
        return $this->belongsTo(ProcesoAula::class, 'id_proceso_aula', 'id_pau');
    }
}
