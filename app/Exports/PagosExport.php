<?php

namespace App\Exports;

use App\Models\ImportacionBancoDetalle;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

/**
 * Lista de pagos con las columnas de la hoja "PAGOS" del sistema anterior.
 */
class PagosExport implements FromCollection, ShouldAutoSize, WithHeadings, WithStrictNullComparison
{
    /**
     * @param  Collection<int, ImportacionBancoDetalle>  $pagos
     */
    public function __construct(
        public Collection $pagos,
    ) {}

    public function collection(): Collection
    {
        return $this->pagos
            ->values()
            ->map(function (ImportacionBancoDetalle $pago, int $indice): array {
                $inscripcion = $pago->inscripcionPago?->obligacionPago?->inscripcion;

                return [
                    $indice + 1,
                    $pago->fecha_pago_ibd?->format('d/m/Y'),
                    $pago->numero_documento_ibd,
                    $pago->referencia_ibd ?? $pago->nombre_depositante_ibd,
                    (float) $pago->monto_ibd,
                    $pago->numero_operacion_visible_ibd ?: $pago->numero_operacion_ibd,
                    $pago->ubicacion_ibd,
                    $pago->estado_ibd->getNombre(),
                    $inscripcion?->codigo_ins,
                    $inscripcion?->sede?->nombre_sed,
                    $pago->observacion_ibd,
                ];
            });
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['NRO.', 'FECHA', 'DNI', 'REFERENCIA', 'IMPORTE', 'N° OPERACIÓN', 'UBICACIÓN', 'ESTADO', 'N° REG.', 'SEDE', 'OBSERVACIÓN'];
    }
}
