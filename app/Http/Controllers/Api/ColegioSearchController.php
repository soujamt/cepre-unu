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
                ->with('ubigeo')
                ->where('estado_col', 'HABILITADO')
                ->where('id_col', $value)
                ->first();

            if ($colegio === null) {
                return response()->json([]);
            }

            return response()->json([
                'value' => $colegio->id_col,
                'label' => $this->formatLabel($colegio),
            ]);
        }

        $query = trim((string) $request->query('query', ''));

        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $colegios = Colegio::query()
            ->with('ubigeo')
            ->where('estado_col', 'HABILITADO')
            ->when(
                filled($request->query('id_tipo_colegio')),
                fn ($builder) => $builder->where('id_tipo_colegio', (int) $request->query('id_tipo_colegio')),
            )
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
                'label' => $this->formatLabel($colegio),
                'description' => $this->formatDescription($colegio),
            ])
            ->values();

        return response()->json($colegios);
    }

    private function formatLabel(Colegio $colegio): string
    {
        $ubigeo = $colegio->ubigeo;

        if ($ubigeo === null) {
            return "{$colegio->id_col} | {$colegio->nombre_col}";
        }

        return "{$colegio->id_col} | {$colegio->nombre_col} | {$ubigeo->nombre_departamento_ubi} / {$ubigeo->nombre_provincia_ubi} / {$ubigeo->nombre_distrito_ubi}";
    }

    private function formatDescription(Colegio $colegio): ?string
    {
        if (blank($colegio->direccion_col)) {
            return filled($colegio->codigo_modular_col)
                ? "Codigo modular: {$colegio->codigo_modular_col}"
                : null;
        }

        return "Direccion: {$colegio->direccion_col}";
    }
}
