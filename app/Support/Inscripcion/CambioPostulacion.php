<?php

namespace App\Support\Inscripcion;

use App\Enums\EstadoCatalogo;
use App\Enums\EstadoInscripcion;
use App\Models\Carrera;
use App\Models\Inscripcion;
use App\Models\InscripcionCambio;
use App\Models\ProcesoAula;
use App\Models\ProcesoCarrera;
use App\Models\Sede;
use App\Models\Turno;
use App\Support\AsignadorAulaInscripcion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Cambio de sede, carrera o turno de una inscripción ya registrada. Valida contra
 * la oferta del proceso de la inscripción, reasigna el aula si cambia el turno y
 * deja el cambio con su motivo en el historial.
 */
class CambioPostulacion
{
    public function __construct(private AsignadorAulaInscripcion $asignador) {}

    /**
     * Solo se editan las inscripciones vigentes (activas o pendientes).
     */
    public function esEditable(Inscripcion $inscripcion): bool
    {
        return in_array($inscripcion->estado_ins, [EstadoInscripcion::Activo, EstadoInscripcion::Pendiente], true);
    }

    /**
     * Sedes con sus carreras habilitadas en el proceso para la modalidad de la inscripción;
     * incluye la actual aunque ya no esté habilitada.
     *
     * @return list<array{valor: string, nombre: string, carreras: list<array{valor: string, nombre: string}>}>
     */
    public function sedes(Inscripcion $inscripcion): array
    {
        $ofertas = ProcesoCarrera::query()
            ->with(['sede', 'carrera'])
            ->where('id_proceso', $inscripcion->id_proceso)
            ->where('id_modalidad_admision', $inscripcion->id_modalidad_admision)
            ->where('estado_pca', EstadoCatalogo::Habilitado->value)
            ->whereHas('sede', fn (Builder $query) => $query->where('estado_sed', EstadoCatalogo::Habilitado->value))
            ->whereHas('carrera', fn (Builder $query) => $query->where('estado_car', EstadoCatalogo::Habilitado->value))
            ->get()
            ->map(fn (ProcesoCarrera $oferta): array => [
                'sede' => (string) $oferta->id_sede,
                'nombreSede' => (string) $oferta->sede?->nombre_sed,
                'carrera' => (string) $oferta->id_carrera,
                'nombreCarrera' => (string) $oferta->carrera?->nombre_car,
            ]);

        $actual = ['sede' => (string) $inscripcion->id_sede, 'carrera' => (string) $inscripcion->id_carrera];

        if (! $ofertas->contains(fn (array $oferta): bool => $oferta['sede'] === $actual['sede'] && $oferta['carrera'] === $actual['carrera'])) {
            $ofertas->push([
                ...$actual,
                'nombreSede' => (string) $inscripcion->sede?->nombre_sed,
                'nombreCarrera' => (string) $inscripcion->carrera?->nombre_car,
            ]);
        }

        return $ofertas
            ->groupBy('sede')
            ->map(fn (Collection $grupo, string $sede): array => [
                'valor' => $sede,
                'nombre' => $grupo->first()['nombreSede'],
                'carreras' => $grupo
                    ->map(fn (array $oferta): array => ['valor' => $oferta['carrera'], 'nombre' => $oferta['nombreCarrera']])
                    ->sortBy('nombre')
                    ->values()
                    ->all(),
            ])
            ->sortBy('nombre')
            ->values()
            ->all();
    }

    /**
     * Turnos con aulas habilitadas en el proceso y sus cupos libres; incluye el actual.
     *
     * @return list<array{valor: string, nombre: string, libres: int}>
     */
    public function turnos(Inscripcion $inscripcion): array
    {
        $turnos = ProcesoAula::query()
            ->with('turno')
            ->withCount(['inscripciones as ocupados' => fn (Builder $query) => $query
                ->where('estado_ins', '!=', EstadoInscripcion::Anulado->value)
                ->where('id_ins', '!=', $inscripcion->id_ins)])
            ->where('id_proceso', $inscripcion->id_proceso)
            ->where('estado_pau', EstadoCatalogo::Habilitado->value)
            ->whereHas('aula', fn (Builder $query) => $query->where('estado_aul', EstadoCatalogo::Habilitado->value))
            ->get()
            ->groupBy('id_turno')
            ->map(fn (Collection $aulas): array => [
                'valor' => (string) $aulas->first()->id_turno,
                'nombre' => $aulas->first()->turno?->descripcionConHorario() ?? 'SIN TURNO',
                'libres' => (int) $aulas->sum(fn (ProcesoAula $aula): int => max(0, $aula->capacidad_pau - $aula->ocupados)),
            ]);

        if (! $turnos->has($inscripcion->id_turno) && $inscripcion->turno !== null) {
            $turnos->put($inscripcion->id_turno, [
                'valor' => (string) $inscripcion->id_turno,
                'nombre' => $inscripcion->turno->descripcionConHorario(),
                'libres' => 0,
            ]);
        }

        return $turnos->sortBy('nombre')->values()->all();
    }

