<?php

namespace App\Http\Controllers\Seguridad;

use App\Enums\EstadoCatalogo;
use App\Http\Controllers\Controller;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use App\Models\Rol;
use App\Models\RolAccion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RolPermisosController extends Controller
{
    public function edit(Rol $rol): Response
    {
        $permisos = Permiso::query()
            ->where('estado_permiso', EstadoCatalogo::Habilitado->value)
            ->with([
                'permisoAcciones' => fn ($query) => $query
                    ->where('estado_peracc', EstadoCatalogo::Habilitado->value)
                    ->whereHas('accion', fn ($accion) => $accion->where('estado_accion', EstadoCatalogo::Habilitado->value))
                    ->with('accion')
                    ->orderBy('id_accion'),
            ])
            ->orderBy('nombre_permiso')
            ->get()
            ->map(fn (Permiso $permiso): array => [
                'id' => $permiso->id_permiso,
                'nombre' => $permiso->nombre_permiso,
                'clave' => $permiso->clave_permiso,
                'acciones' => $permiso->permisoAcciones->map(fn (PermisoAccion $permisoAccion): array => [
                    'id' => $permisoAccion->id_peracc,
                    'nombre' => $permisoAccion->accion?->nombre_accion ?? $permisoAccion->clave_peracc,
                    'clave' => $permisoAccion->clave_peracc,
                ])->values()->all(),
            ]);

        return Inertia::render('seguridad/roles-permisos', [
            'rol' => [
                'id' => $rol->id_rol,
                'nombre' => $rol->nombre_rol,
                'descripcion' => $rol->descripcion_rol,
                'esAdministrador' => $rol->nombre_rol === RolController::ADMINISTRADOR,
            ],
            'permisos' => $permisos,
            'seleccionadas' => $rol->rolPermisoAcciones()->pluck('id_peracc')->map(fn (mixed $id): int => (int) $id)->values(),
        ]);
    }

    /**
     * Deja al rol solo con las acciones marcadas: restaura las que se habían quitado
     * y elimina (de forma lógica) las desmarcadas.
     */
    public function update(Request $request, Rol $rol): RedirectResponse
    {
        if ($rol->nombre_rol === RolController::ADMINISTRADOR) {
            Inertia::flash('toast', ['type' => 'info', 'message' => 'El rol ADMINISTRADOR ya cuenta con acceso total.']);

            return back();
        }

        $datos = $request->validate([
            'seleccionadas' => ['present', 'array'],
            'seleccionadas.*' => ['integer', 'exists:tbl_permiso_accion,id_peracc'],
        ]);

        $seleccionadas = collect($datos['seleccionadas'])->map(fn (mixed $id): int => (int) $id)->unique()->values()->all();

        DB::transaction(function () use ($rol, $seleccionadas): void {
            $existentes = $rol->rolPermisoAcciones()->withTrashed()->get()->keyBy('id_peracc');

            foreach ($seleccionadas as $idPermisoAccion) {
                $relacion = $existentes->get($idPermisoAccion);

                if ($relacion instanceof RolAccion) {
                    if ($relacion->trashed()) {
                        $relacion->restore();
                    }

                    continue;
                }

                $rol->rolPermisoAcciones()->create(['id_peracc' => $idPermisoAccion]);
            }

            $existentes
                ->reject(fn (RolAccion $relacion): bool => in_array($relacion->id_peracc, $seleccionadas, true) || $relacion->trashed())
                ->each(fn (RolAccion $relacion) => $relacion->delete());
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Permisos del rol actualizados correctamente.']);

        return back();
    }
}
