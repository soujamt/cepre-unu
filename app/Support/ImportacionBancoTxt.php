<?php

namespace App\Support;

use App\Enums\EstadoImportacionBanco;
use App\Enums\EstadoImportacionBancoDetalle;
use App\Models\ImportacionBanco;
use App\Models\ImportacionBancoDetalle;
use Illuminate\Support\Facades\DB;

/**
 * Importación del TXT de pagos del banco en dos pasos: primero se revisa (totales,
 * duplicados dentro del archivo y pagos ya importados) y luego se confirma.
 */
class ImportacionBancoTxt
{
    public function __construct(private BancoTxtParser $parser) {}

    /**
     * Lee el TXT y arma la revisión. Si el archivo ya se importó devuelve esa importación.
     *
     * @return array{repetida: ?ImportacionBanco, previsualizacion: ?array<string, mixed>}
     */
    public function previsualizar(string $nombreArchivo, string $contenido): array
    {
        $hash = hash('sha256', $contenido);
        $repetida = ImportacionBanco::query()->where('hash_archivo_iba', $hash)->first();

        if ($repetida !== null) {
            return ['repetida' => $repetida, 'previsualizacion' => null];
        }

        $leido = $this->parser->parse($contenido);
        $duplicados = $this->duplicados($leido['details']);
        $observaciones = [];

        if (! $leido['summary']['cuadra_registros']) {
            $observaciones[] = "La cantidad de operaciones no cuadra: se leyeron {$leido['summary']['cantidad_detalles']} y el banco reporta {$leido['trailer']['total_registros']}.";
        }

        if (! $leido['summary']['cuadra_montos']) {
            $observaciones[] = 'El monto total no cuadra: se leyó S/ '.number_format((float) $leido['summary']['total_detalles'], 2)
                .' y el banco reporta S/ '.number_format((float) $leido['trailer']['total_monto'], 2).'.';
        }

        if ($duplicados !== []) {
            $observaciones[] = 'Se encontraron '.count($duplicados).' pago(s) duplicado(s). Revise las operaciones marcadas antes de importar.';
        }

        return ['repetida' => null, 'previsualizacion' => [
            'nombre_archivo' => $nombreArchivo,
            'hash_archivo' => $hash,
            'moneda' => $leido['header']['moneda'],
            'fecha_lote' => $leido['header']['fecha_lote'],
            'total_registros' => $leido['summary']['cantidad_detalles'],
            'total_registros_reportado' => $leido['trailer']['total_registros'],
            'total_monto' => $leido['summary']['total_detalles'],
            'total_monto_reportado' => $leido['trailer']['total_monto'],
            'detalles' => $leido['details'],
            'duplicados' => $duplicados,
            'observaciones' => $observaciones,
            'valido' => $observaciones === [],
        ]];
    }

    /**
     * Registra la importación revisada con sus pagos pendientes de asociar.
     *
     * @param  array<string, mixed>  $previsualizacion
     */
    public function confirmar(array $previsualizacion): ImportacionBanco
    {
        return DB::transaction(function () use ($previsualizacion): ImportacionBanco {
            $importacion = ImportacionBanco::query()->create([
                'nombre_archivo_iba' => $previsualizacion['nombre_archivo'],
                'hash_archivo_iba' => $previsualizacion['hash_archivo'],
                'moneda_iba' => $previsualizacion['moneda'],
                'fecha_importacion_iba' => now(),
                'fecha_lote_iba' => $previsualizacion['fecha_lote'],
                'total_registros_iba' => $previsualizacion['total_registros'],
                'total_registros_reportado_iba' => $previsualizacion['total_registros_reportado'],
                'total_monto_iba' => $previsualizacion['total_monto'],
                'total_monto_reportado_iba' => $previsualizacion['total_monto_reportado'],
                'estado_iba' => EstadoImportacionBanco::Procesado->value,
                'observacion_iba' => null,
            ]);

            foreach ($previsualizacion['detalles'] as $detalle) {
                ImportacionBancoDetalle::query()->create([
                    'id_importacion_banco' => $importacion->id_iba,
                    'numero_documento_ibd' => $detalle['numero_documento_normalizado'] ?? $detalle['numero_documento'],
                    'nombre_depositante_ibd' => $detalle['nombre_depositante'],
                    'numero_operacion_raw_ibd' => $detalle['numero_operacion_normalizado'] ?? $detalle['numero_operacion'],
                    'numero_operacion_visible_ibd' => $detalle['numero_operacion_visible'],
                    'numero_operacion_ibd' => $detalle['numero_operacion_normalizado'] ?? $detalle['numero_operacion'],
                    'codigo_canal_ibd' => $detalle['codigo_canal'],
                    'codigo_oficina_ibd' => $detalle['codigo_oficina'],
                    'fecha_pago_ibd' => $detalle['fecha_pago'],
                    'monto_ibd' => $detalle['monto'],
                    'texto_linea_ibd' => $detalle['texto_linea'],
                    'estado_ibd' => EstadoImportacionBancoDetalle::Pendiente->value,
                ]);
            }

            return $importacion;
        });
    }

