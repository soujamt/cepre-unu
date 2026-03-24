<?php

namespace Database\Factories;

use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<Usuario>
 */
class UsuarioFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'usuario_usu' => fake()->unique()->userName(),
            'correo_usu' => fake()->unique()->safeEmail(),
            'nombres_usu' => fake()->name(),
            'estado_usu' => 'HABILITADO',
            'clave_usu' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'creado_en' => now(),
            'actualizado_en' => now(),
        ];
    }

    public function withoutEmail(): static
    {
        return $this->state(fn (array $attributes): array => [
            'correo_usu' => null,
        ]);
    }
}
