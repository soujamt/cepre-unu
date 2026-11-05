<?php

use App\Enums\EstadoCatalogo;
use App\Models\LugarInscripcion;
use App\Models\ModalidadAdmision;
use App\Models\ModalidadLugarInscripcion;
use App\Models\Sede;
use App\Traits\AuthorizesPermisos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('layouts.app')]
#[Title('Lugares de inscripción')]
class extends Component
{
    use AuthorizesPermisos;
    use WithPagination;

    public string $buscar = '';

    #[Url(as: 'sede', except: '')]
    public string $idSede = '';

    /**
     * @var array<string, bool>
     */
    public array $filtroEstado = [];

    /**
     * @var array{codigo: string, nombre: string, id_sede: string}
     */
    public array $lugarForm = [
        'codigo' => '',
        'nombre' => '',
        'id_sede' => '',
    ];

    public string $tituloModal = 'Nuevo lugar de inscripción';

    public bool $isEdit = false;

    public ?int $editingId = null;

    public ?int $lugarModalidadesId = null;

    public string $lugarModalidadesNombre = '';

    /**
     * Ids (como texto) de las modalidades marcadas en el modal de modalidades.
     *
     * @var array<int, string>
     */
    public array $modalidadesSeleccionadas = [];

    public function mount(): void
    {
        $this->autorizarPermiso('lugar_inscripcion', 'indice');
    }

    public function updatedBuscar(): void
    {
        $this->resetPage();
    }

