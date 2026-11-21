<?php

namespace App\Http\Controllers\ConfiguracionAcademica;

use App\Enums\EstadoCatalogo;
use App\Enums\EstadoProceso;
use App\Http\Controllers\CatalogoController;
use App\Http\Requests\ConfiguracionAcademica\ReglaPagoRequest;
use App\Models\ConceptoPago;
use App\Models\ModalidadAdmision;
use App\Models\Proceso;
use App\Models\ReglaPago;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Cuánto se paga por cada concepto en un proceso y modalidad, y si se puede pagar en cuotas.
 */
class ReglaPagoController extends CatalogoController
{
    public function index(Request $request): Response
    {
        $proceso = (string) $request->query('proceso', '');
        $filtros = [...$this->filtros($request), 'proceso' => ctype_digit($proceso) ? $proceso : ''];

        $reglas = ReglaPago::query()
            ->with(['conceptoPago', 'proceso', 'modalidadAdmision'])
            ->when($filtros['buscar'] !== '', function (Builder $query) use ($filtros): void {
                $texto = $filtros['buscar'];

                $query->where(fn (Builder $interna) => $interna
                    ->whereHas('conceptoPago', fn (Builder $concepto) => $concepto->where('nombre_cpg', 'like', "%{$texto}%")->orWhere('codigo_cpg', 'like', "%{$texto}%"))
                    ->orWhereHas('proceso', fn (Builder $porProceso) => $porProceso->where('nombre_pro', 'like', "%{$texto}%"))
                    ->orWhereHas('modalidadAdmision', fn (Builder $modalidad) => $modalidad->where('nombre_mad', 'like', "%{$texto}%")));
            })
            ->when($filtros['proceso'] !== '', fn (Builder $query) => $query->where('id_proceso', (int) $filtros['proceso']))
            ->when($filtros['estado'] !== [], fn (Builder $query) => $query->whereIn('estado_rpa', $filtros['estado']))
            ->orderByDesc('id_rpa')
            ->paginate(self::POR_PAGINA)
            ->withQueryString()
            ->through(fn (ReglaPago $regla): array => [
                'id' => $regla->id_rpa,
                'concepto' => $regla->conceptoPago?->nombre_cpg,
                'codigoConcepto' => $regla->conceptoPago?->codigo_cpg,
                'proceso' => $regla->proceso?->nombre_pro,
                'modalidad' => $regla->modalidadAdmision?->nombre_mad,
                'montoTotal' => (float) $regla->monto_total_rpa,
                'permiteCuotas' => (bool) $regla->permite_cuotas_rpa,
                'cuotasMaximas' => $regla->numero_maximo_cuotas_rpa,
                'minimoPrimeraCuota' => (float) $regla->monto_minimo_primera_cuota_rpa,
                'requierePagoCompleto' => (bool) $regla->requiere_pago_completo_rpa,
                'documentoRequerido' => $regla->documento_requerido_rpa,
                'vigenteDesde' => $regla->vigente_desde_rpa?->format('d/m/Y'),
                'vigenteHasta' => $regla->vigente_hasta_rpa?->format('d/m/Y'),
                'formulario' => [
                    'id_concepto_pago' => (string) $regla->id_concepto_pago,
                    'id_proceso' => (string) $regla->id_proceso,
                    'id_modalidad_admision' => $regla->id_modalidad_admision ? (string) $regla->id_modalidad_admision : '',
                    'monto_total' => number_format((float) $regla->monto_total_rpa, 2, '.', ''),
                    'permite_cuotas' => (bool) $regla->permite_cuotas_rpa,
                    'monto_minimo_primera_cuota' => $regla->permite_cuotas_rpa ? number_format((float) $regla->monto_minimo_primera_cuota_rpa, 2, '.', '') : '',
                    'numero_maximo_cuotas' => (string) $regla->numero_maximo_cuotas_rpa,
                    'requiere_pago_completo' => (bool) $regla->requiere_pago_completo_rpa,
                    'documento_requerido' => (string) ($regla->documento_requerido_rpa ?? ''),
                    'vigente_desde' => $regla->vigente_desde_rpa?->format('Y-m-d') ?? '',
                    'vigente_hasta' => $regla->vigente_hasta_rpa?->format('Y-m-d') ?? '',
                ],
                'estado' => $regla->estado_rpa->value,
                'registro' => $this->registro($regla->creado_en),
            ]);

        return Inertia::render('configuracion-academica/reglas-pago', [
            'reglas' => $reglas,
            'filtros' => $filtros,
            'procesosFiltro' => Proceso::query()
                ->where(fn (Builder $query) => $query->whereHas('reglasPago')->orWhere('estado_pro', EstadoProceso::Habilitado->value))
                ->orderByDesc('id_pro')
                ->get()
                ->map(fn (Proceso $opcion): array => ['valor' => (string) $opcion->id_pro, 'nombre' => $opcion->nombre_pro]),
            'procesos' => Proceso::query()
                ->orderByDesc('id_pro')
                ->get()
                ->map(fn (Proceso $opcion): array => [
                    'valor' => (string) $opcion->id_pro,
                    'nombre' => $opcion->nombre_pro,
                    'habilitado' => $opcion->estado_pro === EstadoProceso::Habilitado,
                    'inicio' => $opcion->fecha_inicio_pro?->format('Y-m-d'),
                    'fin' => $opcion->fecha_fin_pro?->format('Y-m-d'),
                ]),
            'conceptos' => ConceptoPago::query()
                ->where('estado_cpg', EstadoCatalogo::Habilitado->value)
                ->orderBy('nombre_cpg')
                ->get()
                ->map(fn (ConceptoPago $concepto): array => ['valor' => (string) $concepto->id_cpg, 'nombre' => $concepto->nombre_cpg, 'permiteCuotas' => (bool) $concepto->permite_cuotas_cpg]),
            'modalidades' => ModalidadAdmision::query()
                ->where('estado_mad', EstadoCatalogo::Habilitado->value)
                ->orderBy('nombre_mad')
                ->get()
                ->map(fn (ModalidadAdmision $modalidad): array => ['valor' => (string) $modalidad->id_mad, 'nombre' => $modalidad->nombre_mad]),
            'documentos' => ReglaPagoRequest::DOCUMENTOS_REQUERIDOS,
        ]);
    }

