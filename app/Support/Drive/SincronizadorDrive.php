<?php

namespace App\Support\Drive;

use App\Enums\EstadoImportacionBancoDetalle;
use App\Enums\EstadoInscripcion;
use App\Exports\InscripcionesFormatoCepreExport;
use App\Exports\PagosExport;
use App\Models\ImportacionBancoDetalle;
use App\Models\Inscripcion;
use App\Models\Parametro;
use App\Models\Proceso;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Envía al Drive de CEPRE UNU virtual las hojas "DATA CEPRE-UNU {periodo}" y
 * "PAGOS {periodo}" del proceso, con la misma estructura que usan allí. Las recibe
 * un Apps Script publicado como aplicación web (resources/drive/SincronizarCepre.gs),
 * que reescribe cada pestaña para que el buscador de estudiantes lea datos al día.
 */
class SincronizadorDrive
{
    /** Parámetro con la fecha y hora de la última sincronización correcta. */
    public const PARAMETRO_ULTIMA = 'drive_ultima_sincronizacion';

    private const TEXTO = '@';

    public function configurado(): bool
    {
        return filled(config('services.drive.url')) && filled(config('services.drive.token'));
    }

    public function ultimaSincronizacion(): ?Carbon
    {
        $valor = Parametro::valor(self::PARAMETRO_ULTIMA);

        return blank($valor) ? null : Carbon::parse($valor);
    }

    /**
     * Envía las hojas del proceso (por defecto el vigente) y devuelve cuántas filas tiene cada una.
     *
     * @return array{proceso: string, hojas: list<array{archivo: string, filas: int}>}
     *
     * @throws RuntimeException si falta configurar, no hay conexión o el Drive rechaza el envío
     */
    public function sincronizar(?Proceso $proceso = null): array
    {
        if (! $this->configurado()) {
            throw new RuntimeException('La sincronización con el Drive no está configurada. Complete DRIVE_SYNC_URL y DRIVE_SYNC_TOKEN en el archivo .env.');
        }

        $proceso ??= Proceso::actual() ?? throw new RuntimeException('No hay un proceso para sincronizar.');
        $hojas = $this->hojas($proceso);

        try {
            $respuesta = Http::timeout(120)->acceptJson()->post((string) config('services.drive.url'), [
                'token' => (string) config('services.drive.token'),
                'proceso' => $this->periodo($proceso),
                'hojas' => $hojas,
            ]);
        } catch (ConnectionException) {
            throw new RuntimeException('No se pudo conectar con el Drive. Revise la conexión a internet de esta PC.');
        }

        $datos = $respuesta->json();

        if (! $respuesta->successful() || ! is_array($datos)) {
            throw new RuntimeException('El Drive no respondió como se esperaba. Revise que el script esté publicado como aplicación web con acceso para "Cualquier usuario".');
        }

        if (($datos['ok'] ?? false) !== true) {
            throw new RuntimeException('El Drive rechazó la sincronización: '.($datos['error'] ?? 'error desconocido').'.');
        }

        Parametro::guardar(self::PARAMETRO_ULTIMA, now()->toIso8601String());

        return [
            'proceso' => $this->periodo($proceso),
            'hojas' => array_map(fn (array $hoja): array => ['archivo' => $hoja['archivo'], 'filas' => count($hoja['filas'])], $hojas),
        ];
    }

    /**
     * Hojas que se envían: archivo, pestaña, encabezados, formato de cada columna y filas.
     *
     * @return list<array{archivo: string, pestana: string, encabezados: list<string>, formatos: list<string>, filas: list<list<string|int|float>>}>
     */
    public function hojas(Proceso $proceso): array
    {
        return [$this->hojaInscritos($proceso), $this->hojaPagos($proceso)];
    }

    /**
     * Nombre del periodo como en el Drive: "2027-2" pasa a "2027-II".
     */
    public function periodo(Proceso $proceso): string
    {
        $anio = $proceso->anioPeriodo();
        $numero = $proceso->numeroPeriodo();
        $romanos = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV'];

        return $anio !== null && isset($romanos[$numero]) ? "{$anio}-{$romanos[$numero]}" : (string) $proceso->nombre_pro;
    }

