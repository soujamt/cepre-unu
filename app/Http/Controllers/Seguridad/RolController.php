<?php

namespace App\Http\Controllers\Seguridad;

use App\Enums\EstadoCatalogo;
use App\Http\Controllers\CatalogoController;
use App\Http\Requests\Seguridad\RolRequest;
use App\Models\Rol;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RolController extends CatalogoController
{
    /**
     * Rol con acceso total: no cambia de nombre ni de estado y no se elimina.
     */
    public const ADMINISTRADOR = 'ADMINISTRADOR';

    public function index(Request $request): Response
    {
        $filtros = $this->filtros($request);

        $consulta = Rol::query()->withCount([
            'usuarios',
            'rolPermisoAcciones as acciones_asignadas_count' => fn (Builder $query) => $query
                ->whereHas('permisoAccion', fn (Builder $permisoAccion) => $permisoAccion
                    ->where('estado_peracc', EstadoCatalogo::Habilitado->value)
                    ->whereHas('permiso', fn (Builder $permiso) => $permiso->where('estado_permiso', EstadoCatalogo::Habilitado->value))),
        ]);

        $roles = $this->aplicarFiltros($consulta, $filtros, ['nombre_rol', 'descripcion_rol'], 'estado_rol')
            ->orderByDesc('id_rol')
            ->paginate(self::POR_PAGINA)
            ->withQueryString()
            ->through(fn (Rol $rol): array => [
                'id' => $rol->id_rol,
                'nombre' => $rol->nombre_rol,
                'descripcion' => $rol->descripcion_rol,
                'usuarios' => $rol->usuarios_count,
                'acciones' => $rol->acciones_asignadas_count,
                'esAdministrador' => $rol->nombre_rol === self::ADMINISTRADOR,
                'estado' => $rol->estado_rol->value,
                'registro' => $this->registro($rol->creado_en),
            ]);

        return Inertia::render('seguridad/roles', [
            'roles' => $roles,
            'filtros' => $filtros,
        ]);
    }

    public function store(RolRequest $request): RedirectResponse
    {
        Rol::query()->create([
            'nombre_rol' => $request->validated('nombre'),
            'descripcion_rol' => $request->validated('descripcion'),
            'estado_rol' => EstadoCatalogo::Habilitado->value,
        ]);

        return $this->volverCon('Rol registrado correctamente.');
    }

    public function update(RolRequest $request, Rol $rol): RedirectResponse
    {
        $rol->update([
            'nombre_rol' => $rol->nombre_rol === self::ADMINISTRADOR ? self::ADMINISTRADOR : $request->validated('nombre'),
            'descripcion_rol' => $request->validated('descripcion'),
        ]);

        return $this->volverCon('Rol actualizado correctamente.');
    }

    public function estado(Rol $rol): RedirectResponse
    {
        if ($rol->nombre_rol === self::ADMINISTRADOR) {
            return $this->volverCon('El rol ADMINISTRADOR no puede cambiar de estado.', 'error');
        }

        return $this->alternarEstado($rol, 'estado_rol', 'Estado del rol actualizado.');
    }

    public function destroy(Rol $rol): RedirectResponse
    {
        return $this->eliminarSiNoSeUsa(
            $rol,
            $rol->nombre_rol === self::ADMINISTRADOR ? 'El rol ADMINISTRADOR no puede eliminarse.' : null,
            'Rol eliminado correctamente.',
        );
    }
}
