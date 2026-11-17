<?php

namespace App\Exports;

use App\Support\CodigosAdmisionProceso;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Plantilla para que Admisión escriba sus códigos. La columna CLAVE identifica
 * la combinación y no se debe modificar.
 */
class PlantillaCodigosAdmisionExport implements FromArray, ShouldAutoSize, WithEvents, WithHeadings, WithStrictNullComparison, WithTitle
{
    /**
     * @param  Collection<int, array{clave:string,tipo:string,sede:string,modalidad:string,carrera:?string,codigo:string}>  $combinaciones
     */
    public function __construct(public Collection $combinaciones) {}

    public function array(): array
    {
        return $this->combinaciones
            ->map(fn (array $fila): array => [$fila['clave'], $fila['tipo'], $fila['sede'], $fila['modalidad'], $fila['carrera'], $fila['codigo'] ?: null])
            ->all();
    }

    public function headings(): array
    {
        return CodigosAdmisionProceso::ENCABEZADOS;
    }

    public function title(): string
    {
        return 'CODIGOS';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $hoja = $event->sheet->getDelegate();
                $ultimaFila = max(1, $hoja->getHighestRow());

                $hoja->getStyle('A1:F1')->getFont()->setBold(true);
                $hoja->getStyle("A2:A{$ultimaFila}")->getFont()->getColor()->setARGB('FF808080');
                $hoja->getStyle("F2:F{$ultimaFila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFF7D6');
                $hoja->getStyle("F2:F{$ultimaFila}")->getNumberFormat()->setFormatCode('@');
                $hoja->freezePane('A2');
            },
        ];
    }
}