    /**
     * Inscripciones no anuladas en el formato de la hoja DATA, con aula, turno y estado al final
     * para el buscador de estudiantes.
     *
     * @return array{archivo: string, pestana: string, encabezados: list<string>, formatos: list<string>, filas: list<list<string|int|float>>}
     */
    private function hojaInscritos(Proceso $proceso): array
    {
        $inscripciones = Inscripcion::query()
            ->with([...InscripcionesFormatoCepreExport::RELACIONES, 'turno', 'procesoAula.aula'])
            ->where('id_proceso', $proceso->id_pro)
            ->where('estado_ins', '!=', EstadoInscripcion::Anulado->value)
            ->orderBy('id_ins')
            ->get()
            ->values();
        $exportacion = new InscripcionesFormatoCepreExport($inscripciones);
        $encabezados = [...$exportacion->headings(), 'AULA', 'TURNO', 'ESTADO'];

        return [
            'archivo' => 'DATA CEPRE-UNU '.$this->periodo($proceso),
            'pestana' => 'DATA',
            'encabezados' => $encabezados,
            'formatos' => array_fill(0, count($encabezados), self::TEXTO),
            'filas' => $exportacion->collection()
                ->map(fn (array $fila, int $indice): array => $this->limpiar([
                    ...$fila,
                    $inscripciones[$indice]->procesoAula?->aula?->nombre_aul,
                    $inscripciones[$indice]->turno?->nombre_tur,
                    $inscripciones[$indice]->estado_ins->value,
                ]))
                ->all(),
        ];
    }

    /**
     * Pagos del periodo: los usados en inscripciones del proceso y los libres pagados
     * después de que terminó el proceso anterior.
     *
     * @return array{archivo: string, pestana: string, encabezados: list<string>, formatos: list<string>, filas: list<list<string|int|float>>}
     */
    private function hojaPagos(Proceso $proceso): array
    {
        $finAnterior = Proceso::query()
            ->where('id_pro', '!=', $proceso->id_pro)
            ->where('fecha_fin_pro', '<', $proceso->fecha_inicio_pro)
            ->max('fecha_fin_pro');

        $pagos = ImportacionBancoDetalle::query()
            ->with('inscripcionPago.obligacionPago.inscripcion.sede')
            ->where(fn (Builder $query) => $query
                ->whereHas('inscripcionPago.obligacionPago.inscripcion', fn (Builder $inscripcion) => $inscripcion->where('id_proceso', $proceso->id_pro))
                ->orWhere(fn (Builder $libre) => $libre
                    ->where('estado_ibd', EstadoImportacionBancoDetalle::Pendiente->value)
                    ->when($finAnterior !== null, fn (Builder $fecha) => $fecha->whereDate('fecha_pago_ibd', '>', Carbon::parse($finAnterior)->toDateString()))))
            ->orderBy('fecha_pago_ibd')
            ->orderBy('id_ibd')
            ->get();
        $exportacion = new PagosExport($pagos);
        $encabezados = $exportacion->headings();

        return [
            'archivo' => 'PAGOS '.$this->periodo($proceso),
            'pestana' => 'PAGOS',
            'encabezados' => $encabezados,
            'formatos' => array_map(fn (string $encabezado): string => match ($encabezado) {
                'NRO.' => '0',
                'IMPORTE' => '0.00',
                default => self::TEXTO,
            }, $encabezados),
            'filas' => $exportacion->collection()->map(fn (array $fila): array => $this->limpiar($fila))->all(),
        ];
    }

    /**
     * Las hojas de Google no aceptan nulos: van como celdas vacías.
     *
     * @param  array<int, mixed>  $fila
     * @return list<string|int|float>
     */
    private function limpiar(array $fila): array
    {
        return array_values(array_map(fn (mixed $valor): string|int|float => $valor ?? '', $fila));
    }
}
