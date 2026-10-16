<?php

use App\Enums\EstadoCatalogo;
use App\Models\Proceso;
use App\Models\RequisitoExpediente;
use App\Models\Semestre;
use App\Models\TipoExpediente;
use App\Traits\AuthorizesPermisos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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
#[Title('REQUISITOS')]
class extends Component
{
    use AuthorizesPermisos;
    use WithPagination;

    #[Url(as: 'q')]
    public string $buscar = '';

    #[Url(as: 'semestre')]
    public string $idSemestre = '';

    #[Url(as: 'proceso')]
    public string $idProceso = '';

    /**
     * @var array<string, bool>
     */
    #[Url(as: 'estado')]
    public array $filtroEstado = [];

    /**
     * @var array{id_proceso:string,id_tipo_expediente:string,obligatorio:bool}
     */
    public array $requisitoForm = [
        'id_proceso' => '',
        'id_tipo_expediente' => '',
        'obligatorio' => true,
    ];

    public string $tituloModal = 'Nuevo requisito';

    public bool $isEdit = false;

    public ?int $editingId = null;

    public function mount(): void
    {
        $this->autorizarPermiso('requisito_expediente', 'indice');
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
            'requisitoForm.id_proceso' => [
                'required',
                'integer',
                'exists:tbl_proceso,id_pro',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (blank($value) || blank($this->requisitoForm['id_tipo_expediente'])) {
                        return;
                    }

                    $exists = RequisitoExpediente::query()
                        ->withTrashed()
                        ->where('id_proceso', (int) $value)
                        ->where('id_tipo_expediente', (int) $this->requisitoForm['id_tipo_expediente'])
                        ->when($this->editingId, fn (Builder $query) => $query->where('id_rex', '!=', $this->editingId))
                        ->exists();

                    if ($exists) {
                        $fail('Ese tipo de expediente ya esta registrado para el proceso seleccionado.');
                    }
                },
            ],
            'requisitoForm.id_tipo_expediente' => ['required', 'integer', 'exists:tbl_tipo_expediente,id_tex'],
            'requisitoForm.obligatorio' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'requisitoForm.id_proceso.required' => 'El proceso es obligatorio.',
            'requisitoForm.id_tipo_expediente.required' => 'El tipo de expediente es obligatorio.',
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

    #[Computed]
    public function semestresFiltroDisponibles(): Collection
    {
        return Semestre::query()
            ->where(function (Builder $query): void {
                $query->whereHas('procesos.requisitosExpediente')
                    ->orWhere(function (Builder $builder): void {
                        $builder->where('estado_sem', EstadoCatalogo::Habilitado->value)
                            ->whereHas('procesos', fn (Builder $procesoQuery) => $procesoQuery->where('estado_pro', EstadoCatalogo::Habilitado->value));
                    });
            })
            ->orderByDesc('fecha_inicio_inscripcion_sem')
            ->orderByDesc('id_sem')
            ->get();
    }

    #[Computed]
    public function procesosFiltroDisponibles(): Collection
    {
        return Proceso::query()
            ->with('semestre')
            ->when(filled($this->idSemestre), fn (Builder $query) => $query->where('id_semestre', (int) $this->idSemestre))
            ->where(function (Builder $query): void {
                $query->whereHas('requisitosExpediente')
                    ->orWhere('estado_pro', EstadoCatalogo::Habilitado->value);
            })
            ->orderByDesc('id_pro')
            ->get();
    }

    #[Computed]
    public function procesosFormularioDisponibles(): Collection
    {
        $procesoSeleccionado = filled($this->requisitoForm['id_proceso'])
            ? (int) $this->requisitoForm['id_proceso']
            : null;

        return Proceso::query()
            ->with('semestre')
            ->where(function (Builder $query) use ($procesoSeleccionado): void {
                $query->where('estado_pro', EstadoCatalogo::Habilitado->value);

                if ($procesoSeleccionado !== null) {
                    $query->orWhere('id_pro', $procesoSeleccionado);
                }
            })
            ->orderByDesc('id_pro')
            ->get();
    }

    #[Computed]
    public function tiposExpedienteDisponibles(): Collection
    {
        return TipoExpediente::query()
            ->where('estado_tex', EstadoCatalogo::Habilitado->value)
            ->orderBy('nombre_tex')
            ->get();
    }

    public function limpiarFiltroEstado(): void
    {
        $this->filtroEstado = [];
    }

    public function limpiarFiltrosAcademicos(): void
    {
        $this->idSemestre = '';
        $this->idProceso = '';
        $this->resetPage();
    }

    public function cargarRequisito(?int $id = null): void
    {
        $this->autorizarPermiso('requisito_expediente', $id === null ? 'crear' : 'editar');
        $this->resetValidation();

        if ($id === null) {
            $this->resetForm();
            $this->tituloModal = 'Nuevo requisito';
            $this->isEdit = false;
            $this->modal('modal-requisito')->show();

            return;
        }

        $requisito = RequisitoExpediente::query()->findOrFail($id);

        $this->editingId = $requisito->id_rex;
        $this->requisitoForm = [
            'id_proceso' => (string) $requisito->id_proceso,
            'id_tipo_expediente' => (string) $requisito->id_tipo_expediente,
            'obligatorio' => $requisito->obligatorio_rex,
        ];
        $this->tituloModal = 'Editar requisito';
        $this->isEdit = true;

        $this->modal('modal-requisito')->show();
    }

