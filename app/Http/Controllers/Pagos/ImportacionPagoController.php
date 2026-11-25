<?php

namespace App\Http\Controllers\Pagos;

use App\Enums\EstadoImportacionBanco;
use App\Enums\EstadoImportacionBancoDetalle;
use App\Http\Controllers\CatalogoController;
use App\Models\ImportacionBanco;
use App\Models\ImportacionBancoDetalle;
use App\Models\InscripcionObligacionPago;
use App\Support\ImportacionBancoTxt;
use App\Support\InscripcionPagoManager;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Importación del TXT de pagos del banco: revisión, confirmación, detalle de cada lote y
 * asociación de sus pagos pendientes a las inscripciones.
 */
class ImportacionPagoController extends CatalogoController
{
    /** Revisión del TXT a la espera de confirmación, guardada en la sesión del usuario. */
    private const CLAVE_PREVISUALIZACION = 'importacion_banco.previsualizacion';

    public function __construct(private ImportacionBancoTxt $importador) {}

    public function index(Request $request): Response
    {
        $filtros = $this->filtros($request, array_column(EstadoImportacionBanco::cases(), 'value'));

        $importaciones = ImportacionBanco::query()
            ->withCount([
                'detalles',
                'detalles as detalles_pendientes_count' => fn (Builder $query) => $query
                    ->where('estado_ibd', EstadoImportacionBancoDetalle::Pendiente->value)
                    ->whereDoesntHave('inscripcionPago'),
            ])
            ->when($filtros['buscar'] !== '', fn (Builder $query) => $query->where(fn (Builder $interna) => $interna
                ->where('nombre_archivo_iba', 'like', "%{$filtros['buscar']}%")
                ->orWhere('moneda_iba', 'like', "%{$filtros['buscar']}%")
                ->orWhere('observacion_iba', 'like', "%{$filtros['buscar']}%")))
            ->when($filtros['estado'] !== [], fn (Builder $query) => $query->whereIn('estado_iba', $filtros['estado']))
            ->orderByDesc('id_iba')
            ->paginate(self::POR_PAGINA)
            ->withQueryString()
            ->through(fn (ImportacionBanco $importacion): array => [
                'id' => $importacion->id_iba,
                'archivo' => $importacion->nombre_archivo_iba,
                'fechaLote' => $importacion->fecha_lote_iba?->format('d/m/Y'),
                'registros' => $importacion->detalles_count,
                'pendientes' => $importacion->detalles_pendientes_count,
                'monto' => (float) $importacion->total_monto_iba,
                'moneda' => $importacion->moneda_iba,
                'estado' => $importacion->estado_iba->value,
                'importado' => $this->registro($importacion->fecha_importacion_iba),
            ]);

        $previsualizacion = $request->session()->get(self::CLAVE_PREVISUALIZACION);

        return Inertia::render('pagos/importaciones', [
            'importaciones' => $importaciones,
            'filtros' => $filtros,
            'detalle' => $this->detalle($request),
            'previsualizacion' => $previsualizacion === null ? null : [
                ...collect($previsualizacion)->except('detalles')->all(),
                'muestra' => array_slice($previsualizacion['detalles'], 0, 8),
            ],
        ]);
    }

    public function previsualizar(Request $request): RedirectResponse
    {
        $request->validate(
            ['archivo' => ['required', 'file', 'extensions:txt', 'max:5120']],
            [
                'archivo.required' => 'Debe seleccionar un archivo TXT.',
                'archivo.extensions' => 'El archivo debe tener extensión .txt.',
                'archivo.max' => 'El archivo no debe exceder los 5 MB.',
            ],
        );

        $archivo = $request->file('archivo');

        try {
            $resultado = $this->importador->previsualizar($archivo->getClientOriginalName(), (string) file_get_contents($archivo->getRealPath()));
        } catch (Throwable $error) {
            Log::error('Fallo al leer el TXT bancario.', ['archivo' => $archivo->getClientOriginalName(), 'mensaje' => $error->getMessage()]);
            report($error);

            return $this->volverCon('No se pudo importar el archivo TXT. '.$error->getMessage(), 'error');
        }

        if ($resultado['repetida'] !== null) {
            Inertia::flash('toast', ['type' => 'warning', 'message' => 'Este archivo TXT ya fue importado anteriormente.']);

            return redirect()->route('pagos.importaciones', ['importacion' => $resultado['repetida']->id_iba]);
        }

        $request->session()->put(self::CLAVE_PREVISUALIZACION, $resultado['previsualizacion']);

        return back();
    }

    public function confirmar(Request $request): RedirectResponse
    {
        $previsualizacion = $request->session()->get(self::CLAVE_PREVISUALIZACION);

        if ($previsualizacion === null) {
            return $this->volverCon('No hay una importación lista para confirmar.', 'error');
        }

        if (! $previsualizacion['valido']) {
            return $this->volverCon('No se puede importar un TXT con observaciones.', 'warning');
        }

        $importacion = $this->importador->confirmar($previsualizacion);
        $request->session()->forget(self::CLAVE_PREVISUALIZACION);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Archivo TXT importado correctamente.']);

        return redirect()->route('pagos.importaciones', ['importacion' => $importacion->id_iba]);
    }

