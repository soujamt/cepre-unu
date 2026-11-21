<?php

namespace App\Http\Controllers\Seguridad;

use App\Enums\EstadoCatalogo;
use App\Enums\EstadoUsuario;
use App\Http\Controllers\CatalogoController;
use App\Http\Requests\Seguridad\UsuarioRequest;
use App\Models\Rol;
use App\Models\Usuario;
use App\Models\UsuarioRol;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class UsuarioController extends CatalogoController
{
    public function index(Request $request): Response
    {
        $filtros = $this->filtros($request);

        $consulta = Usuario::query()->with([
            'roles' => fn ($query) => $query->where('estado_rol', EstadoCatalogo::Habilitado->value)->orderByDesc('id_rol'),
        ]);

        $usuarios = $this->aplicarFiltros($consulta, $filtros, ['correo_usu', 'nombres_usu'], 'estado_usu')
            ->orderByDesc('id_usu')
            ->paginate(self::POR_PAGINA)
            ->withQueryString()
            ->through(fn (Usuario $usuario): array => [
                'id' => $usuario->id_usu,
                'nombres' => $usuario->nombres_usu,
                'correo' => $usuario->correo_usu,
                'iniciales' => $usuario->iniciales(),
                'roles' => $usuario->roles->map(fn (Rol $rol): array => ['id' => $rol->id_rol, 'nombre' => $rol->nombre_rol])->all(),
                'esUsted' => $usuario->id_usu === $request->user()?->id_usu,
                'estado' => $usuario->estado_usu->value,
                'registro' => $this->registro($usuario->creado_en),
            ]);

        return Inertia::render('seguridad/usuarios', [
            'usuarios' => $usuarios,
            'filtros' => $filtros,
            'roles' => Rol::query()
                ->where('estado_rol', EstadoCatalogo::Habilitado->value)
                ->orderByDesc('id_rol')
                ->get()
                ->map(fn (Rol $rol): array => ['valor' => (string) $rol->id_rol, 'nombre' => $rol->nombre_rol]),
        ]);
    }

    public function store(UsuarioRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $usuario = Usuario::query()->create([
                'usuario_usu' => $this->usuarioDesdeCorreo($request->validated('correo')),
                'correo_usu' => $request->validated('correo'),
                'nombres_usu' => $request->validated('nombres'),
                'clave_usu' => Hash::make($request->validated('password')),
                'estado_usu' => EstadoUsuario::Habilitado->value,
            ]);

            $this->sincronizarRoles($usuario, $this->rolesSeleccionados($request));
        });

        return $this->volverCon('Usuario registrado correctamente.');
    }

    public function update(UsuarioRequest $request, Usuario $usuario): RedirectResponse
    {
        DB::transaction(function () use ($request, $usuario): void {
            $datos = [
                'correo_usu' => $request->validated('correo'),
                'nombres_usu' => $request->validated('nombres'),
            ];

            if (filled($request->validated('password'))) {
                $datos['clave_usu'] = Hash::make($request->validated('password'));
            }

            $usuario->update($datos);
            $this->sincronizarRoles($usuario, $this->rolesSeleccionados($request));
        });

        return $this->volverCon('Usuario actualizado correctamente.');
    }

    public function estado(Request $request, Usuario $usuario): RedirectResponse
    {
        if ($usuario->id_usu === $request->user()?->id_usu) {
            return $this->volverCon('No puedes cambiar el estado de tu propio usuario.', 'error');
        }

        return $this->alternarEstado($usuario, 'estado_usu', 'Estado del usuario actualizado.');
    }

    public function destroy(Request $request, Usuario $usuario): RedirectResponse
    {
        $esPropio = $usuario->id_usu === $request->user()?->id_usu;

        return $this->eliminarSiNoSeUsa($usuario, $esPropio ? 'No puedes eliminar tu propio usuario.' : null, 'Usuario eliminado correctamente.');
    }

    /**
     * @return list<int>
     */
    private function rolesSeleccionados(UsuarioRequest $request): array
    {
        return collect($request->validated('roles', []))->map(fn (mixed $id): int => (int) $id)->unique()->values()->all();
    }

    /**
     * Deja al usuario solo con los roles indicados: restaura los que se habían quitado
     * y elimina (de forma lógica) los que ya no están.
     *
     * @param  list<int>  $roles
     */
    private function sincronizarRoles(Usuario $usuario, array $roles): void
    {
        $existentes = $usuario->usuarioRoles()->withTrashed()->get()->keyBy('id_rol');

        foreach ($roles as $idRol) {
            $relacion = $existentes->get($idRol);

            if ($relacion instanceof UsuarioRol) {
                if ($relacion->trashed()) {
                    $relacion->restore();
                }

                continue;
            }

            $usuario->usuarioRoles()->create(['id_rol' => $idRol]);
        }

        $existentes
            ->reject(fn (UsuarioRol $relacion): bool => in_array($relacion->id_rol, $roles, true) || $relacion->trashed())
            ->each(fn (UsuarioRol $relacion) => $relacion->delete());
    }

    /**
     * Nombre de usuario a partir del correo ("analista@unu.edu.pe" → "analista"),
     * con un número al final si ya existe.
     */
    private function usuarioDesdeCorreo(string $correo): string
    {
        $base = Str::slug(Str::lower(Str::before($correo, '@')), '') ?: 'usuario';
        $usuario = $base;
        $contador = 1;

        while (Usuario::query()->withTrashed()->where('usuario_usu', $usuario)->exists()) {
            $usuario = $base.$contador;
            $contador++;
        }

        return $usuario;
    }
}
