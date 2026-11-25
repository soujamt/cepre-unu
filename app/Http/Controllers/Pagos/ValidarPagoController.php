<?php

namespace App\Http\Controllers\Pagos;

use App\Enums\EstadoImportacionBancoDetalle;
use App\Http\Controllers\Controller;
use App\Models\ImportacionBancoDetalle;
use App\Models\InscripcionObligacionPago;
use App\Models\InscripcionPago;
use App\Support\InscripcionPagoManager;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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

        $erroresBusqueda = $seleccionada !== null && $request->boolean('buscar_pago') ? $this->erroresDeBusqueda($filtros) : [];

        $busquedaRealizada = $seleccionada !== null && $erroresBusqueda === []
            && $filtros['dni'] !== '' && $filtros['operacion'] !== '' && $filtros['fecha'] !== '';

        return Inertia::render('pagos/validar', [
            'filtros' => $filtros,
            'obligaciones' => $this->pagos->buscarObligacionesConSaldo($filtros['buscar'])->map(fn (InscripcionObligacionPago $obligacion): array => $this->pagos->resumenObligacion($obligacion))->values(),
            'seleccionada' => $seleccionada === null ? null : [
                ...$this->pagos->resumenObligacion($seleccionada),
                'pagos' => $seleccionada->pagos->map(fn (InscripcionPago $pago): array => [
                    'id' => $pago->id_ipa,
                    'operacion' => $pago->importacionBancoDetalle?->numero_operacion_visible_ibd ?? $pago->numero_operacion_ipa,
                    'fecha' => $pago->importacionBancoDetalle?->fecha_pago_ibd?->format('d/m/Y'),
                    'monto' => (float) $pago->monto_ipa,
                ])->values(),
            ],
            'busquedaRealizada' => $busquedaRealizada,
            'erroresBusqueda' => (object) $erroresBusqueda,
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

    /**
     * Valida los datos con los que se busca el pago del banco (el primer mensaje de cada campo).
     *
     * @param  array{dni: string, operacion: string, fecha: string}  $filtros
     * @return array<string, string>
     */
    private function erroresDeBusqueda(array $filtros): array
    {
        $validador = Validator::make($filtros, [
            'dni' => ['required', 'regex:/^\d{8,12}$/'],
            'operacion' => ['required', 'regex:/^\d+$/'],
            'fecha' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
        ], [
            'dni.required' => 'Ingrese el DNI del depositante.',
            'dni.regex' => 'El DNI debe tener entre 8 y 12 dígitos.',
            'operacion.required' => 'Ingrese el número de operación del voucher.',
            'operacion.regex' => 'El número de operación solo admite dígitos.',
            'fecha.required' => 'Ingrese la fecha de pago.',
            'fecha.date_format' => 'La fecha de pago no es válida.',
            'fecha.before_or_equal' => 'La fecha de pago no puede ser posterior a hoy.',
        ]);

        return collect($validador->errors()->messages())->map(fn (array $mensajes): string => $mensajes[0])->all();
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
}