    public function cancelar(Request $request): RedirectResponse
    {
        $request->session()->forget(self::CLAVE_PREVISUALIZACION);

        return back();
    }

    public function validarPendientes(ImportacionBanco $importacion, InscripcionPagoManager $pagos): RedirectResponse
    {
        $resultado = $pagos->validarPendientesDeImportacion($importacion->id_iba);

        if ($resultado['asociados'] === 0) {
            return $this->volverCon('No se asociaron pagos. Revise que existan pendientes con DNI y saldo coincidente.', 'warning');
        }

        return $this->volverCon("{$resultado['asociados']} pago(s) pendiente(s) asociado(s). {$resultado['pendientes']} quedan por revisar.");
    }

    /**
     * Inscripciones con saldo para asociarles a mano un pago que quedó libre.
     */
    public function obligaciones(Request $request, InscripcionPagoManager $pagos): JsonResponse
    {
        return response()->json([
            'obligaciones' => $pagos->buscarObligacionesConSaldo((string) $request->query('buscar', ''))
                ->map(fn (InscripcionObligacionPago $obligacion): array => $pagos->resumenObligacion($obligacion))
                ->values(),
        ]);
    }

    /**
     * Asocia a mano un pago libre del lote a una inscripción. Si el DNI del pago no es el
     * del postulante (pagó un familiar, por ejemplo), pide el motivo y lo guarda.
     */
    public function asociar(Request $request, ImportacionBancoDetalle $detalle, InscripcionPagoManager $pagos): RedirectResponse
    {
        $datos = $request->validate([
            'obligacion' => ['required', 'integer', 'exists:tbl_inscripcion_obligacion_pago,id_iop'],
            'motivo' => ['nullable', 'string', 'max:200'],
        ], [
            'obligacion.required' => 'Seleccione la inscripción a la que corresponde el pago.',
            'motivo.max' => 'El motivo no puede exceder 200 caracteres.',
        ]);

        $obligacion = InscripcionObligacionPago::query()->with('inscripcion.persona')->findOrFail((int) $datos['obligacion']);
        $motivo = trim((string) ($datos['motivo'] ?? ''));
        $otroDocumento = ! $pagos->documentoCoincide($obligacion, $detalle);

        if ($otroDocumento && mb_strlen($motivo) < 10) {
            throw ValidationException::withMessages(['motivo' => 'El DNI del pago no es el del postulante: indique el motivo (mínimo 10 caracteres).']);
        }

        try {
            $pagos->asociarDetalleAObligacion($obligacion, $detalle, $otroDocumento ? $motivo : null);
        } catch (DomainException $error) {
            return $this->volverCon($error->getMessage(), 'error');
        }

        $operacion = $detalle->numero_operacion_visible_ibd ?: $detalle->numero_operacion_ibd;

        return $this->volverCon("Pago {$operacion} asociado a la inscripción {$obligacion->inscripcion?->codigo_ins}.");
    }

    /**
     * Pagos de la importación indicada en ?importacion=ID.
     *
     * @return array<string, mixed>|null
     */
    private function detalle(Request $request): ?array
    {
        $id = (string) $request->query('importacion', '');
        $importacion = ctype_digit($id) ? ImportacionBanco::query()->with(['detalles' => fn ($query) => $query->orderBy('id_ibd')])->find((int) $id) : null;

        if ($importacion === null) {
            return null;
        }

        return [
            'id' => $importacion->id_iba,
            'archivo' => $importacion->nombre_archivo_iba,
            'fechaLote' => $importacion->fecha_lote_iba?->format('d/m/Y'),
            'pagos' => $importacion->detalles->map(fn (ImportacionBancoDetalle $pago): array => [
                'id' => $pago->id_ibd,
                'documento' => $this->documento($pago->numero_documento_ibd),
                'depositante' => $pago->nombre_depositante_ibd,
                'operacion' => $pago->numero_operacion_visible_ibd ?: $pago->numero_operacion_ibd,
                'oficina' => trim(($pago->codigo_oficina_ibd ?? '').' '.($pago->codigo_canal_ibd ?? '')),
                'fecha' => $pago->fecha_pago_ibd?->format('d/m/Y'),
                'monto' => (float) $pago->monto_ibd,
                'estado' => $pago->estado_ibd->getNombre(),
                'libre' => $pago->estado_ibd === EstadoImportacionBancoDetalle::Pendiente,
            ])->values()->all(),
        ];
    }

    /**
     * DNI con ceros a la izquierda, como en la boleta: 7450332 → 07450332.
     */
    private function documento(?string $numero): string
    {
        $numero = trim((string) $numero);

        if ($numero === '') {
            return 'SIN DOCUMENTO';
        }

        return preg_match('/^\d{1,8}$/', $numero) === 1 ? str_pad($numero, 8, '0', STR_PAD_LEFT) : $numero;
    }
}
