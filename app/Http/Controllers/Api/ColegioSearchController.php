<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Colegio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ColegioSearchController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $value = trim((string) $request->query('value', ''));

        if ($value !== '') {
            $colegio = Colegio::query()
                ->where('estado_col', 'HABILITADO')
                ->where('id_col', $value)
                ->first();

            if ($colegio === null) {
                return response()->json([]);
            }

            return response()->json([
                'value' => $colegio->id_col,
                'label' => "{$colegio->id_col} | {$colegio->nombre_col}",
            ]);
        }

        $query = trim((string) $request->query('query', ''));

        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $colegios = Colegio::query()
            ->where('estado_col', 'HABILITADO')
            ->where(function ($builder) use ($query): void {
                $builder
                    ->where('id_col', 'like', "%{$query}%")
                    ->orWhere('codigo_modular_col', 'like', "%{$query}%")
                    ->orWhere('nombre_col', 'like', "%{$query}%");
            })
            ->orderBy('nombre_col')
            ->limit(20)
            ->get()
            ->map(fn (Colegio $colegio): array => [
                'value' => $colegio->id_col,
                'label' => "{$colegio->id_col} | {$colegio->nombre_col}",
                'description' => filled($colegio->codigo_modular_col)
                    ? "Codigo modular: {$colegio->codigo_modular_col}"
                    : null,
            ])
            ->values();

        return response()->json($colegios);
    }
}
