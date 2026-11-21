<?php

namespace App\Http\Controllers\Mantenedores;

use App\Enums\EstadoCatalogo;
use App\Enums\LugarAula;
use App\Enums\PisoAula;
use App\Http\Controllers\CatalogoController;
use App\Http\Requests\Mantenedores\AulaRequest;
use App\Models\Aula;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AulaController extends CatalogoController
{
    public function index(Request $request): Response
    {
        $filtros = $this->filtros($request);

        $aulas = $this->aplicarFiltros(Aula::query()->withCount('procesoAulas'), $filtros, ['nombre_aul', 'piso_aul', 'lugar_aul'], 'estado_aul')
            ->orderBy('orden_aul')
            ->orderBy('nombre_aul')
            ->paginate(self::POR_PAGINA)
            ->withQueryString()
            ->through(fn (Aula $aula): array => [
                'id' => $aula->id_aul,
                'nombre' => $aula->nombre_aul,
                'piso' => $aula->piso_aul->value,
                'lugar' => $aula->lugar_aul->value,
                'orden' => $aula->orden_aul,
                'uso' => $aula->proceso_aulas_count,
                'estado' => $aula->estado_aul->value,
                'registro' => $this->registro($aula->creado_en),
            ]);

        return Inertia::render('mantenedores/aulas', [
            'aulas' => $aulas,
            'filtros' => $filtros,
            'pisos' => array_map(fn (PisoAula $piso): array => ['valor' => $piso->value, 'nombre' => $piso->getNombre()], PisoAula::cases()),
            'lugares' => array_map(fn (LugarAula $lugar): array => ['valor' => $lugar->value, 'nombre' => $lugar->getNombre()], LugarAula::cases()),
        ]);
    }

    public function store(AulaRequest $request): RedirectResponse
    {
        Aula::query()->create([...$this->datos($request), 'estado_aul' => EstadoCatalogo::Habilitado->value]);

        return $this->volverCon('Aula registrada correctamente.');
    }

    public function update(AulaRequest $request, Aula $aula): RedirectResponse
    {
        $aula->update($this->datos($request));

        return $this->volverCon('Aula actualizada correctamente.');
    }

    public function estado(Aula $aula): RedirectResponse
    {
        return $this->alternarEstado($aula, 'estado_aul', 'Estado del aula actualizado.');
    }

    public function destroy(Aula $aula): RedirectResponse
    {
        return $this->eliminarSiNoSeUsa(
            $aula,
            $aula->procesoAulas()->exists() ? 'El aula tiene asignaciones por proceso y no puede eliminarse.' : null,
            'Aula eliminada correctamente.',
        );
    }

    /**
     * @return array{nombre_aul: string, piso_aul: string, lugar_aul: string, orden_aul: int}
     */
    private function datos(AulaRequest $request): array
    {
        return [
            'nombre_aul' => $request->validated('nombre'),
            'piso_aul' => $request->validated('piso'),
            'lugar_aul' => $request->validated('lugar'),
            'orden_aul' => (int) $request->validated('orden'),
        ];
    }
}
