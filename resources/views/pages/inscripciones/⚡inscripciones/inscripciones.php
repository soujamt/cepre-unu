<?php

use App\Enums\EstadoCatalogo;
use App\Enums\EstadoInscripcion;
use App\Enums\EstadoObligacionPago;
use App\Enums\EstadoRevisionExpediente;
use App\Exports\InscripcionesFormatoCepreExport;
use App\Models\Inscripcion;
use App\Models\LugarInscripcion;
use App\Models\ModalidadAdmision;
use App\Models\Proceso;
use App\Models\ProcesoAula;
use App\Models\Semestre;
use App\Models\Turno;
use App\Support\AsignadorAulaInscripcion;
use App\Traits\AuthorizesPermisos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

new
#[Layout('layouts.app')]
#[Title('INSCRIPCIONES')]
class extends Component
{
    use AuthorizesPermisos;
    use WithPagination;

    #[Url(as: 'q')]
    public string $buscar = '';

    #[Url(as: 'proceso')]
    public string $idProceso = '';

    #[Url(as: 'semestre')]
    public string $idSemestre = '';

    #[Url(as: 'modalidad')]
    public string $idModalidad = '';

    #[Url(as: 'lugar')]
    public string $idLugarInscripcion = '';

    #[Url(as: 'pagina')]
    public int $porPagina = 10;

    /**
     * @var array<string, bool>
     */
    #[Url(as: 'estado')]
    public array $filtroEstado = [];

    #[Url(as: 'pago')]
    public string $filtroPago = '';

    #[Url(as: 'docs')]
    public string $filtroExpediente = '';

    #[Url(as: 'turno')]
    public string $idTurno = '';

    #[Url(as: 'aula')]
    public string $idProcesoAulaFiltro = '';

    public function mount(): void
    {
        $this->autorizarPermiso('inscripcion', 'indice');

        if ($this->idSemestre === '') {
            $this->idSemestre = $this->resolverSemestreActualPorDefecto();
        }
    }

    public function updatedBuscar(): void
    {
        $this->resetPage();
    }

    public function updatedIdSemestre(): void
    {
        $this->idProceso = '';
        $this->resetPage();
    }

    public function updatedIdProceso(): void
    {
        $this->resetPage();
    }

    public function updatedIdModalidad(): void
    {
        $this->resetPage();
    }

    public function updatedIdLugarInscripcion(): void
    {
        $this->resetPage();
    }

