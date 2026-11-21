<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\EstadoProceso;
use App\Models\Proceso;
use Illuminate\Http\Request;

/**
 * Pantallas que trabajan sobre un proceso elegido en una lista (?proceso=ID en la URL).
 * Sin elección se usa el proceso habilitado más reciente, o el último registrado.
 */
trait EligeProceso
{
    protected function procesoElegido(Request $request): ?Proceso
    {
        $id = $request->query('proceso');

        if (filled($id) && ctype_digit((string) $id)) {
            return Proceso::query()->find((int) $id);
        }

        return Proceso::query()->where('estado_pro', EstadoProceso::Habilitado->value)->orderByDesc('fecha_inicio_pro')->first()
            ?? Proceso::query()->orderByDesc('fecha_inicio_pro')->first();
    }

    /**
     * @return list<array{valor: string, nombre: string, estado: string}>
     */
    protected function opcionesProceso(): array
    {
        return Proceso::query()
            ->orderByDesc('fecha_inicio_pro')
            ->get()
            ->map(fn (Proceso $proceso): array => [
                'valor' => (string) $proceso->id_pro,
                'nombre' => $proceso->nombre_pro,
                'estado' => $proceso->estado_pro->value,
            ])
            ->all();
    }

    /**
     * @return array{id: int, nombre: string, estado: string, cerrado: bool, apertura: ?string, cierre: ?string}|null
     */
    protected function resumenProceso(?Proceso $proceso): ?array
    {
        if ($proceso === null) {
            return null;
        }

        return [
            'id' => $proceso->id_pro,
            'nombre' => $proceso->nombre_pro,
            'estado' => $proceso->estado_pro->value,
            'cerrado' => $proceso->estado_pro === EstadoProceso::Cerrado,
            'apertura' => $proceso->fecha_inicio_pro?->format('d/m/Y h:i a'),
            'cierre' => $proceso->fecha_fin_pro?->format('d/m/Y h:i a'),
        ];
    }
}
