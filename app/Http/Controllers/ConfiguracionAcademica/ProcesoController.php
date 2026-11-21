<?php

namespace App\Http\Controllers\ConfiguracionAcademica;

use App\Enums\EstadoProceso;
use App\Http\Controllers\CatalogoController;
use App\Http\Requests\ConfiguracionAcademica\ProcesoRequest;
use App\Models\Proceso;
use App\Support\EstadoAcademicoSynchronizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProcesoController extends CatalogoController
{
    public function __construct(private EstadoAcademicoSynchronizer $sincronizador) {}

    public function index(Request $request): Response
    {
        $this->sincronizador->sync();

        $filtros = $this->filtros($request, array_column(EstadoProceso::cases(), 'value'));

        $procesos = $this->aplicarFiltros(
            Proceso::query()->withCount(['procesoCarreras', 'procesoModalidades', 'reglasPago']),
            $filtros,
            ['codigo_pro', 'nombre_pro'],
            'estado_pro',
        )
            ->orderByDesc('id_pro')
            ->paginate(self::POR_PAGINA)
            ->withQueryString()
            ->through(fn (Proceso $proceso): array => [
                'id' => $proceso->id_pro,
                'nombre' => $proceso->nombre_pro,
                'codigo' => $proceso->codigo_pro,
                'apertura' => $this->registro($proceso->fecha_inicio_pro),
                'cierre' => $this->registro($proceso->fecha_fin_pro),
                'aperturaValor' => $proceso->fecha_inicio_pro?->format('Y-m-d\TH:i') ?? '',
                'cierreValor' => $proceso->fecha_fin_pro?->format('Y-m-d\TH:i') ?? '',
                'anioEgreso' => $proceso->anio_egreso_max_ordinario_pro,
                'uso' => $proceso->proceso_carreras_count + $proceso->proceso_modalidades_count + $proceso->reglas_pago_count,
                'estado' => $proceso->estado_pro->value,
                'registro' => $this->registro($proceso->creado_en),
            ]);

        return Inertia::render('configuracion-academica/procesos', [
            'procesos' => $procesos,
            'filtros' => $filtros,
        ]);
    }

    public function store(ProcesoRequest $request): RedirectResponse
    {
        Proceso::query()->create([...$this->datos($request), 'estado_pro' => EstadoProceso::Habilitado->value]);

        return $this->volverCon('Proceso registrado correctamente.');
    }

    public function update(ProcesoRequest $request, Proceso $proceso): RedirectResponse
    {
        $proceso->update($this->datos($request));

        return $this->volverCon('Proceso actualizado correctamente.');
    }

    /**
     * Alterna entre habilitado y deshabilitado. Un proceso vencido queda cerrado y ya no cambia.
     */
    public function estado(Proceso $proceso): RedirectResponse
    {
        $this->sincronizador->sync();
        $proceso->refresh();

        if ($proceso->estado_pro === EstadoProceso::Cerrado || $this->sincronizador->procesoEstaVencido($proceso)) {
            if ($proceso->estado_pro !== EstadoProceso::Cerrado) {
                $proceso->update(['estado_pro' => EstadoProceso::Cerrado->value]);
            }

            return $this->volverCon('El proceso ya venció por fecha y ahora se encuentra cerrado.', 'error');
        }

        return $this->alternarEstado($proceso, 'estado_pro', 'Estado del proceso actualizado.');
    }

    public function destroy(Proceso $proceso): RedirectResponse
    {
        $tieneConfiguracion = $proceso->procesoCarreras()->exists() || $proceso->procesoModalidades()->exists() || $proceso->reglasPago()->exists();

        return $this->eliminarSiNoSeUsa(
            $proceso,
            $tieneConfiguracion ? 'El proceso tiene configuraciones relacionadas y no puede eliminarse.' : null,
            'Proceso eliminado correctamente.',
        );
    }

    /**
     * @return array{codigo_pro: string, nombre_pro: string, fecha_inicio_pro: string, fecha_fin_pro: string, anio_egreso_max_ordinario_pro: ?int}
     */
    private function datos(ProcesoRequest $request): array
    {
        $anio = $request->validated('anio_egreso_max_ordinario');

        return [
            'codigo_pro' => ProcesoRequest::codigoDesdeNombre($request->validated('nombre')),
            'nombre_pro' => $request->validated('nombre'),
            'fecha_inicio_pro' => $request->validated('fecha_inicio'),
            'fecha_fin_pro' => $request->validated('fecha_fin'),
            'anio_egreso_max_ordinario_pro' => filled($anio) ? (int) $anio : null,
        ];
    }
}
