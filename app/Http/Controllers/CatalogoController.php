<?php

namespace App\Http\Controllers;

use App\Enums\EstadoCatalogo;
use BackedEnum;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * Comportamiento común de los mantenedores (catálogos con estado HABILITADO /
 * DESHABILITADO): filtros del listado, cambio de estado, eliminación y avisos.
 */
abstract class CatalogoController extends Controller
{
    protected const POR_PAGINA = 10;

    /**
     * Filtros del listado leídos de la URL: texto de búsqueda y estados marcados.
     *
     * @return array{buscar: string, estado: list<string>}
     */
    protected function filtros(Request $request): array
    {
        $estados = collect(EstadoCatalogo::cases())->pluck('value')->all();

        return [
            'buscar' => (string) preg_replace('/\s+/u', ' ', trim((string) $request->query('buscar', ''))),
            'estado' => collect((array) $request->query('estado', []))
                ->filter(fn (mixed $estado): bool => in_array($estado, $estados, true))
                ->values()
                ->all(),
        ];
    }

    /**
     * Aplica la búsqueda (en cualquiera de las columnas) y el filtro de estado.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  array{buscar: string, estado: list<string>}  $filtros
     * @param  list<string>  $columnasBusqueda
     * @return Builder<TModel>
     */
    protected function aplicarFiltros(Builder $query, array $filtros, array $columnasBusqueda, string $columnaEstado): Builder
    {
        return $query
            ->when($filtros['buscar'] !== '', function (Builder $consulta) use ($filtros, $columnasBusqueda): void {
                $consulta->where(function (Builder $interna) use ($filtros, $columnasBusqueda): void {
                    foreach ($columnasBusqueda as $columna) {
                        $interna->orWhere($columna, 'like', "%{$filtros['buscar']}%");
                    }
                });
            })
            ->when($filtros['estado'] !== [], fn (Builder $consulta) => $consulta->whereIn($columnaEstado, $filtros['estado']));
    }

    /**
     * Pasa el registro de HABILITADO a DESHABILITADO o al revés.
     */
    protected function alternarEstado(Model $modelo, string $columna, string $mensaje): RedirectResponse
    {
        $actual = $modelo->getAttribute($columna);
        $valorActual = $actual instanceof BackedEnum ? $actual->value : $actual;

        $modelo->update([
            $columna => $valorActual === EstadoCatalogo::Habilitado->value ? EstadoCatalogo::Deshabilitado->value : EstadoCatalogo::Habilitado->value,
        ]);

        return $this->volverCon($mensaje);
    }

    /**
     * Elimina el registro salvo que esté en uso; en ese caso avisa el motivo.
     */
    protected function eliminarSiNoSeUsa(Model $modelo, ?string $motivoEnUso, string $mensaje): RedirectResponse
    {
        if ($motivoEnUso !== null) {
            return $this->volverCon($motivoEnUso, 'error');
        }

        DB::transaction(fn () => $modelo->delete());

        return $this->volverCon($mensaje);
    }

    protected function volverCon(string $mensaje, string $tipo = 'success'): RedirectResponse
    {
        Inertia::flash('toast', ['type' => $tipo, 'message' => $mensaje]);

        return back();
    }

    /**
     * Fecha de creación para la columna "Registro": día y hora por separado.
     *
     * @return array{fecha: string, hora: string}|null
     */
    protected function registro(?CarbonInterface $fecha): ?array
    {
        return $fecha ? ['fecha' => $fecha->format('d/m/Y'), 'hora' => $fecha->format('h:i a')] : null;
    }
}