    /**
     * Pagos repetidos dentro del archivo o ya importados antes (mismo DNI, operación, fecha y monto).
     *
     * @param  list<array<string, mixed>>  $detalles
     * @return list<array{tipo: string, documento: ?string, operacion: ?string, fecha_pago: ?string, monto: float}>
     */
    private function duplicados(array $detalles): array
    {
        $duplicados = [];
        $vistas = [];

        foreach ($detalles as $detalle) {
            $clave = $this->clave($detalle);

            if ($clave === null) {
                continue;
            }

            if (isset($vistas[$clave])) {
                $duplicados[$clave.'|archivo'] = $this->resumen($detalle, 'Duplicado en el TXT');

                continue;
            }

            $vistas[$clave] = true;

            if ($this->yaImportado($detalle)) {
                $duplicados[$clave.'|base'] = $this->resumen($detalle, 'Ya importado');
            }
        }

        return array_values($duplicados);
    }

    /**
     * @param  array<string, mixed>  $detalle
     */
    private function clave(array $detalle): ?string
    {
        $documento = (string) ($detalle['numero_documento_normalizado'] ?? $detalle['numero_documento'] ?? '');
        $operacion = (string) ($detalle['numero_operacion_normalizado'] ?? $detalle['numero_operacion'] ?? '');
        $fecha = (string) ($detalle['fecha_pago'] ?? '');
        $monto = number_format((float) ($detalle['monto'] ?? 0), 2, '.', '');

        if ($documento === '' || $operacion === '' || $fecha === '' || $monto === '0.00') {
            return null;
        }

        return implode('|', [$documento, $operacion, $fecha, $monto]);
    }

    /**
     * @param  array<string, mixed>  $detalle
     */
    private function yaImportado(array $detalle): bool
    {
        return ImportacionBancoDetalle::query()
            ->where('numero_documento_ibd', $detalle['numero_documento_normalizado'] ?? $detalle['numero_documento'])
            ->where('numero_operacion_ibd', $detalle['numero_operacion_normalizado'] ?? $detalle['numero_operacion'])
            ->whereDate('fecha_pago_ibd', (string) $detalle['fecha_pago'])
            ->where('monto_ibd', (float) $detalle['monto'])
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $detalle
     * @return array{tipo: string, documento: ?string, operacion: ?string, fecha_pago: ?string, monto: float}
     */
    private function resumen(array $detalle, string $tipo): array
    {
        return [
            'tipo' => $tipo,
            'documento' => $detalle['numero_documento_normalizado'] ?? $detalle['numero_documento'] ?? null,
            'operacion' => $detalle['numero_operacion_visible'] ?? $detalle['numero_operacion_normalizado'] ?? $detalle['numero_operacion'] ?? null,
            'fecha_pago' => $detalle['fecha_pago'] ?? null,
            'monto' => (float) ($detalle['monto'] ?? 0),
        ];
    }
}
