<?php

namespace App\Http\Controllers\Seguridad;

use App\Enums\EstadoCatalogo;
use App\Http\Controllers\CatalogoController;
use App\Http\Requests\Seguridad\PermisoRequest;
use App\Models\Accion;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PermisoController extends CatalogoController
{
    public function index(Request $request): Response
    {
        $filtros = $this->filtros($request);

        $consulta = Permiso::query()->with([
            'permisoAcciones' => fn ($query) => $query->where('estado_peracc', EstadoCatalogo::Habilitado->value)->with('accion')->orderBy('id_accion'),
        ]);

        $permisos = $this->aplicarFiltros($consulta, $filtros, ['clave_permiso', 'nombre_permiso'], 'estado_permiso')
            ->orderByDesc('id_permiso')
            ->paginate(self::POR_PAGINA)
            ->withQueryString()
            ->through(fn (Permiso $permiso): array => [
                'id' => $permiso->id_permiso,
                'nombre' => $permiso->nombre_permiso,
                'clave' => $permiso->clave_permiso,
                'acciones' => $permiso->permisoAcciones->map(fn (PermisoAccion $permisoAccion): array => [
                    'id' => $permisoAccion->id_accion,
                    'nombre' => $permisoAccion->accion?->nombre_accion ?? $permisoAccion->clave_peracc,
                ])->values()->all(),
                'estado' => $permiso->estado_permiso->value,
                'registro' => $this->registro($permiso->creado_en),
            ]);

        return Inertia::render('seguridad/permisos', [
            'permisos' => $permisos,
            'filtros' => $filtros,
            'acciones' => Accion::query()->habilitadas()->get()->map(fn (Accion $accion): array => ['valor' => (string) $accion->id_acc, 'nombre' => $accion->nombre_accion]),
        ]);
    }

    public function store(PermisoRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $permiso = Permiso::query()->create([
                'clave_permiso' => PermisoRequest::claveDesdeNombre($request->validated('nombre')),
                'nombre_permiso' => $request->validated('nombre'),
                'estado_permiso' => EstadoCatalogo::Habilitado->value,
            ]);

            $this->sincronizarAcciones($permiso, $this->accionesSeleccionadas($request));
        });

        return $this->volverCon('Permiso registrado correctamente.');
    }

    public function update(PermisoRequest $request, Permiso $permiso): RedirectResponse
    {
        DB::transaction(function () use ($request, $permiso): void {
            $permiso->update([
                'clave_permiso' => PermisoRequest::claveDesdeNombre($request->validated('nombre')),
                'nombre_permiso' => $request->validated('nombre'),
            ]);

            $this->sincronizarAcciones($permiso->refresh(), $this->accionesSeleccionadas($request));
        });

        return $this->volverCon('Permiso actualizado correctamente.');
    }

    public function estado(Permiso $permiso): RedirectResponse
    {
        return $this->alternarEstado($permiso, 'estado_permiso', 'Estado del permiso actualizado.');
    }

    public function destroy(Permiso $permiso): RedirectResponse
    {
        return $this->eliminarSiNoSeUsa($permiso, null, 'Permiso eliminado correctamente.');
    }

    /**
     * @return list<int>
     */
    private function accionesSeleccionadas(PermisoRequest $request): array
    {
        return collect($request->validated('acciones'))->map(fn (mixed $id): int => (int) $id)->unique()->values()->all();
    }

    /**
     * Habilita (o crea) la combinación permiso + acción de cada acción marcada, con su clave
     * "{permiso}_{accion}", y deshabilita las demás.
     *
     * @param  list<int>  $acciones
     */
    private function sincronizarAcciones(Permiso $permiso, array $acciones): void
    {
        $existentes = $permiso->permisoAcciones()->withTrashed()->get()->keyBy('id_accion');

        Accion::query()->whereIn('id_acc', $acciones)->get()->each(function (Accion $accion) use ($permiso, $existentes): void {
            $datos = [
                'clave_peracc' => "{$permiso->clave_permiso}_{$accion->clave_accion}",
                'estado_peracc' => EstadoCatalogo::Habilitado->value,
                'eliminado_en' => null,
            ];

            $permisoAccion = $existentes->get($accion->id_acc);

            if ($permisoAccion instanceof PermisoAccion) {
                $permisoAccion->update($datos);

                return;
            }

            $permiso->permisoAcciones()->create(['id_accion' => $accion->id_acc, ...$datos]);
        });

        $permiso->permisoAcciones()
            ->whereNotIn('id_accion', $acciones)
            ->update(['estado_peracc' => EstadoCatalogo::Deshabilitado->value]);
    }
}
