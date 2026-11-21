<?php

namespace App\Http\Controllers\Mantenedores;

use App\Enums\EstadoCatalogo;
use App\Http\Controllers\CatalogoController;
use App\Http\Requests\Mantenedores\ColegioRequest;
use App\Models\Colegio;
use App\Models\TipoColegio;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ColegioController extends CatalogoController
{
    public function index(Request $request): Response
    {
        $filtros = [
            ...$this->filtros($request),
            'tipo' => ctype_digit((string) $request->query('tipo')) ? (string) $request->query('tipo') : '',
        ];

        $consulta = Colegio::query()
            ->with(['tipoColegio', 'ubigeo'])
            ->withCount('inscripciones')
            ->when($filtros['tipo'] !== '', fn (Builder $query) => $query->where('id_tipo_colegio', (int) $filtros['tipo']));

        $colegios = $this->aplicarFiltros($consulta, $filtros, ['id_col', 'codigo_modular_col', 'nombre_col', 'direccion_col'], 'estado_col')
            ->orderBy('nombre_col')
            ->paginate(self::POR_PAGINA)
            ->withQueryString()
            ->through(fn (Colegio $colegio): array => [
                'id' => $colegio->id_col,
                'codigoModular' => $colegio->codigo_modular_col,
                'nombre' => $colegio->nombre_col,
                'idTipo' => $colegio->id_tipo_colegio,
                'tipo' => $colegio->tipoColegio?->nombre_tco,
                'ubigeo' => $colegio->ubigeo === null ? null : [
                    'codigo' => $colegio->ubigeo->id_ubi,
                    'etiqueta' => $colegio->ubigeo->etiqueta(),
                    'ubicacion' => "{$colegio->ubigeo->nombre_departamento_ubi} / {$colegio->ubigeo->nombre_provincia_ubi} / {$colegio->ubigeo->nombre_distrito_ubi}",
                ],
                'direccion' => $colegio->direccion_col,
                'uso' => $colegio->inscripciones_count,
                'estado' => $colegio->estado_col->value,
                'registro' => $this->registro($colegio->creado_en),
            ]);

        return Inertia::render('mantenedores/colegios', [
            'colegios' => $colegios,
            'filtros' => $filtros,
            'tipos' => TipoColegio::query()
                ->where('estado_tco', EstadoCatalogo::Habilitado->value)
                ->orderBy('nombre_tco')
                ->get()
                ->map(fn (TipoColegio $tipo): array => ['valor' => (string) $tipo->id_tco, 'nombre' => $tipo->nombre_tco]),
        ]);
    }

    public function store(ColegioRequest $request): RedirectResponse
    {
        Colegio::query()->create([
            'id_col' => $request->validated('id'),
            ...$this->datos($request),
            'estado_col' => EstadoCatalogo::Habilitado->value,
        ]);

        return $this->volverCon('Colegio registrado correctamente.');
    }

    public function update(ColegioRequest $request, Colegio $colegio): RedirectResponse
    {
        $colegio->update($this->datos($request));

        return $this->volverCon('Colegio actualizado correctamente.');
    }

    public function estado(Colegio $colegio): RedirectResponse
    {
        return $this->alternarEstado($colegio, 'estado_col', 'Estado del colegio actualizado.');
    }

    public function destroy(Colegio $colegio): RedirectResponse
    {
        return $this->eliminarSiNoSeUsa(
            $colegio,
            $colegio->inscripciones()->exists() ? 'El colegio tiene inscripciones relacionadas y no puede eliminarse.' : null,
            'Colegio eliminado correctamente.',
        );
    }

    /**
     * @return array{codigo_modular_col: ?string, nombre_col: string, id_tipo_colegio: ?int, id_ubigeo: ?string, direccion_col: ?string}
     */
    private function datos(ColegioRequest $request): array
    {
        $idTipo = $request->validated('id_tipo_colegio');

        return [
            'codigo_modular_col' => $request->validated('codigo_modular'),
            'nombre_col' => $request->validated('nombre'),
            'id_tipo_colegio' => $idTipo === null ? null : (int) $idTipo,
            'id_ubigeo' => $request->validated('id_ubigeo'),
            'direccion_col' => $request->validated('direccion'),
        ];
    }
}
