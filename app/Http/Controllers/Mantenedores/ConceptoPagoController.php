<?php

namespace App\Http\Controllers\Mantenedores;

use App\Enums\EstadoCatalogo;
use App\Http\Requests\Mantenedores\ConceptoPagoRequest;
use App\Models\ConceptoPago;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConceptoPagoController extends CatalogoController
{
    public function index(Request $request): Response
    {
        $filtros = $this->filtros($request);

        $conceptos = $this->aplicarFiltros(ConceptoPago::query()->withCount('reglasPago'), $filtros, ['codigo_cpg', 'nombre_cpg'], 'estado_cpg')
            ->orderBy('nombre_cpg')
            ->paginate(self::POR_PAGINA)
            ->withQueryString()
            ->through(fn (ConceptoPago $concepto): array => [
                'id' => $concepto->id_cpg,
                'codigo' => $concepto->codigo_cpg,
                'nombre' => $concepto->nombre_cpg,
                'descripcion' => $concepto->descripcion_cpg,
                'permiteCuotas' => (bool) $concepto->permite_cuotas_cpg,
                'reglas' => $concepto->reglas_pago_count,
                'estado' => $concepto->estado_cpg->value,
                'registro' => $this->registro($concepto->creado_en),
            ]);

        return Inertia::render('mantenedores/conceptos-pago', [
            'conceptos' => $conceptos,
            'filtros' => $filtros,
        ]);
    }

    public function store(ConceptoPagoRequest $request): RedirectResponse
    {
        ConceptoPago::query()->create([...$this->datos($request), 'estado_cpg' => EstadoCatalogo::Habilitado->value]);

        return $this->volverCon('Concepto de pago registrado correctamente.');
    }

    public function update(ConceptoPagoRequest $request, ConceptoPago $concepto): RedirectResponse
    {
        $concepto->update($this->datos($request));

        return $this->volverCon('Concepto de pago actualizado correctamente.');
    }

    public function estado(ConceptoPago $concepto): RedirectResponse
    {
        return $this->alternarEstado($concepto, 'estado_cpg', 'Estado del concepto de pago actualizado.');
    }

    public function destroy(ConceptoPago $concepto): RedirectResponse
    {
        $tieneReglas = $concepto->reglasPago()->withTrashed()->exists();

        return $this->eliminarSiNoSeUsa(
            $concepto,
            $tieneReglas ? 'El concepto tiene reglas de pago y no puede eliminarse. Puede deshabilitarlo.' : null,
            'Concepto de pago eliminado correctamente.',
        );
    }

    /**
     * @return array{codigo_cpg: string, nombre_cpg: string, descripcion_cpg: ?string, permite_cuotas_cpg: bool}
     */
    private function datos(ConceptoPagoRequest $request): array
    {
        return [
            'codigo_cpg' => $request->validated('codigo'),
            'nombre_cpg' => $request->validated('nombre'),
            'descripcion_cpg' => $request->validated('descripcion'),
            'permite_cuotas_cpg' => $request->boolean('permite_cuotas'),
        ];
    }
}
