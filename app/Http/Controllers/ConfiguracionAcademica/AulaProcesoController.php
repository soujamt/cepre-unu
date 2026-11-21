<?php

namespace App\Http\Controllers\ConfiguracionAcademica;

use App\Enums\EstadoCatalogo;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\Concerns\EligeProceso;
use App\Models\Aula;
use App\Models\Proceso;
use App\Models\ProcesoAula;
use App\Models\Turno;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Aulas asignadas a cada proceso y turno, con su capacidad.
 */
class AulaProcesoController extends CatalogoController
{
    use EligeProceso;

    public function index(Request $request): Response
    {
        $filtros = [
            ...$this->filtros($request),
            'proceso' => $this->idDeConsulta($request, 'proceso'),
            'turno' => $this->idDeConsulta($request, 'turno'),
        ];

        $asignaciones = ProcesoAula::query()
            ->with(['proceso', 'aula', 'turno'])
            ->withCount('inscripciones')
            ->when($filtros['buscar'] !== '', function (Builder $query) use ($filtros): void {
                $texto = $filtros['buscar'];

                $query->where(fn (Builder $interna) => $interna
                    ->whereHas('aula', fn (Builder $aula) => $aula->where('nombre_aul', 'like', "%{$texto}%")->orWhere('lugar_aul', 'like', "%{$texto}%")->orWhere('piso_aul', 'like', "%{$texto}%"))
                    ->orWhereHas('proceso', fn (Builder $proceso) => $proceso->where('nombre_pro', 'like', "%{$texto}%")->orWhere('codigo_pro', 'like', "%{$texto}%")));
            })
            ->when($filtros['proceso'] !== '', fn (Builder $query) => $query->where('id_proceso', (int) $filtros['proceso']))
            ->when($filtros['turno'] !== '', fn (Builder $query) => $query->where('id_turno', (int) $filtros['turno']))
            ->when($filtros['estado'] !== [], fn (Builder $query) => $query->whereIn('estado_pau', $filtros['estado']))
            ->orderByDesc('id_pau')
            ->paginate(self::POR_PAGINA)
            ->withQueryString()
            ->through(fn (ProcesoAula $asignacion): array => [
                'id' => $asignacion->id_pau,
                'proceso' => $asignacion->proceso?->nombre_pro,
                'aula' => $asignacion->aula?->nombre_aul,
                'piso' => $asignacion->aula?->piso_aul?->value,
                'lugar' => $asignacion->aula?->lugar_aul?->value,
                'turno' => $asignacion->turno?->nombre_tur,
                'capacidad' => $asignacion->capacidad_pau,
                'inscritos' => $asignacion->inscripciones_count,
                'estado' => $asignacion->estado_pau->value,
                'registro' => $this->registro($asignacion->creado_en),
            ]);

        return Inertia::render('configuracion-academica/aulas-proceso', [
            'asignaciones' => $asignaciones,
            'filtros' => $filtros,
            'procesos' => $this->opcionesProceso(),
            'turnos' => Turno::query()
                ->where('estado_tur', EstadoCatalogo::Habilitado->value)
                ->whereHas('procesoAulas', fn (Builder $query) => $query->when($filtros['proceso'] !== '', fn (Builder $porProceso) => $porProceso->where('id_proceso', (int) $filtros['proceso'])))
                ->orderBy('nombre_tur')
                ->get()
                ->map(fn (Turno $turno): array => ['valor' => (string) $turno->id_tur, 'nombre' => $turno->nombre_tur]),
        ]);
    }

    /**
     * Pantalla para asignar varias aulas a un proceso y turno.
     */
    public function asignar(Request $request): Response
    {
        $proceso = $this->idDeConsulta($request, 'proceso') !== '' ? Proceso::query()->find((int) $request->query('proceso')) : null;
        $turno = $this->idDeConsulta($request, 'turno') !== '' ? Turno::query()->find((int) $request->query('turno')) : null;

        $asignadas = $proceso && $turno
            ? ProcesoAula::query()->where('id_proceso', $proceso->id_pro)->where('id_turno', $turno->id_tur)->pluck('id_aula')->map(fn (mixed $id): int => (int) $id)->all()
            : [];

        return Inertia::render('configuracion-academica/aulas-proceso-asignar', [
            'procesos' => $this->opcionesProceso(),
            'turnos' => Turno::query()->where('estado_tur', EstadoCatalogo::Habilitado->value)->orderBy('nombre_tur')->get()
                ->map(fn (Turno $turnoDisponible): array => ['valor' => (string) $turnoDisponible->id_tur, 'nombre' => $turnoDisponible->descripcionConHorario()]),
            'proceso' => $proceso?->id_pro,
            'turno' => $turno?->id_tur,
            'aulas' => Aula::query()
                ->where('estado_aul', EstadoCatalogo::Habilitado->value)
                ->orderBy('lugar_aul')
                ->orderBy('piso_aul')
                ->orderBy('orden_aul')
                ->get()
                ->map(fn (Aula $aula): array => [
                    'id' => $aula->id_aul,
                    'nombre' => $aula->nombre_aul,
                    'piso' => $aula->piso_aul->value,
                    'lugar' => $aula->lugar_aul->value,
                    'asignada' => in_array($aula->id_aul, $asignadas, true),
                ]),
        ]);
    }

