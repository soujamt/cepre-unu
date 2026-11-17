<?php

namespace App\Traits\InscripcionCrear;

use App\Enums\EstadoCatalogo;
use App\Enums\EstadoInscripcion;
use App\Models\Inscripcion;
use App\Models\ModalidadAdmision;
use App\Models\Proceso;
use App\Models\ProcesoAula;
use App\Models\ProcesoCarrera;
use App\Support\AsignadorAulaInscripcion;
use App\Support\GeneradorCodigoInscripcion;
use App\Support\ProcesoInscripcionResolver;
use App\Support\ResolvedorModalidadEgreso;
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
 * Paso 3 del asistente de inscripción: sede, carrera y turno. La modalidad
 * se asigna sola según el año de egreso.
 *
 * Lo usa el componente pages::inscripciones.inscripcion-crear, que declara
 * el estado (formularios y propiedades) sobre el que trabajan estos métodos.
 */
trait GestionaPasoPostulacion
{
    private function cargarPostulacionExistente(Inscripcion $inscripcion): void
    {
        $this->postulacionForm = [
            'id_sede' => (string) ($inscripcion->id_sede ?? ''),
            'id_carrera' => (string) ($inscripcion->id_carrera ?? ''),
            'id_turno' => (string) ($inscripcion->id_turno ?? ''),
        ];
    }

