<?php

use App\Enums\EstadoCatalogo;
use App\Models\Colegio;
use App\Models\TipoColegio;
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
#[Title('COLEGIOS')]
class extends Component
{
    use AuthorizesPermisos;
    use WithPagination;

    public string $buscar = '';

    public string $idTipoColegioFiltro = '';

    /**
     * @var array<string, bool>
     */
    public array $filtroEstado = [];

    /**
     * @var array{id: string, codigo_modular: string, nombre: string, id_tipo_colegio: string, id_ubigeo: string, direccion: string}
     */
    public array $colegioForm = [
        'id' => '',
        'codigo_modular' => '',
        'nombre' => '',
        'id_tipo_colegio' => '',
        'id_ubigeo' => '',
        'direccion' => '',
    ];

    public string $tituloModal = 'Nuevo colegio';

    public bool $isEdit = false;

    public ?string $editingId = null;

    public string $ubigeoLabel = '';

    public function mount(): void
    {
        $this->autorizarPermiso('colegio', 'indice');
    }

    public function updatedBuscar(): void
    {
        $this->resetPage();
    }

    public function updatedIdTipoColegioFiltro(): void
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
            'colegioForm.id' => [
                'required',
                'string',
                'max:10',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $codigo = $this->normalizarCodigo((string) $value);

                    if ($codigo === '') {
                        $fail('El codigo del colegio es obligatorio.');

                        return;
                    }

                    $exists = Colegio::query()
                        ->withTrashed()
                        ->where('id_col', $codigo)
                        ->when($this->editingId, fn (Builder $query) => $query->where('id_col', '!=', $this->editingId))
                        ->exists();

                    if ($exists) {
                        $fail('Ya existe un colegio con ese codigo.');
                    }
                },
            ],
            'colegioForm.codigo_modular' => [
                'nullable',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $codigoModular = $this->normalizarCodigoOpcional((string) $value);

                    if ($codigoModular === null) {
                        return;
                    }

                    $exists = Colegio::query()
                        ->withTrashed()
                        ->where('codigo_modular_col', $codigoModular)
                        ->when($this->editingId, fn (Builder $query) => $query->where('id_col', '!=', $this->editingId))
                        ->exists();

                    if ($exists) {
                        $fail('Ya existe un colegio con ese codigo modular.');
                    }
                },
            ],
            'colegioForm.nombre' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $nombre = $this->normalizarTexto((string) $value) ?? '';

                    if ($nombre === '') {
                        $fail('El nombre es obligatorio.');

                        return;
                    }

                    $exists = Colegio::query()
                        ->withTrashed()
                        ->whereRaw('UPPER(nombre_col) = ?', [mb_strtoupper($nombre, 'UTF-8')])
                        ->when($this->editingId, fn (Builder $query) => $query->where('id_col', '!=', $this->editingId))
                        ->exists();

                    if ($exists) {
                        $fail('Ya existe un colegio con ese nombre.');
                    }
                },
            ],
            'colegioForm.id_tipo_colegio' => ['nullable', 'integer', 'exists:tbl_tipo_colegio,id_tco'],
            'colegioForm.id_ubigeo' => ['nullable', 'string', 'size:6', 'exists:tbl_ubigeo,id_ubi'],
            'colegioForm.direccion' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'colegioForm.id.required' => 'El codigo del colegio es obligatorio.',
            'colegioForm.nombre.required' => 'El nombre es obligatorio.',
            'colegioForm.id_tipo_colegio.integer' => 'Selecciona un tipo de colegio valido.',
            'colegioForm.id_tipo_colegio.exists' => 'Selecciona un tipo de colegio valido.',
            'colegioForm.id_ubigeo.size' => 'Selecciona un ubigeo valido.',
            'colegioForm.id_ubigeo.exists' => 'Selecciona un ubigeo valido.',
            'colegioForm.direccion.max' => 'La direccion no debe exceder los 255 caracteres.',
        ];
    }

    public function updatedColegioFormIdUbigeo(string $value): void
    {
        if ($value === '') {
            $this->ubigeoLabel = '';
        }
    }

    public function estados(): array
    {
        return EstadoCatalogo::cases();
    }

    #[Computed]
    public function tiposColegioDisponibles()
    {
        return TipoColegio::query()
            ->where('estado_tco', EstadoCatalogo::Habilitado->value)
            ->orderBy('nombre_tco')
            ->get();
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

    public function cargarColegio(?string $id = null): void
    {
        $this->autorizarPermiso('colegio', $id === null ? 'crear' : 'editar');
        $this->resetValidation();

        if ($id === null) {
            $this->resetForm();
            $this->tituloModal = 'Nuevo colegio';
            $this->isEdit = false;
            $this->modal('modal-colegio')->show();

            return;
        }

        $colegio = Colegio::query()
            ->with(['tipoColegio', 'ubigeo'])
            ->findOrFail($id);

        $this->editingId = $colegio->id_col;
        $this->colegioForm = [
            'id' => $colegio->id_col,
            'codigo_modular' => (string) ($colegio->codigo_modular_col ?? ''),
            'nombre' => $colegio->nombre_col,
            'id_tipo_colegio' => (string) ($colegio->id_tipo_colegio ?? ''),
            'id_ubigeo' => (string) ($colegio->id_ubigeo ?? ''),
            'direccion' => (string) ($colegio->direccion_col ?? ''),
        ];
        $this->ubigeoLabel = $this->formatearEtiquetaUbigeo($colegio->ubigeo);
        $this->tituloModal = 'Editar colegio';
        $this->isEdit = true;

        $this->modal('modal-colegio')->show();
    }

    public function guardar(): void
    {
        $this->autorizarPermiso('colegio', $this->isEdit ? 'editar' : 'crear');
        $data = $this->validate();

        try {
            DB::transaction(function () use ($data): void {
                $payload = [
                    'codigo_modular_col' => $this->normalizarCodigoOpcional($data['colegioForm']['codigo_modular']),
                    'nombre_col' => mb_strtoupper($this->normalizarTexto($data['colegioForm']['nombre']) ?? '', 'UTF-8'),
                    'id_tipo_colegio' => $data['colegioForm']['id_tipo_colegio'] !== ''
                        ? (int) $data['colegioForm']['id_tipo_colegio']
                        : null,
                    'id_ubigeo' => $data['colegioForm']['id_ubigeo'] !== ''
                        ? $data['colegioForm']['id_ubigeo']
                        : null,
                    'direccion_col' => $this->normalizarTexto($data['colegioForm']['direccion']),
                ];

                if ($this->editingId !== null) {
                    $colegio = Colegio::query()->findOrFail($this->editingId);

                    $colegio->update($payload);

                    return;
                }

                Colegio::query()->create([
                    'id_col' => $this->normalizarCodigo($data['colegioForm']['id']),
                    ...$payload,
                    'estado_col' => EstadoCatalogo::Habilitado->value,
                ]);
            });
        } catch (\Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo guardar el colegio. Intente nuevamente.', type: 'error');

            return;
        }

        $this->dispatch(
            'toast',
            message: $this->isEdit ? 'Colegio actualizado correctamente.' : 'Colegio registrado correctamente.',
            type: 'success'
        );

        unset($this->colegios);
        $this->modal('modal-colegio')->close();
        $this->resetForm();
    }

    #[On('colegio-cambiar-estado')]
    public function cambiarEstado(?string $id_colegio = null): void
    {
        $this->autorizarPermiso('colegio', 'estado');

        if ($id_colegio === null) {
            $this->dispatch('toast', message: 'ID de colegio no proporcionado.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($id_colegio): void {
                $colegio = Colegio::query()->findOrFail($id_colegio);

                $colegio->update([
                    'estado_col' => $colegio->estado_col === EstadoCatalogo::Habilitado
                        ? EstadoCatalogo::Deshabilitado->value
                        : EstadoCatalogo::Habilitado->value,
                ]);
            });
        } catch (\Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo cambiar el estado del colegio.', type: 'error');

            return;
        }

        unset($this->colegios);
        $this->dispatch('toast', message: 'Estado del colegio actualizado.', type: 'success');
    }

    #[On('colegio-eliminar')]
    public function eliminar(?string $id_colegio = null): void
    {
        $this->autorizarPermiso('colegio', 'eliminar');

        if ($id_colegio === null) {
            $this->dispatch('toast', message: 'ID de colegio no proporcionado.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($id_colegio): void {
                $colegio = Colegio::query()
                    ->withCount('inscripciones')
                    ->findOrFail($id_colegio);

                if ($colegio->inscripciones_count > 0) {
                    throw new \RuntimeException('El colegio tiene inscripciones relacionadas y no puede eliminarse.');
                }

                $colegio->delete();
            });
        } catch (\Throwable $throwable) {
            report_if(! $throwable instanceof \RuntimeException, $throwable);
            $this->dispatch(
                'toast',
                message: $throwable->getMessage() ?: 'No se pudo eliminar el colegio.',
                type: 'error'
            );

            return;
        }

        if ($this->editingId === $id_colegio) {
            $this->resetForm();
            $this->modal('modal-colegio')->close();
        }

        unset($this->colegios);
        $this->dispatch('toast', message: 'Colegio eliminado correctamente.', type: 'success');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'isEdit']);
        $this->tituloModal = 'Nuevo colegio';
        $this->colegioForm = [
            'id' => '',
            'codigo_modular' => '',
            'nombre' => '',
            'id_tipo_colegio' => '',
            'id_ubigeo' => '',
            'direccion' => '',
        ];
        $this->ubigeoLabel = '';
        $this->resetValidation();
    }

    #[Computed]
    public function colegios(): LengthAwarePaginator
    {
        return Colegio::query()
            ->with(['tipoColegio', 'ubigeo'])
            ->withCount('inscripciones')
            ->when(filled($this->buscar), function (Builder $query): void {
                $search = $this->normalizarTexto($this->buscar);

                $query->where(function (Builder $innerQuery) use ($search): void {
                    $innerQuery->where('id_col', 'like', "%{$search}%")
                        ->orWhere('codigo_modular_col', 'like', "%{$search}%")
                        ->orWhere('nombre_col', 'like', "%{$search}%")
                        ->orWhere('direccion_col', 'like', "%{$search}%");
                });
            })
            ->when(filled($this->idTipoColegioFiltro), function (Builder $query): void {
                $query->where('id_tipo_colegio', (int) $this->idTipoColegioFiltro);
            })
            ->when($this->estadosSeleccionados !== [], function (Builder $query): void {
                $query->whereIn('estado_col', $this->estadosSeleccionados);
            })
            ->orderBy('nombre_col')
            ->paginate(10);
    }

    private function normalizarTexto(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $normalizado = preg_replace('/\s+/u', ' ', trim($valor));

        return filled($normalizado) ? $normalizado : null;
    }

    private function normalizarCodigo(string $valor): string
    {
        return trim($valor);
    }

    private function normalizarCodigoOpcional(string $valor): ?string
    {
        $valor = trim($valor);

        return $valor !== '' ? $valor : null;
    }

    private function formatearEtiquetaUbigeo(?Ubigeo $ubigeo): string
    {
        if ($ubigeo === null) {
            return '';
        }

        return "{$ubigeo->id_ubi} | {$ubigeo->nombre_departamento_ubi} / {$ubigeo->nombre_provincia_ubi} / {$ubigeo->nombre_distrito_ubi}";
    }
};
