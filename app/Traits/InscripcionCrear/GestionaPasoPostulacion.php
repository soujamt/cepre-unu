<?php

namespace App\Traits\InscripcionCrear;

use App\Enums\EstadoCatalogo;
use App\Enums\EstadoInscripcion;
use App\Enums\EstadoProceso;
use App\Models\ConvenioProceso;
use App\Models\Inscripcion;
use App\Models\Proceso;
use App\Models\ProcesoOferta;
use App\Support\AsignadorAulaInscripcion;
use App\Support\GeneradorCodigoInscripcion;
use App\Support\ProcesoInscripcionResolver;
use App\Support\ValidadorVigenciaInscripcion;
use Closure;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use RuntimeException;
use Throwable;

/**
 * Paso 3 del asistente de inscripción: proceso, modalidad, oferta y convenio.
 *
 * Lo usa el componente pages::inscripciones.inscripcion-crear, que declara
 * el estado (formularios y propiedades) sobre el que trabajan estos métodos.
 */
trait GestionaPasoPostulacion
{
    private function cargarPostulacionExistente(Inscripcion $inscripcion): void
    {
        $procesoOferta = $inscripcion->procesoOferta;

        $this->postulacionForm = [
            'id_modalidad_admision' => (string) ($procesoOferta?->ofertaBase?->modalidadLugarInscripcion?->id_modalidad_admision ?? ''),
            'id_lugar_inscripcion' => (string) ($procesoOferta?->ofertaBase?->modalidadLugarInscripcion?->id_lugar_inscripcion ?? ''),
            'id_turno' => (string) ($procesoOferta?->id_turno ?? ''),
            'id_proceso_oferta' => (string) ($procesoOferta?->id_pof ?? ''),
            'id_convenio_proceso' => (string) ($inscripcion->id_convenio_proceso ?? ''),
        ];
    }

    public function updatedPostulacionFormIdModalidadAdmision(): void
    {
        $this->postulacionForm['id_lugar_inscripcion'] = '';
        $this->postulacionForm['id_turno'] = '';
        $this->postulacionForm['id_proceso_oferta'] = '';
        $this->postulacionForm['id_convenio_proceso'] = '';
    }

    public function updatedPostulacionFormIdLugarInscripcion(): void
    {
        $this->postulacionForm['id_turno'] = '';
        $this->postulacionForm['id_proceso_oferta'] = '';
        $this->postulacionForm['id_convenio_proceso'] = '';
    }

    public function updatedPostulacionFormIdTurno(): void
    {
        $this->postulacionForm['id_proceso_oferta'] = '';
        $this->postulacionForm['id_convenio_proceso'] = '';
    }

    /**
     * @return array{disponible:bool,mensaje:string,proceso:?Proceso}
     */
    #[Computed]
    public function contextoProcesoPostulacion(): array
    {
        return $this->contextoProcesoPostulacionCache
            ?? $this->refrescarContextoProcesoPostulacion();
    }

    #[Computed]
    public function procesoPostulacionActivo(): ?Proceso
    {
        return $this->contextoProcesoPostulacion()['proceso'];
    }

    #[Computed]
    public function inicioInscripcionBloqueado(): bool
    {
        return $this->inscripcionId === null
            && ! $this->contextoProcesoPostulacion()['disponible'];
    }

    #[Computed]
    public function ofertasProcesoDisponibles(): EloquentCollection
    {
        $proceso = $this->procesoPostulacionActivo();

        if ($proceso === null) {
            return new EloquentCollection;
        }

        return ProcesoOferta::query()
            ->with([
                'proceso',
                'ofertaBase.modalidadLugarInscripcion.modalidadAdmision',
                'ofertaBase.modalidadLugarInscripcion.lugarInscripcion.sede',
                'ofertaBase.sedeCarrera.sede',
                'ofertaBase.sedeCarrera.carrera',
                'turno',
            ])
            ->where('id_proceso', $proceso->id_pro)
            ->where('estado_pof', EstadoProceso::Habilitado->value)
            ->get();
    }

