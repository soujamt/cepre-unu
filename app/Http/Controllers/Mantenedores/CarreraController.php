<?php

namespace App\Http\Controllers\Mantenedores;

use App\Enums\EstadoCatalogo;
use App\Http\Controllers\CatalogoController;
use App\Http\Requests\Mantenedores\CarreraRequest;
use App\Models\Carrera;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CarreraController extends CatalogoController
{
    public function index(Request $request): Response
    {
        $filtros = $this->filtros($request);

        $carreras = $this->aplicarFiltros(Carrera::query()->withCount('procesoCarreras'), $filtros, ['codigo_car', 'nombre_car'], 'estado_car')
            ->orderBy('nombre_car')
            ->paginate(self::POR_PAGINA)
            ->withQueryString()
            ->through(fn (Carrera $carrera): array => [
                'id' => $carrera->id_car,
                'codigo' => $carrera->codigo_car,
                'nombre' => $carrera->nombre_car,
                'procesos' => $carrera->proceso_carreras_count,
                'estado' => $carrera->estado_car->value,
                'registro' => $this->registro($carrera->creado_en),
            ]);

        return Inertia::render('mantenedores/carreras', [
            'carreras' => $carreras,
            'filtros' => $filtros,
        ]);
    }

    public function store(CarreraRequest $request): RedirectResponse
    {
        Carrera::query()->create([
            'codigo_car' => $request->validated('codigo'),
            'nombre_car' => $request->validated('nombre'),
            'estado_car' => EstadoCatalogo::Habilitado->value,
        ]);

        return $this->volverCon('Carrera registrada correctamente.');
    }

    public function update(CarreraRequest $request, Carrera $carrera): RedirectResponse
    {
        $carrera->update([
            'codigo_car' => $request->validated('codigo'),
            'nombre_car' => $request->validated('nombre'),
        ]);

        return $this->volverCon('Carrera actualizada correctamente.');
    }

    public function estado(Carrera $carrera): RedirectResponse
    {
        return $this->alternarEstado($carrera, 'estado_car', 'Estado de la carrera actualizado.');
    }

    public function destroy(Carrera $carrera): RedirectResponse
    {
        $enUso = $carrera->procesoCarreras()->withTrashed()->exists() || $carrera->inscripciones()->withTrashed()->exists();

        return $this->eliminarSiNoSeUsa(
            $carrera,
            $enUso ? 'La carrera se usa en la oferta de un proceso o en inscripciones y no puede eliminarse. Puede deshabilitarla.' : null,
            'Carrera eliminada correctamente.',
        );
    }
}
