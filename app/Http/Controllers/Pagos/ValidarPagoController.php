<?php

namespace App\Http\Controllers\Pagos;

use App\Enums\EstadoImportacionBancoDetalle;
use App\Enums\EstadoInscripcion;
use App\Enums\EstadoObligacionPago;
use App\Http\Controllers\Controller;
use App\Models\ImportacionBancoDetalle;
use App\Models\InscripcionObligacionPago;
use App\Models\InscripcionPago;
use App\Support\InscripcionPagoManager;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Validación manual de un pago: se elige la obligación de una inscripción, se busca el
 * pago del banco (DNI, operación y fecha) y se asocia.
 */
class ValidarPagoController extends Controller
{
    public function __construct(private InscripcionPagoManager $pagos) {}

    public function index(Request $request): Response
    {
        $filtros = [
            'buscar' => trim((string) $request->query('buscar', $request->query('codigo', ''))),
            'obligacion' => ctype_digit((string) $request->query('obligacion')) ? (string) $request->query('obligacion') : '',
            'dni' => trim((string) $request->query('dni', '')),
            'operacion' => trim((string) $request->query('operacion', '')),
            'fecha' => (string) $request->query('fecha', now()->subDay()->toDateString()),
        ];

        $seleccionada = $filtros['obligacion'] === ''
            ? null
            : InscripcionObligacionPago::query()->with(['inscripcion.persona', 'reglaPago.conceptoPago', 'pagos.importacionBancoDetalle'])->find((int) $filtros['obligacion']);

        $busquedaRealizada = $seleccionada !== null && $filtros['dni'] !== '' && $filtros['operacion'] !== '' && $filtros['fecha'] !== '';

        return Inertia::render('pagos/validar', [
            'filtros' => $filtros,
            'obligaciones' => $this->obligaciones($filtros['buscar'])->map(fn (InscripcionObligacionPago $obligacion): array => $this->resumen($obligacion))->values(),
            'seleccionada' => $seleccionada === null ? null : [
                ...$this->resumen($seleccionada),
                'pagos' => $seleccionada->pagos->map(fn (InscripcionPago $pago): array => [
                    'id' => $pago->id_ipa,
                    'operacion' => $pago->importacionBancoDetalle?->numero_operacion_visible_ibd ?? $pago->numero_operacion_ipa,
                    'fecha' => $pago->importacionBancoDetalle?->fecha_pago_ibd?->format('d/m/Y'),
                    'monto' => (float) $pago->monto_ipa,
                ])->values(),
            ],
            'busquedaRealizada' => $busquedaRealizada,
            'pagosEncontrados' => $busquedaRealizada
                ? $this->pagos->buscarDetallesDisponibles($filtros['dni'], $filtros['operacion'], $filtros['fecha'])
                    ->sortByDesc('fecha_pago_ibd')
                    ->map(fn (ImportacionBancoDetalle $detalle): array => [
                        'id' => $detalle->id_ibd,
                        'documento' => $detalle->numero_documento_ibd,
                        'depositante' => $detalle->nombre_depositante_ibd ?? $detalle->referencia_ibd,
                        'operacion' => $detalle->numero_operacion_visible_ibd ?: $detalle->numero_operacion_ibd,
                        'fecha' => $detalle->fecha_pago_ibd?->format('d/m/Y'),
                        'monto' => (float) $detalle->monto_ibd,
                        'disponible' => $detalle->estado_ibd === EstadoImportacionBancoDetalle::Pendiente && $detalle->inscripcionPago()->doesntExist(),
                    ])
                    ->values()
                : [],
        ]);
    }

    public function confirmar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'obligacion' => ['required', 'integer', 'exists:tbl_inscripcion_obligacion_pago,id_iop'],
            'detalle' => ['required', 'integer', 'exists:tbl_importacion_banco_detalle,id_ibd'],
        ], [
            'obligacion.required' => 'Debe seleccionar una obligación de pago.',
            'detalle.required' => 'Debe seleccionar un pago importado antes de confirmar.',
        ]);

        try {
            $this->pagos->asociarDetalleAObligacion(
                InscripcionObligacionPago::query()->findOrFail((int) $datos['obligacion']),
                ImportacionBancoDetalle::query()->findOrFail((int) $datos['detalle']),
            );
        } catch (DomainException $error) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $error->getMessage()]);

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pago validado y asociado correctamente.']);

        return back();
    }

    /**
     * Obligaciones pendientes o parciales de inscripciones no anuladas (las 12 más recientes).
     *
     * @return Collection<int, InscripcionObligacionPago>
     */
    private function obligaciones(string $buscar)
    {
        $texto = preg_replace('/\s+/u', ' ', $buscar) ?: '';

        return InscripcionObligacionPago::query()
            ->with(['inscripcion.persona', 'reglaPago.conceptoPago'])
            ->whereIn('estado_iop', [EstadoObligacionPago::Pendiente->value, EstadoObligacionPago::Parcial->value])
            ->whereHas('inscripcion', fn (Builder $query) => $query->where('estado_ins', '!=', EstadoInscripcion::Anulado->value))
            ->when($texto !== '', fn (Builder $query) => $query->whereHas('inscripcion', fn (Builder $inscripcion) => $inscripcion
                ->where('codigo_ins', 'like', "%{$texto}%")
                ->orWhereHas('persona', fn (Builder $persona) => $persona
                    ->where('numero_documento_per', 'like', "%{$texto}%")
                    ->orWhere('apellido_paterno_per', 'like', "%{$texto}%")
                    ->orWhere('apellido_materno_per', 'like', "%{$texto}%")
                    ->orWhere('nombres_per', 'like', "%{$texto}%"))))
            ->orderByDesc('id_iop')
            ->limit(12)
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function resumen(InscripcionObligacionPago $obligacion): array
    {
        $persona = $obligacion->inscripcion?->persona;

        return [
            'id' => $obligacion->id_iop,
            'codigo' => $obligacion->inscripcion?->codigo_ins,
            'dni' => $persona?->numero_documento_per,
            'postulante' => trim(implode(' ', array_filter([$persona?->apellido_paterno_per, $persona?->apellido_materno_per, $persona?->nombres_per]))),
            'concepto' => $obligacion->reglaPago?->conceptoPago?->nombre_cpg,
            'total' => (float) $obligacion->monto_total_iop,
            'pagado' => (float) $obligacion->monto_pagado_iop,
            'saldo' => (float) $obligacion->saldo_iop,
            'estado' => $obligacion->estado_iop->value,
        ];
    }
}
