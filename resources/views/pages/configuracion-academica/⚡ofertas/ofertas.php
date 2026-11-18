<?php

use App\Enums\EstadoCatalogo;
use App\Enums\EstadoProceso;
use App\Models\Carrera;
use App\Models\ModalidadAdmision;
use App\Models\Proceso;
use App\Models\ProcesoAula;
use App\Models\ProcesoCarrera;
use App\Models\ProcesoModalidad;
use App\Models\Sede;
use App\Traits\AuthorizesPermisos;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Oferta del proceso: qué modalidades y qué carreras por sede se habilitan
 * para inscribirse. Los turnos salen de las aulas asignadas al proceso.
 */
new
#[Layout('layouts.app')]
#[Title('Oferta del proceso')]
class extends Component
{
    use AuthorizesPermisos;

    #[Url(as: 'proceso')]
    public string $idProceso = '';

    /**
     * @var array<int|string, bool>
     */
    public array $modalidadesSeleccionadas = [];

    /**
     * Carreras marcadas por sede: [id_sede => [id_carrera => bool]].
     *
     * @var array<int|string, array<int|string, bool>>
     */
    public array $carrerasSeleccionadas = [];

    public function mount(): void
    {
        $this->autorizarPermiso('oferta', 'indice');

        if (blank($this->idProceso)) {
            $this->idProceso = (string) ($this->procesoPorDefecto()?->id_pro ?? '');
        }

        $this->cargarSeleccion();
    }

    public function updatedIdProceso(): void
    {
        unset($this->procesoSeleccionado, $this->turnosConAulas);
        $this->cargarSeleccion();
    }

    #[Computed]
    public function procesosDisponibles(): Collection
    {
        return Proceso::query()->orderByDesc('fecha_inicio_pro')->get();
    }

    #[Computed]
    public function procesoSeleccionado(): ?Proceso
    {
        return blank($this->idProceso) ? null : Proceso::query()->find((int) $this->idProceso);
    }

    #[Computed]
    public function modalidades(): Collection
    {
        return ModalidadAdmision::query()
            ->where('estado_mad', EstadoCatalogo::Habilitado->value)
            ->whereNotNull('condicion_egreso_mad')
            ->orderBy('nombre_mad')
            ->get();
    }

    #[Computed]
    public function sedes(): Collection
    {
        return Sede::query()
            ->where('estado_sed', EstadoCatalogo::Habilitado->value)
            ->orderBy('nombre_sed')
            ->get();
    }

    #[Computed]
    public function carreras(): Collection
    {
        return Carrera::query()
            ->where('estado_car', EstadoCatalogo::Habilitado->value)
            ->orderBy('nombre_car')
            ->get();
    }

    /**
     * Turnos que tienen aulas habilitadas en el proceso, con su capacidad total.
     *
     * @return \Illuminate\Support\Collection<int, array{nombre:string,aulas:int,capacidad:int}>
     */
    #[Computed]
    public function turnosConAulas(): \Illuminate\Support\Collection
    {
        if ($this->procesoSeleccionado === null) {
            return collect();
        }

        return ProcesoAula::query()
            ->with('turno')
            ->where('id_proceso', $this->procesoSeleccionado->id_pro)
            ->where('estado_pau', EstadoCatalogo::Habilitado->value)
            ->get()
            ->groupBy('id_turno')
            ->map(fn (Collection $aulas): array => [
                'nombre' => $aulas->first()->turno?->nombre_tur ?? 'SIN TURNO',
                'aulas' => $aulas->count(),
                'capacidad' => (int) $aulas->sum('capacidad_pau'),
            ])
            ->sortBy('nombre')
            ->values();
    }

    public function carrerasMarcadas(int $idSede): int
    {
        return count(array_filter($this->carrerasSeleccionadas[$idSede] ?? []));
    }

    public function marcarTodasLasCarreras(int $idSede): void
    {
        $this->carrerasSeleccionadas[$idSede] = $this->carreras
            ->mapWithKeys(fn (Carrera $carrera): array => [$carrera->id_car => true])
            ->all();
    }

    public function quitarTodasLasCarreras(int $idSede): void
    {
        $this->carrerasSeleccionadas[$idSede] = [];
    }