    /**
     * Asigna las aulas marcadas al proceso y turno; restaura las que se habían quitado
     * y no duplica las que ya estaban.
     */
    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'proceso' => ['required', 'integer', 'exists:tbl_proceso,id_pro'],
            'turno' => ['required', 'integer', 'exists:tbl_turno,id_tur'],
            'aulas' => ['required', 'array', 'min:1'],
            'aulas.*.id' => ['required', 'integer', 'exists:tbl_aula,id_aul'],
            'aulas.*.capacidad' => ['required', 'integer', 'min:1'],
        ], [
            'proceso.required' => 'Debe seleccionar un proceso.',
            'turno.required' => 'Debe seleccionar un turno.',
            'aulas.required' => 'Debe seleccionar al menos un aula.',
            'aulas.min' => 'Debe seleccionar al menos un aula.',
            'aulas.*.capacidad.required' => 'Todas las aulas seleccionadas deben tener una capacidad válida (mayor a 0).',
            'aulas.*.capacidad.integer' => 'Todas las aulas seleccionadas deben tener una capacidad válida (mayor a 0).',
            'aulas.*.capacidad.min' => 'Todas las aulas seleccionadas deben tener una capacidad válida (mayor a 0).',
        ]);

        DB::transaction(function () use ($datos): void {
            foreach ($datos['aulas'] as $aula) {
                $existente = ProcesoAula::query()
                    ->withTrashed()
                    ->where('id_proceso', (int) $datos['proceso'])
                    ->where('id_turno', (int) $datos['turno'])
                    ->where('id_aula', (int) $aula['id'])
                    ->first();

                if ($existente?->trashed()) {
                    $existente->restore();
                    $existente->update(['capacidad_pau' => (int) $aula['capacidad'], 'estado_pau' => EstadoCatalogo::Habilitado->value]);

                    continue;
                }

                if ($existente !== null) {
                    continue;
                }

                ProcesoAula::query()->create([
                    'id_proceso' => (int) $datos['proceso'],
                    'id_turno' => (int) $datos['turno'],
                    'id_aula' => (int) $aula['id'],
                    'capacidad_pau' => (int) $aula['capacidad'],
                    'estado_pau' => EstadoCatalogo::Habilitado->value,
                ]);
            }
        });

        return $this->volverCon('Aulas asignadas correctamente.');
    }

    public function update(Request $request, ProcesoAula $asignacion): RedirectResponse
    {
        $datos = $request->validate(['capacidad' => ['required', 'integer', 'min:1']], [
            'capacidad.required' => 'La capacidad es obligatoria.',
            'capacidad.integer' => 'La capacidad debe ser un número entero.',
            'capacidad.min' => 'La capacidad debe ser al menos 1.',
        ]);

        $asignacion->update(['capacidad_pau' => (int) $datos['capacidad']]);

        return $this->volverCon('Capacidad actualizada correctamente.');
    }

    public function estado(ProcesoAula $asignacion): RedirectResponse
    {
        return $this->alternarEstado($asignacion, 'estado_pau', 'Estado actualizado.');
    }

    public function destroy(ProcesoAula $asignacion): RedirectResponse
    {
        return $this->eliminarSiNoSeUsa(
            $asignacion,
            $asignacion->inscripciones()->exists() ? 'No se puede eliminar porque existen inscripciones en esta aula.' : null,
            'Asignación eliminada correctamente.',
        );
    }

    private function idDeConsulta(Request $request, string $clave): string
    {
        $valor = (string) $request->query($clave, '');

        return ctype_digit($valor) ? $valor : '';
    }
}
