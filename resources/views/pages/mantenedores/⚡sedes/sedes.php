<?php

use App\Enums\EstadoCatalogo;
use App\Models\Sede;
use App\Models\Ubigeo;
use App\Traits\AuthorizesPermisos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('layouts.app')]
#[Title('Sedes')]
class extends Component
{
    use AuthorizesPermisos;
    use WithPagination;

    public string $buscar = '';

    /**
     * @var array<string, bool>
     */
    public array $filtroEstado = [];

    /**
     * @var array{codigo: string, nombre: string, id_ubigeo: string, direccion: string}
     */
    public array $sedeForm = [
        'codigo' => '',
        'nombre' => '',
        'id_ubigeo' => '',
        'direccion' => '',
    ];

    public string $ubigeoLabel = '';

    public string $tituloModal = 'Nueva sede';

    public bool $isEdit = false;

    public ?int $editingId = null;

    public function mount(): void
    {
        $this->autorizarPermiso('sede', 'indice');
    }

    public function updatedBuscar(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroEstado(): void
    {
        $this->resetPage();
    }

    public function updatedSedeFormIdUbigeo(string $value): void
    {
        if ($value === '') {
            $this->ubigeoLabel = '';
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'sedeForm.codigo' => [
                'required',
                'string',
                'max:20',
                'regex:/^\s*[A-Za-z0-9_-]+\s*$/',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($this->existeSedeCon('codigo_sed', (string) $value)) {
                        $fail('Ya existe una sede con ese código.');
                    }
                },
            ],
            'sedeForm.nombre' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($this->existeSedeCon('nombre_sed', (string) $value)) {
                        $fail('Ya existe una sede con ese nombre.');
                    }
                },
            ],
            'sedeForm.id_ubigeo' => ['required', 'string', 'size:6', 'exists:tbl_ubigeo,id_ubi'],
            'sedeForm.direccion' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'sedeForm.codigo.required' => 'El código es obligatorio.',
            'sedeForm.codigo.max' => 'El código no debe superar los 20 caracteres.',
            'sedeForm.codigo.regex' => 'El código solo admite letras, números, guion y guion bajo.',
            'sedeForm.nombre.required' => 'El nombre es obligatorio.',
            'sedeForm.nombre.max' => 'El nombre no debe superar los 255 caracteres.',
            'sedeForm.id_ubigeo.required' => 'El ubigeo es obligatorio.',
            'sedeForm.id_ubigeo.size' => 'Selecciona un ubigeo válido.',
            'sedeForm.id_ubigeo.exists' => 'Selecciona un ubigeo válido.',
            'sedeForm.direccion.max' => 'La dirección no debe superar los 255 caracteres.',
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

    public function cargarSede(?int $id = null): void
    {
        $this->autorizarPermiso('sede', $id === null ? 'crear' : 'editar');
        $this->resetValidation();

        if ($id === null) {
            $this->resetForm();
            $this->modal('modal-sede')->show();

            return;
        }

        $sede = Sede::query()->with('ubigeo')->findOrFail($id);

        $this->editingId = $sede->id_sed;
        $this->sedeForm = [
            'codigo' => $sede->codigo_sed,
            'nombre' => $sede->nombre_sed,
            'id_ubigeo' => (string) $sede->id_ubigeo,
            'direccion' => (string) ($sede->direccion_sed ?? ''),
        ];
        $this->ubigeoLabel = $this->formatearEtiquetaUbigeo($sede->ubigeo);
        $this->tituloModal = 'Editar sede';
        $this->isEdit = true;

        $this->modal('modal-sede')->show();
    }

    public function guardar(): void
    {
        $this->autorizarPermiso('sede', $this->isEdit ? 'editar' : 'crear');
        $data = $this->validate();

        try {
            DB::transaction(function () use ($data): void {
                $payload = [
                    'codigo_sed' => $this->normalizarMayusculas($data['sedeForm']['codigo']),
                    'nombre_sed' => $this->normalizarMayusculas($data['sedeForm']['nombre']),
                    'id_ubigeo' => $data['sedeForm']['id_ubigeo'],
                    'direccion_sed' => $this->normalizarMayusculas($data['sedeForm']['direccion'] ?? null) ?: null,
                ];

                if ($this->editingId !== null) {
                    Sede::query()->findOrFail($this->editingId)->update($payload);

                    return;
                }

                Sede::query()->create([
                    ...$payload,
                    'estado_sed' => EstadoCatalogo::Habilitado->value,
                ]);
            });
        } catch (Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo guardar la sede. Intente nuevamente.', type: 'error');

            return;
        }

        $this->dispatch(
            'toast',
            message: $this->isEdit ? 'Sede actualizada correctamente.' : 'Sede registrada correctamente.',
            type: 'success'
        );

        unset($this->sedes);
        $this->modal('modal-sede')->close();
        $this->resetForm();
    }

