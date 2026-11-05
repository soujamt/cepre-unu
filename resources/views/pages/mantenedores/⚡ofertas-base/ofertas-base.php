<?php

use App\Enums\EstadoCatalogo;
use App\Models\LugarInscripcion;
use App\Models\ModalidadAdmision;
use App\Models\ModalidadLugarInscripcion;
use App\Models\OfertaBase;
use App\Models\Sede;
use App\Models\SedeCarrera;
use App\Traits\AuthorizesPermisos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('layouts.app')]
#[Title('Ofertas base')]
class extends Component
{
    use AuthorizesPermisos;
    use WithPagination;

    public string $buscar = '';

    #[Url(as: 'sede', except: '')]
    public string $idSede = '';

    #[Url(as: 'modalidad', except: '')]
    public string $idModalidad = '';

    /**
     * @var array<string, bool>
     */
    public array $filtroEstado = [];

    /**
     * @var array{codigo: string, id_sede: string, id_lugar: string, id_modalidad: string, id_sede_carrera: string}
     */
    public array $ofertaForm = [
        'codigo' => '',
        'id_sede' => '',
        'id_lugar' => '',
        'id_modalidad' => '',
        'id_sede_carrera' => '',
    ];

    public string $tituloModal = 'Nueva oferta base';

    public bool $isEdit = false;

    public ?int $editingId = null;

    /**
     * La combinación de una oferta ya usada en procesos no se puede cambiar;
     * solo indica a la vista que bloquee esos campos (guardar() lo vuelve a
     * comprobar en el servidor).
     */
    #[Locked]
    public bool $combinacionBloqueada = false;

    public function mount(): void
    {
        $this->autorizarPermiso('oferta_base', 'indice');
    }

    public function updatedBuscar(): void
    {
        $this->resetPage();
    }

    public function updatedIdSede(): void
    {
        $this->resetPage();
    }

