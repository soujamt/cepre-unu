<?php

namespace App\Http\Controllers\Pagos;

use App\Enums\EstadoImportacionBancoDetalle;
use App\Exports\PagosExport;
use App\Http\Controllers\CatalogoController;
use App\Http\Requests\Pagos\PagoManualRequest;
use App\Models\ImportacionBancoDetalle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Todos los pagos (del TXT del banco y los registrados a mano) y su uso en las inscripciones.
 */
class PagoController extends CatalogoController
{
    public function index(Request $request): Response
    {
        $filtros = $this->filtrosPagos($request);
        $consulta = $this->consulta($filtros);

        $pagos = (clone $consulta)
            ->with('inscripcionPago.obligacionPago.inscripcion.sede')
            ->orderByDesc('fecha_pago_ibd')
            ->orderByDesc('id_ibd')
            ->paginate(15)
            ->withQueryString()
            ->through(function (ImportacionBancoDetalle $pago): array {
                $inscripcion = $pago->inscripcionPago?->obligacionPago?->inscripcion;

                return [
                    'id' => $pago->id_ibd,
                    'fecha' => $pago->fecha_pago_ibd?->format('d/m/Y'),
                    'dni' => $pago->numero_documento_ibd,
                    'referencia' => $pago->referencia_ibd ?? $pago->nombre_depositante_ibd,
                    'importe' => (float) $pago->monto_ibd,
                    'operacion' => $pago->numero_operacion_visible_ibd ?: $pago->numero_operacion_ibd,
                    'ubicacion' => $pago->ubicacion_ibd,
                    'manual' => $pago->origen_ibd === 'MANUAL',
                    'estado' => $pago->estado_ibd->getNombre(),
                    'inscripcion' => $inscripcion === null ? null : [
                        'id' => encode($inscripcion->id_ins),
                        'codigo' => $inscripcion->codigo_ins,
                        'sede' => $inscripcion->sede?->nombre_sed,
                    ],
                    'observacion' => $pago->observacion_ibd,
                    'editable' => $pago->esEditableAMano(),
                    'formulario' => [
                        'fecha' => $pago->fecha_pago_ibd?->format('Y-m-d') ?? '',
                        'dni' => (string) $pago->numero_documento_ibd,
                        'referencia' => (string) ($pago->referencia_ibd ?? $pago->nombre_depositante_ibd ?? ''),
                        'importe' => number_format((float) $pago->monto_ibd, 2, '.', ''),
                        'numero_operacion' => (string) ($pago->numero_operacion_visible_ibd ?? ''),
                        'ubicacion' => (string) ($pago->ubicacion_ibd ?? ''),
                        'observacion' => (string) ($pago->observacion_ibd ?? ''),
                    ],
                ];
            });

        return Inertia::render('pagos/pagos', [
            'pagos' => $pagos,
            'filtros' => $filtros,
            'resumen' => [
                'total' => (clone $consulta)->count(),
                'libres' => (clone $consulta)->where('estado_ibd', EstadoImportacionBancoDetalle::Pendiente->value)->count(),
                'registrados' => (clone $consulta)->where('estado_ibd', EstadoImportacionBancoDetalle::Asociado->value)->count(),
                'monto' => (float) (clone $consulta)->sum('monto_ibd'),
            ],
            'estados' => array_map(fn (EstadoImportacionBancoDetalle $estado): array => ['valor' => $estado->value, 'nombre' => $estado->getNombre()], EstadoImportacionBancoDetalle::cases()),
            'ubicaciones' => PagoManualRequest::UBICACIONES,
            'hoy' => now()->toDateString(),
        ]);
    }

    public function store(PagoManualRequest $request): RedirectResponse
    {
        ImportacionBancoDetalle::query()->create([
            ...$this->datos($request),
            'estado_ibd' => EstadoImportacionBancoDetalle::Pendiente->value,
        ]);

        return $this->volverCon('Pago registrado correctamente.');
    }

    public function update(PagoManualRequest $request, ImportacionBancoDetalle $pago): RedirectResponse
    {
        $pago->update($request->soloObservacion()
            ? ['observacion_ibd' => $this->mayusculas($request->validated('observacion'))]
            : $this->datos($request));

        return $this->volverCon('Pago actualizado correctamente.');
    }

    public function destroy(ImportacionBancoDetalle $pago): RedirectResponse
    {
        return $this->eliminarSiNoSeUsa(
            $pago,
            $pago->esEditableAMano() ? null : 'Solo se pueden eliminar pagos registrados a mano que sigan libres.',
            'Pago eliminado correctamente.',
        );
    }

    public function exportar(Request $request): BinaryFileResponse
    {
        $pagos = $this->consulta($this->filtrosPagos($request))
            ->with('inscripcionPago.obligacionPago.inscripcion.sede')
            ->orderByDesc('fecha_pago_ibd')
            ->orderByDesc('id_ibd')
            ->get();

        return Excel::download(new PagosExport($pagos), 'pagos-cepre-'.now()->format('Ymd-His').'.xlsx');
    }

    /**
     * @return array{buscar: string, estado: string}
     */
    private function filtrosPagos(Request $request): array
    {
        $estado = (string) $request->query('estado', '');

        return [
            'buscar' => trim((string) $request->query('buscar', '')),
            'estado' => in_array($estado, array_column(EstadoImportacionBancoDetalle::cases(), 'value'), true) ? $estado : '',
        ];
    }

    /**
     * @param  array{buscar: string, estado: string}  $filtros
     */
    private function consulta(array $filtros): Builder
    {
        return ImportacionBancoDetalle::query()
            ->when($filtros['buscar'] !== '', fn (Builder $query) => $query->where(fn (Builder $interna) => $interna
                ->where('numero_documento_ibd', 'like', "%{$filtros['buscar']}%")
                ->orWhere('numero_operacion_visible_ibd', 'like', "%{$filtros['buscar']}%")
                ->orWhere('referencia_ibd', 'like', "%{$filtros['buscar']}%")
                ->orWhere('nombre_depositante_ibd', 'like', "%{$filtros['buscar']}%")))
            ->when($filtros['estado'] !== '', fn (Builder $query) => $query->where('estado_ibd', $filtros['estado']));
    }

    /**
     * @return array<string, mixed>
     */
    private function datos(PagoManualRequest $request): array
    {
        $operacion = $request->validated('numero_operacion');

        return [
            'origen_ibd' => 'MANUAL',
            'numero_documento_ibd' => $request->validated('dni'),
            'referencia_ibd' => $this->mayusculas($request->validated('referencia')),
            'numero_operacion_raw_ibd' => $operacion,
            'numero_operacion_visible_ibd' => $operacion,
            'numero_operacion_ibd' => $operacion,
            'fecha_pago_ibd' => $request->validated('fecha'),
            'monto_ibd' => round((float) $request->validated('importe'), 2),
            'ubicacion_ibd' => mb_strtoupper(trim((string) $request->validated('ubicacion')), 'UTF-8'),
            'observacion_ibd' => $this->mayusculas($request->validated('observacion')),
        ];
    }

    private function mayusculas(?string $valor): ?string
    {
        $valor = trim((string) $valor);

        return $valor === '' ? null : mb_strtoupper($valor, 'UTF-8');
    }
}
