<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ubigeo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UbigeoSearchController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('query', ''));

        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $ubigeos = Ubigeo::query()
            ->where('estado_ubi', 'HABILITADO')
            ->where(function ($builder) use ($query): void {
                $builder
                    ->where('id_ubi', 'like', "%{$query}%")
                    ->orWhere('nombre_departamento_ubi', 'like', "%{$query}%")
                    ->orWhere('nombre_provincia_ubi', 'like', "%{$query}%")
                    ->orWhere('nombre_distrito_ubi', 'like', "%{$query}%");
            })
            ->orderBy('nombre_departamento_ubi')
            ->orderBy('nombre_provincia_ubi')
            ->orderBy('nombre_distrito_ubi')
            ->limit(20)
            ->get()
            ->map(fn (Ubigeo $ubigeo): array => [
                'value' => $ubigeo->id_ubi,
                'label' => "{$ubigeo->id_ubi} | {$ubigeo->nombre_departamento_ubi} / {$ubigeo->nombre_provincia_ubi} / {$ubigeo->nombre_distrito_ubi}",
            ])
            ->values();

        return response()->json($ubigeos);
    }
}