    public function updatedIdModalidad(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroEstado(): void
    {
        $this->resetPage();
    }

    public function updatedOfertaFormIdSede(): void
    {
        $this->ofertaForm['id_lugar'] = '';
        $this->ofertaForm['id_modalidad'] = '';
        $this->ofertaForm['id_sede_carrera'] = '';
    }

    public function updatedOfertaFormIdLugar(): void
    {
        $this->ofertaForm['id_modalidad'] = '';
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'ofertaForm.codigo' => [
                'required',
                'string',
                'max:20',
                'regex:/^\s*[A-Za-z0-9_-]+\s*$/',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($this->existeCodigoOferta((string) $value)) {
                        $fail('Ya existe una oferta base con ese código.');
                    }
                },
            ],
            'ofertaForm.id_sede' => ['required', 'integer', 'exists:tbl_sede,id_sed'],
            'ofertaForm.id_lugar' => [
                'required',
                'integer',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $perteneceASede = LugarInscripcion::query()
                        ->whereKey((int) $value)
                        ->where('id_sede', (int) $this->ofertaForm['id_sede'])
                        ->exists();

                    if (! $perteneceASede) {
                        $fail('Selecciona un lugar de inscripción de la sede elegida.');
                    }
                },
            ],
            'ofertaForm.id_modalidad' => [
                'required',
                'integer',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($this->modalidadLugarSeleccionada() === null) {
                        $fail('La modalidad no está asignada a ese lugar de inscripción.');
                    }
                },
            ],
            'ofertaForm.id_sede_carrera' => [
                'required',
                'integer',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $perteneceASede = SedeCarrera::query()
                        ->whereKey((int) $value)
                        ->where('id_sede', (int) $this->ofertaForm['id_sede'])
                        ->exists();

                    if (! $perteneceASede) {
                        $fail('Selecciona una carrera de la sede elegida.');

                        return;
                    }

                    if ($this->existeCombinacionActiva()) {
                        $fail('Ya existe una oferta base con esa modalidad, lugar y carrera.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'ofertaForm.codigo.required' => 'El código es obligatorio.',
            'ofertaForm.codigo.max' => 'El código no debe superar los 20 caracteres.',
            'ofertaForm.codigo.regex' => 'El código solo admite letras, números, guion y guion bajo.',
            'ofertaForm.id_sede.required' => 'La sede es obligatoria.',
            'ofertaForm.id_sede.exists' => 'Selecciona una sede válida.',
            'ofertaForm.id_lugar.required' => 'El lugar de inscripción es obligatorio.',
            'ofertaForm.id_modalidad.required' => 'La modalidad es obligatoria.',
            'ofertaForm.id_sede_carrera.required' => 'La carrera es obligatoria.',
        ];
    }

    public function estados(): array
    {
        return EstadoCatalogo::cases();
    }

    #[Computed]
    public function estadosSeleccionados(): array
    {
        return collect($this->filtroEstado)
            ->filter(fn (bool $selected): bool => $selected)
            ->keys()
            ->values()
            ->all();
    }

    public function limpiarFiltroEstado(): void
    {
        $this->filtroEstado = [];
    }

    /**
     * @return EloquentCollection<int, Sede>
     */
    #[Computed]
    public function sedes(): EloquentCollection
    {
        return Sede::query()->orderBy('nombre_sed')->get(['id_sed', 'nombre_sed', 'estado_sed']);
    }

    /**
     * @return EloquentCollection<int, ModalidadAdmision>
     */
    #[Computed]
    public function modalidadesFiltro(): EloquentCollection
    {
        return ModalidadAdmision::query()->orderBy('nombre_mad')->get(['id_mad', 'nombre_mad']);
    }

    /**
     * Lugares habilitados de la sede elegida en el formulario (y el actual
     * de la oferta que se edita, aunque esté deshabilitado).
     *
     * @return EloquentCollection<int, LugarInscripcion>
     */
    #[Computed]
    public function lugaresFormulario(): EloquentCollection
    {
        if (blank($this->ofertaForm['id_sede'])) {
            return new EloquentCollection;
        }

        $idLugarActual = filled($this->ofertaForm['id_lugar']) ? (int) $this->ofertaForm['id_lugar'] : null;

        return LugarInscripcion::query()
            ->where('id_sede', (int) $this->ofertaForm['id_sede'])
            ->where(function (Builder $query) use ($idLugarActual): void {
                $query->where('estado_lin', EstadoCatalogo::Habilitado->value)
                    ->when($idLugarActual, fn (Builder $query) => $query->orWhere('id_lin', $idLugarActual));
            })
            ->orderBy('codigo_lin')
            ->get(['id_lin', 'codigo_lin', 'nombre_lin']);
    }

    /**
     * Modalidades asignadas (habilitadas) al lugar elegido en el formulario.
     *
     * @return EloquentCollection<int, ModalidadAdmision>
     */
    #[Computed]
    public function modalidadesFormulario(): EloquentCollection
    {
        if (blank($this->ofertaForm['id_lugar'])) {
            return new EloquentCollection;
        }

        $idModalidadActual = filled($this->ofertaForm['id_modalidad']) ? (int) $this->ofertaForm['id_modalidad'] : null;

        return ModalidadAdmision::query()
            ->whereHas('modalidadesLugarInscripcion', function (Builder $query) use ($idModalidadActual): void {
                $query->where('id_lugar_inscripcion', (int) $this->ofertaForm['id_lugar'])
                    ->where(function (Builder $estado) use ($idModalidadActual): void {
                        $estado->where('estado_mli', EstadoCatalogo::Habilitado->value)
                            ->when($idModalidadActual, fn (Builder $query) => $query->orWhere('id_modalidad_admision', $idModalidadActual));
                    });
            })
            ->orderBy('nombre_mad')
            ->get(['id_mad', 'nombre_mad']);
    }

    /**
     * Carreras asignadas (habilitadas) a la sede elegida en el formulario.
     *
     * @return EloquentCollection<int, SedeCarrera>
     */
    #[Computed]
    public function carrerasFormulario(): EloquentCollection
    {
        if (blank($this->ofertaForm['id_sede'])) {
            return new EloquentCollection;
        }

        $idActual = filled($this->ofertaForm['id_sede_carrera']) ? (int) $this->ofertaForm['id_sede_carrera'] : null;

        return SedeCarrera::query()
            ->with('carrera:id_car,nombre_car')
            ->where('id_sede', (int) $this->ofertaForm['id_sede'])
            ->where(function (Builder $query) use ($idActual): void {
                $query->where('estado_sca', EstadoCatalogo::Habilitado->value)
                    ->when($idActual, fn (Builder $query) => $query->orWhere('id_sca', $idActual));
            })
            ->get(['id_sca', 'id_carrera'])
            ->sortBy(fn (SedeCarrera $sedeCarrera): string => (string) $sedeCarrera->carrera?->nombre_car)
            ->values();
    }

    public function cargarOferta(?int $id = null): void
    {
        $this->autorizarPermiso('oferta_base', $id === null ? 'crear' : 'editar');
        $this->resetValidation();

        if ($id === null) {
            $this->resetForm();
            $this->modal('modal-oferta-base')->show();

            return;
        }

        $oferta = OfertaBase::query()
            ->with(['modalidadLugarInscripcion.lugarInscripcion', 'sedeCarrera'])
            ->withCount('procesosOferta')
            ->findOrFail($id);

        $this->editingId = $oferta->id_ofb;
        $this->ofertaForm = [
            'codigo' => (string) $oferta->codigo_externo_ofb,
            'id_sede' => (string) $oferta->sedeCarrera?->id_sede,
            'id_lugar' => (string) $oferta->modalidadLugarInscripcion?->id_lugar_inscripcion,
            'id_modalidad' => (string) $oferta->modalidadLugarInscripcion?->id_modalidad_admision,
            'id_sede_carrera' => (string) $oferta->id_sede_carrera,
        ];
        $this->combinacionBloqueada = $oferta->procesos_oferta_count > 0;
        $this->tituloModal = 'Editar oferta base';
        $this->isEdit = true;
        $this->olvidarListasFormulario();

        $this->modal('modal-oferta-base')->show();
    }

    public function guardar(): void
    {
        $this->autorizarPermiso('oferta_base', $this->isEdit ? 'editar' : 'crear');

        if ($this->editingId !== null && OfertaBase::query()->whereKey($this->editingId)->has('procesosOferta')->exists()) {
            $this->restaurarCombinacionOriginal();
        }

        $this->validate();

        try {
            DB::transaction(function (): void {
                $payload = [
                    'codigo_externo_ofb' => $this->normalizarMayusculas($this->ofertaForm['codigo']),
                    'id_modalidad_lugar_inscripcion' => $this->modalidadLugarSeleccionada()?->id_mli,
                    'id_sede_carrera' => (int) $this->ofertaForm['id_sede_carrera'],
                ];

                if ($this->editingId !== null) {
                    OfertaBase::query()->findOrFail($this->editingId)->update($payload);

                    return;
                }

                $eliminada = OfertaBase::query()
                    ->onlyTrashed()
                    ->where('id_modalidad_lugar_inscripcion', $payload['id_modalidad_lugar_inscripcion'])
                    ->where('id_sede_carrera', $payload['id_sede_carrera'])
                    ->first();

                if ($eliminada !== null) {
                    $eliminada->restore();
                    $eliminada->update([...$payload, 'estado_ofb' => EstadoCatalogo::Habilitado->value]);

                    return;
                }

                OfertaBase::query()->create([
                    ...$payload,
                    'estado_ofb' => EstadoCatalogo::Habilitado->value,
                ]);
            });
        } catch (Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo guardar la oferta base. Intente nuevamente.', type: 'error');

            return;
        }

        $this->dispatch(
            'toast',
            message: $this->isEdit ? 'Oferta base actualizada correctamente.' : 'Oferta base registrada correctamente.',
            type: 'success'
        );

        unset($this->ofertas);
        $this->modal('modal-oferta-base')->close();
        $this->resetForm();
    }

    #[On('oferta-base-cambiar-estado')]
    public function cambiarEstado(?int $id_oferta = null): void
    {
        $this->autorizarPermiso('oferta_base', 'estado');

        if ($id_oferta === null) {
            $this->dispatch('toast', message: 'ID de oferta base no proporcionado.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($id_oferta): void {
                $oferta = OfertaBase::query()->findOrFail($id_oferta);

                $oferta->update([
                    'estado_ofb' => $oferta->estado_ofb === EstadoCatalogo::Habilitado
                        ? EstadoCatalogo::Deshabilitado->value
                        : EstadoCatalogo::Habilitado->value,
                ]);
            });
        } catch (Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo cambiar el estado de la oferta base.', type: 'error');

            return;
        }

        unset($this->ofertas);
        $this->dispatch('toast', message: 'Estado de la oferta base actualizado.', type: 'success');
    }

    #[On('oferta-base-eliminar')]
    public function eliminar(?int $id_oferta = null): void
    {
        $this->autorizarPermiso('oferta_base', 'eliminar');

        if ($id_oferta === null) {
            $this->dispatch('toast', message: 'ID de oferta base no proporcionado.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($id_oferta): void {
                $oferta = OfertaBase::query()
                    ->withCount(['procesosOferta' => fn (Builder $query) => $query->withTrashed()])
                    ->findOrFail($id_oferta);

                if ($oferta->procesos_oferta_count > 0) {
                    throw new RuntimeException('La oferta base se usa en procesos y no puede eliminarse. Puede deshabilitarla.');
                }

                $oferta->delete();
            });
        } catch (Throwable $throwable) {
            report_if(! $throwable instanceof RuntimeException, $throwable);
            $this->dispatch(
                'toast',
                message: $throwable instanceof RuntimeException ? $throwable->getMessage() : 'No se pudo eliminar la oferta base.',
                type: 'error'
            );

            return;
        }

        if ($this->editingId === $id_oferta) {
            $this->resetForm();
            $this->modal('modal-oferta-base')->close();
        }

        unset($this->ofertas);
        $this->dispatch('toast', message: 'Oferta base eliminada correctamente.', type: 'success');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'isEdit', 'combinacionBloqueada']);
        $this->tituloModal = 'Nueva oferta base';
        $this->ofertaForm = [
            'codigo' => '',
            'id_sede' => '',
            'id_lugar' => '',
            'id_modalidad' => '',
            'id_sede_carrera' => '',
        ];
        $this->olvidarListasFormulario();
        $this->resetValidation();
    }

    #[Computed]
    public function ofertas(): LengthAwarePaginator
    {
        return OfertaBase::query()
            ->with([
                'modalidadLugarInscripcion.modalidadAdmision:id_mad,nombre_mad',
                'modalidadLugarInscripcion.lugarInscripcion:id_lin,codigo_lin,nombre_lin',
                'sedeCarrera.sede:id_sed,nombre_sed',
                'sedeCarrera.carrera:id_car,nombre_car',
            ])
            ->withCount('procesosOferta')
            ->when(filled($this->buscar), function (Builder $query): void {
                $search = $this->normalizarTexto($this->buscar);

                $query->where(function (Builder $innerQuery) use ($search): void {
                    $innerQuery->where('codigo_externo_ofb', 'like', "%{$search}%")
                        ->orWhereHas('sedeCarrera.carrera', fn (Builder $carrera) => $carrera->where('nombre_car', 'like', "%{$search}%"))
                        ->orWhereHas('modalidadLugarInscripcion.lugarInscripcion', fn (Builder $lugar) => $lugar->where('nombre_lin', 'like', "%{$search}%"));
                });
            })
            ->when($this->idSede !== '', fn (Builder $query) => $query->whereHas(
                'sedeCarrera',
                fn (Builder $sedeCarrera) => $sedeCarrera->where('id_sede', (int) $this->idSede),
            ))
            ->when($this->idModalidad !== '', fn (Builder $query) => $query->whereHas(
                'modalidadLugarInscripcion',
                fn (Builder $modalidadLugar) => $modalidadLugar->where('id_modalidad_admision', (int) $this->idModalidad),
            ))
            ->when($this->estadosSeleccionados !== [], function (Builder $query): void {
                $query->whereIn('estado_ofb', $this->estadosSeleccionados);
            })
            ->orderBy('codigo_externo_ofb')
            ->paginate(10);
    }

    private function modalidadLugarSeleccionada(): ?ModalidadLugarInscripcion
    {
        if (blank($this->ofertaForm['id_lugar']) || blank($this->ofertaForm['id_modalidad'])) {
            return null;
        }

        return ModalidadLugarInscripcion::query()
            ->where('id_lugar_inscripcion', (int) $this->ofertaForm['id_lugar'])
            ->where('id_modalidad_admision', (int) $this->ofertaForm['id_modalidad'])
            ->first();
    }

    /**
     * Busca otra oferta (no eliminada) con la misma modalidad, lugar y
     * carrera. Las eliminadas se reactivan al guardar.
     */
    private function existeCombinacionActiva(): bool
    {
        $modalidadLugar = $this->modalidadLugarSeleccionada();

        if ($modalidadLugar === null) {
            return false;
        }

        return OfertaBase::query()
            ->where('id_modalidad_lugar_inscripcion', $modalidadLugar->id_mli)
            ->where('id_sede_carrera', (int) $this->ofertaForm['id_sede_carrera'])
            ->when($this->editingId, fn (Builder $query) => $query->where('id_ofb', '!=', $this->editingId))
            ->exists();
    }

    /**
     * El código es único también entre las ofertas eliminadas, salvo la que
     * tenga la misma combinación, porque esa se reactiva al guardar.
     */
    private function existeCodigoOferta(string $valor): bool
    {
        $valor = $this->normalizarMayusculas($valor);

        if ($valor === '') {
            return false;
        }

        $idReactivable = $this->editingId === null ? $this->idOfertaEliminadaConCombinacion() : null;

        return OfertaBase::query()
            ->withTrashed()
            ->whereRaw('UPPER(codigo_externo_ofb) = ?', [$valor])
            ->when($this->editingId, fn (Builder $query) => $query->where('id_ofb', '!=', $this->editingId))
            ->when($idReactivable, fn (Builder $query) => $query->where('id_ofb', '!=', $idReactivable))
            ->exists();
    }

    private function idOfertaEliminadaConCombinacion(): ?int
    {
        $modalidadLugar = $this->modalidadLugarSeleccionada();

        if ($modalidadLugar === null || blank($this->ofertaForm['id_sede_carrera'])) {
            return null;
        }

        return OfertaBase::query()
            ->onlyTrashed()
            ->where('id_modalidad_lugar_inscripcion', $modalidadLugar->id_mli)
            ->where('id_sede_carrera', (int) $this->ofertaForm['id_sede_carrera'])
            ->value('id_ofb');
    }

    /**
     * Si la oferta ya se usa en procesos, solo se puede cambiar el código:
     * se ignora cualquier cambio en sede, lugar, modalidad o carrera.
     */
    private function restaurarCombinacionOriginal(): void
    {
        $oferta = OfertaBase::query()
            ->with(['modalidadLugarInscripcion', 'sedeCarrera'])
            ->findOrFail($this->editingId);

        $this->ofertaForm = [
            ...$this->ofertaForm,
            'id_sede' => (string) $oferta->sedeCarrera?->id_sede,
            'id_lugar' => (string) $oferta->modalidadLugarInscripcion?->id_lugar_inscripcion,
            'id_modalidad' => (string) $oferta->modalidadLugarInscripcion?->id_modalidad_admision,
            'id_sede_carrera' => (string) $oferta->id_sede_carrera,
        ];
    }

    private function olvidarListasFormulario(): void
    {
        unset($this->lugaresFormulario, $this->modalidadesFormulario, $this->carrerasFormulario);
    }

    private function normalizarMayusculas(?string $valor): string
    {
        return mb_strtoupper($this->normalizarTexto($valor) ?? '', 'UTF-8');
    }

    private function normalizarTexto(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $normalizado = preg_replace('/\s+/u', ' ', trim($valor));

        return filled($normalizado) ? $normalizado : null;
    }
};