    public function guardar(): void
    {
        $this->autorizarPermiso('oferta', 'editar');

        $proceso = $this->procesoSeleccionado;

        if ($proceso === null) {
            $this->addError('idProceso', 'Seleccione un proceso.');

            return;
        }

        if ($proceso->estado_pro === EstadoProceso::Cerrado) {
            $this->dispatch('toast', message: 'El proceso ya cerró; su oferta no se puede modificar.', type: 'warning');

            return;
        }

        $idsModalidad = $this->idsMarcados($this->modalidadesSeleccionadas);

        if ($idsModalidad === []) {
            $this->addError('modalidadesSeleccionadas', 'Habilite al menos una modalidad.');

            return;
        }

        try {
            DB::transaction(function () use ($proceso, $idsModalidad): void {
                foreach ($this->modalidades as $modalidad) {
                    $this->sincronizar(
                        ProcesoModalidad::withTrashed()->firstOrNew([
                            'id_proceso' => $proceso->id_pro,
                            'id_modalidad_admision' => $modalidad->id_mad,
                        ]),
                        'estado_pmo',
                        in_array($modalidad->id_mad, $idsModalidad, true),
                    );
                }

                foreach ($this->sedes as $sede) {
                    $idsCarrera = $this->idsMarcados($this->carrerasSeleccionadas[$sede->id_sed] ?? []);

                    foreach ($this->carreras as $carrera) {
                        $this->sincronizar(
                            ProcesoCarrera::withTrashed()->firstOrNew([
                                'id_proceso' => $proceso->id_pro,
                                'id_sede' => $sede->id_sed,
                                'id_carrera' => $carrera->id_car,
                            ]),
                            'estado_pca',
                            in_array($carrera->id_car, $idsCarrera, true),
                        );
                    }
                }
            });
        } catch (Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo guardar la oferta del proceso.', type: 'error');

            return;
        }

        $this->resetErrorBag();
        $this->dispatch('toast', message: 'Oferta del proceso guardada correctamente.', type: 'success');
    }

    /**
     * Habilita o deshabilita un registro sin borrarlo; los que nunca se
     * habilitaron no se crean.
     */
    private function sincronizar(ProcesoModalidad|ProcesoCarrera $registro, string $columnaEstado, bool $habilitado): void
    {
        if (! $registro->exists && ! $habilitado) {
            return;
        }

        if ($registro->trashed()) {
            $registro->restore();
        }

        $registro->{$columnaEstado} = $habilitado ? EstadoCatalogo::Habilitado : EstadoCatalogo::Deshabilitado;
        $registro->save();
    }

    private function cargarSeleccion(): void
    {
        $this->resetErrorBag();
        $this->modalidadesSeleccionadas = [];
        $this->carrerasSeleccionadas = [];

        if (blank($this->idProceso)) {
            return;
        }

        $this->modalidadesSeleccionadas = ProcesoModalidad::query()
            ->where('id_proceso', (int) $this->idProceso)
            ->where('estado_pmo', EstadoCatalogo::Habilitado->value)
            ->pluck('id_modalidad_admision')
            ->mapWithKeys(fn (int|string $id): array => [(int) $id => true])
            ->all();

        ProcesoCarrera::query()
            ->where('id_proceso', (int) $this->idProceso)
            ->where('estado_pca', EstadoCatalogo::Habilitado->value)
            ->get(['id_sede', 'id_carrera'])
            ->each(function (ProcesoCarrera $procesoCarrera): void {
                $this->carrerasSeleccionadas[$procesoCarrera->id_sede][$procesoCarrera->id_carrera] = true;
            });
    }

    /**
     * @param  array<int|string, bool>  $seleccion
     * @return list<int>
     */
    private function idsMarcados(array $seleccion): array
    {
        return array_values(array_map('intval', array_keys(array_filter($seleccion))));
    }

    private function procesoPorDefecto(): ?Proceso
    {
        return Proceso::query()
            ->where('estado_pro', EstadoProceso::Habilitado->value)
            ->orderByDesc('fecha_inicio_pro')
            ->first()
            ?? Proceso::query()->orderByDesc('fecha_inicio_pro')->first();
    }
};
