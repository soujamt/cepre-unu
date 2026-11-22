<?php

namespace App\Models;

use App\Enums\EstadoInscripcion;
use App\Enums\EstadoObligacionPago;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['codigo_ins', 'token_verificacion_ins', 'id_persona', 'id_proceso', 'id_sede', 'id_modalidad_admision', 'id_carrera', 'id_turno', 'id_proceso_oferta', 'id_proceso_aula', 'fecha_inscripcion_ins', 'estado_ins', 'origen_ins', 'requiere_revision_ins', 'revisado_en', 'id_usuario_revisor', 'motivo_anulacion_ins', 'anio_graduacion_ins', 'id_pais_colegio', 'id_colegio', 'nombre_colegio_extranjero_ins', 'id_tipo_colegio', 'veces_postula_unu_ins', 'veces_postula_otras_ins', 'observacion_ins'])]
#[Table('tbl_inscripcion', key: 'id_ins')]
class Inscripcion extends LegacyModel
{
    /**
     * Cada inscripción tiene un código aleatorio para verificar su carnet.
     */
    protected static function booted(): void
    {
        static::creating(function (Inscripcion $inscripcion): void {
            $inscripcion->token_verificacion_ins ??= Str::random(40);
        });
    }

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'fecha_inscripcion_ins' => 'datetime',
            'estado_ins' => EstadoInscripcion::class,
            'requiere_revision_ins' => 'boolean',
            'revisado_en' => 'datetime',
        ];
    }

    /**
     * Hecha en el portal y aún no aprobada por el personal.
     */
    public function enRevision(): bool
    {
        return (bool) $this->requiere_revision_ins && $this->estado_ins !== EstadoInscripcion::Anulado;
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

    /**
     * Cambios de sede, carrera o turno hechos después de registrarla.
     */
    public function cambios(): HasMany
    {
        return $this->hasMany(InscripcionCambio::class, 'id_inscripcion', 'id_ins');
    }

    public function procesoAula(): BelongsTo
    {
        return $this->belongsTo(ProcesoAula::class, 'id_proceso_aula', 'id_pau');
    }

    /**
     * Estado del pago de la inscripción según sus obligaciones: SIN REGLA, EXONERADO,
     * PAGADO, PARCIAL o PENDIENTE (requiere las obligaciones cargadas).
     */
    public function estadoPagoGeneral(): string
    {
        $obligaciones = $this->obligacionesPago;

        return match (true) {
            $obligaciones->isEmpty() => 'SIN REGLA',
            $obligaciones->every(fn ($obligacion) => $obligacion->estado_iop === EstadoObligacionPago::Exonerado) => 'EXONERADO',
            $obligaciones->every(fn ($obligacion) => in_array($obligacion->estado_iop, [EstadoObligacionPago::Pagado, EstadoObligacionPago::Exonerado], true)) => 'PAGADO',
            $obligaciones->contains(fn ($obligacion) => $obligacion->estado_iop === EstadoObligacionPago::Parcial) => 'PARCIAL',
            default => 'PENDIENTE',
        };
    }

    /**
     * Apellidos y nombres del postulante.
     */
    public function nombrePostulante(): string
    {
        $persona = $this->persona;

        return $persona === null ? 'SIN POSTULANTE' : trim(implode(' ', array_filter([
            $persona->apellido_paterno_per,
            $persona->apellido_materno_per,
            $persona->apellido_casada_per,
            $persona->nombres_per,
        ])));
    }
}
