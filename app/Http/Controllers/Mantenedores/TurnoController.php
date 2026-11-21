<?php

namespace App\Http\Controllers\Mantenedores;

use App\Enums\EstadoCatalogo;
use App\Http\Controllers\CatalogoController;
use App\Http\Requests\Mantenedores\TurnoRequest;
use App\Models\Turno;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TurnoController extends CatalogoController
{
    public function index(Request $request): Response
    {
        $filtros = $this->filtros($request);

        $turnos = $this->aplicarFiltros(Turno::query()->withCount('procesoAulas'), $filtros, ['codigo_tur', 'nombre_tur'], 'estado_tur')
            ->orderBy('nombre_tur')
            ->paginate(self::POR_PAGINA)
            ->withQueryString()
            ->through(fn (Turno $turno): array => [
                'id' => $turno->id_tur,
                'codigo' => $turno->codigo_tur,
                'nombre' => $turno->nombre_tur,
                'descripcion' => $turno->descripcionConHorario(),
                'horaInicio' => $turno->hora_inicio_tur ? substr((string) $turno->hora_inicio_tur, 0, 5) : '',
                'horaFin' => $turno->hora_fin_tur ? substr((string) $turno->hora_fin_tur, 0, 5) : '',
                'aulas' => $turno->proceso_aulas_count,
                'estado' => $turno->estado_tur->value,
                'registro' => $this->registro($turno->creado_en),
            ]);

        return Inertia::render('mantenedores/turnos', [
            'turnos' => $turnos,
            'filtros' => $filtros,
        ]);
    }

    public function store(TurnoRequest $request): RedirectResponse
    {
        Turno::query()->create([...$this->datos($request), 'estado_tur' => EstadoCatalogo::Habilitado->value]);

        return $this->volverCon('Turno registrado correctamente.');
    }

    public function update(TurnoRequest $request, Turno $turno): RedirectResponse
    {
        $turno->update($this->datos($request));

        return $this->volverCon('Turno actualizado correctamente.');
    }

    public function estado(Turno $turno): RedirectResponse
    {
        return $this->alternarEstado($turno, 'estado_tur', 'Estado del turno actualizado.');
    }

    public function destroy(Turno $turno): RedirectResponse
    {
        $motivo = match (true) {
            $turno->inscripciones()->withTrashed()->exists() => 'El turno tiene inscripciones y no puede eliminarse. Puede deshabilitarlo.',
            $turno->procesoAulas()->withTrashed()->exists() => 'El turno se usa en aulas por proceso y no puede eliminarse. Puede deshabilitarlo.',
            default => null,
        };

        return $this->eliminarSiNoSeUsa($turno, $motivo, 'Turno eliminado correctamente.');
    }

    /**
     * @return array{codigo_tur: string, nombre_tur: string, hora_inicio_tur: ?string, hora_fin_tur: ?string}
     */
    private function datos(TurnoRequest $request): array
    {
        return [
            'codigo_tur' => $request->validated('codigo'),
            'nombre_tur' => $request->validated('nombre'),
            'hora_inicio_tur' => $request->validated('hora_inicio') ?: null,
            'hora_fin_tur' => $request->validated('hora_fin') ?: null,
        ];
    }
}