    public function sedeValida(Inscripcion $inscripcion, int $sede): bool
    {
        return collect($this->sedes($inscripcion))->contains('valor', (string) $sede);
    }

    public function carreraValida(Inscripcion $inscripcion, int $sede, int $carrera): bool
    {
        $opcion = collect($this->sedes($inscripcion))->firstWhere('valor', (string) $sede);

        return $opcion !== null && collect($opcion['carreras'])->contains('valor', (string) $carrera);
    }

    public function turnoValido(Inscripcion $inscripcion, int $turno): bool
    {
        return collect($this->turnos($inscripcion))->contains('valor', (string) $turno);
    }

    /**
     * Aplica el cambio y lo registra en el historial.
     *
     * @throws RuntimeException si no hay cambios o el nuevo turno no tiene aulas con cupo
     */
    public function aplicar(int $idInscripcion, int $sede, int $carrera, int $turno, string $motivo): InscripcionCambio
    {
        return DB::transaction(function () use ($idInscripcion, $sede, $carrera, $turno, $motivo): InscripcionCambio {
            $inscripcion = Inscripcion::query()->with(['sede', 'carrera', 'turno', 'procesoAula.aula'])->lockForUpdate()->findOrFail($idInscripcion);

            if (! $this->esEditable($inscripcion)) {
                throw new RuntimeException('Solo se pueden editar inscripciones activas o pendientes.');
            }

            $cambios = array_values(array_filter([
                $this->cambio('Sede', $inscripcion->id_sede, $sede, $inscripcion->sede?->nombre_sed, fn () => Sede::query()->find($sede)?->nombre_sed),
                $this->cambio('Carrera', $inscripcion->id_carrera, $carrera, $inscripcion->carrera?->nombre_car, fn () => Carrera::query()->find($carrera)?->nombre_car),
                $this->cambio('Turno', $inscripcion->id_turno, $turno, $inscripcion->turno?->nombre_tur, fn () => Turno::query()->find($turno)?->nombre_tur),
            ]));

            if ($cambios === []) {
                throw new RuntimeException('No hay cambios: la sede, la carrera y el turno son los mismos.');
            }

            $aulaAnterior = $inscripcion->procesoAula?->aula?->nombre_aul;
            $cambiaTurno = $inscripcion->id_turno !== $turno;

            $inscripcion->update(['id_sede' => $sede, 'id_carrera' => $carrera, 'id_turno' => $turno]);

            if ($cambiaTurno) {
                $inscripcion->update(['id_proceso_aula' => null]);

                try {
                    $aulaNueva = $this->asignador->asignarDisponible($inscripcion, $inscripcion->id_proceso, $turno);
                } catch (RuntimeException) {
                    throw new RuntimeException('El turno elegido no tiene aulas con cupo libre. Amplíe la capacidad o elija otro turno.');
                }

                $cambios[] = ['campo' => 'Aula', 'antes' => $aulaAnterior ?? 'Sin aula', 'despues' => $aulaNueva?->aula?->nombre_aul ?? 'Sin aula'];
            }

            return $inscripcion->cambios()->create(['cambios_ica' => $cambios, 'motivo_ica' => trim($motivo)]);
        });
    }

    /**
     * @param  callable(): ?string  $nombreNuevo
     * @return array{campo: string, antes: ?string, despues: ?string}|null
     */
    private function cambio(string $campo, ?int $actual, int $nuevo, ?string $nombreActual, callable $nombreNuevo): ?array
    {
        return $actual === $nuevo ? null : ['campo' => $campo, 'antes' => $nombreActual, 'despues' => $nombreNuevo()];
    }
}
