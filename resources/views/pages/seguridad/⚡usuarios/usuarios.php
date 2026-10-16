<?php

use App\Enums\EstadoCatalogo;
use App\Enums\EstadoUsuario;
use App\Models\Rol;
use App\Models\Usuario;
use App\Models\UsuarioRol;
use App\Traits\AuthorizesPermisos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('layouts.app')]
#[Title('USUARIOS')]
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
     * @var array{correo: string, nombres: string, password: string, roles: array<int, string>}
     */
    public array $usuarioForm = [
        'correo' => '',
        'nombres' => '',
        'password' => '',
        'roles' => [],
    ];

    public string $tituloModal = 'Nuevo usuario';

    public bool $isEdit = false;

    public ?int $editingId = null;

    public function mount(): void
    {
        $this->autorizarPermiso('usuario', 'indice');
    }

    public function updatedBuscar(): void
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
            'usuarioForm.correo' => [
                'required',
                'email',
                'max:150',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $correo = Str::lower(trim((string) $value));

                    $exists = Usuario::query()
                        ->where('correo_usu', $correo)
                        ->when($this->editingId, fn (Builder $query) => $query->where('id_usu', '!=', $this->editingId))
                        ->exists();

                    if ($exists) {
                        $fail('Ya existe un usuario con ese correo.');
                    }
                },
            ],
            'usuarioForm.nombres' => ['required', 'string', 'max:150'],
            'usuarioForm.password' => $this->isEdit
                ? ['nullable', 'string', 'min:8']
                : ['required', 'string', 'min:8'],
            'usuarioForm.roles' => ['array'],
            'usuarioForm.roles.*' => ['integer', 'exists:tbl_rol,id_rol'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'usuarioForm.correo.required' => 'El correo es obligatorio.',
            'usuarioForm.correo.email' => 'Ingresa un correo valido.',
            'usuarioForm.nombres.required' => 'Los nombres son obligatorios.',
            'usuarioForm.password.required' => 'La contrasena es obligatoria.',
            'usuarioForm.password.min' => 'La contrasena debe tener al menos 8 caracteres.',
        ];
    }

    public function estados(): array
    {
        return EstadoUsuario::cases();
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
    public function rolesDisponibles(): Collection
    {
        return Rol::query()
            ->where('estado_rol', EstadoCatalogo::Habilitado->value)
            ->orderByDesc('id_rol')
            ->get();
    }

    public function limpiarFiltroEstado(): void
    {
        $this->filtroEstado = [];
    }

    public function cargarUsuario(?int $id = null): void
    {
        $this->autorizarPermiso('usuario', $id === null ? 'crear' : 'editar');
        $this->resetValidation();

        if ($id === null) {
            $this->resetForm();
            $this->tituloModal = 'Nuevo usuario';
            $this->isEdit = false;
            $this->modal('modal-usuario')->show();

            return;
        }

        $usuario = Usuario::query()
            ->with('roles')
            ->findOrFail($id);

        $this->editingId = $usuario->id_usu;
        $this->usuarioForm = [
            'correo' => $usuario->correo_usu ?? '',
            'nombres' => $usuario->nombres_usu,
            'password' => '',
            'roles' => $usuario->roles->pluck('id_rol')->map(fn (int $roleId): string => (string) $roleId)->all(),
        ];
        $this->tituloModal = 'Editar usuario';
        $this->isEdit = true;

        $this->modal('modal-usuario')->show();
    }

    public function guardar(): void
    {
        $this->autorizarPermiso('usuario', $this->isEdit ? 'editar' : 'crear');
        $data = $this->validate();

        try {
            DB::transaction(function () use ($data): void {
                $correo = Str::lower(trim($data['usuarioForm']['correo']));
                $nombres = $this->mayusculas($this->normalizarTexto($data['usuarioForm']['nombres']) ?? '');
                $password = trim($data['usuarioForm']['password']);
                $rolesSeleccionados = collect($data['usuarioForm']['roles'] ?? [])
                    ->map(fn (string|int $roleId): int => (int) $roleId)
                    ->values()
                    ->all();

                if ($this->editingId) {
                    $usuario = Usuario::query()->findOrFail($this->editingId);

                    $payload = [
                        'correo_usu' => $correo,
                        'nombres_usu' => $nombres,
                    ];

                    if ($password !== '') {
                        $payload['clave_usu'] = Hash::make($password);
                    }

                    $usuario->update($payload);
                    $this->sincronizarRoles($usuario, $rolesSeleccionados);

                    return;
                }

                $usuario = Usuario::query()->create([
                    'usuario_usu' => $this->generarUsuarioDesdeCorreo($correo),
                    'correo_usu' => $correo,
                    'nombres_usu' => $nombres,
                    'clave_usu' => Hash::make($password),
                    'estado_usu' => EstadoUsuario::Habilitado->value,
                ]);

                $this->sincronizarRoles($usuario, $rolesSeleccionados);
            });
        } catch (Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo guardar el usuario. Intente nuevamente.', type: 'error');

            return;
        }

        $this->dispatch(
            'toast',
            message: $this->isEdit ? 'Usuario actualizado correctamente.' : 'Usuario registrado correctamente.',
            type: 'success'
        );

        unset($this->usuarios);
        $this->modal('modal-usuario')->close();
        $this->resetForm();
    }

    #[On('usuario-cambiar-estado')]
    public function cambiarEstado(?int $id_usuario = null): void
    {
        $this->autorizarPermiso('usuario', 'estado');

        if ($id_usuario === null) {
            $this->dispatch('toast', message: 'ID de usuario no proporcionado.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($id_usuario): void {
                $usuario = Usuario::query()->findOrFail($id_usuario);

                if ((int) $usuario->id_usu === (int) Auth::id()) {
                    throw new RuntimeException('No puedes cambiar el estado de tu propio usuario.');
                }

                $estado = $usuario->estado_usu === EstadoUsuario::Habilitado
                    ? EstadoUsuario::Deshabilitado
                    : EstadoUsuario::Habilitado;

                $usuario->update([
                    'estado_usu' => $estado->value,
                ]);
            });
        } catch (Throwable $throwable) {
            report_if(! $throwable instanceof RuntimeException, $throwable);
            $this->dispatch('toast', message: $throwable->getMessage() ?: 'No se pudo cambiar el estado del usuario.', type: 'error');

            return;
        }

        unset($this->usuarios);
        $this->dispatch('toast', message: 'Estado del usuario actualizado.', type: 'success');
    }

    #[On('usuario-eliminar')]
    public function eliminar(?int $id_usuario = null): void
    {
        $this->autorizarPermiso('usuario', 'eliminar');

        if ($id_usuario === null) {
            $this->dispatch('toast', message: 'ID de usuario no proporcionado.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($id_usuario): void {
                $usuario = Usuario::query()->findOrFail($id_usuario);

                if ((int) $usuario->id_usu === (int) Auth::id()) {
                    throw new RuntimeException('No puedes eliminar tu propio usuario.');
                }

                $usuario->delete();
            });
        } catch (Throwable $throwable) {
            report_if(! $throwable instanceof RuntimeException, $throwable);
            $this->dispatch('toast', message: $throwable->getMessage() ?: 'No se pudo eliminar el usuario.', type: 'error');

            return;
        }

        if ($this->editingId === $id_usuario) {
            $this->resetForm();
            $this->modal('modal-usuario')->close();
        }

        unset($this->usuarios);
        $this->dispatch('toast', message: 'Usuario eliminado correctamente.', type: 'success');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'isEdit']);
        $this->tituloModal = 'Nuevo usuario';
        $this->usuarioForm = [
            'correo' => '',
            'nombres' => '',
            'password' => '',
            'roles' => [],
        ];
        $this->resetValidation();
    }

    #[Computed]
    public function usuarios(): LengthAwarePaginator
    {
        return Usuario::query()
            ->with([
                'roles' => fn ($query) => $query
                    ->where('estado_rol', EstadoCatalogo::Habilitado->value)
                    ->orderByDesc('id_rol'),
            ])
            ->when(filled($this->buscar), function (Builder $query): void {
                $search = $this->normalizarTexto($this->buscar);

                $query->where(function (Builder $innerQuery) use ($search): void {
                    $innerQuery->where('correo_usu', 'like', "%{$search}%")
                        ->orWhere('nombres_usu', 'like', "%{$search}%");
                });
            })
            ->when($this->estadosSeleccionados !== [], function (Builder $query): void {
                $query->whereIn('estado_usu', $this->estadosSeleccionados);
            })
            ->orderByDesc('id_usu')
            ->paginate(10);
    }

    /**
     * @param  array<int, int>  $rolesSeleccionados
     */
    private function sincronizarRoles(Usuario $usuario, array $rolesSeleccionados): void
    {
        $existentes = $usuario->usuarioRoles()
            ->withTrashed()
            ->get()
            ->keyBy('id_rol');

        foreach ($rolesSeleccionados as $roleId) {
            $relacion = $existentes->get($roleId);

            if ($relacion instanceof UsuarioRol) {
                if ($relacion->trashed()) {
                    $relacion->restore();
                }

                continue;
            }

            $usuario->usuarioRoles()->create([
                'id_rol' => $roleId,
            ]);
        }

        $existentes
            ->filter(fn (UsuarioRol $relacion): bool => ! in_array($relacion->id_rol, $rolesSeleccionados, true))
            ->each(function (UsuarioRol $relacion): void {
                if (! $relacion->trashed()) {
                    $relacion->delete();
                }
            });
    }

    private function generarUsuarioDesdeCorreo(string $correo): string
    {
        $base = Str::lower((string) Str::before($correo, '@'));
        $base = Str::slug($base, '');
        $base = $base !== '' ? $base : 'usuario';
        $usuario = $base;
        $contador = 1;

        while (Usuario::query()->where('usuario_usu', $usuario)->exists()) {
            $usuario = $base.$contador;
            $contador++;
        }

        return $usuario;
    }

    private function normalizarTexto(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $normalizado = preg_replace('/\s+/u', ' ', trim($valor));

        return filled($normalizado) ? $normalizado : null;
    }

    private function mayusculas(string $valor): string
    {
        return mb_strtoupper($valor, 'UTF-8');
    }
};