    public function store(ReglaPagoRequest $request): RedirectResponse
    {
        ReglaPago::query()->create([...$this->datos($request), 'estado_rpa' => EstadoCatalogo::Habilitado->value]);

        return $this->volverCon('Regla de pago registrada correctamente.');
    }

    public function update(ReglaPagoRequest $request, ReglaPago $regla): RedirectResponse
    {
        $regla->update($this->datos($request));

        return $this->volverCon('Regla de pago actualizada correctamente.');
    }

    public function estado(ReglaPago $regla): RedirectResponse
    {
        return $this->alternarEstado($regla, 'estado_rpa', 'Estado de la regla de pago actualizado.');
    }

    public function destroy(ReglaPago $regla): RedirectResponse
    {
        return $this->eliminarSiNoSeUsa(
            $regla,
            $regla->obligacionesPago()->exists() ? 'La regla de pago tiene obligaciones relacionadas y no puede eliminarse.' : null,
            'Regla de pago eliminada correctamente.',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function datos(ReglaPagoRequest $request): array
    {
        $permiteCuotas = $request->boolean('permite_cuotas');

        return [
            'id_concepto_pago' => (int) $request->validated('id_concepto_pago'),
            'id_proceso' => (int) $request->validated('id_proceso'),
            'id_modalidad_admision' => filled($request->validated('id_modalidad_admision')) ? (int) $request->validated('id_modalidad_admision') : null,
            'monto_total_rpa' => (float) $request->validated('monto_total'),
            'permite_cuotas_rpa' => $permiteCuotas,
            'monto_minimo_primera_cuota_rpa' => $permiteCuotas ? (float) $request->validated('monto_minimo_primera_cuota') : 0,
            'numero_maximo_cuotas_rpa' => $permiteCuotas ? (int) $request->validated('numero_maximo_cuotas') : 1,
            'requiere_pago_completo_rpa' => $request->boolean('requiere_pago_completo'),
            'documento_requerido_rpa' => filled($request->validated('documento_requerido')) ? $request->validated('documento_requerido') : null,
            'vigente_desde_rpa' => $request->validated('vigente_desde'),
            'vigente_hasta_rpa' => filled($request->validated('vigente_hasta')) ? $request->validated('vigente_hasta') : null,
        ];
    }
}
