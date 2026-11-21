<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ubigeo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UbigeoSearchController extends Controller
{
    /**
     * Código de departamento de Ucayali en el ubigeo del INEI.
     */
    private const DEPARTAMENTO_UCAYALI = '25';

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $value = trim((string) $request->query('value', ''));

        if ($value !== '') {
            $ubigeo = Ubigeo::query()
                ->where('estado_ubi', 'HABILITADO')
                ->where('id_ubi', $value)
                ->first();

            if ($ubigeo === null) {
                return response()->json([]);
            }

            return response()->json([
                'value' => $ubigeo->id_ubi,
                'label' => $ubigeo->etiqueta(),
            ]);
        }

        $query = trim((string) $request->query('query', ''));

        // Sin texto (o muy corto) se ofrecen los distritos de Ucayali, la región de la UNU.
        $ubigeos = Ubigeo::query()
            ->where('estado_ubi', 'HABILITADO')
            ->when(
                mb_strlen($query) < 2,
                fn (Builder $builder): Builder => $builder->where('id_ubi', 'like', self::DEPARTAMENTO_UCAYALI.'%'),
                fn (Builder $builder): Builder => $builder->where(function (Builder $condicion) use ($query): void {
                    $condicion
                        ->where('id_ubi', 'like', "%{$query}%")
                        ->orWhere('nombre_departamento_ubi', 'like', "%{$query}%")
                        ->orWhere('nombre_provincia_ubi', 'like', "%{$query}%")
                        ->orWhere('nombre_distrito_ubi', 'like', "%{$query}%");
                }),
            )
            // Ucayali primero; dentro de cada departamento, por código (la capital va primero).
            ->orderByRaw('CASE WHEN id_ubi LIKE ? THEN 0 ELSE 1 END', [self::DEPARTAMENTO_UCAYALI.'%'])
            ->orderBy('id_ubi')
            ->limit(20)
            ->get()
            ->map(fn (Ubigeo $ubigeo): array => [
                'value' => $ubigeo->id_ubi,
                'label' => $ubigeo->etiqueta(),
            ])
            ->values();

        return response()->json($ubigeos);
    }
}