    public function guardar(): void
    {
        $this->autorizarPermiso('requisito_expediente', $this->isEdit ? 'editar' : 'crear');
        $data = $this->validate();

        try {
            DB::transaction(function () use ($data): void {
                $payload = [
                    'id_proceso' => (int) $data['requisitoForm']['id_proceso'],
                    'id_tipo_expediente' => (int) $data['requisitoForm']['id_tipo_expediente'],
                    'obligatorio_rex' => (bool) $data['requisitoForm']['obligatorio'],
                ];

                if ($this->editingId !== null) {
                    RequisitoExpediente::query()->findOrFail($this->editingId)->update($payload);

                    return;
                }

                RequisitoExpediente::query()->create([
                    ...$payload,
                    'estado_rex' => EstadoCatalogo::Habilitado->value,
                ]);
            });
        } catch (Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo guardar el requisito. Intente nuevamente.', type: 'error');

            return;
        }

        $this->dispatch(
            'toast',
            message: $this->isEdit ? 'Requisito actualizado correctamente.' : 'Requisito registrado correctamente.',
            type: 'success'
        );

        unset($this->requisitos);
        $this->modal('modal-requisito')->close();
        $this->resetForm();
    }

    #[On('requisito-cambiar-estado')]
    public function cambiarEstado(?int $id_requisito = null): void
    {
        $this->autorizarPermiso('requisito_expediente', 'estado');

        if ($id_requisito === null) {
            $this->dispatch('toast', message: 'ID de requisito no proporcionado.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($id_requisito): void {
                $requisito = RequisitoExpediente::query()->findOrFail($id_requisito);

                $requisito->update([
                    'estado_rex' => $requisito->estado_rex === EstadoCatalogo::Habilitado
                        ? EstadoCatalogo::Deshabilitado->value
                        : EstadoCatalogo::Habilitado->value,
                ]);
            });
        } catch (Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo cambiar el estado del requisito.', type: 'error');

            return;
        }

        unset($this->requisitos);
        $this->dispatch('toast', message: 'Estado del requisito actualizado.', type: 'success');
    }

    #[On('requisito-eliminar')]
    public function eliminar(?int $id_requisito = null): void
    {
        $this->autorizarPermiso('requisito_expediente', 'eliminar');

        if ($id_requisito === null) {
            $this->dispatch('toast', message: 'ID de requisito no proporcionado.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($id_requisito): void {
                $requisito = RequisitoExpediente::query()->findOrFail($id_requisito);
                $requisito->delete();
            });
        } catch (Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo eliminar el requisito.', type: 'error');

            return;
        }

        if ($this->editingId === $id_requisito) {
            $this->resetForm();
            $this->modal('modal-requisito')->close();
        }

        unset($this->requisitos);
        $this->dispatch('toast', message: 'Requisito eliminado correctamente.', type: 'success');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'isEdit']);
        $this->tituloModal = 'Nuevo requisito';
        $this->requisitoForm = [
            'id_proceso' => '',
            'id_tipo_expediente' => '',
            'obligatorio' => true,
        ];
        $this->resetValidation();
    }

    #[Computed]
    public function requisitos(): LengthAwarePaginator
    {
        return RequisitoExpediente::query()
            ->with(['proceso.semestre', 'tipoExpediente'])
            ->when(filled($this->buscar), function (Builder $query): void {
                $search = trim($this->buscar);

                $query->where(function (Builder $innerQuery) use ($search): void {
                    $innerQuery->whereHas('proceso', fn (Builder $procesoQuery) => $procesoQuery
                        ->where('nombre_pro', 'like', "%{$search}%")
                        ->orWhereHas('semestre', fn (Builder $semestreQuery) => $semestreQuery
                            ->where('nombre_sem', 'like', "%{$search}%")))
                        ->orWhereHas('tipoExpediente', fn (Builder $tipoExpedienteQuery) => $tipoExpedienteQuery
                            ->where('nombre_tex', 'like', "%{$search}%")
                            ->orWhere('codigo_tex', 'like', "%{$search}%"));
                });
            })
            ->when(filled($this->idSemestre), fn (Builder $query) => $query
                ->whereHas('proceso', fn (Builder $procesoQuery) => $procesoQuery
                    ->where('id_semestre', (int) $this->idSemestre)))
            ->when(filled($this->idProceso), fn (Builder $query) => $query->where('id_proceso', (int) $this->idProceso))
            ->when($this->estadosSeleccionados !== [], fn (Builder $query) => $query->whereIn('estado_rex', $this->estadosSeleccionados))
            ->orderByDesc('id_rex')
            ->paginate(10);
    }
};