    #[On('sede-cambiar-estado')]
    public function cambiarEstado(?int $id_sede = null): void
    {
        $this->autorizarPermiso('sede', 'estado');

        if ($id_sede === null) {
            $this->dispatch('toast', message: 'ID de sede no proporcionado.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($id_sede): void {
                $sede = Sede::query()->findOrFail($id_sede);

                $sede->update([
                    'estado_sed' => $sede->estado_sed === EstadoCatalogo::Habilitado
                        ? EstadoCatalogo::Deshabilitado->value
                        : EstadoCatalogo::Habilitado->value,
                ]);
            });
        } catch (Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo cambiar el estado de la sede.', type: 'error');

            return;
        }

        unset($this->sedes);
        $this->dispatch('toast', message: 'Estado de la sede actualizado.', type: 'success');
    }

    #[On('sede-eliminar')]
    public function eliminar(?int $id_sede = null): void
    {
        $this->autorizarPermiso('sede', 'eliminar');

        if ($id_sede === null) {
            $this->dispatch('toast', message: 'ID de sede no proporcionado.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($id_sede): void {
                $sede = Sede::query()
                    ->withCount([
                        'procesoCarreras' => fn (Builder $query) => $query->withTrashed(),
                        'inscripciones' => fn (Builder $query) => $query->withTrashed(),
                    ])
                    ->findOrFail($id_sede);

                if ($sede->proceso_carreras_count > 0 || $sede->inscripciones_count > 0) {
                    throw new RuntimeException('La sede se usa en la oferta de un proceso o en inscripciones y no puede eliminarse. Puede deshabilitarla.');
                }

                $sede->delete();
            });
        } catch (Throwable $throwable) {
            report_if(! $throwable instanceof RuntimeException, $throwable);
            $this->dispatch(
                'toast',
                message: $throwable instanceof RuntimeException ? $throwable->getMessage() : 'No se pudo eliminar la sede.',
                type: 'error'
            );

            return;
        }

        if ($this->editingId === $id_sede) {
            $this->resetForm();
            $this->modal('modal-sede')->close();
        }

        unset($this->sedes);
        $this->dispatch('toast', message: 'Sede eliminada correctamente.', type: 'success');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'isEdit', 'ubigeoLabel']);
        $this->tituloModal = 'Nueva sede';
        $this->sedeForm = [
            'codigo' => '',
            'nombre' => '',
            'id_ubigeo' => '',
            'direccion' => '',
        ];
        $this->resetValidation();
    }

    #[Computed]
    public function sedes(): LengthAwarePaginator
    {
        return Sede::query()
            ->with('ubigeo')
            ->withCount([
                'inscripciones as inscripciones_count' => fn (Builder $query) => $query->whereNot('estado_ins', 'ANULADO'),
            ])
            ->when(filled($this->buscar), function (Builder $query): void {
                $search = $this->normalizarTexto($this->buscar);

                $query->where(function (Builder $innerQuery) use ($search): void {
                    $innerQuery->where('codigo_sed', 'like', "%{$search}%")
                        ->orWhere('nombre_sed', 'like', "%{$search}%")
                        ->orWhere('direccion_sed', 'like', "%{$search}%");
                });
            })
            ->when($this->estadosSeleccionados !== [], function (Builder $query): void {
                $query->whereIn('estado_sed', $this->estadosSeleccionados);
            })
            ->orderBy('nombre_sed')
            ->paginate(10);
    }

    /**
     * Revisa duplicados también entre las sedes eliminadas, porque la tabla
     * mantiene los índices únicos sobre ellas.
     */
    private function existeSedeCon(string $columna, string $valor): bool
    {
        $valor = $this->normalizarMayusculas($valor);

        if ($valor === '') {
            return false;
        }

        return Sede::query()
            ->withTrashed()
            ->whereRaw("UPPER({$columna}) = ?", [$valor])
            ->when($this->editingId, fn (Builder $query) => $query->where('id_sed', '!=', $this->editingId))
            ->exists();
    }

    private function formatearEtiquetaUbigeo(?Ubigeo $ubigeo): string
    {
        if ($ubigeo === null) {
            return '';
        }

        return "{$ubigeo->id_ubi} | {$ubigeo->nombre_departamento_ubi} / {$ubigeo->nombre_provincia_ubi} / {$ubigeo->nombre_distrito_ubi}";
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