    #[Computed]
    public function modalidadesPostulacionDisponibles(): Collection
    {
        return $this->ofertasProcesoDisponibles()
            ->map(function (ProcesoOferta $oferta): array {
                return [
                    'id' => (string) $oferta->ofertaBase?->modalidadLugarInscripcion?->modalidadAdmision?->id_mad,
                    'nombre' => $oferta->ofertaBase?->modalidadLugarInscripcion?->modalidadAdmision?->nombre_mad ?? 'SIN MODALIDAD',
                ];
            })
            ->filter(fn (array $modalidad): bool => filled($modalidad['id']))
            ->unique('id')
            ->sortBy('nombre')
            ->values();
    }

    #[Computed]
    public function lugaresPostulacionDisponibles(): Collection
    {
        return $this->ofertasProcesoDisponibles()
            ->filter(fn (ProcesoOferta $oferta): bool => $this->postulacionForm['id_modalidad_admision'] !== ''
                && (string) $oferta->ofertaBase?->modalidadLugarInscripcion?->id_modalidad_admision === (string) $this->postulacionForm['id_modalidad_admision'])
            ->map(function (ProcesoOferta $oferta): array {
                return [
                    'id' => (string) $oferta->ofertaBase?->modalidadLugarInscripcion?->lugarInscripcion?->id_lin,
                    'nombre' => $oferta->ofertaBase?->modalidadLugarInscripcion?->lugarInscripcion?->nombre_lin ?? 'SIN LUGAR',
                ];
            })
            ->filter(fn (array $lugar): bool => filled($lugar['id']))
            ->unique('id')
            ->sortBy('nombre')
            ->values();
    }

    #[Computed]
    public function turnosPostulacionDisponibles(): Collection
    {
        return $this->ofertasProcesoDisponibles()
            ->filter(function (ProcesoOferta $oferta): bool {
                return $this->postulacionForm['id_modalidad_admision'] !== ''
                    && $this->postulacionForm['id_lugar_inscripcion'] !== ''
                    && (string) $oferta->ofertaBase?->modalidadLugarInscripcion?->id_modalidad_admision === (string) $this->postulacionForm['id_modalidad_admision']
                    && (string) $oferta->ofertaBase?->modalidadLugarInscripcion?->id_lugar_inscripcion === (string) $this->postulacionForm['id_lugar_inscripcion'];
            })
            ->map(function (ProcesoOferta $oferta): array {
                return [
                    'id' => (string) $oferta->turno?->id_tur,
                    'nombre' => $oferta->turno?->nombre_tur ?? 'SIN TURNO',
                ];
            })
            ->filter(fn (array $turno): bool => filled($turno['id']))
            ->unique('id')
            ->sortBy('nombre')
            ->values();
    }

    #[Computed]
    public function procesoOfertasPostulacionDisponibles(): EloquentCollection
    {
        return $this->ofertasProcesoDisponibles()
            ->filter(function (ProcesoOferta $oferta): bool {
                return $this->postulacionForm['id_modalidad_admision'] !== ''
                    && $this->postulacionForm['id_lugar_inscripcion'] !== ''
                    && $this->postulacionForm['id_turno'] !== ''
                    && (string) $oferta->ofertaBase?->modalidadLugarInscripcion?->id_modalidad_admision === (string) $this->postulacionForm['id_modalidad_admision']
                    && (string) $oferta->ofertaBase?->modalidadLugarInscripcion?->id_lugar_inscripcion === (string) $this->postulacionForm['id_lugar_inscripcion']
                    && (string) $oferta->id_turno === (string) $this->postulacionForm['id_turno'];
            })
            ->values();
    }