    public function updatedPorPagina(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroEstado(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroPago(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroExpediente(): void
    {
        $this->resetPage();
    }

    public function updatedIdTurno(): void
    {
        $this->resetPage();
    }

    public function updatedIdProcesoAulaFiltro(): void
    {
        $this->resetPage();
    }

    public function estados(): array
    {
        return EstadoInscripcion::cases();
    }

    #[Computed]
    public function estadosSeleccionados(): array
    {
        return collect($this->filtroEstado)
            ->filter(fn (bool $seleccionado): bool => $seleccionado)
            ->keys()
            ->values()
            ->all();
    }

    #[Computed]
    public function semestresDisponibles(): Collection
    {
        return Semestre::query()
            ->whereHas('procesos.procesosOferta.inscripciones')
            ->orderByDesc('fecha_inicio_inscripcion_sem')
            ->orderByDesc('id_sem')
            ->get();
    }

    #[Computed]
    public function procesosDisponibles(): Collection
    {
        return Proceso::query()
            ->with('semestre')
            ->when(filled($this->idSemestre), fn (Builder $query) => $query->where('id_semestre', (int) $this->idSemestre))
            ->whereHas('procesosOferta.inscripciones')
            ->orderByDesc('id_pro')
            ->get();
    }

    #[Computed]
    public function modalidadesDisponibles(): Collection
    {
        return ModalidadAdmision::query()
            ->whereHas('modalidadesLugarInscripcion.ofertasBase.procesosOferta.inscripciones', function (Builder $query): void {
                $this->aplicarFiltrosRelacionados($query);
            })
            ->orderBy('nombre_mad')
            ->get();
    }

    #[Computed]
    public function lugaresInscripcionDisponibles(): Collection
    {
        return LugarInscripcion::query()
            ->whereHas('modalidadesLugarInscripcion.ofertasBase.procesosOferta.inscripciones', function (Builder $query): void {
                $this->aplicarFiltrosRelacionados($query);
            })
            ->orderBy('nombre_lin')
            ->get();
    }

    #[Computed]
    public function turnosDisponibles(): Collection
    {
        return Turno::query()
            ->whereHas('procesosOferta.inscripciones', function (Builder $query): void {
                $this->aplicarFiltrosRelacionados($query);
            })
            ->orderBy('nombre_tur')
            ->get();
    }

    #[Computed]
    public function aulasProcesoDisponibles(): Collection
    {
        return ProcesoAula::query()
            ->with(['aula', 'turno'])
            ->when(filled($this->idProceso), fn (Builder $q) => $q->where('id_proceso', (int) $this->idProceso))
            ->when(! filled($this->idProceso) && filled($this->idSemestre), function (Builder $q): void {
                $q->whereHas('proceso', fn (Builder $p) => $p->where('id_semestre', (int) $this->idSemestre));
            })
            ->when(filled($this->idTurno), fn (Builder $q) => $q->where('id_turno', (int) $this->idTurno))
            ->whereHas('inscripciones')
            ->orderBy('id_pau')
            ->get();
    }

    #[Computed]
    public function resumen(): array
    {
        $query = $this->baseInscripcionesQuery();

        $counts = (clone $query)->selectRaw('
            COUNT(*) as total,
            SUM(estado_ins = ?) as activas,
            SUM(estado_ins = ?) as pendientes,
            SUM(estado_ins = ?) as anuladas
        ', [
            EstadoInscripcion::Activo->value,
            EstadoInscripcion::Pendiente->value,
            EstadoInscripcion::Anulado->value,
        ])->first();

        return [
            'total' => (int) $counts->total,
            'activas' => (int) $counts->activas,
            'pendientes' => (int) $counts->pendientes,
            'pago_pendiente' => (clone $query)
                ->whereHas('obligacionesPago', fn (Builder $q) => $q->whereIn('estado_iop', [
                    EstadoObligacionPago::Pendiente->value,
                    EstadoObligacionPago::Parcial->value,
                ]))
                ->count(),
            'docs_observados' => (clone $query)
                ->whereHas('expedientes', fn (Builder $q) => $q->whereIn('estado_revision_iex', [
                    EstadoRevisionExpediente::Observado->value,
                    EstadoRevisionExpediente::Rechazado->value,
                ]))
                ->count(),
            'anuladas' => (int) $counts->anuladas,
        ];
    }

    #[Computed]
    public function inscripciones(): LengthAwarePaginator
    {
        return $this->baseInscripcionesQuery()
            ->with($this->relacionesListado())
            ->orderByDesc('id_ins')
            ->paginate($this->porPagina);
    }

    public function exportarExcel(): BinaryFileResponse
    {
        $this->autorizarPermiso('inscripcion', 'exportar_excel');

        $inscripciones = $this->baseInscripcionesQuery()
            ->with($this->relacionesExportacion())
            ->orderByDesc('id_ins')
            ->get();

        return Excel::download(
            new InscripcionesFormatoCepreExport($inscripciones),
            $this->nombreArchivoExportacion(),
        );
    }

    public function limpiarFiltroEstado(): void
    {
        $this->filtroEstado = [];
    }

    public function limpiarFiltroPago(): void
    {
        $this->filtroPago = '';
    }

    public function limpiarFiltroExpediente(): void
    {
        $this->filtroExpediente = '';
    }

    public function limpiarFiltroProceso(): void
    {
        $this->idProceso = '';
    }

    public function limpiarFiltrosAcademicos(): void
    {
        $this->idSemestre = $this->resolverSemestreActualPorDefecto();
        $this->idProceso = '';
        $this->idModalidad = '';
        $this->idLugarInscripcion = '';
        $this->idTurno = '';
        $this->idProcesoAulaFiltro = '';
        $this->resetPage();
    }

    public ?int $idInscripcionAnular = null;

    // --- Asignar Aula ---

    public ?int $idInscripcionAula = null;

    public string $idProcesoAulaSeleccionada = '';

    public function abrirModalAsignarAula(int $idInscripcion): void
    {
        $this->autorizarPermiso('inscripcion', 'editar');

        $inscripcion = Inscripcion::query()
            ->with(['procesoOferta.proceso', 'procesoOferta.turno', 'procesoAula.aula'])
            ->findOrFail($idInscripcion);

        if (! $this->puedeAsignarAula($inscripcion)) {
            $this->dispatch('toast', message: 'No se puede asignar aula a una inscripción anulada.', type: 'error');

            return;
        }

        $this->idInscripcionAula = $inscripcion->id_ins;
        $this->idProcesoAulaSeleccionada = $inscripcion->id_proceso_aula !== null
            ? (string) $inscripcion->id_proceso_aula
            : '';

        $aulasData = $this->aulasDisponiblesParaInscripcion->map(fn (ProcesoAula $a): array => [
            'id' => (string) $a->id_pau,
            'nombre' => $a->aula?->nombre_aul ?? '',
            'piso' => $a->aula?->piso_aul?->getNombre() ?? '',
            'lugar' => $a->aula?->lugar_aul?->getNombre() ?? '',
            'ocupados' => $a->inscripciones_count,
            'capacidad' => $a->capacidad_pau,
        ])->values()->all();

        $this->resetValidation('idProcesoAulaSeleccionada');
        $this->modal('modal-asignar-aula')->show();

        $json = json_encode([
            'turno' => $inscripcion->procesoOferta?->turno?->nombre_tur ?? '',
            'aulas' => $aulasData,
        ], JSON_THROW_ON_ERROR);
        $this->js("window.dispatchEvent(new CustomEvent('cargar-aulas-modal', { detail: {$json} }))");
    }

    #[Computed]
    public function aulasDisponiblesParaInscripcion(): Collection
    {
        if ($this->idInscripcionAula === null) {
            return new Collection;
        }

        $inscripcion = Inscripcion::query()
            ->with('procesoOferta')
            ->findOrFail($this->idInscripcionAula);

        $idProceso = $inscripcion->procesoOferta?->id_proceso;
        $idTurno = $inscripcion->procesoOferta?->id_turno;

        if ($idProceso === null || $idTurno === null) {
            return new Collection;
        }

        return ProcesoAula::query()
            ->select('tbl_proceso_aula.*')
            ->join('tbl_aula', 'tbl_aula.id_aul', '=', 'tbl_proceso_aula.id_aula')
            ->with('aula')
            ->withCount([
                'inscripciones as inscripciones_count' => fn (Builder $query) => $query
                    ->where('estado_ins', '!=', EstadoInscripcion::Anulado->value),
            ])
            ->where('tbl_proceso_aula.id_proceso', $idProceso)
            ->where('tbl_proceso_aula.id_turno', $idTurno)
            ->where('tbl_proceso_aula.estado_pau', EstadoCatalogo::Habilitado->value)
            ->where('tbl_aula.estado_aul', EstadoCatalogo::Habilitado->value)
            ->orderBy('tbl_aula.lugar_aul')
            ->orderBy('tbl_aula.piso_aul')
            ->orderBy('tbl_aula.nombre_aul')
            ->get();
    }

    public function guardarAulaInscripcion(): void
    {
        $this->autorizarPermiso('inscripcion', 'editar');
        $this->resetValidation('idProcesoAulaSeleccionada');

        if (blank($this->idProcesoAulaSeleccionada)) {
            $this->addError('idProcesoAulaSeleccionada', 'Debe seleccionar un aula.');

            return;
        }

        try {
            DB::transaction(function (): void {
                /** @var ProcesoAula $procesoAula */
                $procesoAula = ProcesoAula::query()
                    ->lockForUpdate()
                    ->withCount('inscripciones')
                    ->findOrFail((int) $this->idProcesoAulaSeleccionada);

                $inscripcion = Inscripcion::query()
                    ->lockForUpdate()
                    ->with('procesoOferta')
                    ->findOrFail($this->idInscripcionAula);

                if (! $this->puedeAsignarAula($inscripcion)) {
                    throw new RuntimeException('No se puede asignar aula a una inscripción anulada.');
                }

                app(AsignadorAulaInscripcion::class)->validarCompatibilidad($procesoAula, $inscripcion);
                app(AsignadorAulaInscripcion::class)->validarCupo($procesoAula, $inscripcion);

                $inscripcion->update(['id_proceso_aula' => $procesoAula->id_pau]);
            });
        } catch (Throwable $throwable) {
            report_if(! $throwable instanceof RuntimeException, $throwable);
            $this->dispatch('toast', message: $throwable->getMessage() ?: 'No se pudo asignar el aula.', type: 'error');

            return;
        }

        $this->modal('modal-asignar-aula')->close();
        $this->idInscripcionAula = null;
        $this->idProcesoAulaSeleccionada = '';
        unset($this->inscripciones, $this->aulasDisponiblesParaInscripcion);
        $this->dispatch('toast', message: 'Aula asignada correctamente.', type: 'success');
    }

    #[Validate('required|string|min:10|max:500')]
    public string $motivoAnulacion = '';

    public function abrirModalAnular(int $idInscripcion): void
    {
        $this->idInscripcionAnular = $idInscripcion;
        $this->motivoAnulacion = '';
        $this->resetValidation('motivoAnulacion');

        $this->modal('modal-anular-inscripcion')->show();
    }

    public function anularInscripcion(): void
    {
        $this->autorizarPermiso('inscripcion', 'estado');

        $this->validate([
            'motivoAnulacion' => 'required|string|min:10|max:500',
        ], [
            'motivoAnulacion.required' => 'El motivo de anulación es obligatorio.',
            'motivoAnulacion.min' => 'El motivo debe tener al menos 10 caracteres.',
            'motivoAnulacion.max' => 'El motivo no puede exceder 500 caracteres.',
        ]);

        try {
            $inscripcion = Inscripcion::query()->findOrFail($this->idInscripcionAnular);

            if ($inscripcion->estado_ins !== EstadoInscripcion::Activo) {
                throw new RuntimeException('Solo se pueden anular inscripciones activas.');
            }

            $inscripcion->update([
                'estado_ins' => EstadoInscripcion::Anulado->value,
                'id_proceso_aula' => null,
                'motivo_anulacion_ins' => $this->motivoAnulacion,
            ]);
        } catch (Throwable $throwable) {
            report_if(! $throwable instanceof RuntimeException, $throwable);
            $this->dispatch('toast', message: $throwable->getMessage() ?: 'No se pudo anular la inscripción.', type: 'error');

            return;
        }

        $this->modal('modal-anular-inscripcion')->close();
        $this->idInscripcionAnular = null;
        $this->motivoAnulacion = '';

        unset($this->inscripciones, $this->resumen);
        $this->dispatch('toast', message: 'Inscripción anulada correctamente.', type: 'success');
    }

    public function puedeContinuarRegistro(Inscripcion $inscripcion): bool
    {
        return $inscripcion->estado_ins === EstadoInscripcion::Pendiente;
    }

    public function puedeAsignarAula(Inscripcion $inscripcion): bool
    {
        return in_array($inscripcion->estado_ins, [
            EstadoInscripcion::Activo,
            EstadoInscripcion::Pendiente,
        ], true);
    }

    public function puedeVerDetalleRegistro(Inscripcion $inscripcion): bool
    {
        return in_array($inscripcion->estado_ins, [
            EstadoInscripcion::Activo,
            EstadoInscripcion::Pendiente,
            EstadoInscripcion::Anulado,
        ], true);
    }

    public function tienePagoPorValidar(Inscripcion $inscripcion): bool
    {
        return $inscripcion->obligacionesPago->contains(
            fn ($obligacion) => in_array($obligacion->estado_iop, [EstadoObligacionPago::Pendiente, EstadoObligacionPago::Parcial], true)
        );
    }

    /**
     * @return array{label:string,icon:string}
     */
    public function accionEstadoRegistro(Inscripcion $inscripcion): array
    {
        return match ($inscripcion->estado_ins) {
            EstadoInscripcion::Activo => [
                'label' => 'Detalle disponible',
                'icon' => 'eye',
            ],
            EstadoInscripcion::Pendiente => [
                'label' => 'Detalle y continuación disponibles',
                'icon' => 'eye',
            ],
            EstadoInscripcion::Anulado => [
                'label' => 'Disponible solo para consulta',
                'icon' => 'x-circle',
            ],
            default => [
                'label' => 'Registro en proceso',
                'icon' => 'clock',
            ],
        };
    }

    /**
     * @return array{label:string,color:string}
     */
    public function resumenPago(Inscripcion $inscripcion): array
    {
        $obligaciones = $inscripcion->obligacionesPago;

        if ($obligaciones->isEmpty()) {
            return ['label' => 'SIN REGLA', 'color' => 'zinc'];
        }

        if ($obligaciones->every(fn ($obligacion) => $obligacion->estado_iop === EstadoObligacionPago::Exonerado)) {
            return ['label' => 'EXONERADO', 'color' => 'violet'];
        }

        if ($obligaciones->every(fn ($obligacion) => in_array($obligacion->estado_iop, [EstadoObligacionPago::Pagado, EstadoObligacionPago::Exonerado], true))) {
            return ['label' => 'PAGADO', 'color' => 'green'];
        }

        if ($obligaciones->contains(fn ($obligacion) => $obligacion->estado_iop === EstadoObligacionPago::Parcial)) {
            return ['label' => 'PARCIAL', 'color' => 'sky'];
        }

        if ($obligaciones->contains(fn ($obligacion) => $obligacion->estado_iop === EstadoObligacionPago::Pendiente)) {
            return ['label' => 'PENDIENTE', 'color' => 'amber'];
        }

        return ['label' => 'REVISION', 'color' => 'zinc'];
    }

    /**
     * @return array{label:string,color:string,detalle:string}
     */
    public function resumenExpedientes(Inscripcion $inscripcion): array
    {
        $requisitos = $inscripcion->procesoOferta?->proceso?->requisitosExpediente?->count() ?? 0;
        $expedientes = $inscripcion->expedientes;

        if ($requisitos === 0) {
            return ['label' => 'SIN REQUISITOS', 'color' => 'zinc', 'detalle' => '0 / 0'];
        }

        if ($expedientes->isEmpty()) {
            return ['label' => 'SIN CARGA', 'color' => 'amber', 'detalle' => "0 / {$requisitos}"];
        }

        if ($expedientes->contains(fn ($expediente) => $expediente->estado_revision_iex === EstadoRevisionExpediente::Rechazado)) {
            return ['label' => 'RECHAZADO', 'color' => 'red', 'detalle' => "{$expedientes->count()} / {$requisitos}"];
        }

        if ($expedientes->contains(fn ($expediente) => $expediente->estado_revision_iex === EstadoRevisionExpediente::Observado)) {
            return ['label' => 'OBSERVADO', 'color' => 'sky', 'detalle' => "{$expedientes->count()} / {$requisitos}"];
        }

        if (
            $expedientes->count() >= $requisitos
            && $expedientes->every(fn ($expediente) => $expediente->estado_revision_iex === EstadoRevisionExpediente::Aprobado)
        ) {
            return ['label' => 'COMPLETO', 'color' => 'green', 'detalle' => "{$expedientes->count()} / {$requisitos}"];
        }

        return ['label' => 'PENDIENTE', 'color' => 'amber', 'detalle' => "{$expedientes->count()} / {$requisitos}"];
    }

    private function baseInscripcionesQuery(): Builder
    {
        return Inscripcion::query()
            ->when(filled($this->buscar), function (Builder $query): void {
                $texto = $this->normalizarTexto($this->buscar);

                $query->where(function (Builder $builder) use ($texto): void {
                    $builder->where('codigo_ins', 'like', "%{$texto}%")
                        ->orWhereHas('persona', function (Builder $personaQuery) use ($texto): void {
                            $personaQuery->where('numero_documento_per', 'like', "%{$texto}%")
                                ->orWhere('apellido_paterno_per', 'like', "%{$texto}%")
                                ->orWhere('apellido_materno_per', 'like', "%{$texto}%")
                                ->orWhere('nombres_per', 'like', "%{$texto}%");
                        })
                        ->orWhereHas('procesoOferta.proceso', fn (Builder $procesoQuery) => $procesoQuery
                            ->where('nombre_pro', 'like', "%{$texto}%"))
                        ->orWhereHas('procesoOferta.ofertaBase.sedeCarrera.carrera', fn (Builder $carreraQuery) => $carreraQuery
                            ->where('nombre_car', 'like', "%{$texto}%"));
                });
            })
            ->when(filled($this->idSemestre), function (Builder $query): void {
                $query->whereHas('procesoOferta.proceso', fn (Builder $builder) => $builder->where('id_semestre', (int) $this->idSemestre));
            })
            ->when(filled($this->idProceso), function (Builder $query): void {
                $query->whereHas('procesoOferta', fn (Builder $builder) => $builder->where('id_proceso', (int) $this->idProceso));
            })
            ->when(filled($this->idModalidad), function (Builder $query): void {
                $query->whereHas(
                    'procesoOferta.ofertaBase.modalidadLugarInscripcion',
                    fn (Builder $builder) => $builder->where('id_modalidad_admision', (int) $this->idModalidad)
                );
            })
            ->when(filled($this->idLugarInscripcion), function (Builder $query): void {
                $query->whereHas(
                    'procesoOferta.ofertaBase.modalidadLugarInscripcion',
                    fn (Builder $builder) => $builder->where('id_lugar_inscripcion', (int) $this->idLugarInscripcion)
                );
            })
            ->when($this->estadosSeleccionados !== [], function (Builder $query): void {
                $query->whereIn('estado_ins', $this->estadosSeleccionados);
            })
            ->when(filled($this->filtroPago), function (Builder $query): void {
                $query->whereHas('obligacionesPago', fn (Builder $q) => $q->where('estado_iop', $this->filtroPago));
            })
            ->when(filled($this->filtroExpediente), function (Builder $query): void {
                if ($this->filtroExpediente === 'sin_carga') {
                    $query->whereDoesntHave('expedientes');
                } else {
                    $query->whereHas('expedientes', fn (Builder $q) => $q->where('estado_revision_iex', $this->filtroExpediente));
                }
            })
            ->when(filled($this->idTurno), function (Builder $query): void {
                $query->whereHas('procesoOferta', fn (Builder $q) => $q->where('id_turno', (int) $this->idTurno));
            })
            ->when(filled($this->idProcesoAulaFiltro), function (Builder $query): void {
                $query->where('id_proceso_aula', (int) $this->idProcesoAulaFiltro);
            });
    }

    /**
     * @return array<int, string>
     */
    private function relacionesListado(): array
    {
        return [
            'persona',
            'procesoOferta.proceso.semestre',
            'procesoOferta.proceso.requisitosExpediente',
            'procesoOferta.ofertaBase.sedeCarrera.carrera',
            'procesoOferta.ofertaBase.sedeCarrera.sede',
            'procesoOferta.ofertaBase.modalidadLugarInscripcion.modalidadAdmision',
            'procesoOferta.turno',
            'procesoAula.aula',
            'procesoAula.turno',
            'obligacionesPago',
            'expedientes',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function relacionesExportacion(): array
    {
        return [
            'persona.tipoDocumento',
            'persona.estadoCivil',
            'persona.paisNacimiento',
            'persona.nacionalidad',
            'persona.ubigeoNacimiento',
            'persona.ubigeoDomicilio',
            'persona.tipoDiscapacidad',
            'persona.identidadEtnica',
            'persona.lenguaNativa',
            'persona.lenguaExtranjera',
            'paisColegio',
            'colegio',
            'tipoColegio',
            'procesoOferta.ofertaBase.modalidadLugarInscripcion.lugarInscripcion',
        ];
    }

    private function nombreArchivoExportacion(): string
    {
        return 'inscripciones-cepre-'.now()->format('Ymd-His').'.xlsx';
    }

    private function resolverSemestreActualPorDefecto(): string
    {
        $semestre = Semestre::query()
            ->whereDate('fecha_inicio_inscripcion_sem', '<=', now()->toDateString())
            ->whereDate('fecha_fin_inscripcion_sem', '>=', now()->toDateString())
            ->orderByDesc('fecha_inicio_inscripcion_sem')
            ->orderByDesc('id_sem')
            ->first();

        if ($semestre !== null) {
            return (string) $semestre->id_sem;
        }

        return (string) (Semestre::query()
            ->whereHas('procesos.procesosOferta.inscripciones')
            ->orderByDesc('fecha_inicio_inscripcion_sem')
            ->orderByDesc('id_sem')
            ->value('id_sem') ?? '');
    }

    private function aplicarFiltrosRelacionados(Builder $query): void
    {
        if (filled($this->idSemestre)) {
            $query->whereHas('procesoOferta.proceso', fn (Builder $builder) => $builder->where('id_semestre', (int) $this->idSemestre));
        }

        if (filled($this->idProceso)) {
            $query->whereHas('procesoOferta', fn (Builder $builder) => $builder->where('id_proceso', (int) $this->idProceso));
        }
    }

    private function normalizarTexto(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $normalizado = preg_replace('/\s+/u', ' ', trim($valor));

        if (! filled($normalizado)) {
            return null;
        }

        return str_replace(['%', '_'], ['\%', '\_'], $normalizado);
    }
};
