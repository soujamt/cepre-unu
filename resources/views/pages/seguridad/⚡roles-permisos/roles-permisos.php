<?php

use App\Enums\EstadoCatalogo;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\RolAccion;
use App\Traits\AuthorizesPermisos;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('ASIGNAR PERMISOS')]
class extends Component
{
    use AuthorizesPermisos;

    public Rol $rolActual;

    /**
     * @var array<int, string>
     */
    public array $seleccionadas = [];

    public function mount(int $rol): void
    {
        $this->autorizarPermiso('roles', 'permisos');
        $this->rolActual = Rol::query()->findOrFail($rol);
        $this->seleccionadas = $this->rolActual->rolPermisoAcciones()
            ->pluck('id_peracc')
            ->map(fn (int $id): string => (string) $id)
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'seleccionadas' => ['array'],
            'seleccionadas.*' => ['integer', 'exists:tbl_permiso_accion,id_peracc'],
        ];
    }

    public function guardar(): void
    {
        $this->autorizarPermiso('roles', 'permisos');

        if ($this->rolActual->nombre_rol === 'ADMINISTRADOR') {
            $this->dispatch('toast', message: 'El rol ADMINISTRADOR ya cuenta con acceso total.', type: 'info');

            return;
        }

        $data = $this->validate();
        $seleccionadas = collect($data['seleccionadas'])
            ->map(fn (string|int $id): int => (int) $id)
            ->values()
            ->all();

        try {
            DB::transaction(function () use ($seleccionadas): void {
                $existentes = $this->rolActual->rolPermisoAcciones()
                    ->withTrashed()
                    ->get()
                    ->keyBy('id_peracc');

                foreach ($seleccionadas as $idPermisoAccion) {
                    $relacion = $existentes->get($idPermisoAccion);

                    if ($relacion instanceof RolAccion) {
                        if ($relacion->trashed()) {
                            $relacion->restore();
                        }

                        continue;
                    }

                    $this->rolActual->rolPermisoAcciones()->create([
                        'id_peracc' => $idPermisoAccion,
                    ]);
                }

                $existentes
                    ->filter(fn (RolAccion $relacion): bool => ! in_array($relacion->id_peracc, $seleccionadas, true))
                    ->each(function (RolAccion $relacion): void {
                        if (! $relacion->trashed()) {
                            $relacion->delete();
                        }
                    });
            });
        } catch (\Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudieron guardar los permisos del rol.', type: 'error');

            return;
        }

        $this->rolActual = $this->rolActual->fresh();
        $this->dispatch('toast', message: 'Permisos del rol actualizados correctamente.', type: 'success');
    }

    #[Computed]
    public function permisosDisponibles(): Collection
    {
        return Permiso::query()
            ->where('estado_permiso', EstadoCatalogo::Habilitado->value)
            ->with([
                'permisoAcciones' => fn ($query) => $query
                    ->where('estado_peracc', EstadoCatalogo::Habilitado->value)
                    ->whereHas('accion', fn ($actionQuery) => $actionQuery->where('estado_accion', EstadoCatalogo::Habilitado->value))
                    ->with('accion')
                    ->orderBy('id_accion'),
            ])
            ->orderBy('nombre_permiso')
            ->get();
    }
};
