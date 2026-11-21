<?php

namespace App\Http\Controllers\Mantenedores;

use App\Enums\EstadoCatalogo;
use App\Http\Requests\Mantenedores\SedeRequest;
use App\Models\Sede;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SedeController extends CatalogoController
{
    public function index(Request $request): Response
    {
        $filtros = $this->filtros($request);

        $consulta = Sede::query()
            ->with('ubigeo')
            ->withCount(['inscripciones' => fn (Builder $query) => $query->whereNot('estado_ins', 'ANULADO')]);

        $sedes = $this->aplicarFiltros($consulta, $filtros, ['codigo_sed', 'nombre_sed', 'direccion_sed'], 'estado_sed')
            ->orderBy('nombre_sed')
            ->paginate(self::POR_PAGINA)
            ->withQueryString()
            ->through(fn (Sede $sede): array => [
                'id' => $sede->id_sed,
                'codigo' => $sede->codigo_sed,
                'nombre' => $sede->nombre_sed,
                'direccion' => $sede->direccion_sed,
                'ubigeo' => $sede->ubigeo === null ? null : [
                    'codigo' => $sede->ubigeo->id_ubi,
                    'etiqueta' => $sede->ubigeo->etiqueta(),
                    'distrito' => $sede->ubigeo->nombre_distrito_ubi,
                    'provinciaDepartamento' => "{$sede->ubigeo->nombre_provincia_ubi} / {$sede->ubigeo->nombre_departamento_ubi}",
                ],
                'inscritos' => $sede->inscripciones_count,
                'estado' => $sede->estado_sed->value,
                'registro' => $this->registro($sede->creado_en),
            ]);

        return Inertia::render('mantenedores/sedes', [
            'sedes' => $sedes,
            'filtros' => $filtros,
        ]);
    }

    public function store(SedeRequest $request): RedirectResponse
    {
        Sede::query()->create([...$this->datos($request), 'estado_sed' => EstadoCatalogo::Habilitado->value]);

        return $this->volverCon('Sede registrada correctamente.');
    }

    public function update(SedeRequest $request, Sede $sede): RedirectResponse
    {
        $sede->update($this->datos($request));

        return $this->volverCon('Sede actualizada correctamente.');
    }

    public function estado(Sede $sede): RedirectResponse
    {
        return $this->alternarEstado($sede, 'estado_sed', 'Estado de la sede actualizado.');
    }

    public function destroy(Sede $sede): RedirectResponse
    {
        $enUso = $sede->procesoCarreras()->withTrashed()->exists() || $sede->inscripciones()->withTrashed()->exists();

        return $this->eliminarSiNoSeUsa(
            $sede,
            $enUso ? 'La sede se usa en la oferta de un proceso o en inscripciones y no puede eliminarse. Puede deshabilitarla.' : null,
            'Sede eliminada correctamente.',
        );
    }

    /**
     * @return array{codigo_sed: string, nombre_sed: string, id_ubigeo: string, direccion_sed: ?string}
     */
    private function datos(SedeRequest $request): array
    {
        return [
            'codigo_sed' => $request->validated('codigo'),
            'nombre_sed' => $request->validated('nombre'),
            'id_ubigeo' => $request->validated('id_ubigeo'),
            'direccion_sed' => $request->validated('direccion'),
        ];
    }
}