    #[Computed]
    public function conveniosPostulacionDisponibles(): EloquentCollection
    {
        $proceso = $this->procesoPostulacionActivo();

        if ($proceso === null) {
            return new EloquentCollection;
        }

        $fechaReferencia = now()->toDateString();

        return ConvenioProceso::query()
            ->with('convenio')
            ->where('id_proceso', $proceso->id_pro)
            ->where('estado_cpr', EstadoCatalogo::Habilitado->value)
            ->whereDate('fecha_inicio_cpr', '<=', $fechaReferencia)
            ->whereDate('fecha_fin_cpr', '>=', $fechaReferencia)
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rulesPasoPostulacion(): array
    {
        return [
            'postulacionForm.id_modalidad_admision' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $valido = $this->modalidadesPostulacionDisponibles()
                        ->contains(fn (array $modalidad): bool => $modalidad['id'] === (string) $value);

                    if (! $valido) {
                        $fail('La modalidad seleccionada no es valida para el proceso activo.');
                    }
                },
            ],
            'postulacionForm.id_lugar_inscripcion' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $valido = $this->lugaresPostulacionDisponibles()
                        ->contains(fn (array $lugar): bool => $lugar['id'] === (string) $value);

                    if (! $valido) {
                        $fail('El lugar de inscripcion seleccionado no es valido.');
                    }
                },
            ],
            'postulacionForm.id_turno' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $valido = $this->turnosPostulacionDisponibles()
                        ->contains(fn (array $turno): bool => $turno['id'] === (string) $value);

                    if (! $valido) {
                        $fail('El turno seleccionado no es valido.');
                    }
                },
            ],
            'postulacionForm.id_proceso_oferta' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $valido = $this->procesoOfertasPostulacionDisponibles()
                        ->contains(fn (ProcesoOferta $oferta): bool => (string) $oferta->id_pof === (string) $value);

                    if (! $valido) {
                        $fail('La oferta seleccionada no es valida para la postulacion.');
                    }
                },
            ],
            'postulacionForm.id_convenio_proceso' => [
                'nullable',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (blank($value)) {
                        return;
                    }

                    $convenioProceso = $this->conveniosPostulacionDisponibles()
                        ->first(fn (ConvenioProceso $convenioProceso): bool => (string) $convenioProceso->id_cpr === (string) $value);

                    if ($convenioProceso === null) {
                        $fail('El convenio seleccionado no es valido para el proceso activo.');

                        return;
                    }

                    $inscritosEnConvenio = Inscripcion::query()
                        ->where('id_convenio_proceso', $convenioProceso->id_cpr)
                        ->whereNot('estado_ins', EstadoInscripcion::Anulado->value)
                        ->when($this->inscripcionId !== null, fn ($query) => $query->whereNot('id_ins', $this->inscripcionId))
                        ->count();

                    if ($inscritosEnConvenio >= $convenioProceso->cupo_maximo_cpr) {
                        $fail("El convenio seleccionado ya alcanzo su cupo maximo ({$convenioProceso->cupo_maximo_cpr} inscritos).");
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messagesPasoPostulacion(): array
    {
        return [
            'postulacionForm.id_modalidad_admision.required' => 'La modalidad es obligatoria.',
            'postulacionForm.id_lugar_inscripcion.required' => 'El lugar de inscripcion es obligatorio.',
            'postulacionForm.id_turno.required' => 'El turno es obligatorio.',
            'postulacionForm.id_proceso_oferta.required' => 'La oferta es obligatoria.',
        ];
    }

    public function guardarPasoPostulacion(): void
    {
        $contextoProceso = $this->refrescarContextoProcesoPostulacion();

        if (! $contextoProceso['disponible']) {
            $this->dispatch('toast', message: $contextoProceso['mensaje'], type: 'warning');

            return;
        }

        // Verificar inscripcion duplicada en el mismo proceso (excepto edicion actual)
        $proceso = $contextoProceso['proceso'];

        $inscripcionExistente = Inscripcion::query()
            ->with(['procesoOferta.ofertaBase.sedeCarrera.carrera'])
            ->where('id_persona', $this->personaId)
            ->whereNotIn('estado_ins', [EstadoInscripcion::Anulado->value])
            ->whereHas('procesoOferta', fn ($q) => $q->where('id_proceso', $proceso->id_pro))
            ->when($this->inscripcionId, fn ($q) => $q->where('id_ins', '!=', $this->inscripcionId))
            ->first();

        if ($inscripcionExistente !== null) {
            $carrera = $inscripcionExistente->procesoOferta?->ofertaBase?->sedeCarrera?->carrera?->nombre_car ?? 'otra carrera';
            $codigo = (string) $inscripcionExistente->codigo_ins;

            $this->dispatch(
                'alert',
                type: 'question',
                title: 'Postulante ya inscrito',
                message: "El postulante ya tiene una inscripcion en este proceso para \"{$carrera}\" (codigo: {$codigo}). ¿Desea continuar de todas formas?",
                showCancel: true,
                showConfirm: true,
                confirmText: 'Continuar',
                cancelText: 'Cancelar',
                confirmEvent: 'duplicado-confirmado',
                cancelEvent: 'duplicado-cancelado',
            );

            return;
        }

        $data = $this->validate(
            $this->rulesPasoPostulacion(),
            $this->messagesPasoPostulacion(),
        );

        $this->persistirPostulacion($data);
    }

    public function confirmarGuardadoDuplicado(): void
    {
        $contextoProceso = $this->refrescarContextoProcesoPostulacion();

        if (! $contextoProceso['disponible']) {
            $this->dispatch('toast', message: $contextoProceso['mensaje'], type: 'warning');

            return;
        }

        $data = $this->validate(
            $this->rulesPasoPostulacion(),
            $this->messagesPasoPostulacion(),
        );

        $this->persistirPostulacion($data);
    }

    public function cancelarDuplicadoPostulacion(): void
    {
        $this->resetPostulacionForm();
        $this->resetValidation([
            'postulacionForm.id_modalidad_admision',
            'postulacionForm.id_lugar_inscripcion',
            'postulacionForm.id_turno',
            'postulacionForm.id_proceso_oferta',
            'postulacionForm.id_convenio_proceso',
        ]);
        $this->pasoActual = 3;

        $this->dispatch(
            'toast',
            message: 'Se cancelo la continuidad de la postulacion duplicada. Puede seleccionar otra oferta.',
            type: 'info',
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function persistirPostulacion(array $data): void
    {
        try {
            DB::transaction(function () use ($data): void {
                $procesoActivo = $this->obtenerProcesoActivoVigenteOrFail();
                app(ValidadorVigenciaInscripcion::class)->ofertaVigente(
                    (int) $data['postulacionForm']['id_proceso_oferta'],
                    $procesoActivo,
                );

                $idConvenioProceso = filled($data['postulacionForm']['id_convenio_proceso'])
                    ? (int) $data['postulacionForm']['id_convenio_proceso']
                    : null;

                if ($idConvenioProceso !== null) {
                    $convenioProceso = app(ValidadorVigenciaInscripcion::class)->convenioVigente(
                        $idConvenioProceso,
                        $procesoActivo,
                        true,
                    );

                    $inscritos = Inscripcion::query()
                        ->where('id_convenio_proceso', $idConvenioProceso)
                        ->whereNot('estado_ins', EstadoInscripcion::Anulado->value)
                        ->when($this->inscripcionId !== null, fn ($query) => $query->whereNot('id_ins', $this->inscripcionId))
                        ->count();

                    if ($inscritos >= $convenioProceso->cupo_maximo_cpr) {
                        throw new RuntimeException("El convenio ya alcanzo su cupo maximo ({$convenioProceso->cupo_maximo_cpr} inscritos). No se puede registrar la inscripcion.");
                    }
                }

                $payload = [
                    'id_persona' => $this->personaId,
                    'id_proceso_oferta' => (int) $data['postulacionForm']['id_proceso_oferta'],
                    'id_convenio_proceso' => $idConvenioProceso,
                    'fecha_inscripcion_ins' => now(),
                    'estado_ins' => EstadoInscripcion::Pendiente->value,
                    'anio_graduacion_ins' => (int) $this->generalForm['anio_graduacion'],
                    'id_pais_colegio' => (int) $this->generalForm['id_pais_colegio'],
                    'id_colegio' => filled($this->generalForm['id_colegio']) ? $this->generalForm['id_colegio'] : null,
                    'nombre_colegio_extranjero_ins' => $this->nullableUpper($this->generalForm['nombre_colegio_extranjero']),
                    'id_tipo_colegio' => (int) $this->generalForm['id_tipo_colegio'],
                    'veces_postula_unu_ins' => (int) $this->generalForm['veces_postula_unu'],
                    'veces_postula_otras_ins' => (int) $this->generalForm['veces_postula_otras'],
                    'observacion_ins' => $this->nullableUpper($this->generalForm['observacion']),
                ];

                if ($this->inscripcionId !== null) {
                    $inscripcion = Inscripcion::query()->findOrFail($this->inscripcionId);
                    $inscripcion->update($payload);
                } else {
                    $inscripcion = Inscripcion::query()->firstOrNew([
                        'id_persona' => $this->personaId,
                        'id_proceso_oferta' => (int) $data['postulacionForm']['id_proceso_oferta'],
                    ]);

                    $inscripcion->fill($payload);

                    if (! $inscripcion->exists) {
                        $inscripcion->codigo_ins = 'TMP-'.Str::upper(Str::random(10));
                    }

                    $inscripcion->save();

                    if (Str::startsWith((string) $inscripcion->codigo_ins, 'TMP-')) {
                        $inscripcion->update([
                            'codigo_ins' => app(GeneradorCodigoInscripcion::class)->generar($inscripcion),
                        ]);
                    }
                }

                $aulaActual = $inscripcion->procesoAula()->first();
                $turnoPostulacion = (int) $data['postulacionForm']['id_turno'];
                $debeAsignarAula = $aulaActual === null
                    || $aulaActual->id_proceso !== $procesoActivo->id_pro
                    || $aulaActual->id_turno !== $turnoPostulacion;

                if ($debeAsignarAula) {
                    if ($aulaActual !== null) {
                        $inscripcion->update(['id_proceso_aula' => null]);
                    }

                    app(AsignadorAulaInscripcion::class)->asignarDisponible($inscripcion, $procesoActivo->id_pro, $turnoPostulacion);
                }

                $this->inscripcionId = $inscripcion->id_ins;
            });
        } catch (Throwable $throwable) {
            report_if(! $throwable instanceof RuntimeException, $throwable);
            $this->dispatch('toast', message: $throwable->getMessage() ?: 'No se pudo guardar la postulacion. Intente nuevamente.', type: 'error');

            return;
        }

        $this->pasoActual = 4;
        $this->prepararPasoPago();
        $this->dispatch('toast', message: 'Postulacion guardada correctamente.', type: 'success');
    }

    /**
     * @return array{disponible:bool,mensaje:string,proceso:?Proceso}
     */
    private function refrescarContextoProcesoPostulacion(): array
    {
        return $this->contextoProcesoPostulacionCache = app(ProcesoInscripcionResolver::class)->resolver();
    }

    private function validarInicioInscripcionDisponible(): bool
    {
        if (! $this->inicioInscripcionBloqueado) {
            return true;
        }

        $this->dispatch(
            'toast',
            message: 'No hay un proceso de inscripcion activo en este momento. No se puede iniciar una nueva inscripcion.',
            type: 'warning'
        );

        return false;
    }

    public function descripcionProcesoOferta(?ProcesoOferta $procesoOferta): string
    {
        if ($procesoOferta === null) {
            return 'SIN OFERTA';
        }

        $carrera = $procesoOferta->ofertaBase?->sedeCarrera?->carrera?->nombre_car ?? 'SIN CARRERA';
        $sede = $procesoOferta->ofertaBase?->sedeCarrera?->sede?->nombre_sed ?? 'SIN SEDE';
        $modalidad = $procesoOferta->ofertaBase?->modalidadLugarInscripcion?->modalidadAdmision?->nombre_mad ?? 'SIN MODALIDAD';
        $lugar = $procesoOferta->ofertaBase?->modalidadLugarInscripcion?->lugarInscripcion?->nombre_lin ?? 'SIN LUGAR';
        $turno = $procesoOferta->turno?->nombre_tur ?? 'SIN TURNO';

        return "{$carrera} | {$sede} | {$modalidad} | {$lugar} | {$turno}";
    }

    public function descripcionConvenioProceso(?ConvenioProceso $convenioProceso): string
    {
        if ($convenioProceso === null) {
            return 'Sin convenio';
        }

        return $convenioProceso->convenio?->nombre_con ?? 'SIN CONVENIO';
    }

    private function resetPostulacionForm(): void
    {
        $this->postulacionForm = [
            'id_modalidad_admision' => '',
            'id_lugar_inscripcion' => '',
            'id_turno' => '',
            'id_proceso_oferta' => '',
            'id_convenio_proceso' => '',
        ];
    }

    private function obtenerProcesoActivoVigenteOrFail(): Proceso
    {
        $contextoProceso = $this->refrescarContextoProcesoPostulacion();

        if (! $contextoProceso['disponible'] || $contextoProceso['proceso'] === null) {
            throw new RuntimeException($contextoProceso['mensaje']);
        }

        return $contextoProceso['proceso'];
    }
}
