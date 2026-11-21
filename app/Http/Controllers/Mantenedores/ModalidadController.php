<?php

namespace App\Http\Controllers\Mantenedores;

use App\Enums\CondicionEgresoModalidad;
use App\Enums\EstadoCatalogo;
use App\Http\Requests\Mantenedores\ModalidadRequest;
use App\Models\ModalidadAdmision;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ModalidadController extends CatalogoController
{
    public function index(Request $request): Response
    {
        $filtros = $this->filtros($request);

        $consulta = ModalidadAdmision::query()->withCount([
            'procesoModalidades as procesos_habilitados_count' => fn (Builder $query) => $query->where('estado_pmo', EstadoCatalogo::Habilitado->value),
        ]);

        $modalidades = $this->aplicarFiltros($consulta, $filtros, ['codigo_mad', 'nombre_mad'], 'estado_mad')
            ->orderBy('nombre_mad')
            ->paginate(self::POR_PAGINA)
            ->withQueryString()
            ->through(fn (ModalidadAdmision $modalidad): array => [
                'id' => $modalidad->id_mad,
                'codigo' => $modalidad->codigo_mad,
                'nombre' => $modalidad->nombre_mad,
                'descripcion' => $modalidad->descripcion_mad,
                'condicionEgreso' => $modalidad->condicion_egreso_mad?->value ?? '',
                'procesos' => $modalidad->procesos_habilitados_count,
                'estado' => $modalidad->estado_mad->value,
                'registro' => $this->registro($modalidad->creado_en),
            ]);

        return Inertia::render('mantenedores/modalidades', [
            'modalidades' => $modalidades,
            'filtros' => $filtros,
            'condiciones' => array_map(
                fn (CondicionEgresoModalidad $condicion): array => ['valor' => $condicion->value, 'nombre' => $condicion->getNombre()],
                CondicionEgresoModalidad::cases(),
            ),
        ]);
    }

    public function store(ModalidadRequest $request): RedirectResponse
    {
        ModalidadAdmision::query()->create([...$this->datos($request), 'estado_mad' => EstadoCatalogo::Habilitado->value]);

        return $this->volverCon('Modalidad registrada correctamente.');
    }

    public function update(ModalidadRequest $request, ModalidadAdmision $modalidad): RedirectResponse
    {
        $modalidad->update($this->datos($request));

        return $this->volverCon('Modalidad actualizada correctamente.');
    }

    public function estado(ModalidadAdmision $modalidad): RedirectResponse
    {
        return $this->alternarEstado($modalidad, 'estado_mad', 'Estado de la modalidad actualizado.');
    }

    public function destroy(ModalidadAdmision $modalidad): RedirectResponse
    {
        $motivo = match (true) {
            $modalidad->procesoModalidades()->withTrashed()->exists(),
            $modalidad->inscripciones()->withTrashed()->exists() => 'La modalidad está usada en procesos o inscripciones y no puede eliminarse. Puede deshabilitarla.',
            $modalidad->reglasPago()->withTrashed()->exists() => 'La modalidad tiene reglas de pago y no puede eliminarse. Puede deshabilitarla.',
            default => null,
        };

        return $this->eliminarSiNoSeUsa($modalidad, $motivo, 'Modalidad eliminada correctamente.');
    }

    /**
     * @return array{codigo_mad: string, nombre_mad: string, descripcion_mad: ?string, condicion_egreso_mad: string}
     */
    private function datos(ModalidadRequest $request): array
    {
        return [
            'codigo_mad' => $request->validated('codigo'),
            'nombre_mad' => $request->validated('nombre'),
            'descripcion_mad' => $request->validated('descripcion'),
            'condicion_egreso_mad' => $request->validated('condicion_egreso'),
        ];
    }
}