    public function updatedIdSede(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroEstado(): void
    {
        $this->resetPage();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'lugarForm.codigo' => [
                'required',
                'string',
                'max:20',
                'regex:/^\s*[A-Za-z0-9_-]+\s*$/',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($this->existeCodigoLugar((string) $value)) {
                        $fail('Ya existe un lugar de inscripción con ese código.');
                    }
                },
            ],
            'lugarForm.nombre' => ['required', 'string', 'max:255'],
            'lugarForm.id_sede' => [
                'required',
                'integer',
                'exists:tbl_sede,id_sed',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($this->cambiaSedeConOfertas((int) $value)) {
                        $fail('No se puede cambiar la sede porque el lugar ya tiene ofertas configuradas.');
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
            'lugarForm.codigo.required' => 'El código es obligatorio.',
            'lugarForm.codigo.max' => 'El código no debe superar los 20 caracteres.',
            'lugarForm.codigo.regex' => 'El código solo admite letras, números, guion y guion bajo.',
            'lugarForm.nombre.required' => 'El nombre es obligatorio.',
            'lugarForm.nombre.max' => 'El nombre no debe superar los 255 caracteres.',
            'lugarForm.id_sede.required' => 'La sede es obligatoria.',
            'lugarForm.id_sede.integer' => 'Selecciona una sede válida.',
            'lugarForm.id_sede.exists' => 'Selecciona una sede válida.',
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
     * Todas las sedes, para filtrar el listado.
     *
     * @return EloquentCollection<int, Sede>
     */
    #[Computed]
    public function sedesFiltro(): EloquentCollection
    {
        return Sede::query()->orderBy('nombre_sed')->get(['id_sed', 'nombre_sed']);
    }

    /**
     * Sedes que se pueden elegir en el formulario: las habilitadas y la que
     * ya tiene el lugar que se está editando.
     *
     * @return EloquentCollection<int, Sede>
     */
    #[Computed]
    public function sedesFormulario(): EloquentCollection
    {
        $idSedeActual = $this->editingId !== null ? (int) $this->lugarForm['id_sede'] : null;

        return Sede::query()
            ->where(function (Builder $query) use ($idSedeActual): void {
                $query->where('estado_sed', EstadoCatalogo::Habilitado->value)
                    ->when($idSedeActual, fn (Builder $query) => $query->orWhere('id_sed', $idSedeActual));
            })
            ->orderBy('nombre_sed')
            ->get(['id_sed', 'nombre_sed']);
    }

    public function cargarLugar(?int $id = null): void
    {
        $this->autorizarPermiso('lugar_inscripcion', $id === null ? 'crear' : 'editar');
        $this->resetValidation();

        if ($id === null) {
            $this->resetForm();
            $this->modal('modal-lugar')->show();

            return;
        }

        $lugar = LugarInscripcion::query()->findOrFail($id);

        $this->editingId = $lugar->id_lin;
        $this->lugarForm = [
            'codigo' => $lugar->codigo_lin,
            'nombre' => $lugar->nombre_lin,
            'id_sede' => (string) $lugar->id_sede,
        ];
        $this->tituloModal = 'Editar lugar de inscripción';
        $this->isEdit = true;
        unset($this->sedesFormulario);

        $this->modal('modal-lugar')->show();
    }

    public function guardar(): void
    {
        $this->autorizarPermiso('lugar_inscripcion', $this->isEdit ? 'editar' : 'crear');
        $data = $this->validate();

        try {
            DB::transaction(function () use ($data): void {
                $payload = [
                    'codigo_lin' => $this->normalizarMayusculas($data['lugarForm']['codigo']),
                    'nombre_lin' => $this->normalizarMayusculas($data['lugarForm']['nombre']),
                    'id_sede' => (int) $data['lugarForm']['id_sede'],
                ];

                if ($this->editingId !== null) {
                    LugarInscripcion::query()->findOrFail($this->editingId)->update($payload);

                    return;
                }

                LugarInscripcion::query()->create([
                    ...$payload,
                    'estado_lin' => EstadoCatalogo::Habilitado->value,
                ]);
            });
        } catch (Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo guardar el lugar de inscripción. Intente nuevamente.', type: 'error');

            return;
        }

        $this->dispatch(
            'toast',
            message: $this->isEdit ? 'Lugar de inscripción actualizado correctamente.' : 'Lugar de inscripción registrado correctamente.',
            type: 'success'
        );

        unset($this->lugares);
        $this->modal('modal-lugar')->close();
        $this->resetForm();
    }

    public function cargarModalidadesLugar(int $id): void
    {
        $this->autorizarPermiso('lugar_inscripcion', 'editar');

        $lugar = LugarInscripcion::query()->findOrFail($id);

        $this->lugarModalidadesId = $lugar->id_lin;
        $this->lugarModalidadesNombre = "{$lugar->codigo_lin} · {$lugar->nombre_lin}";
        $this->modalidadesSeleccionadas = ModalidadLugarInscripcion::query()
            ->where('id_lugar_inscripcion', $lugar->id_lin)
            ->where('estado_mli', EstadoCatalogo::Habilitado->value)
            ->pluck('id_modalidad_admision')
            ->map(fn (int $idModalidad): string => (string) $idModalidad)
            ->values()
            ->all();

        unset($this->modalidadesDisponibles);
        $this->resetValidation();
        $this->modal('modal-lugar-modalidades')->show();
    }

    /**
     * Modalidades que se pueden marcar: las habilitadas y las que el lugar
     * ya tiene asignadas (aunque estén deshabilitadas, para poder quitarlas).
     *
     * @return EloquentCollection<int, ModalidadAdmision>
     */
    #[Computed]
    public function modalidadesDisponibles(): EloquentCollection
    {
        if ($this->lugarModalidadesId === null) {
            return new EloquentCollection;
        }

        return ModalidadAdmision::query()
            ->where(function (Builder $query): void {
                $query->where('estado_mad', EstadoCatalogo::Habilitado->value)
                    ->orWhereHas('modalidadesLugarInscripcion', fn (Builder $relacion) => $relacion->where('id_lugar_inscripcion', $this->lugarModalidadesId));
            })
            ->orderBy('nombre_mad')
            ->get();
    }

    /**
     * Sincroniza las modalidades del lugar: crea o reactiva las marcadas y,
     * de las desmarcadas, elimina las que no tienen ofertas y deshabilita las
     * que sí, para no romper las ofertas ya configuradas.
     */
    public function guardarModalidades(): void
    {
        $this->autorizarPermiso('lugar_inscripcion', 'editar');

        if ($this->lugarModalidadesId === null) {
            return;
        }

        $this->validate([
            'modalidadesSeleccionadas' => ['array'],
            'modalidadesSeleccionadas.*' => ['integer', 'exists:tbl_modalidad_admision,id_mad'],
        ], [
            'modalidadesSeleccionadas.*.exists' => 'Una de las modalidades seleccionadas ya no existe.',
        ]);

        $seleccionadas = collect($this->modalidadesSeleccionadas)
            ->map(fn (string|int $idModalidad): int => (int) $idModalidad)
            ->unique()
            ->values();

        try {
            DB::transaction(function () use ($seleccionadas): void {
                $lugar = LugarInscripcion::query()->findOrFail($this->lugarModalidadesId);

                $asignaciones = ModalidadLugarInscripcion::query()
                    ->withTrashed()
                    ->withCount('ofertasBase')
                    ->where('id_lugar_inscripcion', $lugar->id_lin)
                    ->get()
                    ->keyBy('id_modalidad_admision');

                foreach ($seleccionadas as $idModalidad) {
                    $asignacion = $asignaciones->get($idModalidad);

                    if ($asignacion === null) {
                        ModalidadLugarInscripcion::query()->create([
                            'id_modalidad_admision' => $idModalidad,
                            'id_lugar_inscripcion' => $lugar->id_lin,
                            'estado_mli' => EstadoCatalogo::Habilitado->value,
                        ]);

                        continue;
                    }

                    if ($asignacion->trashed()) {
                        $asignacion->restore();
                    }

                    if ($asignacion->estado_mli !== EstadoCatalogo::Habilitado) {
                        $asignacion->update(['estado_mli' => EstadoCatalogo::Habilitado->value]);
                    }
                }

                foreach ($asignaciones as $idModalidad => $asignacion) {
                    if ($asignacion->trashed() || $seleccionadas->contains($idModalidad)) {
                        continue;
                    }

                    if ($asignacion->ofertas_base_count > 0) {
                        if ($asignacion->estado_mli !== EstadoCatalogo::Deshabilitado) {
                            $asignacion->update(['estado_mli' => EstadoCatalogo::Deshabilitado->value]);
                        }

                        continue;
                    }

                    $asignacion->delete();
                }
            });
        } catch (Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudieron guardar las modalidades del lugar.', type: 'error');

            return;
        }

        unset($this->lugares);
        $this->modal('modal-lugar-modalidades')->close();
        $this->reset(['lugarModalidadesId', 'lugarModalidadesNombre', 'modalidadesSeleccionadas']);
        $this->dispatch('toast', message: 'Modalidades del lugar actualizadas.', type: 'success');
    }

    #[On('lugar-cambiar-estado')]
    public function cambiarEstado(?int $id_lugar = null): void
    {
        $this->autorizarPermiso('lugar_inscripcion', 'estado');

        if ($id_lugar === null) {
            $this->dispatch('toast', message: 'ID de lugar de inscripción no proporcionado.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($id_lugar): void {
                $lugar = LugarInscripcion::query()->findOrFail($id_lugar);

                $lugar->update([
                    'estado_lin' => $lugar->estado_lin === EstadoCatalogo::Habilitado
                        ? EstadoCatalogo::Deshabilitado->value
                        : EstadoCatalogo::Habilitado->value,
                ]);
            });
        } catch (Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo cambiar el estado del lugar de inscripción.', type: 'error');

            return;
        }

        unset($this->lugares);
        $this->dispatch('toast', message: 'Estado del lugar de inscripción actualizado.', type: 'success');
    }

    #[On('lugar-eliminar')]
    public function eliminar(?int $id_lugar = null): void
    {
        $this->autorizarPermiso('lugar_inscripcion', 'eliminar');

        if ($id_lugar === null) {
            $this->dispatch('toast', message: 'ID de lugar de inscripción no proporcionado.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($id_lugar): void {
                $lugar = LugarInscripcion::query()
                    ->withCount(['modalidadesLugarInscripcion' => fn (Builder $query) => $query->withTrashed()])
                    ->findOrFail($id_lugar);

                if ($lugar->modalidades_lugar_inscripcion_count > 0) {
                    throw new RuntimeException('El lugar tiene modalidades asignadas y no puede eliminarse. Puede deshabilitarlo.');
                }

                $lugar->delete();
            });
        } catch (Throwable $throwable) {
            report_if(! $throwable instanceof RuntimeException, $throwable);
            $this->dispatch(
                'toast',
                message: $throwable instanceof RuntimeException ? $throwable->getMessage() : 'No se pudo eliminar el lugar de inscripción.',
                type: 'error'
            );

            return;
        }

        if ($this->editingId === $id_lugar) {
            $this->resetForm();
            $this->modal('modal-lugar')->close();
        }

        unset($this->lugares);
        $this->dispatch('toast', message: 'Lugar de inscripción eliminado correctamente.', type: 'success');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'isEdit']);
        $this->tituloModal = 'Nuevo lugar de inscripción';
        $this->lugarForm = [
            'codigo' => '',
            'nombre' => '',
            'id_sede' => '',
        ];
        unset($this->sedesFormulario);
        $this->resetValidation();
    }

    #[Computed]
    public function lugares(): LengthAwarePaginator
    {
        return LugarInscripcion::query()
            ->with([
                'sede:id_sed,nombre_sed',
                'modalidadesLugarInscripcion' => fn ($query) => $query
                    ->where('estado_mli', EstadoCatalogo::Habilitado->value)
                    ->with('modalidadAdmision:id_mad,nombre_mad'),
            ])
            ->when(filled($this->buscar), function (Builder $query): void {
                $search = $this->normalizarTexto($this->buscar);

                $query->where(function (Builder $innerQuery) use ($search): void {
                    $innerQuery->where('codigo_lin', 'like', "%{$search}%")
                        ->orWhere('nombre_lin', 'like', "%{$search}%");
                });
            })
            ->when($this->idSede !== '', fn (Builder $query) => $query->where('id_sede', (int) $this->idSede))
            ->when($this->estadosSeleccionados !== [], function (Builder $query): void {
                $query->whereIn('estado_lin', $this->estadosSeleccionados);
            })
            ->orderBy('codigo_lin')
            ->paginate(10);
    }

    /**
     * Revisa duplicados también entre los lugares eliminados, porque la tabla
     * mantiene el índice único del código sobre ellos.
     */
    private function existeCodigoLugar(string $valor): bool
    {
        $valor = $this->normalizarMayusculas($valor);

        if ($valor === '') {
            return false;
        }

        return LugarInscripcion::query()
            ->withTrashed()
            ->whereRaw('UPPER(codigo_lin) = ?', [$valor])
            ->when($this->editingId, fn (Builder $query) => $query->where('id_lin', '!=', $this->editingId))
            ->exists();
    }

    /**
     * Cambiar de sede un lugar con ofertas dejaría ofertas que mezclan un
     * lugar de una sede con carreras de otra.
     */
    private function cambiaSedeConOfertas(int $idSedeNueva): bool
    {
        if ($this->editingId === null) {
            return false;
        }

        $lugar = LugarInscripcion::query()->withCount('ofertasBase')->find($this->editingId);

        return $lugar !== null
            && (int) $lugar->id_sede !== $idSedeNueva
            && $lugar->ofertas_base_count > 0;
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