    public function updatedPostulacionFormIdSede(): void
    {
        $this->postulacionForm['id_carrera'] = '';
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

    /**
     * Modalidad que corresponde al año de egreso ingresado en el paso 2.
     */
    #[Computed]
    public function modalidadPostulacion(): ?ModalidadAdmision
    {
        $proceso = $this->procesoPostulacionActivo();
        $anioEgreso = $this->generalForm['anio_graduacion'] ?? '';

        if ($proceso === null || ! is_numeric($anioEgreso)) {
            return null;
        }

        return app(ResolvedorModalidadEgreso::class)->resolver($proceso, (int) $anioEgreso);
    }

    /**
     * Carreras habilitadas en el proceso activo, con su sede.
     */
    #[Computed]
    public function carrerasProcesoDisponibles(): EloquentCollection
    {
        $proceso = $this->procesoPostulacionActivo();

        if ($proceso === null) {
            return new EloquentCollection;
        }

        return ProcesoCarrera::query()
            ->with(['sede', 'carrera'])
            ->where('id_proceso', $proceso->id_pro)
            ->where('estado_pca', EstadoCatalogo::Habilitado->value)
            ->whereHas('sede', fn ($query) => $query->where('estado_sed', EstadoCatalogo::Habilitado->value))
            ->whereHas('carrera', fn ($query) => $query->where('estado_car', EstadoCatalogo::Habilitado->value))
            ->get();
    }

    /**
     * @return Collection<int, array{id:string,nombre:string}>
     */
    #[Computed]
    public function sedesPostulacionDisponibles(): Collection
    {
        return $this->carrerasProcesoDisponibles()
            ->map(fn (ProcesoCarrera $procesoCarrera): array => [
                'id' => (string) $procesoCarrera->id_sede,
                'nombre' => $procesoCarrera->sede?->nombre_sed ?? 'SIN SEDE',
            ])
            ->unique('id')
            ->sortBy('nombre')
            ->values();
    }

    /**
     * @return Collection<int, array{id:string,nombre:string}>
     */
    #[Computed]
    public function carrerasPostulacionDisponibles(): Collection
    {
        return $this->carrerasProcesoDisponibles()
            ->filter(fn (ProcesoCarrera $procesoCarrera): bool => $this->postulacionForm['id_sede'] !== ''
                && (string) $procesoCarrera->id_sede === (string) $this->postulacionForm['id_sede'])
            ->map(fn (ProcesoCarrera $procesoCarrera): array => [
                'id' => (string) $procesoCarrera->id_carrera,
                'nombre' => $procesoCarrera->carrera?->nombre_car ?? 'SIN CARRERA',
            ])
            ->sortBy('nombre')
            ->values();
    }

    /**
     * Turnos con al menos un aula habilitada en el proceso activo.
     *
     * @return Collection<int, array{id:string,nombre:string}>
     */
    #[Computed]
    public function turnosPostulacionDisponibles(): Collection
    {
        $proceso = $this->procesoPostulacionActivo();

        if ($proceso === null) {
            return collect();
        }

        return ProcesoAula::query()
            ->with('turno')
            ->where('id_proceso', $proceso->id_pro)
            ->where('estado_pau', EstadoCatalogo::Habilitado->value)
            ->whereHas('aula', fn ($query) => $query->where('estado_aul', EstadoCatalogo::Habilitado->value))
            ->get()
            ->map(fn (ProcesoAula $procesoAula): array => [
                'id' => (string) $procesoAula->id_turno,
                'nombre' => $procesoAula->turno?->descripcionConHorario() ?? 'SIN TURNO',
            ])
            ->unique('id')
            ->sortBy('nombre')
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rulesPasoPostulacion(): array
    {
        return [
            'postulacionForm.id_sede' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! $this->sedesPostulacionDisponibles()->contains('id', (string) $value)) {
                        $fail('La sede seleccionada no es valida para el proceso activo.');
                    }
                },
            ],
            'postulacionForm.id_carrera' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! $this->carrerasPostulacionDisponibles()->contains('id', (string) $value)) {
                        $fail('La carrera seleccionada no esta habilitada en esa sede.');
                    }
                },
            ],
            'postulacionForm.id_turno' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! $this->turnosPostulacionDisponibles()->contains('id', (string) $value)) {
                        $fail('El turno seleccionado no es valido.');
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
            'postulacionForm.id_sede.required' => 'La sede es obligatoria.',
            'postulacionForm.id_carrera.required' => 'La carrera es obligatoria.',
            'postulacionForm.id_turno.required' => 'El turno es obligatorio.',
        ];
    }

    public function guardarPasoPostulacion(): void
    {
        $contextoProceso = $this->refrescarContextoProcesoPostulacion();

        if (! $contextoProceso['disponible']) {
            $this->dispatch('toast', message: $contextoProceso['mensaje'], type: 'warning');

            return;
        }

        if (! $this->validarModalidadPostulacion()) {
            return;
        }

        if (! $this->confirmacionDatos) {
            $this->addError('confirmacionDatos', 'Revise el resumen y confirme que los datos son correctos.');

            return;
        }

        // Verificar inscripcion duplicada en el mismo proceso (excepto edicion actual)
        $proceso = $contextoProceso['proceso'];

        $inscripcionExistente = Inscripcion::query()
            ->with(['carrera'])
            ->where('id_persona', $this->personaId)
            ->whereNotIn('estado_ins', [EstadoInscripcion::Anulado->value])
            ->where('id_proceso', $proceso->id_pro)
            ->when($this->inscripcionId, fn ($q) => $q->where('id_ins', '!=', $this->inscripcionId))
            ->first();

        if ($inscripcionExistente !== null) {
            $carrera = $inscripcionExistente->carrera?->nombre_car ?? 'otra carrera';
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

        if (! $this->validarModalidadPostulacion()) {
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
            'postulacionForm.id_sede',
            'postulacionForm.id_carrera',
            'postulacionForm.id_turno',
        ]);
        $this->pasoActual = 4;

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
                $modalidad = app(ResolvedorModalidadEgreso::class)
                    ->resolver($procesoActivo, (int) $this->generalForm['anio_graduacion']);

                if ($modalidad === null) {
                    throw new RuntimeException('El proceso activo no tiene habilitada una modalidad para ese año de egreso.');
                }

                $postulacion = [
                    'id_proceso' => $procesoActivo->id_pro,
                    'id_sede' => (int) $data['postulacionForm']['id_sede'],
                    'id_modalidad_admision' => $modalidad->id_mad,
                    'id_carrera' => (int) $data['postulacionForm']['id_carrera'],
                    'id_turno' => (int) $data['postulacionForm']['id_turno'],
                ];

                app(ValidadorVigenciaInscripcion::class)->postulacionVigente(
                    $postulacion['id_sede'],
                    $postulacion['id_carrera'],
                    $postulacion['id_modalidad_admision'],
                    $procesoActivo,
                );

                $payload = [
                    'id_persona' => $this->personaId,
                    ...$postulacion,
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
                        'id_proceso' => $procesoActivo->id_pro,
                        'id_sede' => $postulacion['id_sede'],
                        'id_carrera' => $postulacion['id_carrera'],
                        'id_turno' => $postulacion['id_turno'],
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

                $this->registrarPagosDeInscripcion($inscripcion);

                $this->inscripcionId = $inscripcion->id_ins;
            });
        } catch (Throwable $throwable) {
            report_if(! $throwable instanceof RuntimeException, $throwable);
            $this->dispatch('toast', message: $throwable->getMessage() ?: 'No se pudo guardar la postulacion. Intente nuevamente.', type: 'error');

            return;
        }

        $inscripcion = Inscripcion::query()->find($this->inscripcionId);
        $activa = $inscripcion?->estado_ins === EstadoInscripcion::Activo;

        $this->dispatch(
            'toast',
            message: $activa
                ? "Inscripcion {$inscripcion->codigo_ins} registrada. Quedo activa y los pagos quedaron registrados."
                : 'Postulacion guardada. La inscripcion sigue pendiente hasta completar el pago.',
            type: 'success',
        );
        $this->despuesDeRegistrar($inscripcion);
    }

    /**
     * A dónde va el usuario después de registrar la inscripción.
     */
    abstract protected function despuesDeRegistrar(?Inscripcion $inscripcion): void;

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

    /**
     * Sin modalidad no hay postulación: avisa y devuelve al paso 2.
     */
    private function validarModalidadPostulacion(): bool
    {
        unset($this->modalidadPostulacion);

        if ($this->modalidadPostulacion !== null) {
            return true;
        }

        $this->dispatch(
            'toast',
            message: 'El proceso activo no tiene habilitada una modalidad para el año de egreso ingresado. Revise la oferta del proceso.',
            type: 'warning',
        );

        return false;
    }

    private function resetPostulacionForm(): void
    {
        $this->postulacionForm = [
            'id_sede' => '',
            'id_carrera' => '',
            'id_turno' => '',
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
