<?php

namespace App\Http\Controllers\ConfiguracionAcademica;

use App\Enums\EstadoCatalogo;
use App\Enums\EstadoProceso;
use App\Http\Controllers\Concerns\EligeProceso;
use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\ModalidadAdmision;
use App\Models\Proceso;
use App\Models\ProcesoAula;
use App\Models\ProcesoCarrera;
use App\Models\ProcesoModalidad;
use App\Models\Sede;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Oferta del proceso: modalidades habilitadas y carreras que se ofrecen en cada sede.
 */
class OfertaController extends Controller
{
    use EligeProceso;

    public function index(Request $request): Response
    {
        $proceso = $this->procesoElegido($request);

        return Inertia::render('configuracion-academica/ofertas', [
            'procesos' => $this->opcionesProceso(),
            'proceso' => $proceso === null ? null : [
                ...$this->resumenProceso($proceso),
                'anioEgresoOrdinario' => $proceso->anioEgresoMaximoOrdinario(),
            ],
            'modalidades' => $this->modalidades()->map(fn (ModalidadAdmision $modalidad): array => [
                'id' => $modalidad->id_mad,
                'nombre' => $modalidad->nombre_mad,
                'condicion' => $modalidad->condicion_egreso_mad?->getNombre(),
                'condicionValor' => $modalidad->condicion_egreso_mad?->value,
            ]),
            'sedes' => $this->sedes()->map(fn (Sede $sede): array => ['id' => $sede->id_sed, 'nombre' => $sede->nombre_sed]),
            'carreras' => $this->carreras()->map(fn (Carrera $carrera): array => ['id' => $carrera->id_car, 'codigo' => $carrera->codigo_car, 'nombre' => $carrera->nombre_car]),
            'seleccion' => $this->seleccion($proceso),
            'turnos' => $this->turnosConAulas($proceso),
        ]);
    }

    /**
     * Habilita las modalidades y carreras marcadas y deshabilita las demás, sin borrar
     * registros; las combinaciones que nunca se habilitaron no se crean.
     */
    public function update(Request $request, Proceso $proceso): RedirectResponse
    {
        if ($proceso->estado_pro === EstadoProceso::Cerrado) {
            Inertia::flash('toast', ['type' => 'warning', 'message' => 'El proceso ya cerró; su oferta no se puede modificar.']);

            return back();
        }

        $datos = $request->validate([
            'modalidades' => ['required', 'array', 'min:1'],
            'modalidades.*' => ['integer'],
            'carreras' => ['array'],
            'carreras.*' => ['array'],
            'carreras.*.*' => ['integer'],
        ], [
            'modalidades.required' => 'Habilite al menos una modalidad.',
            'modalidades.min' => 'Habilite al menos una modalidad.',
        ]);

        $idsModalidad = array_map('intval', $datos['modalidades']);
        $carrerasPorSede = collect($datos['carreras'] ?? [])->map(fn (array $ids): array => array_map('intval', $ids));

        DB::transaction(function () use ($proceso, $idsModalidad, $carrerasPorSede): void {
            foreach ($this->modalidades() as $modalidad) {
                $this->sincronizar(
                    ProcesoModalidad::withTrashed()->firstOrNew(['id_proceso' => $proceso->id_pro, 'id_modalidad_admision' => $modalidad->id_mad]),
                    'estado_pmo',
                    in_array($modalidad->id_mad, $idsModalidad, true),
                );
            }

            $carreras = $this->carreras();

            foreach ($this->sedes() as $sede) {
                $idsCarrera = $carrerasPorSede->get($sede->id_sed, []);

                foreach ($carreras as $carrera) {
                    $this->sincronizar(
                        ProcesoCarrera::withTrashed()->firstOrNew(['id_proceso' => $proceso->id_pro, 'id_sede' => $sede->id_sed, 'id_carrera' => $carrera->id_car]),
                        'estado_pca',
                        in_array($carrera->id_car, $idsCarrera, true),
                    );
                }
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Oferta del proceso guardada correctamente.']);

        return back();
    }

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

    /**
     * @return Collection<int, ModalidadAdmision>
     */
    private function modalidades(): Collection
    {
        return ModalidadAdmision::query()
            ->where('estado_mad', EstadoCatalogo::Habilitado->value)
            ->whereNotNull('condicion_egreso_mad')
            ->orderBy('nombre_mad')
            ->get();
    }

    /**
     * @return Collection<int, Sede>
     */
    private function sedes(): Collection
    {
        return Sede::query()->where('estado_sed', EstadoCatalogo::Habilitado->value)->orderBy('nombre_sed')->get();
    }

    /**
     * @return Collection<int, Carrera>
     */
    private function carreras(): Collection
    {
        return Carrera::query()->where('estado_car', EstadoCatalogo::Habilitado->value)->orderBy('nombre_car')->get();
    }

    /**
     * @return array{modalidades: list<int>, carreras: array<int, list<int>>}
     */
    private function seleccion(?Proceso $proceso): array
    {
        if ($proceso === null) {
            return ['modalidades' => [], 'carreras' => []];
        }

        return [
            'modalidades' => ProcesoModalidad::query()
                ->where('id_proceso', $proceso->id_pro)
                ->where('estado_pmo', EstadoCatalogo::Habilitado->value)
                ->pluck('id_modalidad_admision')
                ->map(fn (mixed $id): int => (int) $id)
                ->values()
                ->all(),
            'carreras' => ProcesoCarrera::query()
                ->where('id_proceso', $proceso->id_pro)
                ->where('estado_pca', EstadoCatalogo::Habilitado->value)
                ->get(['id_sede', 'id_carrera'])
                ->groupBy('id_sede')
                ->map(fn ($grupo): array => $grupo->pluck('id_carrera')->map(fn (mixed $id): int => (int) $id)->values()->all())
                ->all(),
        ];
    }

    /**
     * Turnos con aulas habilitadas en el proceso y su capacidad total.
     *
     * @return list<array{nombre: string, aulas: int, capacidad: int}>
     */
    private function turnosConAulas(?Proceso $proceso): array
    {
        if ($proceso === null) {
            return [];
        }

        return ProcesoAula::query()
            ->with('turno')
            ->where('id_proceso', $proceso->id_pro)
            ->where('estado_pau', EstadoCatalogo::Habilitado->value)
            ->get()
            ->groupBy('id_turno')
            ->map(fn ($aulas): array => [
                'nombre' => $aulas->first()->turno?->nombre_tur ?? 'SIN TURNO',
                'aulas' => $aulas->count(),
                'capacidad' => (int) $aulas->sum('capacidad_pau'),
            ])
            ->sortBy('nombre')
            ->values()
            ->all();
    }
}
